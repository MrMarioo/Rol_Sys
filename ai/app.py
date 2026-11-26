import hmac
import logging
from datetime import datetime

from flask import Flask, jsonify, request
from werkzeug.exceptions import HTTPException

from analytics.anomaly_detection import detect_anomalies
from analytics.plant_growth_prediction import PlantGrowthPredictor
from analytics.recommendations import generate_basic_recommendations, merge_recommendations
from analytics.soil_moisture import predict_moisture
from analytics.vegetation_health import analyze_ndvi
from config import Config
from validation import ValidationError, parse_analysis_request

logging.basicConfig(
    level=getattr(logging, Config.LOG_LEVEL, logging.INFO),
    format="%(asctime)s %(levelname)s %(name)s: %(message)s",
)
logger = logging.getLogger(__name__)

API_VERSION = "1.0.0"
TOKEN_HEADER = "X-AI-Token"
PUBLIC_ENDPOINTS = {"health"}


def create_app(predictor=None, api_token=None):
    app = Flask(__name__)
    growth_predictor = predictor or PlantGrowthPredictor(Config.MODEL_PATH)
    token = Config.API_TOKEN if api_token is None else api_token

    @app.before_request
    def require_token():
        if not token or request.endpoint in PUBLIC_ENDPOINTS:
            return None
        provided = request.headers.get(TOKEN_HEADER, "")
        if not hmac.compare_digest(provided.encode(), token.encode()):
            return jsonify({"error": "Unauthorized"}), 401
        return None

    @app.get("/health")
    def health():
        return jsonify({"status": "ok"})

    @app.get("/")
    def index():
        return jsonify(
            {
                "status": "online",
                "name": "Precision Agriculture AI API",
                "version": API_VERSION,
                "model_status": "loaded" if growth_predictor.model else "not_loaded",
            }
        )

    @app.post("/analyze/field/<int:field_id>")
    def analyze_field(field_id):
        try:
            analysis_request = parse_analysis_request(request.get_json(silent=True))
        except ValidationError as error:
            logger.info("Rejected analysis request for field %s: %s", field_id, error.errors)
            return jsonify({"error": "Invalid request payload", "details": error.errors}), 400

        field_data = analysis_request.field_data
        ndvi_data = [item for item in field_data if item.get("data_type") == "ndvi"]
        moisture_data = [item for item in field_data if item.get("data_type") == "soil_moisture"]

        vegetation_analysis = analyze_ndvi(ndvi_data) if ndvi_data else None
        moisture_analysis = predict_moisture(moisture_data) if moisture_data else None
        anomalies = detect_anomalies(field_data, analysis_request.sensitivity)
        basic_recommendations = generate_basic_recommendations(vegetation_analysis, moisture_analysis, anomalies)

        response = {
            "field_id": field_id,
            "analysis_date": datetime.now().strftime("%Y-%m-%d"),
            "vegetation_health": vegetation_analysis,
            "soil_moisture": moisture_analysis,
            "anomalies": anomalies,
            "recommendations": basic_recommendations,
        }

        if analysis_request.include_growth_prediction:
            try:
                growth_prediction = growth_predictor.predict_growth(
                    field_data, analysis_request.field_info, analysis_request.prediction_days
                )
            except Exception:
                logger.exception("Growth prediction failed for field %s", field_id)
                response["growth_prediction"] = {"error": "Growth prediction failed"}
            else:
                response["growth_prediction"] = growth_prediction
                response["recommendations"] = merge_recommendations(
                    basic_recommendations, growth_prediction["recommendations"]
                )

        return jsonify(response)

    @app.errorhandler(HTTPException)
    def handle_http_error(error):
        return jsonify({"error": error.name}), error.code

    @app.errorhandler(Exception)
    def handle_unexpected_error(error):
        logger.exception("Unhandled error on %s %s", request.method, request.path)
        return jsonify({"error": "Internal server error"}), 500

    return app


app = create_app()
