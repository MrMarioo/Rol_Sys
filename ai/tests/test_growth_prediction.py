import pytest

from analytics.plant_growth_prediction import ModelNotAvailableError, PlantGrowthPredictor
from config import Config
from tests.conftest import moisture_item, ndvi_item


@pytest.fixture(scope="module")
def predictor():
    return PlantGrowthPredictor(Config.MODEL_PATH)


def test_missing_model_fails_fast(tmp_path):
    with pytest.raises(ModelNotAvailableError):
        PlantGrowthPredictor(tmp_path / "missing.pkl")


def test_prediction_is_deterministic(predictor):
    field_data = [ndvi_item([0.6, 0.7]), moisture_item([0.4, 0.5])]
    field_info = {"size": 12.5, "planting_date": "2026-04-01"}

    first = predictor.predict_growth(field_data, field_info, 7)
    second = predictor.predict_growth(field_data, field_info, 7)

    assert first == second
    assert len(first["predictions"]) == 7
