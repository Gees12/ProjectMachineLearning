# Technical Documentation

> Proyek: DormInsight
> Dokumen: Fase 5 - Application Development
> Versi: 0.2
> Penyusun: Geraldo (Backend), Cheryl (Frontend), Gerald (ML Service)
> Terakhir diperbarui: 23 November 2026

## 1. Tech Stack

| Lapisan | Teknologi |
|---|---|
| Web framework | Laravel 12 (PHP 8.2+) |
| Tampilan | Blade, Bootstrap 5.3, Bootstrap Icons, Chart.js 4 (CDN, tanpa build npm) |
| Database | MySQL 8 (produksi/lokal), SQLite in-memory (test) |
| PDF | barryvdh/laravel-dompdf |
| Excel | rap2hpoutre/fast-excel |
| ML service | Python, FastAPI, scikit-learn, SHAP (lihat `ml-service/README.md`) |
| Test | PHPUnit (Laravel), pytest (ML) |

## 2. Struktur Repository

```
DormInsight/
├── web-app/        Aplikasi web Laravel lengkap (tampilan + logika + setup.ps1)
├── ml-service/     FastAPI + pipeline ML
├── design/         Aset desain UI/UX dan ekspor Figma
├── database/       Dump dan skrip backup
└── docs/
```

Kode khusus DormInsight di `web-app/` diletakkan di atas kerangka Laravel standar. `setup.ps1` memasang kerangka Laravel tanpa menimpa file DormInsight. Detail di `web-app/README.md`.

## 3. Pemetaan Modul ke Kode

| Modul (Scope) | Controller | Service | View |
|---|---|---|---|
| Authentication | `AuthController`, `HomeController` | (Laravel Auth, RateLimiter) | `auth/` |
| User Management | `Admin\UserController` | | `admin/users/` |
| Survey Management | `Admin\SurveyController`, `FactorController`, `QuestionController` | | `admin/surveys/` |
| Survey Submission | `Student\SubmissionController` | `SubmissionService`, `FeatureBuilder`, `ResponseValidator` | `student/` |
| Data Management | `Admin\ResponseController` | | `admin/responses/` |
| ML Integration | `Admin\AnalysisController` | `AnalysisService`, `MlClient` | `admin/analyses/` |
| Analytics Dashboard | `DashboardController`, `SimulationController` | `DashboardService`, `MlClient` | `dashboard/`, `simulation/` |
| Reporting | `ReportController` | `DashboardService` | `reports/pdf.blade.php` |

## 4. Keterlacakan Requirement

| Requirement | Implementasi |
|---|---|
| FR-001 sampai FR-003 Login, logout, redirect per role | `AuthController@login/logout`, `User::homeRoute()` |
| FR-004, NFR-001 Pembatasan akses | Middleware `role:` di `routes/web.php`, `EnsureRole` |
| FR-005 Ganti password awal | `must_change_password`, `EnsurePasswordChanged` |
| NFR-005 Kunci login | `RateLimiter`, 5 percobaan per menit per login+IP |
| FR-011 Impor CSV | `UserController@import` (password awal = NIM) |
| FR-015, FR-016 Pendaftaran mandiri | `AuthController@showRegister/register`, `auth/register.blade.php` |
| FR-017 Persetujuan pendaftaran | `UserController@toggleActive` (Setujui), `UserController@reject` (Tolak), `User::scopePendingApproval` |
| FR-018 Pengaturan pendaftaran | `config/dorminsight.php` bagian `registration`, variabel `DORM_REGISTRATION_*` |
| FR-024 Satu label target | `QuestionController::consistencyErrors`, `SurveyController@open` |
| FR-025 Pertanyaan terkunci | Cek `hasResponses()` di Factor/QuestionController |
| FR-026 Satu survei aktif | `SurveyController@open` |
| FR-027 Duplikasi | `SurveyController@duplicate` |
| FR-030 Consent | `student/consent.blade.php`, `SubmissionController@start` |
| FR-031, FR-033 Per bagian + tinjau | `student/fill.blade.php` (stepper JavaScript) |
| FR-035, FR-036, NFR-010 Anonim, sekali per periode | `SubmissionService` (satu transaksi, unique `survey_id,user_id`) |
| FR-037 Tolak setelah tutup | `Survey::isOpenForSubmission()` |
| FR-038 Beranda mahasiswa | `SubmissionController@index`, `SubmissionController::remainingText()`, `student/index.blade.php` |
| FR-042, FR-043 Respons mencurigakan | `ResponseValidator`, `ResponseController@toggleValidity` |
| FR-044 Ekspor CSV anonim | `ResponseController@export` |
| FR-050 sampai FR-055 Analisis ML | `AnalysisService` |
| FR-060 sampai FR-065, FR-068 Dashboard | `DashboardService::data()`, `dashboard/index.blade.php` |
| FR-066 Simulasi | `SimulationController` |
| FR-067 Perbandingan periode | `DashboardController@compare` |
| FR-070 sampai FR-072 Laporan | `ReportController` |
| NFR-004 API key ML | `MlClient` header `X-API-Key` dari `ML_API_KEY` |

## 5. Alur Penting

### 5.1 Pengiriman Survei Anonim

1. `start` menyimpan waktu mulai di **session** (bukan database).
2. `submit` memvalidasi semua jawaban (wajib, rentang 1 sampai 5, opsi valid).
3. `SubmissionService` dalam satu transaksi:
   - `responses`: UUID v4 acak, durasi, tanggal tanpa jam, status valid.
   - `answers`: jawaban per pertanyaan (insert massal, tanpa ID berurutan).
   - `response_features`: skor faktor (rata-rata item, 2 desimal), kategori, target.
   - `survey_submissions`: user_id + tanggal saja.
4. Jika `survey_submissions` melanggar unique (kirim dua kali), seluruh transaksi dibatalkan.

`Response::newUniqueId()` sengaja memakai UUID v4, karena bawaan Laravel 12 (UUID v7) berurutan menurut waktu dan dapat membuka celah pencocokan urutan.

### 5.2 Analisis ML

1. Hanya untuk survei berstatus `closed`.
2. `GET /model/info` lalu cek kecocokan daftar fitur.
3. `POST /predict/batch` (dipecah per 1.000) dan `POST /explain/global`.
4. Hasil disimpan atomik. Jika gagal, dicatat sebagai `analysis_runs.status = failed` tanpa hasil parsial.
5. `metrics` menyimpan `data_source` dan `data_sufficient`; dashboard menampilkan peringatan "eksperimental" bila model dari data dummy atau data belum memadai.

### 5.3 Dashboard

- Semua angka dari respons valid; filter angkatan dan lama tinggal.
- Segmen di bawah 5 responden disembunyikan (`dorminsight.min_segment_size`).
- Faktor dominan menampilkan SHAP beserta stabilitas bootstrap ("masuk 3 besar pada x% uji ulang"); faktor dengan stabilitas di bawah 50% diwarnai abu-abu dan diberi kalimat "belum stabil".

## 6. Konfigurasi

| Lokasi | Kunci | Fungsi |
|---|---|---|
| `.env` | `DB_*` | Koneksi MySQL |
| `.env` | `APP_TIMEZONE=Asia/Makassar` | Jadwal survei dalam WITA |
| `.env` | `DORM_POPULATION` | Jumlah penghuni (default 300) |
| `.env` | `ML_SERVICE_URL`, `ML_API_KEY`, `ML_TIMEOUT` | Koneksi ML service |
| `.env` | `DORM_REGISTRATION_ENABLED`, `DORM_REGISTRATION_REQUIRE_APPROVAL`, `DORM_REGISTRATION_EMAIL_DOMAIN` | Pendaftaran mandiri mahasiswa |
| `config/dorminsight.php` | `min_valid_responses`, `min_segment_size`, `min_duration_seconds`, `stable_top3_frequency` | Ambang aturan bisnis |

## 7. Pengujian Otomatis

| File | Cakupan |
|---|---|
| `tests/Feature/AuthTest.php` | Login email/NIM, salah password, akun nonaktif, kunci 5 kali gagal, wajib ganti password |
| `tests/Feature/RoleAccessTest.php` | Akses per role, akun dinonaktifkan saat login |
| `tests/Feature/SubmissionTest.php` | Alur consent, anonimitas skema, perhitungan fitur, tidak bisa kirim dua kali, validasi, survei tutup, respons mencurigakan |
| `tests/Feature/SurveyManagementTest.php` | Syarat buka survei, satu survei aktif, satu target, kunci pertanyaan, duplikasi, impor CSV |
| `tests/Feature/AnalysisTest.php` | Analisis sukses (ML dipalsukan), ML mati, fitur tidak cocok, survei belum tutup, dashboard + peringatan dummy |
| `tests/Feature/DashboardTest.php` | Dashboard tanpa analisis, segmen kecil disembunyikan, unduh PDF/Excel, ekspor CSV anonim |
| `tests/Feature/RegistrationTest.php` | Pendaftaran, menunggu persetujuan, setujui/tolak, NIM/email ganda, password lemah, domain email, mode tanpa persetujuan, pendaftaran dimatikan, peran selalu Mahasiswa |
| `tests/Feature/StudentHomeTest.php` | Sisa waktu, progres partisipasi dan target, riwayat hanya milik sendiri tanpa jawaban, beranda tanpa survei aktif, format teks sisa waktu |
| `tests/Unit/FeatureBuilderTest.php` | Rata-rata skor faktor, kolom terlarang, pembulatan sama dengan Python |

Jalankan: `php artisan test` (web-app) dan `pytest -q` (ml-service).

**Status verifikasi:** seluruh test Laravel lulus di laptop tim (lihat bagian 9). Aplikasi juga sudah dijalankan dan halaman login, dashboard, perbandingan, simulasi, survei, dan manajemen akun tampil sesuai rancangan.

## 8. Konvensi Pengembangan

| Item | Konvensi |
|---|---|
| Branch | `main` (rilis), `develop`, `feature/<nama>` |
| Commit | Bahasa Indonesia atau Inggris, singkat, satu perubahan per commit |
| Review | Pull request ke `develop`, minimal 1 reviewer |
| Status tugas | Backlog → To Do → In Progress → Code Review → Testing → Done |
| Logika bisnis | Di `app/Services`, bukan di controller atau view |
| Teks antarmuka | Bahasa Indonesia |

## 9. Catatan Hasil Test (diisi tim)

| Tanggal | Penguji | `php artisan test` | `pytest -q` | Catatan |
|---|---|---|---|---|
| 23 Nov 2026 | Tim (Windows, Laragon, PHP 8.3.37) | 37 passed (162 assertions), 9,24 detik | 14 passed (lingkungan pengembangan) | Instalasi via `setup.ps1`; ekstensi `zip` perlu diaktifkan manual di Laragon |
| 24 Nov 2026 | Tim (Windows, Laragon, PHP 8.3.37) | 48 passed (210 assertions), 16,09 detik | - | Setelah fitur pendaftaran mandiri, seeder idempoten, dan persiapan deployment (PostgreSQL/Render); test memakai SQLite in-memory |
