# DormInsight

**AI-Based Student Dormitory Satisfaction and Retention Analysis System**

Sistem untuk menganalisis kepuasan mahasiswa asrama dan mengidentifikasi faktor dominan yang membuat mahasiswa ingin meninggalkan asrama.

## Tech Stack

| Bagian | Teknologi |
|---|---|
| Frontend | Laravel Blade, Bootstrap |
| Backend | Laravel |
| Database | MySQL |
| ML Service | Python, FastAPI, scikit-learn, SHAP |

## Struktur Repository

```
DormInsight/
├── docs/          Dokumentasi per fase SDLC (01 sampai 08)
├── web-app/       Aplikasi web Laravel: tampilan (Blade), logika, database, integrasi ML
├── design/        Aset desain UI/UX: wireframe, ekspor Figma, screenshot
├── database/      Skema dan migrasi MySQL
├── ml-service/    FastAPI + pipeline Machine Learning
└── README.md
```

## Metode Pengembangan

SDLC sebagai kerangka fase, Agile/Scrum yang disederhanakan untuk pengelolaan pekerjaan.

| Sprint | Pekerjaan |
|---|---|
| Sprint 1 | Requirements dan project planning |
| Sprint 2 | System design dan UI/UX |
| Sprint 3 | Database, authentication, survey |
| Sprint 4 | ML development dan model evaluation |
| Sprint 5 | Dashboard dan ML integration |
| Sprint 6 | Testing, UAT, dan deployment |

## Status

| Fase | Status |
|---|---|
| 1. Project Initiation | Selesai |
| 2. Requirements | Selesai |
| 3. System & Data Design | Selesai |
| 4. ML Development | Pipeline dan API selesai (model masih dari data dummy) |
| 5. Application Development | Aplikasi Laravel berjalan, 37 test lulus |
| 6 sampai 8 | Belum dimulai |

## Mulai Cepat untuk Anggota Tim

Kebutuhan: Git, Laragon (PHP 8.2+, MySQL, Composer; aktifkan ekstensi `zip` dan `pdo_sqlite`), Python 3.12, VS Code.

```powershell
git clone https://github.com/Gees12/ProjectMachineLearning.git
cd ProjectMachineLearning
```

**ML service** (detail: `ml-service/README.md`):

```powershell
cd ml-service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
python -m src.train --data data/dummy/dummy_survey.csv --data-source dummy
uvicorn app.main:app --port 8001
```

**Aplikasi web** (terminal baru, detail: `web-app/README.md`):

```powershell
cd web-app
powershell -ExecutionPolicy Bypass -File .\setup.ps1
# buat database MySQL "dorminsight", sesuaikan DB_* dan ML_API_KEY di .env
php artisan migrate --seed
php artisan serve
```

File `.env` tidak ada di repository (berisi password dan API key). Setiap anggota membuatnya sendiri dari `.env.example`.

## Alur Kerja Git Tim

| Branch | Fungsi |
|---|---|
| `main` | Versi stabil; hanya diubah lewat pull request |
| `feature/<nama-fitur>` | Pekerjaan per fitur, mis. `feature/dashboard-tren` |

1. `git pull` di `main`, lalu `git checkout -b feature/<nama-fitur>`.
2. Commit perubahan kecil dan jelas, lalu `git push -u origin feature/<nama-fitur>`.
3. Buat pull request di GitHub; minimal 1 anggota lain me-review.
4. Jalankan `php artisan test` dan `pytest -q` sebelum merge.

**Jangan pernah commit:** file `.env`, data survei asli (`ml-service/data/raw/`), atau backup database.
