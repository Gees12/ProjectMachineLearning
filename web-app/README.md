# DormInsight Web App (Laravel)

Aplikasi web DormInsight: survei anonim, manajemen survei dan akun, integrasi ML, dashboard, simulasi, dan laporan.
Penanggung jawab: Geraldo (backend), Cheryl (tampilan).

Folder ini berisi **kode khusus DormInsight** yang diletakkan di atas kerangka Laravel 12 standar.
Kerangka Laravel (vendor, config bawaan, dan lainnya) dipasang otomatis oleh `setup.ps1`.

## Catatan Instalasi dengan Laragon

- Aktifkan ekstensi `zip` (klik kanan Laragon, pilih PHP, lalu Extensions, lalu zip). Tanpa ekstensi ini, Composer dan paket Excel gagal dipasang.
- Klik Tools, lalu Path, lalu Add Laragon to Path, kemudian buka ulang VS Code agar `php` dan `composer` dikenali terminal.
- Pastikan ekstensi `pdo_sqlite` aktif dan `phpunit.xml` memakai SQLite in-memory (baris `DB_CONNECTION` dan `DB_DATABASE` tidak dikomentari). `setup.ps1` mengatur ini otomatis. **Jika test berjalan di MySQL, seluruh isi database `dorminsight` akan terhapus setiap kali `php artisan test` dijalankan.**
- Setelah mengubah `ml-service/.env`, restart uvicorn; ML service hanya membaca `.env` saat dinyalakan.

## Kebutuhan

| Perangkat lunak | Versi | Catatan |
|---|---|---|
| PHP | 8.2 atau lebih baru | Ekstensi: pdo_mysql, mbstring, openssl, fileinfo, gd, zip, intl |
| Composer | 2.x | https://getcomposer.org |
| MySQL / MariaDB | MySQL 8 | Paling mudah lewat XAMPP atau Laragon |
| ML service | lihat `../ml-service/README.md` | Dibutuhkan untuk analisis dan simulasi |

Tidak perlu Node.js/npm: Bootstrap, ikon, dan Chart.js dimuat dari CDN.

## Instalasi (Windows)

```powershell
cd web-app
powershell -ExecutionPolicy Bypass -File .\setup.ps1
```

Lalu:

1. Buat database `dorminsight` (phpMyAdmin atau `CREATE DATABASE dorminsight;`).
2. Edit `.env`: `DB_USERNAME`, `DB_PASSWORD`, dan `ML_API_KEY` (harus sama dengan `ml-service/.env`).
3. Jalankan:

```powershell
php artisan migrate --seed
php artisan serve
```

Buka http://localhost:8000.

### Akun awal (dari `DatabaseSeeder`)

| Peran | Login | Password awal |
|---|---|---|
| Admin | admin@dorminsight.test | Admin12345 |
| Pengelola | pengelola@dorminsight.test | Pengelola123 |
| Mahasiswa demo | NIM 10500001 sampai 10500003 | sama dengan NIM |

Semua akun wajib mengganti password saat login pertama. **Ganti atau hapus akun demo sebelum dipakai sungguhan.**

Seeder juga membuat survei **template** (draft) berisi 10 faktor × 2 item, angkatan, lama tinggal, kepuasan umum, pertanyaan target, dan saran.

### Data demo (opsional)

```powershell
php artisan db:seed --class=DemoResponsesSeeder
```

Membuat survei "[DEMO]" yang sudah ditutup dengan 150 respons dummy, untuk mencoba dashboard, ekspor CSV, dan analisis ML. Jangan jalankan di server produksi.

## Menjalankan Bersama ML Service

Terminal 1 (folder `ml-service`):

```powershell
.venv\Scripts\activate
uvicorn app.main:app --port 8001
```

Terminal 2 (folder `web-app`):

```powershell
php artisan serve
```

Lalu login sebagai admin → Survei & Data → survei demo → Analisis → Jalankan Analisis.

## Test

```powershell
php artisan test
```

Test memakai SQLite in-memory (bawaan `phpunit.xml` Laravel) dan memalsukan ML service, jadi tidak membutuhkan MySQL maupun FastAPI.

## Struktur Kode Khusus DormInsight

```
app/
├── Exceptions/          AnalysisException, MlServiceException, SubmissionException
├── Http/
│   ├── Controllers/     Auth, Home, Dashboard, Simulation, Report
│   │   ├── Admin/       User, Survey, Factor, Question, Response, Analysis
│   │   └── Student/     Submission
│   └── Middleware/      EnsureRole, EnsurePasswordChanged
├── Models/              12 model sesuai ERD
└── Services/            FeatureBuilder, ResponseValidator, SubmissionService,
                         MlClient, AnalysisService, DashboardService
bootstrap/app.php        alias middleware
config/dorminsight.php   populasi, ambang, konfigurasi ML
database/migrations/     users (diubah), survey tables, analysis tables
database/seeders/        DatabaseSeeder, SurveyTemplateSeeder, DemoResponsesSeeder
resources/views/         Blade + Bootstrap 5
routes/web.php           semua route
tests/                   Feature dan Unit test
```

Dokumentasi teknis lengkap: `docs/05_Implementation/`.
