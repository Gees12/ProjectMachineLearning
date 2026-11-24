# Sequence Diagram

> Proyek: DormInsight | Fase 3 | Versi 0.1 | Penyusun: Geraldo, Gerald

## SD-01 Login

```mermaid
sequenceDiagram
    actor U as Pengguna
    participant B as Browser
    participant L as Laravel (AuthController)
    participant DB as MySQL

    U->>B: Isi email dan password
    B->>L: POST /login (CSRF token)
    L->>L: Rate limiter: cek percobaan gagal
    alt Lebih dari 5 kali gagal
        L-->>B: 429 Login dikunci sementara
    else
        L->>DB: SELECT user WHERE email
        DB-->>L: Data user
        L->>L: Verifikasi hash password dan is_active
        alt Valid
            L->>L: Buat session, regenerasi session ID
            L-->>B: Redirect sesuai role
        else Tidak valid
            L-->>B: Pesan "Email atau password salah"
        end
    end
```

## SD-02 Mengirim Survei

```mermaid
sequenceDiagram
    actor M as Mahasiswa
    participant B as Browser
    participant C as SubmissionController
    participant S as SubmissionService
    participant FB as FeatureBuilder
    participant RV as ResponseValidator
    participant DB as MySQL

    M->>B: Tekan Kirim
    B->>C: POST /survey/submit (jawaban)
    C->>C: Form Request: validasi pertanyaan wajib dan rentang nilai
    C->>DB: Cek survei aktif dan belum ada submission (survey_id, user_id)
    DB-->>C: OK
    C->>S: submit(survey, user, answers, startedAt)
    S->>FB: build(answers)
    FB-->>S: features JSON, target, kep_umum
    S->>RV: check(answers, duration)
    RV-->>S: is_valid, flag_reason
    S->>DB: BEGIN TRANSACTION
    S->>DB: INSERT responses (UUID, durasi, tanggal, tanpa user_id)
    S->>DB: INSERT answers
    S->>DB: INSERT response_features
    S->>DB: INSERT survey_submissions (user_id, tanggal, tanpa jawaban)
    S->>DB: COMMIT
    S-->>C: sukses
    C-->>B: Redirect /survey/thanks
```

Jika INSERT `survey_submissions` gagal karena unique constraint (misalnya dikirim dua kali dari tab berbeda), seluruh transaksi dibatalkan sehingga tidak ada respons ganda.

## SD-03 Menjalankan Analisis ML

```mermaid
sequenceDiagram
    actor A as Admin
    participant AC as AnalysisController
    participant AS as AnalysisService
    participant MC as MlClient
    participant ML as ML Service (FastAPI)
    participant DB as MySQL

    A->>AC: POST /admin/surveys/{id}/analyze
    AC->>AS: run(survey, admin)
    AS->>DB: SELECT response_features WHERE survey_id AND is_valid = 1
    DB-->>AS: records
    AS->>MC: health() dan modelInfo()
    MC->>ML: GET /health
    ML-->>MC: status ok
    MC->>ML: GET /model/info (X-API-Key)
    ML-->>MC: model_version, features, metrics
    AS->>AS: Cocokkan daftar fitur model dengan fitur survei
    AS->>MC: predictBatch(records)
    MC->>ML: POST /predict/batch
    ML-->>MC: probability, risk_level per id
    AS->>MC: explainGlobal(records)
    MC->>ML: POST /explain/global
    ML-->>MC: SHAP per fitur
    AS->>DB: BEGIN
    AS->>DB: INSERT analysis_runs (success)
    AS->>DB: INSERT analysis_predictions
    AS->>DB: INSERT analysis_shap
    AS->>DB: COMMIT
    AS-->>AC: hasil
    AC-->>A: "Analisis selesai"

    Note over MC,ML: Jika timeout atau error: rollback, simpan analysis_runs status failed dengan error_message
```

## SD-04 Simulasi Skenario

```mermaid
sequenceDiagram
    actor P as Pengelola
    participant SC as SimulationController
    participant DB as MySQL
    participant MC as MlClient
    participant ML as ML Service

    P->>SC: GET /simulation
    SC->>DB: Rata-rata fitur survei terakhir
    DB-->>SC: baseline
    SC-->>P: Form slider dengan nilai baseline
    P->>SC: POST /simulation (nilai faktor baru)
    SC->>MC: simulate(baseline, scenario)
    MC->>ML: POST /simulate
    ML-->>MC: baseline_probability, scenario_probability, delta
    MC-->>SC: hasil
    SC-->>P: Tampilkan perbandingan dan catatan "perkiraan model"
```
