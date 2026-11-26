from analytics.anomaly_detection import detect_anomalies
from tests.conftest import ndvi_item


def series(values):
    return [ndvi_item([value], date=f"2026-06-{day:02d}") for day, value in enumerate(values, start=1)]


def test_requires_minimum_number_of_records():
    assert detect_anomalies(series([0.6] * 9)) == []


def test_flags_outlier():
    anomalies = detect_anomalies(series([0.6, 0.61, 0.59, 0.6, 0.62, 0.58, 0.6, 0.61, 0.59, 0.6, 0.05]), "low")

    assert len(anomalies) >= 1
    outlier = next(a for a in anomalies if a["date"] == "2026-06-11")
    assert outlier["type"] == "ndvi_anomaly"
    assert outlier["severity"] == "high"
    assert outlier["value"] == 0.05


def test_ignores_unsupported_data_types():
    records = [{"data_type": "weather", "data": {"temperature_avg": 20}} for _ in range(12)]
    assert detect_anomalies(records) == []
