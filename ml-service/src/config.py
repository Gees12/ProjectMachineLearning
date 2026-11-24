"""Konfigurasi bersama untuk pipeline ML DormInsight.

Daftar fitur, kode item survei, dan nilai kategori harus sama dengan
docs/03_System_Design/Data_Dictionary.md bagian B.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent  # folder ml-service/

DATA_DIR = ROOT / "data"
RAW_DIR = DATA_DIR / "raw"
PROCESSED_DIR = DATA_DIR / "processed"
DUMMY_DIR = DATA_DIR / "dummy"
MODELS_DIR = ROOT / "models"
REPORTS_DIR = ROOT / "reports"
FIGURES_DIR = REPORTS_DIR / "figures"

RANDOM_STATE = 42

# 10 faktor kepuasan, masing-masing 2 item Likert 1-5
FACTORS = {
    "F_FAS": "Fasilitas kamar",
    "F_KEB": "Kebersihan",
    "F_MAK": "Makanan / kafetaria",
    "F_BIA": "Biaya",
    "F_PER": "Peraturan asrama",
    "F_PRI": "Privasi",
    "F_KEA": "Keamanan",
    "F_SOS": "Lingkungan sosial",
    "F_PEL": "Pelayanan pengelola",
    "F_BEL": "Kenyamanan belajar dan internet",
}
ITEMS_PER_FACTOR = 2
FACTOR_ITEMS = {f: [f"{f}_{i}" for i in range(1, ITEMS_PER_FACTOR + 1)] for f in FACTORS}

# Fitur demografis
ANGKATAN_VALUES = ["2023_atau_sebelumnya", "2024", "2025", "2026"]
LAMA_TINGGAL_VALUES = ["lt1", "1_2", "gt2"]  # diurutkan: ordinal 0, 1, 2

NUMERIC_FEATURES = list(FACTORS)
ORDINAL_FEATURES = ["LAMA_TINGGAL"]
CATEGORICAL_FEATURES = ["ANGKATAN"]
FEATURES = NUMERIC_FEATURES + CATEGORICAL_FEATURES + ORDINAL_FEATURES

TARGET = "INGIN_KELUAR"

# Kolom yang TIDAK boleh menjadi fitur (lihat Data Dictionary B.8)
EXCLUDED_COLUMNS = ["KEP_UMUM", "SARAN", "duration_seconds", "response_id", "is_valid"]

# Ambang kategori risiko (Data Dictionary B.6), ditinjau ulang setelah data asli
RISK_THRESHOLDS = {"sedang": 0.40, "tinggi": 0.70}

# Batas minimum data (Feasibility Study 4.2)
MIN_VALID_RESPONSES = 100
MIN_SEGMENT_SIZE = 5


def risk_level(probability: float) -> str:
    if probability >= RISK_THRESHOLDS["tinggi"]:
        return "tinggi"
    if probability >= RISK_THRESHOLDS["sedang"]:
        return "sedang"
    return "rendah"
