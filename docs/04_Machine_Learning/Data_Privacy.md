# Data Privacy Documentation

> Proyek: DormInsight
> Dokumen: Fase 4 - AI/ML Development
> Versi: 0.1 (Draft)
> Penyusun: Prayshe, Gerald
> Terakhir diperbarui: 23 November 2026

## 1. Data yang Dikumpulkan

| Data | Tujuan | Terhubung ke identitas? |
|---|---|---|
| Akun (nama, NIM, email) | Login dan mencegah pengisian ganda | Ya (tabel `users`) |
| Status "sudah mengisi" per periode | Mencegah pengisian ganda | Ya, tetapi **tanpa jawaban** |
| Jawaban survei (Likert, angkatan, lama tinggal, target, saran) | Analisis | **Tidak** |
| Durasi pengisian | Deteksi respons asal-asalan | Tidak |

Data yang **tidak** dikumpulkan: nomor kamar, jenis kelamin, asal daerah, program studi, atau data lain yang dapat mempersempit identitas dalam populasi 300 orang.

## 2. Persetujuan (Informed Consent)

Sebelum survei, mahasiswa membaca dan menyetujui pernyataan berikut (usulan teks):

> Survei ini bertujuan memahami pengalaman penghuni Crystal Dormitory untuk perbaikan layanan asrama. Partisipasi bersifat sukarela. Jawaban Anda **tidak terhubung** dengan akun Anda; pengelola asrama hanya melihat hasil gabungan dan tidak dapat mengetahui jawaban perorangan. Anda boleh berhenti kapan saja sebelum mengirim, dan jawaban yang belum dikirim tidak disimpan. Data hanya digunakan untuk proyek DormInsight.

## 3. Anonimisasi Teknis

| Teknik | Penjelasan |
|---|---|
| Pemisahan tabel | `survey_submissions` (siapa yang sudah mengisi) terpisah dari `responses` (apa jawabannya); tidak ada kolom penghubung |
| UUID acak | ID respons tidak berurutan sehingga tidak bisa dicocokkan dengan urutan submission |
| Tanggal tanpa jam | Waktu kirim tidak bisa dicocokkan |
| Kategori dikelompokkan | Angkatan 2023 dan sebelumnya digabung agar tidak ada kelompok kecil |
| Ambang segmen | Statistik segmen dengan kurang dari 5 responden tidak ditampilkan |
| Ekspor anonim | CSV ekspor hanya berisi `response_id` dan jawaban |

## 4. Hak Akses

| Role | Akses data |
|---|---|
| Mahasiswa | Status pengisiannya sendiri |
| Pengelola | Hanya statistik agregat dan laporan |
| Admin | Daftar respons anonim, ekspor CSV anonim, data akun |
| ML Service | Menerima fitur anonim saat analisis; tidak menyimpan data |

## 5. Penyimpanan dan Retensi

1. Data mentah survei asli disimpan di database aplikasi dan di `ml-service/data/raw/` pada perangkat tim ML saja.
2. `data/raw/` dan `data/processed/` masuk `.gitignore` dan **tidak boleh** diunggah ke GitHub.
3. Demo publik memakai data dummy.
4. Setelah proyek selesai, data mentah diserahkan ke pengelola asrama atau dihapus sesuai kesepakatan, dan dicatat di Maintenance Log.

## 6. Jawaban Teks Bebas (SARAN)

Jawaban teks dapat tanpa sengaja memuat nama atau detail pribadi. Sebelum ditampilkan ke pengelola (fitur F-11), admin meninjau dan menghapus informasi yang dapat mengidentifikasi seseorang.

## 7. Checklist Sebelum Survei Disebar

- [ ] Teks consent disetujui pengelola asrama dan dosen pembimbing
- [ ] Izin penyebaran survei diperoleh
- [ ] Uji coba: pastikan tabel `responses` dan `answers` tidak berisi `user_id`
- [ ] `.gitignore` memblokir `data/raw/`
- [ ] Akun admin memakai password kuat
