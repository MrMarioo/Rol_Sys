import pytest

from analytics.vegetation_health import analyze_ndvi
from tests.conftest import ndvi_item


def test_returns_none_without_data():
    assert analyze_ndvi([]) is None


def test_reports_error_when_items_have_no_values():
    assert analyze_ndvi([ndvi_item([])]) == {"error": "No NDVI values found in provided data."}


@pytest.mark.parametrize(
    ("value", "status"),
    [(0.29, "Critical"), (0.3, "Poor"), (0.49, "Poor"), (0.5, "Moderate"), (0.69, "Moderate"), (0.7, "Good")],
)
def test_health_status_thresholds(value, status):
    assert analyze_ndvi([ndvi_item([value])])["health_status"] == status


def test_statistics_across_items():
    result = analyze_ndvi([ndvi_item([0.2, 0.4]), ndvi_item([0.6, 0.8])])

    assert result["avg_ndvi"] == pytest.approx(0.5)
    assert result["min_ndvi"] == pytest.approx(0.2)
    assert result["max_ndvi"] == pytest.approx(0.8)
    assert result["problem_area_percent"] == pytest.approx(50.0)
