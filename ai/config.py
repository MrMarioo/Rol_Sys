import os

class Config:
    """Konfiguracja aplikacji."""

    DB_HOST = os.environ.get('DB_HOST', 'db')
    DB_USER = os.environ.get('DB_USER', 'root')
    DB_PASSWORD = os.environ.get('DB_PASSWORD', 'l3tm31n')
    DB_NAME = os.environ.get('DB_NAME', 'laravel')

    MODEL_PATH = '/app/models'

    TENSORFLOW_DEVICE = os.environ.get('TENSORFLOW_DEVICE', 'cpu')

    API_URL = os.environ.get('AI_SERVICE_URL', 'http://localhost:5000')

    DATA_PATH = '/app/data'
