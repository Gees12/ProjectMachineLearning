"""Pengaturan ML service dari environment variable (file .env)."""
import os
from pathlib import Path

from dotenv import load_dotenv

ROOT = Path(__file__).resolve().parent.parent
load_dotenv(ROOT / ".env")

ML_API_KEY = os.getenv("ML_API_KEY", "")
MODEL_PATH = ROOT / os.getenv("MODEL_PATH", "models/model.joblib")
MAX_RECORDS = int(os.getenv("MAX_RECORDS", "1000"))
