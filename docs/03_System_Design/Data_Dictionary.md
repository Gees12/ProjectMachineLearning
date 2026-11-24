# Data Dictionary

> Proyek: DormInsight
> Dokumen: Fase 3 - System & Data Design
> Versi: 0.1 (Draft)
> Penyusun: Geraldo (database), Gerald (variabel ML)
> Terakhir diperbarui: 23 November 2026

Dokumen ini terdiri dari dua bagian: **(A) kamus tabel database** dan **(B) kamus variabel survei dan ML**.

---

# A. Kamus Tabel Database

Konvensi: PK = primary key, FK = foreign key, UK = unique, NN = not null.

### A.1 `users`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment | |
| name | VARCHAR(100) | NN | Nama lengkap |
| nim | VARCHAR(20) | UK, NULL | NIM mahasiswa; NULL untuk pengelola dan admin |
| email | VARCHAR(150) | UK, NN | Dipakai untuk login |
| password | VARCHAR(255) | NN | Hash bcrypt |
| role | ENUM('mahasiswa','pengelola','admin') | NN | |
| is_active | BOOLEAN | NN, default 1 | Akun nonaktif tidak dapat login |
| must_change_password | BOOLEAN | NN, default 1 | Wajib ganti password saat login pertama |
| self_registered | BOOLEAN | NN, default 0 | Akun dibuat lewat pendaftaran mandiri |
| approved_at | TIMESTAMP | NULL | Waktu admin menyetujui pendaftaran mandiri; NULL = belum disetujui |
| last_login_at | DATETIME | NULL | |
| remember_token | VARCHAR(100) | NULL | Bawaan Laravel |
| created_at, updated_at | TIMESTAMP | NULL | |

### A.2 `surveys`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| title | VARCHAR(150) | NN | Contoh: "Survei Kepuasan Semester II 2026/2027" |
| description | TEXT | NULL | Teks pengantar dan consent |
| open_at | DATETIME | NN | |
| close_at | DATETIME | NN | Harus lebih besar dari `open_at` |
| status | ENUM('draft','active','closed') | NN, default 'draft' | Maksimal satu survei `active` |
| created_by | BIGINT UNSIGNED | FK users.id | |
| created_at, updated_at | TIMESTAMP | | |

### A.3 `factors`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| survey_id | BIGINT UNSIGNED | FK surveys.id, cascade delete | |
| code | VARCHAR(10) | NN, UK bersama survey_id | Contoh: `F_MAK` |
| name | VARCHAR(100) | NN | Contoh: "Makanan / Kafetaria" |
| description | VARCHAR(255) | NULL | |
| sort_order | SMALLINT | NN | |

### A.4 `questions`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| survey_id | BIGINT UNSIGNED | FK surveys.id | |
| factor_id | BIGINT UNSIGNED | FK factors.id, NULL | NULL untuk pertanyaan non-faktor |
| code | VARCHAR(20) | NN, UK bersama survey_id | Contoh: `F_MAK_1`, `INGIN_KELUAR` |
| text | TEXT | NN | |
| type | ENUM('likert','single_choice','yes_no','text') | NN | |
| ml_role | ENUM('factor_item','feature','target','descriptive','open') | NN | Lihat bagian B.4 |
| is_required | BOOLEAN | NN, default 1 | |
| sort_order | SMALLINT | NN | |

Aturan: tepat satu pertanyaan per survei dengan `ml_role = 'target'` dan `type = 'yes_no'`.

### A.5 `question_options`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| question_id | BIGINT UNSIGNED | FK questions.id | |
| label | VARCHAR(100) | NN | Teks yang tampil |
| value | VARCHAR(50) | NN | Nilai yang disimpan |
| sort_order | SMALLINT | NN | |

### A.6 `survey_submissions`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| survey_id | BIGINT UNSIGNED | FK surveys.id | |
| user_id | BIGINT UNSIGNED | FK users.id | |
| submitted_date | DATE | NN | Tanggal saja, tanpa jam |

Unique: `(survey_id, user_id)`. Tabel ini **tidak** menyimpan jawaban.

### A.7 `responses`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | CHAR(36) | PK | UUID v4 acak |
| survey_id | BIGINT UNSIGNED | FK surveys.id | |
| duration_seconds | INT UNSIGNED | NN | Lama pengisian, dihitung di server |
| submitted_date | DATE | NN | Tanggal saja |
| is_valid | BOOLEAN | NN, default 1 | |
| flag_reason | VARCHAR(100) | NULL | Contoh: `straight_lining`, `too_fast` |

Tidak ada `user_id`, `created_at`, atau `updated_at`.

### A.8 `answers`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| response_id | CHAR(36) | PK, FK responses.id | |
| question_id | BIGINT UNSIGNED | PK, FK questions.id | |
| value_numeric | DECIMAL(5,2) | NULL | Likert 1 sampai 5, Ya = 1, Tidak = 0 |
| value_text | TEXT | NULL | Pilihan tunggal (nilai opsi) atau teks bebas |

### A.9 `response_features`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| response_id | CHAR(36) | PK, FK responses.id | |
| survey_id | BIGINT UNSIGNED | FK surveys.id | Mempermudah query per survei |
| features | JSON | NN | Contoh pada bagian B.5 |
| target | TINYINT | NN | 1 = ingin keluar, 0 = tidak |
| kep_umum | TINYINT | NULL | Kepuasan keseluruhan 1 sampai 5 (deskriptif, bukan fitur) |

### A.10 `analysis_runs`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| survey_id | BIGINT UNSIGNED | FK surveys.id | |
| run_by | BIGINT UNSIGNED | FK users.id | Admin yang menjalankan |
| model_version | VARCHAR(30) | NN | Contoh: `rf-1.0.0` |
| algorithm | VARCHAR(50) | NN | Contoh: `RandomForestClassifier` |
| metrics | JSON | NN | Metrik evaluasi dari `/model/info` |
| n_responses | INT UNSIGNED | NN | Jumlah respons valid yang dianalisis |
| status | ENUM('success','failed') | NN | |
| error_message | TEXT | NULL | |
| run_at | DATETIME | NN | |

### A.11 `analysis_predictions`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| analysis_run_id | BIGINT UNSIGNED | PK, FK analysis_runs.id | |
| response_id | CHAR(36) | PK, FK responses.id | |
| probability | DECIMAL(5,4) | NN | 0.0000 sampai 1.0000 |
| risk_level | ENUM('rendah','sedang','tinggi') | NN | Ambang awal pada bagian B.6 |

### A.12 `analysis_shap`

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| analysis_run_id | BIGINT UNSIGNED | PK, FK analysis_runs.id | |
| feature_code | VARCHAR(30) | PK | Contoh: `F_MAK` |
| mean_abs_shap | DECIMAL(8,5) | NN | Rata-rata nilai SHAP absolut (besarnya pengaruh) |
| direction | ENUM('negatif','positif','campuran') | NN | Lihat bagian B.7 |
| rank | SMALLINT | NN | 1 = paling berpengaruh |
| top3_frequency | DECIMAL(4,3) | NULL | Frekuensi masuk 3 besar pada uji bootstrap (dari `/model/info`); ditampilkan sebagai tingkat keyakinan |

---

# B. Kamus Variabel Survei dan ML

### B.1 Skala Likert

| Nilai | Label |
|---|---|
| 1 | Sangat Tidak Puas / Sangat Tidak Setuju |
| 2 | Tidak Puas / Tidak Setuju |
| 3 | Netral |
| 4 | Puas / Setuju |
| 5 | Sangat Puas / Sangat Setuju |

Semua pernyataan ditulis dalam arah positif, sehingga nilai lebih tinggi selalu berarti lebih puas. Tidak ada item yang perlu dibalik (reverse-coded).

### B.2 Variabel Faktor (Fitur Input Utama)

Setiap faktor terdiri dari 2 item Likert. Skor faktor = rata-rata kedua item (rentang 1.0 sampai 5.0, kelipatan 0.5).

| Kode Fitur | Faktor | Item | Tipe Data | Rentang |
|---|---|---|---|---|
| F_FAS | Fasilitas kamar | F_FAS_1, F_FAS_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_KEB | Kebersihan | F_KEB_1, F_KEB_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_MAK | Makanan / kafetaria | F_MAK_1, F_MAK_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_BIA | Biaya | F_BIA_1, F_BIA_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_PER | Peraturan asrama | F_PER_1, F_PER_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_PRI | Privasi | F_PRI_1, F_PRI_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_KEA | Keamanan | F_KEA_1, F_KEA_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_SOS | Lingkungan sosial | F_SOS_1, F_SOS_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_PEL | Pelayanan pengelola | F_PEL_1, F_PEL_2 | Numerik kontinu | 1.0 sampai 5.0 |
| F_BEL | Kenyamanan belajar dan internet | F_BEL_1, F_BEL_2 | Numerik kontinu | 1.0 sampai 5.0 |

Reliabilitas setiap faktor diperiksa di Fase 4 (misalnya korelasi antaritem). Jika kedua item tidak konsisten, item dapat dipakai terpisah.

### B.3 Variabel Demografis

| Kode | Pertanyaan | Nilai | Encoding untuk ML | Peran |
|---|---|---|---|---|
| ANGKATAN | Tahun masuk kuliah | `2023_atau_sebelumnya`, `2024`, `2025`, `2026` | One-hot | Fitur dan segmen |
| LAMA_TINGGAL | Lama tinggal di asrama | `lt1` (kurang dari 1 tahun), `1_2` (1 sampai 2 tahun), `gt2` (lebih dari 2 tahun) | Ordinal 0, 1, 2 | Fitur dan segmen |

Pilihan angkatan dikelompokkan agar tidak ada kelompok yang terlalu kecil (perlindungan anonimitas).

### B.4 Peran Pertanyaan (`ml_role`)

| Nilai | Arti | Contoh |
|---|---|---|
| `factor_item` | Item Likert yang dirata-rata menjadi skor faktor | F_MAK_1 |
| `feature` | Langsung dipakai sebagai fitur | ANGKATAN, LAMA_TINGGAL |
| `target` | Label yang diprediksi | INGIN_KELUAR |
| `descriptive` | Hanya untuk statistik deskriptif, **tidak boleh** menjadi fitur | KEP_UMUM |
| `open` | Jawaban teks bebas | SARAN |

### B.5 Variabel Target dan Contoh JSON Fitur

| Kode | Pertanyaan | Nilai |
|---|---|---|
| INGIN_KELUAR | "Apakah Anda berencana atau ingin pindah keluar dari Crystal Dormitory pada semester berikutnya?" | 1 = Ya, 0 = Tidak |

Contoh isi `response_features.features`:

```json
{
  "F_FAS": 3.5, "F_KEB": 4.0, "F_MAK": 2.0, "F_BIA": 3.0, "F_PER": 2.5,
  "F_PRI": 3.0, "F_KEA": 4.5, "F_SOS": 4.0, "F_PEL": 3.5, "F_BEL": 2.5,
  "ANGKATAN": "2025",
  "LAMA_TINGGAL": "1_2"
}
```

### B.6 Kategori Risiko (Ambang Awal)

| Risk Level | Probabilitas | Catatan |
|---|---|---|
| Rendah | kurang dari 0.40 | |
| Sedang | 0.40 sampai kurang dari 0.70 | |
| Tinggi | 0.70 atau lebih | |

Ambang ini sementara dan akan ditinjau di Fase 4 berdasarkan distribusi probabilitas dan kalibrasi model. Kategori hanya ditampilkan secara agregat.

### B.7 Arah Pengaruh SHAP (`direction`)

| Nilai | Arti | Contoh Kalimat di Dashboard |
|---|---|---|
| negatif | Semakin tinggi kepuasan, semakin rendah kecenderungan keluar | "Kepuasan terhadap makanan yang rendah meningkatkan keinginan keluar." |
| positif | Semakin tinggi nilai fitur, semakin tinggi kecenderungan keluar | |
| campuran | Hubungan tidak konsisten | "Pengaruh faktor ini bervariasi antarmahasiswa." |

Arah ditentukan dari korelasi antara nilai fitur dan nilai SHAP-nya.

### B.8 Variabel yang Dikecualikan dari Fitur

| Variabel | Alasan |
|---|---|
| KEP_UMUM | Sangat dekat dengan target, berisiko data leakage (NFR-062) |
| SARAN | Teks bebas, di luar ruang lingkup NLP |
| duration_seconds | Metadata kualitas data, bukan karakteristik kepuasan |
| Identitas apa pun | Tidak disimpan di data respons (NFR-010) |
