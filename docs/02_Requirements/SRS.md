# Software Requirements Specification (SRS)

> Proyek: DormInsight
> Dokumen: Fase 2 - Requirements Engineering
> Versi: 0.2 (Draft)
> Penyusun: Prayshe (Business/System Analyst)
> Terakhir diperbarui: 23 November 2026

## 1. Introduction

### 1.1 Purpose

Dokumen ini menspesifikasikan kebutuhan fungsional dan nonfungsional perangkat lunak DormInsight secara formal dan terukur. SRS menjadi acuan untuk desain sistem (Fase 3), implementasi (Fase 5), dan pengujian (Fase 6).

### 1.2 Scope

DormInsight terdiri dari aplikasi web Laravel (survei, manajemen, dashboard, laporan), database MySQL, dan ML service berbasis Python FastAPI (prediksi dan explainability). Sistem digunakan oleh penghuni, pengelola, dan administrator Crystal Dormitory, Universitas Klabat.

### 1.3 Definitions

| Istilah | Definisi |
|---|---|
| Periode survei | Rentang waktu survei dibuka untuk diisi |
| Faktor | Kelompok pertanyaan yang mengukur satu aspek kepuasan |
| Skor faktor | Rata-rata jawaban Likert dalam satu faktor |
| Label target | Jawaban mahasiswa atas pertanyaan keinginan keluar asrama (Ya/Tidak) |
| ML service | Layanan FastAPI yang menyediakan model dan analisis |
| SHAP | SHapley Additive exPlanations, metode menjelaskan kontribusi fitur terhadap prediksi |
| RBAC | Role-Based Access Control |

### 1.4 References

- [Project_Charter.md](../01_Project_Initiation/Project_Charter.md)
- [Project_Scope.md](../01_Project_Initiation/Project_Scope.md)
- [PRD.md](PRD.md)

## 2. Overall System Description

### 2.1 Product Perspective

```
[Browser Mahasiswa/Pengelola/Admin]
            |
            v
  [Laravel Web App] <---- REST JSON + API key ----> [FastAPI ML Service]
            |                                              |
            v                                              v
       [MySQL]                                 [File model .joblib]
```

### 2.2 User Classes

| Role | Hak Akses |
|---|---|
| Mahasiswa | Mengisi survei aktif, melihat status pengisiannya sendiri |
| Pengelola | Melihat dashboard, simulasi, dan mengunduh laporan; tidak dapat melihat jawaban per individu |
| Admin | Mengelola akun, survei, data respons, dan menjalankan analisis ML |

### 2.3 Operating Environment

- Browser: Chrome, Firefox, Edge, dan Safari versi terbaru (desktop dan ponsel).
- Server: PHP 8.2 atau lebih baru, Laravel 11 atau lebih baru, MySQL 8, Python 3.10 atau lebih baru.
- Hosting: layanan hosting gratis untuk demo.

### 2.4 Design Constraints

1. Tech stack ditetapkan: Laravel, Blade, Bootstrap, MySQL, FastAPI, scikit-learn, SHAP.
2. Data survei mentah tidak boleh disimpan di repository publik.
3. Hasil ML hanya digunakan dalam bentuk agregat.

## 3. Functional Requirements

Prioritas: M = Must, S = Should, C = Could.

### 3.1 Authentication (AUTH)

| Kode | Requirement | Prioritas |
|---|---|---|
| FR-001 | Sistem harus menyediakan login menggunakan email/NIM dan password. | M |
| FR-002 | Sistem harus menyediakan logout dan mengakhiri sesi pengguna. | M |
| FR-003 | Sistem harus mengarahkan pengguna ke halaman sesuai role setelah login. | M |
| FR-004 | Sistem harus menolak akses ke halaman yang tidak sesuai role dan menampilkan pesan "akses ditolak". | M |
| FR-005 | Sistem harus mewajibkan pengguna mengganti password awal pada login pertama. | S |

### 3.2 User Management (USR)

| Kode | Requirement | Prioritas |
|---|---|---|
| FR-010 | Admin harus dapat menambah akun pengguna dengan role tertentu. | M |
| FR-011 | Admin harus dapat mengimpor akun mahasiswa dari file CSV. | S |
| FR-012 | Admin harus dapat mengubah data akun dan me-reset password. | M |
| FR-013 | Admin harus dapat menonaktifkan akun; akun nonaktif tidak dapat login. | M |
| FR-014 | Sistem harus menolak pembuatan akun dengan email/NIM yang sudah terdaftar. | M |
| FR-015 | Sistem harus menyediakan halaman pendaftaran mandiri bagi mahasiswa dengan nama, NIM, email, dan password. | S |
| FR-016 | Akun hasil pendaftaran mandiri harus berperan Mahasiswa dan, secara bawaan, menunggu persetujuan admin sebelum dapat login. | S |
| FR-017 | Admin harus dapat melihat daftar pendaftaran yang menunggu persetujuan, lalu menyetujui atau menolaknya. | S |
| FR-018 | Admin harus dapat mengatur pendaftaran mandiri: aktif/nonaktif, wajib persetujuan atau tidak, dan pembatasan domain email. | C |

### 3.3 Survey Management (SVM)

| Kode | Requirement | Prioritas |
|---|---|---|
| FR-020 | Admin harus dapat membuat periode survei dengan nama, tanggal buka, dan tanggal tutup. | M |
| FR-021 | Admin harus dapat mengelola faktor (kode, nama, urutan). | M |
| FR-022 | Admin harus dapat mengelola pertanyaan (teks, jenis, faktor, wajib/opsional, urutan). | M |
| FR-023 | Sistem harus mendukung jenis pertanyaan Likert 1 sampai 5, pilihan tunggal, Ya/Tidak, dan teks. | M |
| FR-024 | Admin harus dapat menandai satu pertanyaan Ya/Tidak sebagai label target. | M |
| FR-025 | Sistem harus mencegah perubahan pertanyaan pada survei yang sudah memiliki respons. | M |
| FR-026 | Admin harus dapat membuka dan menutup survei; hanya satu survei yang aktif pada satu waktu. | M |
| FR-027 | Admin harus dapat menduplikasi survei sebelumnya sebagai dasar periode baru. | S |

### 3.4 Survey Submission (SUB)

| Kode | Requirement | Prioritas |
|---|---|---|
| FR-030 | Sistem harus menampilkan halaman persetujuan (informed consent) sebelum survei dimulai. | M |
| FR-031 | Sistem harus menampilkan survei aktif per bagian faktor dengan indikator progres. | M |
| FR-032 | Sistem harus memvalidasi bahwa semua pertanyaan wajib telah dijawab sebelum dikirim. | M |
| FR-033 | Sistem harus menampilkan halaman ringkasan untuk ditinjau sebelum pengiriman. | S |
| FR-034 | Sistem harus menyimpan jawaban yang telah divalidasi ke database. | M |
| FR-035 | Sistem harus mencegah mahasiswa mengirim survei lebih dari satu kali per periode. | M |
| FR-036 | Sistem harus menyimpan catatan "sudah mengisi" terpisah dari jawaban, sehingga jawaban tidak memiliki relasi ke akun mahasiswa. | M |
| FR-037 | Sistem harus menolak pengiriman setelah tanggal tutup survei. | M |

### 3.5 Data Management (DAT)

| Kode | Requirement | Prioritas |
|---|---|---|
| FR-040 | Admin harus dapat melihat jumlah respons masuk dibandingkan target dan total penghuni. | M |
| FR-041 | Admin harus dapat melihat daftar respons anonim beserta skor faktornya. | M |
| FR-042 | Sistem harus menandai respons tidak valid (misalnya semua jawaban sama atau waktu pengisian di bawah 2 menit) untuk ditinjau admin. | S |
| FR-043 | Admin harus dapat mengecualikan respons tidak valid dari analisis. | S |
| FR-044 | Admin harus dapat mengekspor respons anonim ke CSV. | M |

### 3.6 ML Integration (MLI)

| Kode | Requirement | Prioritas |
|---|---|---|
| FR-050 | Sistem harus menghitung skor faktor setiap respons sebelum dikirim ke ML service. | M |
| FR-051 | Admin harus dapat menjalankan analisis ML untuk survei yang sudah ditutup. | M |
| FR-052 | Sistem harus mengirim data respons valid ke ML service dan menerima probabilitas keinginan keluar untuk setiap respons. | M |
| FR-053 | Sistem harus menerima dan menyimpan nilai SHAP global (kontribusi rata-rata tiap faktor). | M |
| FR-054 | Sistem harus menyimpan versi model, tanggal analisis, dan metrik evaluasi model bersama hasil analisis. | M |
| FR-055 | Sistem harus menampilkan pesan kesalahan yang jelas dan tidak menyimpan hasil parsial jika ML service gagal atau tidak tersedia. | M |

### 3.7 Analytics Dashboard (DSB)

| Kode | Requirement | Prioritas |
|---|---|---|
| FR-060 | Dashboard harus menampilkan jumlah responden, tingkat respons, rata-rata kepuasan keseluruhan, dan proporsi "ingin keluar". | M |
| FR-061 | Dashboard harus menampilkan rata-rata skor setiap faktor dalam grafik. | M |
| FR-062 | Dashboard harus menampilkan peringkat faktor dominan berdasarkan SHAP beserta penjelasan sederhana. | M |
| FR-063 | Dashboard harus menampilkan distribusi probabilitas keinginan keluar (rendah, sedang, tinggi). | M |
| FR-064 | Pengelola harus dapat memfilter dashboard berdasarkan angkatan dan lama tinggal. | S |
| FR-065 | Sistem harus menyembunyikan statistik segmen yang memiliki kurang dari 5 responden. | M |
| FR-066 | Pengelola harus dapat mengubah nilai skor faktor pada form simulasi dan melihat perubahan perkiraan probabilitas keinginan keluar. | S |
| FR-067 | Pengelola harus dapat membandingkan hasil dua periode survei. | S |
| FR-068 | Dashboard harus menampilkan versi model dan metrik evaluasinya. | M |

### 3.8 Reporting (RPT)

| Kode | Requirement | Prioritas |
|---|---|---|
| FR-070 | Pengelola harus dapat mengunduh laporan ringkasan analisis dalam format PDF. | M |
| FR-071 | Pengelola harus dapat mengunduh data agregat analisis dalam format Excel. | M |
| FR-072 | Laporan harus mencantumkan periode survei, jumlah responden, tanggal analisis, dan versi model. | M |

## 4. Non-Functional Requirements

### 4.1 Security

| Kode | Requirement |
|---|---|
| NFR-001 | Sistem harus membatasi akses dashboard analisis hanya kepada role Pengelola dan Admin. |
| NFR-002 | Password harus disimpan dalam bentuk hash (bcrypt atau argon2), tidak pernah dalam teks biasa. |
| NFR-003 | Semua form harus dilindungi dari CSRF; input harus divalidasi di sisi server untuk mencegah SQL injection dan XSS. |
| NFR-004 | Komunikasi Laravel ke ML service harus menggunakan API key yang disimpan di environment variable. |
| NFR-005 | Sistem harus mengunci login sementara setelah 5 kali percobaan gagal berturut-turut. |
| NFR-006 | Aplikasi yang dipublikasikan harus diakses melalui HTTPS. |

### 4.2 Privacy

| Kode | Requirement |
|---|---|
| NFR-010 | Tabel jawaban survei tidak boleh menyimpan ID pengguna, nama, atau NIM. |
| NFR-011 | Tidak ada role yang dapat melihat jawaban survei yang dikaitkan dengan identitas mahasiswa. |
| NFR-012 | Demo publik harus menggunakan data dummy atau data yang telah dianonimkan. |

### 4.3 Performance

| Kode | Requirement |
|---|---|
| NFR-020 | Halaman survei dan dashboard harus termuat dalam waktu kurang dari 3 detik untuk data hingga 1.000 respons pada kondisi server normal. |
| NFR-021 | Analisis ML untuk 300 respons harus selesai dalam waktu kurang dari 30 detik (tidak termasuk waktu bangun server hosting gratis). |
| NFR-022 | Permintaan simulasi skenario harus mengembalikan hasil dalam waktu kurang dari 3 detik. |

### 4.4 Usability

| Kode | Requirement |
|---|---|
| NFR-030 | Survei harus dapat diselesaikan dalam waktu kurang dari 10 menit. |
| NFR-031 | Tampilan harus responsif dan dapat digunakan pada layar ponsel dengan lebar minimal 360 px. |
| NFR-032 | Seluruh antarmuka menggunakan Bahasa Indonesia. |
| NFR-033 | Istilah teknis ML di dashboard harus disertai penjelasan singkat. |

### 4.5 Reliability and Availability

| Kode | Requirement |
|---|---|
| NFR-040 | Jawaban survei yang sudah dikirim tidak boleh hilang; penyimpanan dilakukan dalam satu transaksi database. |
| NFR-041 | Kegagalan ML service tidak boleh mengganggu fitur survei dan dashboard yang sudah ada. |
| NFR-042 | Database harus dapat di-backup dan dipulihkan menggunakan file dump SQL. |

### 4.6 Maintainability

| Kode | Requirement |
|---|---|
| NFR-050 | Kode dikelola di Git dengan alur branch fitur dan pull request. |
| NFR-051 | Model ML disimpan dengan versi dan dapat diganti tanpa mengubah kode Laravel. |
| NFR-052 | Konfigurasi (database, URL dan API key ML service) disimpan di file environment. |

### 4.7 ML Quality

| Kode | Requirement |
|---|---|
| NFR-060 | Model harus dievaluasi dengan stratified k-fold cross-validation (k = 5). |
| NFR-061 | Model terpilih harus memiliki F1-Score kelas "ingin keluar" lebih tinggi daripada baseline kelas mayoritas. |
| NFR-062 | Pertanyaan kepuasan keseluruhan dan pertanyaan target tidak boleh dipakai sebagai fitur input (mencegah data leakage). |
| NFR-063 | Setiap eksperimen dicatat di ML Experiment Log. |

## 5. External Interface Requirements

### 5.1 User Interface

| Halaman | Role |
|---|---|
| Login, ganti password | Semua |
| Consent, pengisian survei, konfirmasi | Mahasiswa |
| Dashboard, simulasi, perbandingan, laporan | Pengelola, Admin |
| Manajemen akun, survei, pertanyaan, respons, analisis | Admin |

Wireframe detail dibuat di Fase 3 (Figma).

### 5.2 Software Interface: Laravel ke ML Service

Format JSON melalui HTTP, autentikasi header `X-API-Key`. Endpoint awal (detail di API_Documentation.md, Fase 3):

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/health` | Mengecek ML service aktif |
| GET | `/model/info` | Versi model, daftar fitur, metrik evaluasi |
| POST | `/predict/batch` | Probabilitas keinginan keluar untuk banyak respons |
| POST | `/explain/global` | Nilai SHAP global per faktor |
| POST | `/simulate` | Probabilitas untuk satu skenario skor faktor |

### 5.3 Hardware Interface

Tidak ada kebutuhan perangkat keras khusus.

## 6. System Constraints

1. Hosting gratis memiliki keterbatasan sumber daya dan dapat mengalami jeda saat server "bangun".
2. Jumlah data dibatasi populasi 300 penghuni.
3. Model dilatih secara offline (notebook/script) lalu diunggah sebagai file ke ML service.

## 7. Data Requirements

### 7.1 Entitas Utama

| Entitas | Atribut Kunci | Catatan |
|---|---|---|
| users | id, name, email/nim, password_hash, role, is_active | |
| surveys | id, title, open_at, close_at, status | |
| factors | id, survey_id, code, name, order | |
| questions | id, survey_id, factor_id, text, type, is_required, is_target, order | |
| question_options | id, question_id, label, value | Untuk pilihan tunggal |
| survey_submissions | id, survey_id, user_id, submitted_at | Hanya mencatat sudah mengisi, tanpa jawaban |
| responses | id, survey_id, started_at, submitted_at, is_valid | Tanpa user_id |
| answers | id, response_id, question_id, value_numeric, value_text | |
| response_features | response_id, skor per faktor, angkatan, lama_tinggal, label target | Hasil perhitungan untuk ML |
| analysis_runs | id, survey_id, model_version, metrics_json, run_at, status | |
| analysis_predictions | analysis_run_id, response_id, probability | |
| analysis_shap | analysis_run_id, factor_code, mean_abs_shap, rank | |

ERD lengkap dan Data Dictionary dibuat di Fase 3.

### 7.2 Aturan Data

1. Nilai Likert harus bilangan bulat 1 sampai 5.
2. Label target bernilai 1 (Ya) atau 0 (Tidak).
3. Respons yang belum lengkap tidak disimpan.
4. Data mentah survei disimpan maksimal selama masa proyek, kemudian diarsipkan atau dihapus sesuai kesepakatan dengan pengelola asrama.

## 8. Traceability Matrix (Ringkas)

| Fitur PRD | Functional Requirement |
|---|---|
| F-01 Autentikasi | FR-001 sampai FR-005 |
| F-02 Manajemen akun | FR-010 sampai FR-018 |
| F-03 Manajemen survei | FR-020 sampai FR-027 |
| F-04 Pengisian survei | FR-030 sampai FR-037 |
| F-05 Manajemen data | FR-040 sampai FR-044 |
| F-06 Analisis ML | FR-050 sampai FR-055 |
| F-07 Dashboard | FR-060 sampai FR-065, FR-068 |
| F-08 Laporan | FR-070 sampai FR-072 |
| F-09 Simulasi | FR-066 |
| F-10 Perbandingan periode | FR-067 |
