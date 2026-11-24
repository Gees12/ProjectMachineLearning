# Product Requirements Document (PRD)

> Proyek: DormInsight
> Dokumen: Fase 2 - Requirements Engineering
> Versi: 0.1 (Draft)
> Penyusun: Prayshe (Business/System Analyst)
> Terakhir diperbarui: 23 November 2026

## 1. Product Overview

DormInsight adalah aplikasi web untuk mengumpulkan survei kepuasan penghuni Crystal Dormitory, Universitas Klabat, lalu menganalisisnya dengan Machine Learning untuk menemukan faktor yang paling memengaruhi keinginan mahasiswa meninggalkan asrama. Hasilnya disajikan dalam dashboard dan laporan agar pengelola dapat menentukan prioritas perbaikan berdasarkan data.

## 2. Problem Statement

Pengelola Crystal Dormitory belum memiliki sistem analisis terstruktur untuk mengetahui tingkat kepuasan mahasiswa dan faktor yang membuat mahasiswa ingin meninggalkan asrama. Masukan mahasiswa dikumpulkan secara informal sehingga sulit diukur dan dijadikan dasar keputusan. Detail di [Problem_Statement.md](../01_Project_Initiation/Problem_Statement.md).

## 3. Product Objectives

| Kode | Tujuan Produk | Kaitan Masalah |
|---|---|---|
| PO-01 | Menyediakan survei digital yang singkat, anonim, dan mudah diisi lewat ponsel | P-01 |
| PO-02 | Menampilkan tingkat kepuasan per faktor secara agregat | P-03 |
| PO-03 | Mengidentifikasi faktor dominan keinginan keluar asrama dengan model ML yang dapat dijelaskan (SHAP) | P-02 |
| PO-04 | Memperkirakan proporsi dan kecenderungan keinginan keluar pada kelompok mahasiswa | P-04 |
| PO-05 | Menghasilkan laporan yang dapat diunduh untuk rapat dan pengambilan keputusan | P-03 |

## 4. Target Users / Personas

### Persona 1: Mahasiswa Penghuni

| Item | Keterangan |
|---|---|
| Profil | Mahasiswa Universitas Klabat yang tinggal di Crystal Dormitory (sekitar 300 orang) |
| Tujuan | Menyampaikan pengalaman tinggal di asrama dengan cepat dan aman |
| Kekhawatiran | Jawaban dapat dikaitkan dengan identitasnya |
| Perangkat | Mayoritas ponsel |
| Kebutuhan utama | Survei singkat (di bawah 10 menit), jaminan anonimitas, tampilan ramah ponsel |

### Persona 2: Pengelola Asrama

| Item | Keterangan |
|---|---|
| Profil | Dean/pengelola Crystal Dormitory dan stafnya |
| Tujuan | Mengetahui masalah utama asrama dan prioritas perbaikan |
| Kekhawatiran | Hasil analisis terlalu teknis dan sulit dipahami |
| Perangkat | Laptop/PC |
| Kebutuhan utama | Dashboard ringkas, penjelasan faktor dalam bahasa sederhana, laporan PDF/Excel |

### Persona 3: Administrator Sistem

| Item | Keterangan |
|---|---|
| Profil | Anggota tim atau staf yang ditunjuk untuk mengelola aplikasi |
| Tujuan | Menyiapkan survei, akun, dan menjaga kualitas data |
| Kebutuhan utama | Manajemen survei dan akun yang sederhana, ekspor data untuk tim ML |

## 5. Core Features

Prioritas memakai metode MoSCoW: **M** = Must have, **S** = Should have, **C** = Could have.

| Kode | Fitur | Deskripsi | Pengguna | Prioritas |
|---|---|---|---|---|
| F-01 | Autentikasi dan role | Login/logout dengan tiga role: Mahasiswa, Pengelola, Admin | Semua | M |
| F-02 | Manajemen akun | Admin menambah, mengimpor (CSV), mengubah, dan menonaktifkan akun | Admin | M |
| F-03 | Manajemen survei | Admin membuat periode survei, mengatur faktor dan pertanyaan, membuka dan menutup survei | Admin | M |
| F-04 | Pengisian survei | Mahasiswa mengisi survei anonim satu kali per periode | Mahasiswa | M |
| F-05 | Manajemen data respons | Admin melihat jumlah respons, memvalidasi, dan mengekspor data anonim (CSV) | Admin | M |
| F-06 | Analisis ML | Sistem mengirim data periode ke ML service dan menyimpan hasil (prediksi, metrik, SHAP) | Sistem | M |
| F-07 | Dashboard analitik | Statistik kepuasan per faktor, proporsi keinginan keluar, faktor dominan, segmentasi | Pengelola | M |
| F-08 | Laporan | Ekspor ringkasan analisis ke PDF dan Excel | Pengelola | M |
| F-09 | Simulasi skenario | Pengelola mengubah nilai faktor (misalnya kepuasan makanan naik) dan melihat perubahan perkiraan kecenderungan keluar | Pengelola | S |
| F-10 | Perbandingan antarperiode | Membandingkan hasil survei antarperiode | Pengelola | S |
| F-11 | Ringkasan jawaban terbuka | Menampilkan daftar saran terbuka mahasiswa tanpa identitas | Pengelola | C |

## 6. User Journey

### 6.1 Mahasiswa

1. Menerima informasi survei dari pengelola asrama (pengumuman atau grup).
2. Login ke DormInsight menggunakan akun yang diberikan.
3. Membaca penjelasan tujuan survei dan pernyataan anonimitas, lalu menyetujui (informed consent).
4. Mengisi survei per bagian faktor dengan skala 1 sampai 5.
5. Menjawab pertanyaan target ("ingin keluar asrama semester depan?") dan saran terbuka.
6. Meninjau dan mengirim jawaban.
7. Melihat konfirmasi bahwa survei terkirim; survei tidak dapat diisi ulang pada periode yang sama.

### 6.2 Pengelola Asrama

1. Login dan membuka dashboard.
2. Melihat ringkasan: jumlah responden, rata-rata kepuasan, proporsi ingin keluar.
3. Melihat grafik faktor dominan beserta penjelasan sederhana.
4. Memfilter berdasarkan segmen (misalnya tahun angkatan atau lama tinggal).
5. (Opsional) Menjalankan simulasi skenario perbaikan.
6. Mengunduh laporan PDF/Excel untuk rapat.

### 6.3 Administrator

1. Login dan membuat periode survei baru.
2. Mengatur faktor dan pertanyaan, lalu membuka survei.
3. Memantau jumlah respons terhadap target (minimal 100).
4. Menutup survei, menjalankan analisis ML, dan memverifikasi hasilnya.

## 7. Product Requirements

### 7.1 Instrumen Survei (Usulan Awal)

Survei terdiri dari 10 faktor kepuasan. Setiap faktor diukur dengan 2 pernyataan skala Likert 1 sampai 5 (1 = Sangat Tidak Puas, 5 = Sangat Puas). Skor faktor adalah rata-rata kedua pernyataan dan menjadi fitur input model.

| Kode | Faktor | Contoh Pernyataan |
|---|---|---|
| F_FAS | Fasilitas kamar | "Kondisi tempat tidur, lemari, dan meja di kamar saya memadai." |
| F_KEB | Kebersihan | "Kamar mandi dan area umum asrama terjaga kebersihannya." |
| F_MAK | Makanan / kafetaria | "Kualitas dan variasi makanan memenuhi kebutuhan saya." |
| F_BIA | Biaya | "Biaya asrama sebanding dengan fasilitas dan layanan yang diterima." |
| F_PER | Peraturan asrama | "Peraturan asrama jelas dan diterapkan secara adil." |
| F_PRI | Privasi | "Saya memiliki cukup privasi di asrama." |
| F_KEA | Keamanan | "Saya merasa aman dengan barang dan diri saya di asrama." |
| F_SOS | Lingkungan sosial | "Hubungan saya dengan penghuni lain berjalan baik." |
| F_PEL | Pelayanan pengelola | "Pengelola asrama menanggapi keluhan dengan baik." |
| F_BEL | Kenyamanan belajar dan internet | "Suasana dan koneksi internet di asrama mendukung kegiatan belajar." |

Pertanyaan tambahan:

| Kode | Pertanyaan | Jenis | Peran |
|---|---|---|---|
| KEP_UMUM | Kepuasan keseluruhan terhadap asrama | Likert 1 sampai 5 | Analisis deskriptif (tidak dipakai sebagai fitur untuk menghindari kebocoran informasi) |
| ANGKATAN | Tahun masuk kuliah | Pilihan | Segmentasi dan fitur |
| LAMA_TINGGAL | Lama tinggal di asrama | Pilihan (kurang dari 1 tahun, 1 sampai 2 tahun, lebih dari 2 tahun) | Segmentasi dan fitur |
| INGIN_KELUAR | "Apakah Anda berencana atau ingin pindah keluar dari Crystal Dormitory pada semester berikutnya?" | Ya/Tidak | **Label target** |
| SARAN | Saran untuk perbaikan asrama | Teks bebas, opsional | Analisis kualitatif |

Total sekitar 25 pertanyaan, estimasi pengisian 5 sampai 8 menit. Pertanyaan final divalidasi bersama pengelola asrama dan diuji coba pada 5 sampai 10 mahasiswa sebelum disebar.

### 7.2 Kebutuhan Produk Utama

| Kode | Kebutuhan | Fitur |
|---|---|---|
| PR-01 | Mahasiswa hanya dapat mengisi survei satu kali per periode | F-04 |
| PR-02 | Jawaban survei tidak boleh dapat dikaitkan dengan identitas mahasiswa oleh pengelola | F-04, F-07 |
| PR-03 | Survei dapat diisi dengan nyaman di layar ponsel | F-04 |
| PR-04 | Dashboard hanya menampilkan data agregat; segmen dengan kurang dari 5 responden disembunyikan | F-07 |
| PR-05 | Faktor dominan dijelaskan dengan bahasa sederhana, bukan hanya angka teknis | F-07 |
| PR-06 | Hasil analisis mencantumkan versi model dan metrik evaluasinya | F-06, F-07 |
| PR-07 | Laporan dapat diunduh dalam format PDF dan Excel | F-08 |
| PR-08 | Admin dapat mengekspor data anonim untuk keperluan training model | F-05 |

## 8. Success Metrics

| Kode | Metrik | Target |
|---|---|---|
| SM-01 | Jumlah respons valid per periode | Minimal 100 (ideal 150 atau lebih dari 300 penghuni) |
| SM-02 | Rata-rata waktu pengisian survei | Kurang dari 10 menit |
| SM-03 | Tingkat penyelesaian survei (mulai sampai kirim) | 80% atau lebih |
| SM-04 | F1-Score kelas "ingin keluar" (cross-validation) | Lebih baik dari baseline kelas mayoritas; target final ditetapkan setelah distribusi label diketahui |
| SM-05 | Pemahaman pengelola terhadap dashboard | Pengelola dapat menyebutkan 3 faktor dominan setelah melihat dashboard (diuji saat UAT) |
| SM-06 | Kelulusan UAT | Seluruh skenario UAT prioritas Must lulus |

## 9. Out of Scope

- Aplikasi mobile native.
- Integrasi dengan sistem akademik atau keuangan Universitas Klabat.
- Survei untuk asrama selain Crystal Dormitory.
- Prediksi atau tindakan terhadap mahasiswa tertentu secara individual.
- Notifikasi email/WhatsApp otomatis.
- Analisis NLP lanjutan pada jawaban terbuka.
- Retraining model otomatis.

## 10. Asumsi dan Ketergantungan

1. Akun mahasiswa dibuat oleh admin berdasarkan daftar penghuni dari pengelola asrama.
2. Pengelola asrama membantu menyebarkan informasi survei.
3. ML service (FastAPI) tersedia saat analisis dijalankan.

## 11. Pertanyaan Terbuka

| No | Pertanyaan | Penanggung Jawab |
|---|---|---|
| 1 | Apakah penghuni wajib tinggal di asrama (tidak bisa memilih keluar)? Jika ya, bagaimana memperlakukan jawaban mereka? | Prayshe, dengan pengelola asrama |
| 2 | Apakah akun dibuat per mahasiswa atau memakai kode akses survei satu kali pakai? | Prayshe dan Geraldo |
| 3 | Apakah ada faktor khas Crystal Dormitory yang belum tercakup (misalnya kegiatan wajib asrama)? | Prayshe dan Gerald |
