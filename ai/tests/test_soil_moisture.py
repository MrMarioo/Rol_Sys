import pytest

from analytics.soil_moisture import predict_moisture
from tests.conftest import moisture_item


def test_returns_none_without_data():
    assert predict_moisture([]) is None


def test_missing_values_key_does_not_crash():
    assert predict_moisture([{"data_type": "soil_moisture", "data": {}}]) == {"error": "No moisture values found"}


@pytest.mark.parametrize(
    ("value", "status"),
    [(0.29, "Low"), (0.3, "Optimal"), (0.7, "Optimal"), (0.71, "High")],
)
def test_moisture_status_thresholds(value, status):
    assert predict_moisture([moisture_item([value])])["moisture_status"] == status


def test_statistics():
    result = predict_moisture([moisture_item([0.2, 0.4, 0.6])])

    assert result["avg_moisture"] == pytest.approx(0.4)
    assert result["predicted_moisture"] == pytest.approx(0.4)
    assert result["min_moisture"] == pytest.approx(0.2)
    assert result["max_moisture"] == pytest.approx(0.6)
    assert result["recommendation"] == "Maintain irrigation"
