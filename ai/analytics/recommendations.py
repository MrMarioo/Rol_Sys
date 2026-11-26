PRIORITY_ORDER = {"high": 3, "medium": 2, "low": 1}


def generate_basic_recommendations(vegetation, moisture, anomalies):
    recommendations = []

    if vegetation and vegetation.get("health_status") in ("Critical", "Poor"):
        recommendations.append(
            {
                "type": "urgent",
                "priority": "high",
                "action": "Inspect crop condition",
                "description": (
                    f"Low NDVI ({vegetation.get('avg_ndvi', 0):.2f}) indicates potential crop health issues."
                ),
                "source": "vegetation_analysis",
            }
        )

    if moisture:
        if moisture.get("moisture_status") == "Low":
            recommendations.append(
                {
                    "type": "high",
                    "priority": "high",
                    "action": "Increase irrigation",
                    "description": f"Soil moisture is below optimal levels ({moisture.get('avg_moisture', 0):.2f}).",
                    "source": "moisture_analysis",
                }
            )
        elif moisture.get("moisture_status") == "High":
            recommendations.append(
                {
                    "type": "medium",
                    "priority": "medium",
                    "action": "Reduce irrigation",
                    "description": f"Soil moisture is above optimal levels ({moisture.get('avg_moisture', 0):.2f}).",
                    "source": "moisture_analysis",
                }
            )

    for anomaly in anomalies:
        if anomaly.get("severity") == "high":
            recommendations.append(
                {
                    "type": "urgent",
                    "priority": "high",
                    "action": "Investigate anomaly",
                    "description": anomaly.get("description", "Significant anomaly detected."),
                    "source": "anomaly_detection",
                }
            )

    if not recommendations:
        recommendations.append(
            {
                "type": "info",
                "priority": "low",
                "action": "Continue standard procedures",
                "description": "All parameters are within normal ranges.",
                "source": "general",
            }
        )

    return recommendations


def merge_recommendations(basic_recs, growth_recs):
    merged = list(basic_recs)

    for growth_rec in growth_recs:
        growth_action = growth_rec.get("action", "").lower()
        is_duplicate = any(
            growth_action in basic.get("action", "").lower() or basic.get("action", "").lower() in growth_action
            for basic in basic_recs
        )
        if not is_duplicate:
            merged.append({**growth_rec, "source": "growth_prediction"})

    merged.sort(key=lambda rec: PRIORITY_ORDER.get(rec.get("priority", "low"), 1), reverse=True)
    return merged
