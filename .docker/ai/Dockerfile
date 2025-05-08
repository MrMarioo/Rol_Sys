FROM python:3.10-slim

WORKDIR /app

RUN apt-get update && apt-get install -y \
    build-essential \
    libffi-dev \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN echo "flask==2.3.3\n\
flask-cors==4.0.0\n\
numpy==1.24.3\n\
pandas==2.0.3\n\
scikit-learn==1.3.0\n\
tensorflow==2.13.0\n\
pillow==10.0.0\n\
matplotlib==3.7.2\n\
seaborn==0.12.2\n\
pymysql==1.1.0\n\
sqlalchemy==2.0.19\n\
gunicorn==21.2.0\n\
opencv-python-headless==4.8.0.76" > /app/requirements.txt

RUN pip install --no-cache-dir -r /app/requirements.txt

RUN mkdir -p /app/models /app/data

EXPOSE 5000

CMD ["python", "app.py"]
