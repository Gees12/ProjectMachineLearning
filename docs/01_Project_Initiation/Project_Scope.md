# Project Scope

> Proyek: DormInsight
> Dokumen: Fase 1 - Project Initiation
> Versi: 0.2 (Draft)
> Terakhir diperbarui: 23 November 2026

## 1. Tujuan Dokumen

Dokumen ini menetapkan batas pekerjaan proyek DormInsight: fitur dan pekerjaan yang termasuk, yang tidak termasuk, serta asumsi dan batasan yang berlaku. Perubahan ruang lingkup setelah dokumen ini disetujui harus melalui Change Request.

## 2. Pengguna Sistem

| Aktor | Deskripsi |
|---|---|
| Mahasiswa | Penghuni Crystal Dormitory yang mengisi survei |
| Pengelola Asrama | Melihat hasil analisis, dashboard, dan mengunduh laporan |
| Administrator | Mengelola akun pengguna, pertanyaan survei, dan periode survei |

## 3. In Scope

### 3.1 Fitur Aplikasi

| Modul | Fitur | Aktor |
|---|---|---|
| Authentication | Login, logout, pembatasan akses berdasarkan role | Semua |
| User Management | Tambah, ubah, nonaktifkan akun | Administrator |
| Survey Management | Membuat, mengubah, mengaktifkan, dan menutup survei serta pertanyaannya | Administrator |
| Survey Submission | Mengisi dan mengirim survei satu kali per periode | Mahasiswa |
| Data Management | Melihat, memvalidasi, dan mengekspor data respons | Administrator |
| ML Integration | Mengirim data survei ke ML service dan menyimpan hasil analisis | Sistem |
| Analytics Dashboard | Statistik kepuasan per faktor, proporsi keinginan keluar, faktor dominan (SHAP) | Pengelola |
| Reporting | Ekspor laporan ke PDF dan Excel | Pengelola |

### 3.2 Pekerjaan Machine Learning

1. Perancangan instrumen survei dan variabel target.
2. Pengumpulan data survei dari penghuni Crystal Dormitory.
3. Preprocessing, EDA, dan feature engineering.
4. Training dan perbandingan Logistic Regression, Decision Tree, dan Random Forest.
5. Evaluasi dengan Accuracy, Precision, Recall, F1-Score, dan cross-validation.
6. Explainability dengan feature importance dan SHAP.
7. Penyajian model melalui FastAPI.

### 3.3 Dokumentasi

Seluruh dokumen fase 1 sampai 8 sesuai struktur folder `docs/`.

## 4. Out of Scope

| Item | Alasan |
|---|---|
| Aplikasi mobile native (Android/iOS) | Aplikasi web responsif sudah dapat diakses lewat ponsel |
| Integrasi dengan sistem akademik atau keuangan kampus | Membutuhkan izin dan akses di luar kendali tim |
| Survei untuk asrama selain Crystal Dormitory | Fokus studi kasus satu asrama |
| Pengambilan keputusan otomatis terhadap mahasiswa tertentu | Hasil model hanya untuk analisis agregat dan pendukung keputusan, bukan untuk menilai atau memberi sanksi individu |
| Notifikasi real-time (email, WhatsApp, push) | Bukan kebutuhan inti |
| Analisis teks lanjutan (NLP) pada jawaban terbuka | Dapat menjadi pengembangan lanjutan |
| Retraining model otomatis | Retraining dilakukan manual dan didokumentasikan |

## 5. Asumsi

1. Survei menggunakan skala Likert (misalnya 1 sampai 5) untuk faktor kepuasan.
2. Setiap mahasiswa mengisi survei satu kali per periode.
3. Identitas mahasiswa tidak ditampilkan di dashboard; analisis bersifat agregat.
4. Deployment menggunakan hosting gratis untuk keperluan demo.

## 6. Batasan

1. Tech stack: Laravel, MySQL, Python FastAPI, scikit-learn, SHAP.
2. Jumlah data dibatasi oleh jumlah penghuni Crystal Dormitory, yaitu 300 orang.
3. Pengerjaan mengikuti 6 sprint pada Project Timeline.

## 7. Kriteria Penerimaan Proyek

1. Semua fitur In Scope berjalan dan lulus test case.
2. Model ML terdokumentasi lengkap dan hasil evaluasinya dilaporkan apa adanya, termasuk jika performanya terbatas.
3. UAT dengan pengelola asrama dinyatakan lulus.
4. Dokumentasi prioritas 1 dan 2 lengkap.
