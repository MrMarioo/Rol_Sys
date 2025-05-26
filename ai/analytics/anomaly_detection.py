import numpy as np
from sklearn.ensemble import IsolationForest
import pandas as pd
import json


def detect_anomalies(field_data, sensitivity='medium'):
    if not field_data or len(field_data) < 10:
        return []

    # Set threshold based on sensitivity
    contamination = 0.05  # Default medium
    if sensitivity == 'low':
        contamination = 0.1
    elif sensitivity == 'high':
        contamination = 0.01

    # Prepare data for analysis
    df = pd.DataFrame()
    rows = []

    for item in field_data:
        data_type = item.get('data_type')
        data = item.get('data', {})

        if isinstance(data, str):
            try:
                data = json.loads(data)
            except:
                continue

        row = {
            'date': item.get('collection_date'),
            'data_type': data_type
        }

        if data_type == 'ndvi' and 'ndvi_values' in data:
            row['value'] = np.mean(data['ndvi_values'])
            row['feature'] = 'ndvi'
            rows.append(row)
        elif data_type == 'soil_moisture' and 'moisture_values' in data:
            row['value'] = np.mean(data['moisture_values'])
            row['feature'] = 'moisture'
            rows.append(row)

    if not rows:
        return []

    df = pd.DataFrame(rows)

    # Detect anomalies for each feature separately
    anomalies = []

    for feature in df['feature'].unique():
        feature_df = df[df['feature'] == feature].copy()

        if len(feature_df) < 5:  # Need enough data points
            continue

        # Use only numerical values for anomaly detection
        X = feature_df[['value']].values

        # Apply Isolation Forest
        model = IsolationForest(contamination=contamination, random_state=42)
        feature_df['anomaly'] = model.fit_predict(X)

        # Extract anomalies
        anomaly_df = feature_df[feature_df['anomaly'] == -1]

        for _, row in anomaly_df.iterrows():
            # Calculate deviation from mean
            mean_value = feature_df['value'].mean()
            std_value = feature_df['value'].std()
            deviation = abs(row['value'] - mean_value) / (std_value if std_value > 0 else 1)

            anomalies.append({
                'date': row['date'],
                'type': f"{row['feature']}_anomaly",
                'description': f"Anomalous {row['feature']} value detected ({row['value']:.2f})",
                'severity': 'high' if deviation > 3 else 'medium',
                'value': float(row['value']),
                'expected': float(mean_value)
            })

    return anomalies
