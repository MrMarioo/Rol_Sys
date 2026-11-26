import pytest

from app import create_app


def ndvi_item(values, date="2026-06-01"):
    return {"data_type": "ndvi", "collection_date": date, "data": {"ndvi_values": values}}


def moisture_item(values, date="2026-06-01"):
    return {"data_type": "soil_moisture", "collection_date": date, "data": {"moisture_values": values}}


@pytest.fixture(scope="session")
def app():
    return create_app(api_token="")


@pytest.fixture
def client(app):
    return app.test_client()
