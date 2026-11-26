import numpy as np

NDVI_CRITICAL_THRESHOLD = 0.3
NDVI_POOR_THRESHOLD = 0.5
NDVI_MODERATE_THRESHOLD = 0.7
NDVI_PROBLEM_AREA_THRESHOLD = 0.5


def analyze_ndvi(ndvi_data):
    if not ndvi_data:
        return None

    ndvi_values = []
    for item in ndvi_data:
        ndvi_values.extend(item.get("data", {}).get("ndvi_values", []))

    if not ndvi_values:
        return {"error": "No NDVI values found in provided data."}

    avg_ndvi = float(np.mean(ndvi_values))
    health_status, recommendation = classify_ndvi(avg_ndvi)
    problem_count = sum(1 for value in ndvi_values if value < NDVI_PROBLEM_AREA_THRESHOLD)

    return {
        "avg_ndvi": avg_ndvi,
        "min_ndvi": float(np.min(ndvi_values)),
        "max_ndvi": float(np.max(ndvi_values)),
        "health_status": health_status,
        "problem_area_percent": float(problem_count / len(ndvi_values) * 100),
        "recommendation": recommendation,
    }


def classify_ndvi(avg_ndvi):
    if avg_ndvi < NDVI_CRITICAL_THRESHOLD:
        return "Critical", "Immediate intervention recommended."
    if avg_ndvi < NDVI_POOR_THRESHOLD:
        return "Poor", "Additional fertilization recommended."
    if avg_ndvi < NDVI_MODERATE_THRESHOLD:
        return "Moderate", "Monitor crop condition."
    return "Good", "Continue standard procedures."
