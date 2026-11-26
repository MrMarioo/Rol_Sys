import pytest

from app import create_app
from tests.conftest import moisture_item, ndvi_item

RESPONSE_KEYS = {"field_id", "analysis_date", "vegetation_health", "soil_moisture", "anomalies", "recommendations"}


def payload(**parameters):
    return {
        "field_data": [ndvi_item([0.2, 0.25]), moisture_item([0.1, 0.2])],
        "field_info": {"size": "31.60", "planting_date": "2026-04-01", "crop_type": "Corn"},
        "parameters": {"sensitivity": "medium", **parameters},
    }


def test_health(client):
    response = client.get("/health")

    assert response.status_code == 200
    assert response.get_json() == {"status": "ok"}


def test_analyze_field_returns_expected_shape(client):
    response = client.post("/analyze/field/7", json=payload())

    assert response.status_code == 200
    body = response.get_json()
    assert set(body) == RESPONSE_KEYS
    assert body["field_id"] == 7
    assert body["vegetation_health"]["health_status"] == "Critical"
    assert body["soil_moisture"]["moisture_status"] == "Low"
    assert {rec["source"] for rec in body["recommendations"]} == {"vegetation_analysis", "moisture_analysis"}


def test_analyze_field_with_growth_prediction(client):
    response = client.post("/analyze/field/7", json=payload(include_growth_prediction=True, prediction_days=5))

    body = response.get_json()
    assert response.status_code == 200
    assert set(body) == RESPONSE_KEYS | {"growth_prediction"}
    assert set(body["growth_prediction"]) == {
        "current_status",
        "current_biomass",
        "predictions",
        "recommendations",
        "summary",
        "model_info",
    }
    assert len(body["growth_prediction"]["predictions"]) == 5
    assert all(isinstance(issue, str) for issue in body["growth_prediction"]["current_status"]["issues"])
    merged = [rec for rec in body["recommendations"] if rec["source"] == "growth_prediction"]
    assert merged
    assert all({"category", "estimated_impact", "urgency_days"} <= set(rec) for rec in merged)


def test_job_style_payload_without_field_info(client):
    item = {
        **ndvi_item([0.8]),
        "id": 1,
        "collection_date": "2026-08-28T00:00:00.000000Z",
        "latitude": "50.1234567",
        "created_at": "2026-08-28T10:00:00.000000Z",
    }
    response = client.post("/analyze/field/1", json={"field_data": [item], "parameters": {"sensitivity": "medium"}})

    assert response.status_code == 200
    assert response.get_json()["vegetation_health"]["health_status"] == "Good"


@pytest.mark.parametrize(("requested", "expected"), [(0, 1), (500, 30), ("10", 10)])
def test_prediction_days_are_clamped(client, requested, expected):
    response = client.post("/analyze/field/1", json=payload(include_growth_prediction=True, prediction_days=requested))

    assert len(response.get_json()["growth_prediction"]["predictions"]) == expected


@pytest.mark.parametrize(
    ("body", "error_key"),
    [
        ([1, 2], "body"),
        ({"field_data": "nope"}, "field_data"),
        (
            {"field_data": [{"data_type": "soil_moisture", "data": {"moisture_values": ["x"]}}]},
            "field_data.0.data.moisture_values",
        ),
        ({"field_data": [{"data_type": "ndvi", "data": 5}]}, "field_data.0.data"),
        ({"parameters": {"sensitivity": "extreme"}}, "parameters.sensitivity"),
        ({"parameters": {"prediction_days": "soon"}}, "parameters.prediction_days"),
        ({"parameters": {"include_growth_prediction": "yes"}}, "parameters.include_growth_prediction"),
        ({"field_info": {"size": "big"}}, "field_info.size"),
    ],
)
def test_invalid_payload_returns_400(client, body, error_key):
    response = client.post("/analyze/field/1", json=body)

    assert response.status_code == 400
    assert response.get_json()["error"] == "Invalid request payload"
    assert error_key in response.get_json()["details"]


def test_non_json_body_returns_400(client):
    response = client.post("/analyze/field/1", data="not json", content_type="text/plain")

    assert response.status_code == 400


def test_unknown_route_returns_json_404(client):
    response = client.get("/nope")

    assert response.status_code == 404
    assert response.get_json() == {"error": "Not Found"}


def test_growth_prediction_errors_are_generic(client):
    class BrokenPredictor:
        model = object()

        def predict_growth(self, *args):
            raise RuntimeError("secret internals")

    broken_client = create_app(predictor=BrokenPredictor(), api_token="").test_client()
    response = broken_client.post("/analyze/field/1", json=payload(include_growth_prediction=True))

    assert response.status_code == 200
    assert response.get_json()["growth_prediction"] == {"error": "Growth prediction failed"}


def test_token_is_enforced_when_configured():
    secured = create_app(api_token="s3cret").test_client()

    assert secured.get("/health").status_code == 200
    assert secured.post("/analyze/field/1", json=payload()).status_code == 401
    assert secured.post("/analyze/field/1", json=payload(), headers={"X-AI-Token": "wrong"}).status_code == 401
    assert secured.post("/analyze/field/1", json=payload(), headers={"X-AI-Token": "s3cret"}).status_code == 200
