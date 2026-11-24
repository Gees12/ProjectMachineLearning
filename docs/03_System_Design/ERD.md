# Entity Relationship Diagram (ERD)

> Proyek: DormInsight
> Dokumen: Fase 3 - System & Data Design
> Versi: 0.1 (Draft)
> Penyusun: Geraldo (Backend Developer)
> Terakhir diperbarui: 23 November 2026

Diagram ditulis dalam Mermaid. Untuk melihatnya di VS Code, pasang ekstensi **Markdown Preview Mermaid Support**, lalu buka preview (`Ctrl+Shift+V`). GitHub juga merender Mermaid secara otomatis.

## 1. Diagram

```mermaid
erDiagram
    USERS ||--o{ SURVEYS : "membuat"
    USERS ||--o{ SURVEY_SUBMISSIONS : "mengirim"
    USERS ||--o{ ANALYSIS_RUNS : "menjalankan"

    SURVEYS ||--|{ FACTORS : "memiliki"
    SURVEYS ||--|{ QUESTIONS : "memiliki"
    SURVEYS ||--o{ SURVEY_SUBMISSIONS : "dicatat"
    SURVEYS ||--o{ RESPONSES : "menerima"
    SURVEYS ||--o{ ANALYSIS_RUNS : "dianalisis"

    FACTORS ||--o{ QUESTIONS : "mengelompokkan"
    QUESTIONS ||--o{ QUESTION_OPTIONS : "memiliki"
    QUESTIONS ||--o{ ANSWERS : "dijawab"

    RESPONSES ||--|{ ANSWERS : "berisi"
    RESPONSES ||--|| RESPONSE_FEATURES : "menghasilkan"
    RESPONSES ||--o{ ANALYSIS_PREDICTIONS : "diprediksi"

    ANALYSIS_RUNS ||--o{ ANALYSIS_PREDICTIONS : "menghasilkan"
    ANALYSIS_RUNS ||--o{ ANALYSIS_SHAP : "menghasilkan"

    USERS {
        bigint id PK
        varchar name
        varchar nim UK
        varchar email UK
        varchar password
        enum role
        boolean is_active
        boolean must_change_password
        boolean self_registered
        timestamp approved_at
    }
    SURVEYS {
        bigint id PK
        varchar title
        datetime open_at
        datetime close_at
        enum status
        bigint created_by FK
    }
    FACTORS {
        bigint id PK
        bigint survey_id FK
        varchar code
        varchar name
        int sort_order
    }
    QUESTIONS {
        bigint id PK
        bigint survey_id FK
        bigint factor_id FK
        varchar code
        text text
        enum type
        enum ml_role
        boolean is_required
        int sort_order
    }
    QUESTION_OPTIONS {
        bigint id PK
        bigint question_id FK
        varchar label
        varchar value
        int sort_order
    }
    SURVEY_SUBMISSIONS {
        bigint id PK
        bigint survey_id FK
        bigint user_id FK
        date submitted_date
    }
    RESPONSES {
        char36 id PK "UUID acak"
        bigint survey_id FK
        int duration_seconds
        date submitted_date
        boolean is_valid
        varchar flag_reason
    }
    ANSWERS {
        char36 response_id PK,FK
        bigint question_id PK,FK
        decimal value_numeric
        text value_text
    }
    RESPONSE_FEATURES {
        char36 response_id PK,FK
        bigint survey_id FK
        json features
        tinyint target
        tinyint kep_umum
    }
    ANALYSIS_RUNS {
        bigint id PK
        bigint survey_id FK
        bigint run_by FK
        varchar model_version
        varchar algorithm
        json metrics
        int n_responses
        enum status
        datetime run_at
    }
    ANALYSIS_PREDICTIONS {
        bigint analysis_run_id PK,FK
        char36 response_id PK,FK
        decimal probability
        enum risk_level
    }
    ANALYSIS_SHAP {
        bigint analysis_run_id PK,FK
        varchar feature_code PK
        decimal mean_abs_shap
        enum direction
        int rank
        decimal top3_frequency
    }
```

## 2. Penjelasan Relasi

| Relasi | Kardinalitas | Keterangan |
|---|---|---|
| users - surveys | 1 : N | Admin pembuat survei |
| users - survey_submissions | 1 : N | Satu mahasiswa satu submission per survei (unique `survey_id, user_id`) |
| surveys - factors | 1 : N | Faktor milik satu survei |
| surveys - questions | 1 : N | Pertanyaan milik satu survei |
| factors - questions | 1 : N (opsional) | Pertanyaan non-faktor (angkatan, target, saran) memiliki `factor_id` NULL |
| questions - question_options | 1 : N | Opsi untuk jenis `single_choice` |
| surveys - responses | 1 : N | Respons anonim |
| responses - answers | 1 : N | Satu baris per pertanyaan yang dijawab |
| responses - response_features | 1 : 1 | Fitur siap ML hasil `FeatureBuilder` |
| analysis_runs - analysis_predictions | 1 : N | Probabilitas per respons untuk satu kali analisis |
| analysis_runs - analysis_shap | 1 : N | Satu baris per fitur |

## 3. Desain Anonimitas

**Tidak ada relasi antara `users` dan `responses`.** Satu-satunya hubungan mahasiswa dengan survei adalah tabel `survey_submissions`, yang hanya mencatat bahwa mahasiswa sudah mengisi.

| Teknik | Tujuan |
|---|---|
| `responses.id` berupa UUID acak | Urutan respons tidak bisa dicocokkan dengan urutan `survey_submissions.id` |
| Hanya tanggal (`DATE`), tanpa jam, di kedua tabel | Waktu kirim tidak bisa dicocokkan |
| `answers` memakai primary key gabungan, tanpa ID auto-increment | Urutan penyimpanan jawaban tidak terekam |
| Model `Response` menonaktifkan `timestamps` Laravel | Mencegah kolom `created_at` yang presisi |

## 4. Perubahan dari SRS

| SRS | ERD | Alasan |
|---|---|---|
| `responses.started_at`, `submitted_at` | `duration_seconds`, `submitted_date` | Mencegah pencocokan waktu (anonimitas); durasi tetap tersedia untuk deteksi respons asal-asalan |
| `survey_submissions.submitted_at` | `submitted_date` | Alasan yang sama |
| `answers.id` | Primary key gabungan `(response_id, question_id)` | Alasan yang sama |
| `response_features` dengan kolom per faktor | Kolom `features` JSON | Faktor dapat berubah antarperiode (ADR-05) |
| `questions.is_target` | `questions.ml_role` | Satu kolom menjelaskan peran pertanyaan dalam ML: item faktor, fitur, target, deskriptif, atau terbuka |

## 5. Tabel Bawaan Laravel

Tabel `sessions`, `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, dan `failed_jobs` dibuat oleh migrasi bawaan Laravel dan tidak digambarkan di diagram.
