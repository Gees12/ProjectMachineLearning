# Database Implementation Documentation

> Proyek: DormInsight
> Dokumen: Fase 5 - Application Development
> Versi: 0.1 (Draft)
> Penyusun: Geraldo (Backend Developer)
> Terakhir diperbarui: 23 November 2026

Desain logis ada di [ERD.md](../03_System_Design/ERD.md) dan [Data_Dictionary.md](../03_System_Design/Data_Dictionary.md). Dokumen ini menjelaskan implementasinya di Laravel.

## 1. File Migrasi

| File | Tabel |
|---|---|
| `0001_01_01_000000_create_users_table.php` | `users` (diubah: nim, role, is_active, must_change_password, last_login_at; tanpa email_verified_at), `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table.php` | Bawaan Laravel |
| `0001_01_01_000002_create_jobs_table.php` | Bawaan Laravel |
| `2026_11_23_000100_create_survey_tables.php` | `surveys`, `factors`, `questions`, `question_options`, `survey_submissions`, `responses`, `answers`, `response_features` |
| `2026_11_23_000200_create_analysis_tables.php` | `analysis_runs`, `analysis_predictions`, `analysis_shap` |
| `2026_11_23_000300_add_registration_fields_to_users_table.php` | Menambah `self_registered` dan `approved_at` pada `users` |

Perintah: `php artisan migrate` (struktur saja) atau `php artisan migrate:fresh --seed` (hapus semua lalu isi ulang, **hanya untuk pengembangan**).

## 2. Constraint Penting

| Tabel | Constraint | Tujuan |
|---|---|---|
| `users` | unique `email`, unique `nim` | Tidak ada akun ganda |
| `factors` | unique `(survey_id, code)` | Kode faktor unik per survei |
| `questions` | unique `(survey_id, code)` | Kode pertanyaan unik per survei (dipakai di ekspor CSV) |
| `survey_submissions` | unique `(survey_id, user_id)` | Satu mahasiswa satu kali per survei, dijamin di level database |
| `responses` | PK UUID, tanpa `user_id`, tanpa timestamp | Anonimitas |
| `answers` | PK `(response_id, question_id)` | Satu jawaban per pertanyaan, tanpa ID berurutan |
| `response_features` | PK `response_id` | Relasi 1:1 dengan respons |
| `analysis_predictions` | PK `(analysis_run_id, response_id)` | |
| `analysis_shap` | PK `(analysis_run_id, feature_code)` | |
| Semua FK ke `surveys` dan `responses` | `cascadeOnDelete` | Menghapus survei ikut menghapus data turunannya |
| `surveys.created_by`, `analysis_runs.run_by` | `nullOnDelete` | Riwayat tetap ada bila akun dihapus |

## 3. Seeder

| Seeder | Isi | Kapan |
|---|---|---|
| `DatabaseSeeder` | Admin, pengelola, 3 mahasiswa demo, lalu `SurveyTemplateSeeder` | Instalasi awal |
| `SurveyTemplateSeeder` | Survei template (draft): 10 faktor × 2 item, ANGKATAN, LAMA_TINGGAL, KEP_UMUM, INGIN_KELUAR, SARAN | Instalasi awal |
| `DemoResponsesSeeder` | Survei "[DEMO]" berstatus ditutup + 150 respons dummy (pola sama dengan data dummy ML) | Demo dan pengujian saja |

Nilai opsi ANGKATAN (`2023_atau_sebelumnya`, `2024`, `2025`, `2026`) dan LAMA_TINGGAL (`lt1`, `1_2`, `gt2`) **harus sama** dengan `ml-service/src/config.py`. Jika diubah di aplikasi, model harus dilatih ulang.

## 4. Backup dan Pemulihan (NFR-042)

Backup (XAMPP, dari folder `mysql\bin`):

```powershell
mysqldump -u root -p dorminsight > ..\..\DormInsight\database\backup_dorminsight_YYYYMMDD.sql
```

Pemulihan:

```powershell
mysql -u root -p dorminsight < backup_dorminsight_YYYYMMDD.sql
```

File backup berisi data survei asli: simpan di luar repository dan jangan di-commit ke GitHub.

## 5. Query Verifikasi Anonimitas

Jalankan setelah uji coba pengisian survei (checklist Data Privacy):

```sql
-- Harus kosong: tidak ada kolom yang menautkan jawaban ke akun
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'dorminsight' AND TABLE_NAME IN ('responses', 'answers', 'response_features')
  AND COLUMN_NAME IN ('user_id', 'nim', 'email', 'created_at', 'updated_at');

-- Jumlah submission harus sama dengan jumlah respons per survei (kecuali survei [DEMO] yang dibuat seeder)
SELECT s.id, (SELECT COUNT(*) FROM survey_submissions WHERE survey_id = s.id) AS submissions,
       (SELECT COUNT(*) FROM responses WHERE survey_id = s.id) AS responses
FROM surveys s;
```
