import numpy as np
import pandas as pd
from sklearn.ensemble import IsolationForest

CONTAMINATION_BY_SENSITIVITY = {"low": 0.1, "medium": 0.05, "high": 0.01}
MIN_RECORDS = 10
MIN_POINTS_PER_FEATURE = 5
HIGH_SEVERITY_DEVIATION = 3
RANDOM_STATE = 42

FEATURES_BY_TYPE = {
    "ndvi": ("ndvi_values", "ndvi"),
    "soil_moisture": ("moisture_values", "moisture"),
}


def detect_anomalies(field_data, sensitivity="medium"):
    if not field_data or len(field_data) < MIN_RECORDS:
        return []

    contamination = CONTAMINATION_BY_SENSITIVITY.get(sensitivity, CONTAMINATION_BY_SENSITIVITY["medium"])
    rows = []

    for item in field_data:
        mapping = FEATURES_BY_TYPE.get(item.get("data_type"))
        if mapping is None:
            continue

        values_key, feature = mapping
        values = (item.get("data") or {}).get(values_key)
        if not values:
            continue

        rows.append({"date": item.get("collection_date"), "value": float(np.mean(values)), "feature": feature})

    if not rows:
        return []

    df = pd.DataFrame(rows)
    anomalies = []

    for feature in df["feature"].unique():
        feature_df = df[df["feature"] == feature].copy()
        if len(feature_df) < MIN_POINTS_PER_FEATURE:
            continue

        model = IsolationForest(contamination=contamination, random_state=RANDOM_STATE)
        feature_df["anomaly"] = model.fit_predict(feature_df[["value"]].values)

        mean_value = feature_df["value"].mean()
        std_value = feature_df["value"].std()

        for _, row in feature_df[feature_df["anomaly"] == -1].iterrows():
            deviation = abs(row["value"] - mean_value) / (std_value if std_value > 0 else 1)
            anomalies.append(
                {
                    "date": row["date"],
                    "type": f"{row['feature']}_anomaly",
                    "description": f"Anomalous {row['feature']} value detected ({row['value']:.2f})",
                    "severity": "high" if deviation > HIGH_SEVERITY_DEVIATION else "medium",
                    "value": float(row["value"]),
                    "expected": float(mean_value),
                }
            )

    return anomalies
