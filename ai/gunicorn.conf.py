import os

bind = "0.0.0.0:5000"
workers = int(os.environ.get("GUNICORN_WORKERS", "2"))
timeout = int(os.environ.get("GUNICORN_TIMEOUT", "90"))
preload_app = True
accesslog = "-"
errorlog = "-"
loglevel = os.environ.get("AI_LOG_LEVEL", "info").lower()
