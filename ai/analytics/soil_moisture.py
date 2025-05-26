import numpy as np
import json
from sklearn.ensemble import RandomForestRegressor
import os


def predict_moisture(moisture_data):
    if not moisture_data:
        return None

    moisture_values = []
    for item in moisture_data:
        data = item.get('data', {})
        if isinstance(data, str):
            data = json.loads(data)

        values = data.get('moisture_values', [])
        moisture_values.extend(values)

    if not moisture_values:
        return {
            'error': 'No moisture values found in provided data.'
        }

    # Calculate statistics
    avg_moisture = np.mean(moisture_values)
    min_moisture = np.min(moisture_values)
    max_moisture = np.max(moisture_values)

    # Simple predictive model using recent trends
    if len(moisture_data) > 3:
        sorted_data = sorted(moisture_data, key=lambda x: x.get('collection_date', ''))
        recent_values = [np.mean(json.loads(item.get('data', '{}')).get('moisture_values', [0]))
                         for item in sorted_data[-3:]]

        # Simple trend prediction
        if len(recent_values) >= 3:
            trend = (recent_values[2] - recent_values[0]) / 2
            predicted_value = recent_values[2] + trend
        else:
            predicted_value = avg_moisture
    else:
        predicted_value = avg_moisture

    # Determine moisture status
    if avg_moisture < 0.3:
        moisture_status = "Low"
        recommendation = "Increase irrigation recommended."
    elif avg_moisture > 0.7:
        moisture_status = "High"
        recommendation = "Reduce irrigation recommended."
    else:
        moisture_status = "Optimal"
        recommendation = "Maintain current irrigation levels."

    # Determine dry area percentage
    dry_area_percent = len([v for v in moisture_values if v < 0.3]) / len(moisture_values) * 100

    return {
        'avg_moisture': float(avg_moisture),
        'min_moisture': float(min_moisture),
        'max_moisture': float(max_moisture),
        'predicted_moisture': float(predicted_value),
        'moisture_status': moisture_status,
        'dry_area_percent': float(dry_area_percent),
        'recommendation': recommendation
    }
