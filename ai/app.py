from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import logging
from datetime import datetime

# Import analytics modules
from analytics.vegetation_health import analyze_ndvi
from analytics.soil_moisture import predict_moisture
from analytics.anomaly_detection import detect_anomalies
from analytics.plant_growth_prediction import PlantGrowthPredictor

# Initialize Flask app
app = Flask(__name__)
CORS(app)
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

growth_predictor = PlantGrowthPredictor()


@app.route('/')
def index():
    return jsonify({
        "status": "online",
        "name": "Precision Agriculture AI API",
        "version": "1.0.0",
        "model_status": "loaded" if growth_predictor.model else "not_loaded"
    })


@app.route('/analyze/field/<int:field_id>', methods=['POST'])
def analyze_field(field_id):
    try:
        data = request.json
        field_data = data.get('field_data', [])
        field_info = data.get('field_info', {})
        parameters = data.get('parameters', {})

        # Standardowa analiza
        ndvi_data = [item for item in field_data if item.get('data_type') == 'ndvi']
        moisture_data = [item for item in field_data if item.get('data_type') == 'soil_moisture']

        # Uruchom podstawowe analizy
        vegetation_analysis = analyze_ndvi(ndvi_data) if ndvi_data else None
        moisture_analysis = predict_moisture(moisture_data) if moisture_data else None
        anomalies = detect_anomalies(field_data, parameters.get('sensitivity', 'medium'))

        # Generuj podstawowe rekomendacje
        basic_recommendations = generate_basic_recommendations(vegetation_analysis, moisture_analysis, anomalies)

        # Przygotuj podstawową odpowiedź
        response = {
            'field_id': field_id,
            'analysis_date': datetime.now().strftime('%Y-%m-%d'),
            'vegetation_health': vegetation_analysis,
            'soil_moisture': moisture_analysis,
            'anomalies': anomalies,
            'recommendations': basic_recommendations
        }

        # Dodaj predykcję wzrostu jeśli jest wymagana
        if parameters.get('include_growth_prediction', False):
            logger.info("Including growth prediction in analysis...")

            try:
                prediction_days = parameters.get('prediction_days', 7)
                growth_prediction = growth_predictor.predict_growth(
                    field_data, field_info, prediction_days
                )

                if 'error' not in growth_prediction:
                    response['growth_prediction'] = growth_prediction

                    # Połącz rekomendacje z podstawowych analiz i predykcji
                    growth_recommendations = growth_prediction.get('recommendations', [])
                    response['recommendations'] = merge_recommendations(basic_recommendations, growth_recommendations)

                    logger.info("Growth prediction completed successfully")
                else:
                    logger.warning(f"Growth prediction error: {growth_prediction['error']}")
                    response['growth_prediction'] = {'error': growth_prediction['error']}

            except Exception as e:
                logger.error(f"Growth prediction failed: {str(e)}")
                response['growth_prediction'] = {'error': f'Prediction failed: {str(e)}'}

        return jsonify(response)

    except Exception as e:
        logger.error(f"Analysis error: {str(e)}")
        return jsonify({"error": str(e)}), 500


def generate_basic_recommendations(vegetation, moisture, anomalies):
    """Generuj podstawowe rekomendacje z analiz NDVI i wilgotności"""
    recommendations = []

    # Rekomendacje na podstawie wegetacji
    if vegetation and vegetation.get('health_status') in ["Critical", "Poor"]:
        recommendations.append({
            'type': 'urgent',
            'priority': 'high',
            'action': 'Inspect crop condition',
            'description': f'Low NDVI ({vegetation.get("avg_ndvi", 0):.2f}) indicates potential crop health issues.',
            'source': 'vegetation_analysis'
        })

    # Rekomendacje na podstawie wilgotności
    if moisture:
        if moisture.get('moisture_status') == "Low":
            recommendations.append({
                'type': 'high',
                'priority': 'high',
                'action': 'Increase irrigation',
                'description': f'Soil moisture is below optimal levels ({moisture.get("avg_moisture", 0):.2f}).',
                'source': 'moisture_analysis'
            })
        elif moisture.get('moisture_status') == "High":
            recommendations.append({
                'type': 'medium',
                'priority': 'medium',
                'action': 'Reduce irrigation',
                'description': f'Soil moisture is above optimal levels ({moisture.get("avg_moisture", 0):.2f}).',
                'source': 'moisture_analysis'
            })

    # Rekomendacje na podstawie anomalii
    for anomaly in anomalies:
        if anomaly.get('severity') == 'high':
            recommendations.append({
                'type': 'urgent',
                'priority': 'high',
                'action': 'Investigate anomaly',
                'description': anomaly.get('description', 'Significant anomaly detected.'),
                'source': 'anomaly_detection'
            })

    # Domyślna rekomendacja jeśli brak problemów
    if not recommendations:
        recommendations.append({
            'type': 'info',
            'priority': 'low',
            'action': 'Continue standard procedures',
            'description': 'All parameters are within normal ranges.',
            'source': 'general'
        })

    return recommendations


def merge_recommendations(basic_recs, growth_recs):
    """Połącz rekomendacje z różnych źródeł, unikając duplikatów"""
    merged = basic_recs.copy()

    # Dodaj rekomendacje z predykcji wzrostu, unikając duplikatów
    for growth_rec in growth_recs:
        # Sprawdź czy nie ma już podobnej rekomendacji
        similar_exists = False
        for basic_rec in basic_recs:
            if (growth_rec.get('action', '').lower() in basic_rec.get('action', '').lower() or
                basic_rec.get('action', '').lower() in growth_rec.get('action', '').lower()):
                similar_exists = True
                break

        if not similar_exists:
            growth_rec['source'] = 'growth_prediction'
            merged.append(growth_rec)

    priority_order = {'high': 3, 'medium': 2, 'low': 1}
    merged.sort(key=lambda x: priority_order.get(x.get('priority', 'low'), 1), reverse=True)

    return merged


if __name__ == '__main__':
    logger.info("Starting Precision Agriculture AI API...")
    logger.info(f"Growth prediction model status: {'loaded' if growth_predictor.model else 'not loaded'}")
    app.run(host='0.0.0.0', port=int(os.environ.get('AI_PORT', 5000)))
