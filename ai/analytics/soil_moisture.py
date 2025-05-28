import numpy as np

def predict_moisture(moisture_data):
    print("🔥 SIMPLE MOISTURE FUNCTION CALLED")

    if not moisture_data:
        return None

    all_values = []

    # Super proste przetwarzanie - ZERO json.loads()
    for item in moisture_data:
        data = item['data']  # Direct access - no json decoding!
        values = data['moisture_values']  # Direct access
        all_values.extend(values)

    if not all_values:
        return {'error': 'No moisture values found'}

    avg = float(np.mean(all_values))

    # Simple status
    if avg < 0.3:
        status = "Low"
        recommendation = "Increase irrigation"
    elif avg > 0.7:
        status = "High"
        recommendation = "Reduce irrigation"
    else:
        status = "Optimal"
        recommendation = "Maintain irrigation"

    return {
        'avg_moisture': avg,
        'min_moisture': float(np.min(all_values)),
        'max_moisture': float(np.max(all_values)),
        'predicted_moisture': avg,
        'moisture_status': status,
        'dry_area_percent': 0.0,  # Simplified
        'recommendation': recommendation
    }
