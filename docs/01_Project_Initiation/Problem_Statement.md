# Problem Statement

> Proyek: DormInsight
> Dokumen: Fase 1 - Project Initiation
> Versi: 0.2 (Draft)
> Terakhir diperbarui: 23 November 2026

## 1. Kondisi Saat Ini

Crystal Dormitory, Universitas Klabat, menampung sekitar 300 mahasiswa yang tinggal dan beraktivitas sehari-hari di lingkungan asrama. Pengalaman mereka dipengaruhi oleh banyak faktor, antara lain fasilitas kamar, kebersihan, makanan, biaya, peraturan, privasi, keamanan, dan hubungan sosial dengan penghuni lain maupun pengelola.

Saat ini:

1. Masukan mahasiswa tentang asrama dikumpulkan secara informal, misalnya melalui percakapan langsung atau keluhan sesekali.
2. Belum ada instrumen survei standar yang diisi secara berkala oleh penghuni asrama.
3. Data masukan yang ada tidak tersimpan dalam format yang mudah dianalisis.
4. Pengelola belum memiliki alat bantu untuk melihat faktor mana yang paling berpengaruh terhadap keinginan mahasiswa meninggalkan asrama.

## 2. Permasalahan

**Pengelola Crystal Dormitory belum memiliki sistem analisis terstruktur untuk mengetahui tingkat kepuasan mahasiswa dan faktor yang membuat mahasiswa ingin meninggalkan asrama.**

Rincian masalah:

| Kode | Masalah |
|---|---|
| P-01 | Tidak ada mekanisme pengumpulan data kepuasan mahasiswa yang terstruktur dan dapat diulang. |
| P-02 | Pengelola sulit mengetahui faktor mana yang paling memengaruhi keinginan mahasiswa keluar asrama. |
| P-03 | Keputusan perbaikan fasilitas dan layanan belum didukung data yang terukur. |
| P-04 | Tidak ada cara untuk melihat kecenderungan keinginan keluar pada kelompok mahasiswa tertentu sebelum terjadi. |

## 3. Dampak

Jika masalah ini tidak diselesaikan:

1. Pengelola dapat memprioritaskan perbaikan yang kurang berdampak bagi mahasiswa.
2. Ketidakpuasan mahasiswa tidak terdeteksi sejak awal.
3. Anggaran dan tenaga perbaikan berpotensi tidak tepat sasaran.
4. Tidak ada data pembanding untuk menilai apakah perbaikan yang dilakukan berhasil.

## 4. Solusi yang Diusulkan

DormInsight, sebuah aplikasi web yang:

1. Menyediakan survei digital kepuasan mahasiswa Crystal Dormitory.
2. Menyimpan jawaban survei secara terstruktur di database.
3. Menggunakan Machine Learning (klasifikasi) untuk menganalisis hubungan antara faktor kepuasan dan keinginan keluar asrama.
4. Menjelaskan faktor dominan menggunakan feature importance dan SHAP.
5. Menyajikan hasil dalam dashboard dan laporan untuk pengelola.

## 5. Pertanyaan Penelitian

1. Faktor apa saja yang paling berpengaruh terhadap keinginan mahasiswa Crystal Dormitory untuk meninggalkan asrama?
2. Seberapa baik model klasifikasi (Logistic Regression, Decision Tree, Random Forest) memperkirakan keinginan keluar asrama berdasarkan data survei kepuasan?
3. Bagaimana hasil analisis dapat disajikan agar mudah dipahami dan digunakan pengelola asrama?

## 6. Definisi Variabel Target (Awal)

Variabel target harus dikumpulkan sebagai pertanyaan tersendiri di survei, bukan disimpulkan dari jawaban lain.

| Item | Definisi Awal |
|---|---|
| Nama variabel | `ingin_keluar` |
| Pertanyaan survei (usulan) | "Apakah Anda berencana atau ingin pindah keluar dari Crystal Dormitory pada semester berikutnya?" |
| Nilai | 1 = Ya, 0 = Tidak |
| Catatan | Definisi final ditetapkan di Fase 2 (SRS dan Data Dictionary). Perlu dipertimbangkan apakah mahasiswa yang wajib tinggal di asrama tetap disertakan. |
