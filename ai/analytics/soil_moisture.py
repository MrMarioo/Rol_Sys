import numpy as np

MOISTURE_LOW_THRESHOLD = 0.3
MOISTURE_HIGH_THRESHOLD = 0.7


def predict_moisture(moisture_data):
    if not moisture_data:
        return None

    all_values = []
    for item in moisture_data:
        all_values.extend(item.get("data", {}).get("moisture_values", []))

    if not all_values:
        return {"error": "No moisture values found"}

    avg = float(np.mean(all_values))
    status, recommendation = classify_moisture(avg)

    return {
        "avg_moisture": avg,
        "min_moisture": float(np.min(all_values)),
        "max_moisture": float(np.max(all_values)),
        "predicted_moisture": avg,
        "moisture_status": status,
        "dry_area_percent": 0.0,
        "recommendation": recommendation,
    }


def classify_moisture(avg_moisture):
    if avg_moisture < MOISTURE_LOW_THRESHOLD:
        return "Low", "Increase irrigation"
    if avg_moisture > MOISTURE_HIGH_THRESHOLD:
        return "High", "Reduce irrigation"
    return "Optimal", "Maintain irrigation"
