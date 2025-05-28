import numpy as np
import json


def analyze_ndvi(ndvi_data):
    if not ndvi_data:
        return None

    ndvi_values = []
    for item in ndvi_data:
        data = item.get('data', {})
        #if isinstance(data, str):
        #    data = json.loads(data)

        values = data.get('ndvi_values', [])
        ndvi_values.extend(values)

    if not ndvi_values:
        return {
            'error': 'No NDVI values found in provided data.'
        }

    # Calculate statistics
    avg_ndvi = np.mean(ndvi_values)
    min_ndvi = np.min(ndvi_values)
    max_ndvi = np.max(ndvi_values)

    # Determine health status
    if avg_ndvi < 0.3:
        health_status = "Critical"
        recommendation = "Immediate intervention recommended."
    elif avg_ndvi < 0.5:
        health_status = "Poor"
        recommendation = "Additional fertilization recommended."
    elif avg_ndvi < 0.7:
        health_status = "Moderate"
        recommendation = "Monitor crop condition."
    else:
        health_status = "Good"
        recommendation = "Continue standard procedures."

    # Calculate problem areas percentage
    problem_area_percent = len([v for v in ndvi_values if v < 0.5]) / len(ndvi_values) * 100

    return {
        'avg_ndvi': float(avg_ndvi),
        'min_ndvi': float(min_ndvi),
        'max_ndvi': float(max_ndvi),
        'health_status': health_status,
        'problem_area_percent': float(problem_area_percent),
        'recommendation': recommendation
    }
