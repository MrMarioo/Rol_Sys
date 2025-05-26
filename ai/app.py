from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import logging
from datetime import datetime

# Import analytics modules
from analytics.vegetation_health import analyze_ndvi
from analytics.soil_moisture import predict_moisture
from analytics.anomaly_detection import detect_anomalies

# Initialize Flask app
app = Flask(__name__)
CORS(app)
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


@app.route('/')
def index():
    return jsonify({
        "status": "online",
        "name": "Precision Agriculture AI API",
        "version": "1.0.0"
    })


@app.route('/analyze/field/<int:field_id>', methods=['POST'])
def analyze_field(field_id):
    try:
        data = request.json
        field_data = data.get('field_data', [])
        parameters = data.get('parameters', {})

        # Process different data types
        ndvi_data = [item for item in field_data if item.get('data_type') == 'ndvi']
        moisture_data = [item for item in field_data if item.get('data_type') == 'soil_moisture']

        # Run analysis
        vegetation_analysis = analyze_ndvi(ndvi_data) if ndvi_data else None
        moisture_analysis = predict_moisture(moisture_data) if moisture_data else None
        anomalies = detect_anomalies(field_data, parameters.get('sensitivity', 'medium'))

        # Generate recommendations
        recommendations = generate_recommendations(vegetation_analysis, moisture_analysis, anomalies)

        response = {
            'field_id': field_id,
            'analysis_date': datetime.now().strftime('%Y-%m-%d'),
            'vegetation_health': vegetation_analysis,
            'soil_moisture': moisture_analysis,
            'anomalies': anomalies,
            'recommendations': recommendations
        }

        return jsonify(response)

    except Exception as e:
        logger.error(f"Analysis error: {str(e)}")
        return jsonify({"error": str(e)}), 500


def generate_recommendations(vegetation, moisture, anomalies):
    recommendations = []

    # Add vegetation recommendations
    if vegetation and vegetation.get('health_status') in ["Critical", "Poor"]:
        recommendations.append({
            'type': 'urgent',
            'action': 'Inspect crop condition',
            'description': 'Low NDVI indicates potential crop health issues.'
        })

    # Add moisture recommendations
    if moisture and moisture.get('moisture_status') == "Low":
        recommendations.append({
            'type': 'high',
            'action': 'Increase irrigation',
            'description': 'Soil moisture is below optimal levels.'
        })
    elif moisture and moisture.get('moisture_status') == "High":
        recommendations.append({
            'type': 'medium',
            'action': 'Reduce irrigation',
            'description': 'Soil moisture is above optimal levels.'
        })

    # Add anomaly recommendations
    for anomaly in anomalies:
        if anomaly.get('severity') == 'high':
            recommendations.append({
                'type': 'urgent',
                'action': 'Investigate anomaly',
                'description': anomaly.get('description', 'Significant anomaly detected.')
            })

    # Default recommendation if none exists
    if not recommendations:
        recommendations.append({
            'type': 'info',
            'action': 'Continue standard procedures',
            'description': 'All parameters are within normal ranges.'
        })

    return recommendations


if __name__ == '__main__':
    logger.info("Starting Precision Agriculture AI API...")
    app.run(host='0.0.0.0', port=int(os.environ.get('AI_PORT', 5000)))
