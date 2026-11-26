import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent


class Config:
    MODEL_PATH = Path(os.environ.get("AI_MODEL_PATH", BASE_DIR / "models" / "growth_model.pkl"))
    API_TOKEN = os.environ.get("AI_SERVICE_TOKEN", "")
    LOG_LEVEL = os.environ.get("AI_LOG_LEVEL", "INFO").upper()
