# Project Charter

> Proyek: DormInsight
> Dokumen: Fase 1 - Project Initiation
> Versi: 0.2 (Draft)
> Terakhir diperbarui: 23 November 2026

## 1. Identitas Proyek

| Item | Keterangan |
|---|---|
| Nama Proyek | DormInsight: AI-Based Student Dormitory Satisfaction and Retention Analysis System |
| Lokasi Studi Kasus | Crystal Dormitory, Universitas Klabat |
| Jenis Proyek | Aplikasi web dengan komponen Machine Learning |
| Metode Pengembangan | SDLC dengan Agile/Scrum yang disederhanakan (6 sprint) |
| Tanggal Mulai | 9 November 2026 |
| Periode Fase 1 | 9 sampai 23 November 2026 |
| Tanggal Selesai | [TBD, menyusul jadwal proyek penuh] |
| Project Manager | Prayshe (Ketua Tim) |

## 2. Latar Belakang

Asrama merupakan bagian penting dari kehidupan mahasiswa di Universitas Klabat. Kepuasan mahasiswa terhadap fasilitas, makanan, biaya, peraturan, privasi, dan lingkungan sosial asrama dapat memengaruhi keputusan mereka untuk tetap tinggal atau meninggalkan asrama.

Saat ini pengelola Crystal Dormitory belum memiliki sistem yang mengumpulkan dan menganalisis pendapat mahasiswa secara terstruktur. Masukan mahasiswa biasanya diperoleh secara informal sehingga sulit diukur, dibandingkan dari waktu ke waktu, dan dijadikan dasar keputusan perbaikan.

DormInsight dikembangkan untuk mengumpulkan data kepuasan mahasiswa melalui survei digital, lalu menganalisisnya dengan Machine Learning untuk mengidentifikasi faktor yang paling berpengaruh terhadap keinginan mahasiswa meninggalkan asrama.

## 3. Tujuan Proyek

1. Menyediakan survei digital kepuasan mahasiswa Crystal Dormitory yang mudah diisi.
2. Mengidentifikasi faktor dominan yang memengaruhi keinginan mahasiswa meninggalkan asrama.
3. Membangun model klasifikasi yang memperkirakan kecenderungan keinginan keluar asrama berdasarkan jawaban survei.
4. Menyajikan hasil analisis dalam dashboard dan laporan yang dapat digunakan pengelola untuk menentukan prioritas perbaikan.

## 4. Sasaran Terukur

| Kode | Sasaran | Indikator Keberhasilan |
|---|---|---|
| S-01 | Survei digital berjalan | Mahasiswa dapat mengisi dan mengirim survei tanpa error |
| S-02 | Data cukup untuk ML | Jumlah respons valid memenuhi batas minimum di Feasibility Study |
| S-03 | Model layak digunakan | F1-Score kelas "ingin keluar" lebih baik dari baseline (lihat Feasibility Study) |
| S-04 | Hasil dapat dipahami | Dashboard menampilkan faktor dominan beserta penjelasan SHAP |
| S-05 | Diterima pengguna | UAT dengan pengelola asrama dinyatakan lulus |

## 5. Stakeholder

| Stakeholder | Peran | Kepentingan |
|---|---|---|
| Mahasiswa Crystal Dormitory | Responden survei | Menyampaikan pengalaman dan masukan tentang asrama |
| Pengelola Crystal Dormitory | Pengguna utama dashboard | Mengetahui faktor ketidakpuasan dan prioritas perbaikan |
| Administrator Sistem | Pengelola aplikasi | Mengelola akun, pertanyaan survei, dan data |
| Dosen Pembimbing / Pengampu | Pengawas proyek | Menilai proses dan hasil proyek |
| Tim Pengembang | Pelaksana | Membangun sistem sesuai requirement |

## 6. Tim Proyek

| Nama | Role | Tanggung Jawab |
|---|---|---|
| Prayshe (Ketua) | Project Manager, Business/System Analyst | Timeline, pembagian tugas, dokumentasi proyek, requirements, PRD, SRS, UML, koordinasi dengan pengelola asrama |
| Geraldo | Backend Developer | Laravel, database MySQL, API, integrasi dengan ML service |
| Gerald | ML Engineer | Instrumen survei (bersama analyst), data processing, model training, evaluasi, FastAPI |
| Cheryl | UI/UX Designer, Frontend Developer | Wireframe, prototype Figma, user journey, tampilan survei dan dashboard |

Tim terdiri dari 4 orang; beberapa anggota memegang lebih dari satu role.

## 7. Ruang Lingkup Ringkas

Detail lengkap ada di [Project_Scope.md](Project_Scope.md).

- **Termasuk:** autentikasi berbasis role, manajemen survei, pengisian survei, analisis ML, prediksi, dashboard, laporan PDF/Excel.
- **Tidak termasuk:** aplikasi mobile native, integrasi dengan sistem akademik kampus, dan pengambilan keputusan otomatis terhadap mahasiswa secara individual.

## 8. Deliverable Utama

| Fase | Deliverable |
|---|---|
| 1. Project Initiation | Project Charter, Problem Statement, Project Scope, Feasibility Study, Timeline |
| 2. Requirements | PRD, SRS, User Stories, Use Case Specification |
| 3. System Design | Architecture, ERD, UML, UI/UX Prototype, API Documentation |
| 4. ML Development | Dataset Documentation, Experiment Log, Model Evaluation, Model Card |
| 5. Implementation | Aplikasi web dan ML service yang berjalan |
| 6. Testing | Test Plan, Test Cases, Test Report, UAT |
| 7. Deployment | Deployment Guide, User Manual, Release Notes |
| 8. Maintenance | Maintenance Log, Model Monitoring |

## 9. Asumsi

1. Pengelola Crystal Dormitory bersedia menjadi narasumber dan peserta UAT.
2. Mahasiswa penghuni asrama bersedia mengisi survei secara sukarela.
3. Survei dapat disebarkan kepada penghuni asrama dengan izin pihak terkait.
4. Tim memiliki akses ke perangkat dan server untuk pengembangan dan deployment.

## 10. Batasan

1. Data hanya berasal dari Crystal Dormitory, sehingga hasil model tidak otomatis berlaku untuk asrama lain.
2. Data dikumpulkan dalam satu periode survei sehingga belum mencerminkan perubahan jangka panjang.
3. Model memprediksi **keinginan** keluar asrama, bukan keputusan keluar yang sebenarnya.
4. Waktu pengerjaan dibatasi oleh jadwal akademik.

## 11. Risiko Awal

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Jumlah responden terlalu sedikit | Model tidak layak dilatih | Tetapkan target minimum responden, sebar survei lebih awal, siapkan analisis statistik deskriptif sebagai alternatif |
| Label target tidak seimbang | Model bias ke kelas mayoritas | Gunakan F1-Score, class weighting, atau resampling |
| Mahasiswa tidak jujur karena takut teridentifikasi | Data bias | Survei anonim, jelaskan tujuan dan perlindungan data |
| Izin penyebaran survei terlambat | Jadwal ML mundur | Ajukan izin di Sprint 1 |
| Perubahan requirement | Pekerjaan berulang | Review requirement di akhir setiap sprint |

## 12. Persetujuan

| Nama | Jabatan | Tanda Tangan | Tanggal |
|---|---|---|---|
| Prayshe | Project Manager / Ketua Tim | | |
| [Nama] | Dosen Pembimbing | | |
| [Nama] | Perwakilan Pengelola Crystal Dormitory | | |
