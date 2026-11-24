# DormInsight ML Service

Pipeline Machine Learning dan REST API (FastAPI) untuk DormInsight.
Penanggung jawab: Gerald (ML Engineer).

> **Status saat ini:** model dilatih dengan **data dummy** (`lr-0.1.0-dummy`) untuk menguji pipeline dan integrasi. Hasilnya **bukan** temuan tentang Crystal Dormitory. Latih ulang dengan data survei asli setelah tersedia.

## Struktur

```
ml-service/
├── app/                FastAPI (main.py, schemas.py, security.py, model_store.py, settings.py)
├── src/
│   ├── config.py       Daftar fitur, kategori, ambang risiko
│   ├── features.py     Validasi CSV dan perhitungan skor faktor
│   ├── modeling.py     Preprocessing dan kandidat model
│   ├── explain.py      SHAP global per fitur
│   ├── eda.py          Exploratory Data Analysis
│   ├── train.py        Nested CV, pemilihan model, uji stabilitas, simpan model
│   └── generate_dummy_data.py
├── tests/              Test fitur dan kontrak API (pytest)
├── data/
│   ├── dummy/          Data sintetis (boleh di-commit)
│   ├── raw/            Ekspor survei asli (TIDAK di-commit)
│   └── processed/      (TIDAK di-commit)
├── models/             model.joblib, model_meta.json
├── reports/            Hasil EDA, perbandingan model, grafik, experiment log
└── notebooks/          Eksplorasi tambahan
```

## Instalasi (Windows, VS Code)

Buka terminal di folder `ml-service`:

```powershell
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env      # lalu isi ML_API_KEY
```

## Alur Kerja

### 1. Data dummy (untuk uji coba)

```powershell
python -m src.generate_dummy_data --n 150
```

### 2. EDA

```powershell
python -m src.eda --data data/dummy/dummy_survey.csv
```

Hasil di `reports/eda/`: ringkasan, statistik faktor, korelasi antaritem, dan grafik.

### 3. Training dan evaluasi

```powershell
python -m src.train --data data/dummy/dummy_survey.csv --data-source dummy
```

Langkah ini membandingkan baseline, Logistic Regression, Decision Tree, dan Random Forest dengan nested stratified 5-fold CV, memilih model terbaik, menguji stabilitas peringkat SHAP (bootstrap), lalu menyimpan `models/model.joblib` dan laporan di `reports/`. Durasi sekitar 1 menit.

> Jalankan langkah ini sekali setelah instalasi. File `model.joblib` sebaiknya dibuat di versi scikit-learn yang sama dengan yang dipakai untuk menjalankan API.

### 4. Test

```powershell
pytest -q
```

### 5. Menjalankan API

```powershell
uvicorn app.main:app --reload --port 8001
```

Buka http://localhost:8001/docs untuk mencoba endpoint. Kontrak lengkap ada di `docs/03_System_Design/API_Documentation.md`.

## Saat Data Asli Tersedia

1. Admin mengekspor CSV dari Laravel, simpan sebagai `data/raw/survey_export.csv` (format di `docs/04_Machine_Learning/Dataset_Documentation.md`).
2. Jalankan EDA, periksa `reports/eda/summary.json` dan `item_correlations.csv`.
3. Training:
   ```powershell
   python -m src.train --data data/raw/survey_export.csv --data-source real --version 1.0.0
   ```
4. Catat hasil di `docs/04_Machine_Learning/ML_Experiment_Log.md` dan perbarui Model Evaluation serta Model Card.
5. Restart API agar memuat model baru.
