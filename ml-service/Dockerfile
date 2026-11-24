# DormInsight ML service (FastAPI) untuk Render / hosting berbasis Docker
FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1 PORT=10000
WORKDIR /app

COPY requirements-deploy.txt .
RUN pip install --no-cache-dir -r requirements-deploy.txt

COPY app ./app
COPY src ./src
COPY models ./models

# Gagal saat build jika model belum dilatih, agar tidak lolos ke server
RUN test -f models/model.joblib || (echo "models/model.joblib tidak ada: jalankan python -m src.train" && exit 1)

EXPOSE 10000
CMD ["sh", "-c", "uvicorn app.main:app --host 0.0.0.0 --port ${PORT}"]
