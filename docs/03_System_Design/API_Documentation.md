# API Documentation

> Proyek: DormInsight
> Dokumen: Fase 3 - System & Data Design
> Versi: 0.2 (Draft)
> Penyusun: Gerald (ML Service), Geraldo (Laravel)
> Terakhir diperbarui: 23 November 2026

Dokumen ini adalah **kontrak** antara Laravel dan ML service. Perubahan pada kontrak harus disepakati Geraldo dan Gerald, lalu dicatat di bagian Riwayat Perubahan.

FastAPI juga menghasilkan dokumentasi interaktif otomatis di `/docs` (Swagger UI) setelah service berjalan.

---

# Bagian A. ML Service (FastAPI)

## A.1 Informasi Umum

| Item | Nilai |
|---|---|
| Base URL lokal | `http://localhost:8001` |
| Base URL demo | `[TBD saat deployment]` |
| Format | JSON (`Content-Type: application/json`) |
| Autentikasi | Header `X-API-Key: <ML_API_KEY>` pada semua endpoint kecuali `/health` |
| Batas batch | Maksimal 1.000 record per request |

Variabel environment:

| Layanan | Variabel |
|---|---|
| Laravel `.env` | `ML_SERVICE_URL`, `ML_API_KEY`, `ML_TIMEOUT=60` |
| FastAPI `.env` | `ML_API_KEY`, `MODEL_PATH=models/model.joblib`, `MODEL_META_PATH=models/model_meta.json` |

## A.2 Objek `features`

Dipakai di semua endpoint prediksi. Daftar fitur harus sama dengan `features` dari `/model/info`.

```json
{
  "F_FAS": 3.5, "F_KEB": 4.0, "F_MAK": 2.0, "F_BIA": 3.0, "F_PER": 2.5,
  "F_PRI": 3.0, "F_KEA": 4.5, "F_SOS": 4.0, "F_PEL": 3.5, "F_BEL": 2.5,
  "ANGKATAN": "2025",
  "LAMA_TINGGAL": "1_2"
}
```

Validasi: skor faktor bernilai 1.0 sampai 5.0; `ANGKATAN` dan `LAMA_TINGGAL` harus salah satu nilai di Data Dictionary B.3.

## A.3 `GET /health`

Mengecek service aktif dan model termuat. Tidak memerlukan API key.

**Response 200**

```json
{ "status": "ok", "model_loaded": true }
```

## A.4 `GET /model/info`

**Response 200**

```json
{
  "model_version": "rf-1.0.0",
  "algorithm": "RandomForestClassifier",
  "trained_at": "2026-12-10T14:00:00+08:00",
  "data_source": "real",
  "data_sufficient": true,
  "features": ["F_FAS","F_KEB","F_MAK","F_BIA","F_PER","F_PRI","F_KEA","F_SOS","F_PEL","F_BEL","ANGKATAN","LAMA_TINGGAL"],
  "target": "INGIN_KELUAR",
  "risk_thresholds": { "sedang": 0.40, "tinggi": 0.70 },
  "metrics": {
    "cv_folds": 5,
    "n_train": 140,
    "accuracy": 0.80,
    "precision_keluar": 0.62,
    "recall_keluar": 0.58,
    "f1_keluar": 0.60,
    "baseline_f1_keluar": 0.00
  },
  "shap_stability": [
    { "code": "F_MAK", "top3_frequency": 0.67 },
    { "code": "F_PRI", "top3_frequency": 0.63 }
  ]
}
```

Angka metrik di atas hanya contoh format, bukan hasil sebenarnya.

`shap_stability` berisi seberapa sering setiap fitur masuk 3 besar SHAP pada uji bootstrap saat training (0 sampai 1). Laravel menyimpannya ke `analysis_shap.top3_frequency` dan dashboard menampilkannya sebagai tingkat keyakinan.

`data_source` bernilai `dummy` atau `real`. Jika `dummy` atau `data_sufficient` bernilai `false`, Laravel wajib menampilkan peringatan di dashboard bahwa hasil belum dapat dijadikan dasar keputusan.

## A.5 `POST /predict/batch`

Menghitung probabilitas keinginan keluar untuk banyak respons.

**Request**

```json
{
  "records": [
    { "id": "8f6c1e2a-4b1d-4c55-9f0e-2a7d1b3c9e10", "features": { "...": "objek features" } },
    { "id": "0b2d7a91-6c3e-4f8a-b1d2-5e6f7a8b9c0d", "features": { "...": "objek features" } }
  ]
}
```

**Response 200**

```json
{
  "model_version": "rf-1.0.0",
  "predictions": [
    { "id": "8f6c1e2a-4b1d-4c55-9f0e-2a7d1b3c9e10", "probability": 0.7312, "risk_level": "tinggi" },
    { "id": "0b2d7a91-6c3e-4f8a-b1d2-5e6f7a8b9c0d", "probability": 0.1840, "risk_level": "rendah" }
  ]
}
```

`id` hanya dikembalikan apa adanya; ML service tidak menyimpan data apa pun.

## A.6 `POST /explain/global`

Menghitung kontribusi rata-rata setiap fitur (SHAP) untuk sekumpulan respons.

**Request:** sama dengan `/predict/batch`.

**Response 200**

```json
{
  "model_version": "rf-1.0.0",
  "method": "shap.TreeExplainer",
  "n_records": 142,
  "features": [
    { "code": "F_MAK", "mean_abs_shap": 0.08120, "direction": "negatif", "rank": 1 },
    { "code": "F_BIA", "mean_abs_shap": 0.06350, "direction": "negatif", "rank": 2 },
    { "code": "F_PRI", "mean_abs_shap": 0.04410, "direction": "negatif", "rank": 3 }
  ]
}
```

Untuk fitur one-hot (ANGKATAN), nilai SHAP semua kolom hasil encoding dijumlahkan kembali menjadi satu fitur `ANGKATAN` agar mudah dibaca.

## A.7 `POST /simulate`

Membandingkan probabilitas sebelum dan sesudah perubahan skor faktor.

**Request**

```json
{
  "baseline": { "...": "objek features (rata-rata periode terakhir)" },
  "scenario": { "...": "objek features dengan nilai yang diubah" }
}
```

**Response 200**

```json
{
  "model_version": "rf-1.0.0",
  "baseline_probability": 0.4200,
  "scenario_probability": 0.3100,
  "delta": -0.1100,
  "note": "Hasil simulasi adalah perkiraan model, bukan jaminan."
}
```

## A.8 Format Error

| Status | Kapan | Body |
|---|---|---|
| 401 | API key tidak ada atau salah | `{"detail": "Invalid API key"}` |
| 413 | Lebih dari 1.000 record | `{"detail": "Too many records (max 1000)"}` |
| 422 | Validasi gagal (fitur hilang, nilai di luar rentang) | Format validasi bawaan FastAPI |
| 503 | Model belum termuat | `{"detail": "Model not loaded"}` |
| 500 | Kesalahan internal | `{"detail": "Internal error"}` |

---

# Bagian B. Laravel Web Routes

Semua route kecuali login berada di dalam middleware `auth`. Kolom Role menunjukkan middleware `role:` yang dipakai.

### B.1 Autentikasi

| Method | URI | Controller@method | Role |
|---|---|---|---|
| GET | `/login` | AuthController@showLogin | guest |
| POST | `/login` | AuthController@login | guest, throttle |
| POST | `/logout` | AuthController@logout | semua |
| GET | `/password/change` | AuthController@showChangePassword | semua |
| PUT | `/password/change` | AuthController@changePassword | semua |

### B.2 Mahasiswa

| Method | URI | Controller@method | Keterangan |
|---|---|---|---|
| GET | `/survey` | SubmissionController@index | Status survei aktif dan status pengisian |
| GET | `/survey/consent` | SubmissionController@consent | Halaman persetujuan |
| POST | `/survey/start` | SubmissionController@start | Menyimpan waktu mulai di session |
| GET | `/survey/fill` | SubmissionController@fill | Formulir survei |
| POST | `/survey/submit` | SubmissionController@submit | Validasi dan simpan |
| GET | `/survey/thanks` | SubmissionController@thanks | Konfirmasi |

### B.3 Admin

| Method | URI | Controller@method |
|---|---|---|
| GET, POST | `/admin/users` | UserController@index, store |
| POST | `/admin/users/import` | UserController@import |
| GET, PUT | `/admin/users/{user}/edit`, `/admin/users/{user}` | UserController@edit, update |
| PATCH | `/admin/users/{user}/toggle` | UserController@toggleActive |
| PATCH | `/admin/users/{user}/reset-password` | UserController@resetPassword |
| resource | `/admin/surveys` | SurveyController |
| POST | `/admin/surveys/{survey}/duplicate` | SurveyController@duplicate |
| PATCH | `/admin/surveys/{survey}/open`, `/close` | SurveyController@open, close |
| resource | `/admin/surveys/{survey}/factors` | FactorController |
| resource | `/admin/surveys/{survey}/questions` | QuestionController |
| GET | `/admin/surveys/{survey}/responses` | ResponseController@index |
| PATCH | `/admin/responses/{response}/validity` | ResponseController@toggleValidity |
| GET | `/admin/surveys/{survey}/responses/export` | ResponseController@export (CSV) |
| POST | `/admin/surveys/{survey}/analyze` | AnalysisController@run |
| GET | `/admin/surveys/{survey}/analyses` | AnalysisController@index |

### B.4 Pengelola (juga dapat diakses Admin)

| Method | URI | Controller@method |
|---|---|---|
| GET | `/dashboard` | DashboardController@index (query: `survey`, `angkatan`, `lama_tinggal`) |
| GET | `/dashboard/compare` | DashboardController@compare (query: `a`, `b`) |
| GET | `/simulation` | SimulationController@index |
| POST | `/simulation` | SimulationController@run |
| GET | `/reports/{survey}/pdf` | ReportController@pdf |
| GET | `/reports/{survey}/excel` | ReportController@excel |

---

## Riwayat Perubahan

| Versi | Tanggal | Perubahan | Disetujui |
|---|---|---|---|
| 0.1 | 23 Nov 2026 | Kontrak awal | Geraldo, Gerald |
| 0.2 | 23 Nov 2026 | `/model/info` menambah `data_source`, `data_sufficient`, dan `shap_stability` | Geraldo, Gerald |
