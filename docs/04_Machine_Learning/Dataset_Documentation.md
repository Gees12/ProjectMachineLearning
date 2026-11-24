# Dataset Documentation

> Proyek: DormInsight
> Dokumen: Fase 4 - AI/ML Development
> Versi: 0.1 (Draft)
> Penyusun: Gerald (ML Engineer)
> Terakhir diperbarui: 23 November 2026

## 1. Status Dataset

| Dataset | Status | Lokasi | Boleh di-commit? |
|---|---|---|---|
| Data dummy (sintetis) | Tersedia | `ml-service/data/dummy/dummy_survey.csv` | Ya |
| Data survei asli Crystal Dormitory | **Belum dikumpulkan** (rencana Sprint 3) | `ml-service/data/raw/` | **Tidak** |

Bagian 3 sampai 5 menjelaskan data dummy. Bagian 6 adalah templat yang diisi setelah data asli terkumpul.

## 2. Format File (Ekspor dari Laravel)

Satu baris = satu respons survei. File CSV, pemisah koma, encoding UTF-8, baris pertama header.

| Kolom | Tipe | Nilai | Keterangan |
|---|---|---|---|
| response_id | string | UUID | ID respons anonim |
| F_FAS_1 ... F_BEL_2 | integer | 1 sampai 5 | 20 item Likert (10 faktor × 2 item) |
| ANGKATAN | string | `2023_atau_sebelumnya`, `2024`, `2025`, `2026` | |
| LAMA_TINGGAL | string | `lt1`, `1_2`, `gt2` | |
| KEP_UMUM | integer | 1 sampai 5 | Kepuasan keseluruhan, **bukan fitur** |
| INGIN_KELUAR | integer | 0 atau 1 | **Label target** |
| SARAN | string | teks bebas | Opsional |
| duration_seconds | integer | detik | Durasi pengisian |
| is_valid | integer | 0 atau 1 | Hasil validasi admin/sistem |

File ini divalidasi otomatis oleh `src/features.py` (kolom wajib, rentang Likert, nilai kategori, label 0/1).

## 3. Data Dummy: Cara Pembuatan

Dibuat dengan `python -m src.generate_dummy_data --n 150` (seed 42).

1. Setiap responden memiliki tingkat kepuasan laten per faktor, ditambah komponen "kepuasan umum" sehingga faktor saling berkorelasi, seperti pada survei nyata.
2. Dua item per faktor = kepuasan laten + noise, dibulatkan ke 1 sampai 5.
3. Label target dibuat dari model logistik dengan bobot yang **sengaja ditanamkan**: makanan (paling kuat), biaya, privasi, peraturan, lalu faktor lain lebih lemah. Lama tinggal lebih dari 2 tahun sedikit menaikkan peluang keluar.
4. Sekitar 3% respons ditandai tidak valid (durasi di bawah 110 detik) untuk menguji filter.

Tujuan pola yang ditanamkan: memeriksa apakah pipeline mampu menemukan kembali faktor yang benar.

## 4. Data Dummy: Karakteristik

| Item | Nilai |
|---|---|
| Jumlah baris | 150 |
| Respons valid | 145 |
| Respons tidak valid | 5 |
| Missing value | 0 |
| INGIN_KELUAR = 1 | 52 (35.9%) |
| INGIN_KELUAR = 0 | 93 (64.1%) |
| Segmen dengan kurang dari 5 responden | Tidak ada |

Rata-rata skor faktor (respons valid):

| Faktor | Semua | Tidak keluar | Ingin keluar | Selisih |
|---|---|---|---|---|
| F_FAS Fasilitas | 3.23 | 3.44 | 2.87 | 0.57 |
| F_KEB Kebersihan | 3.77 | 3.97 | 3.40 | 0.57 |
| F_MAK Makanan | 2.84 | 3.08 | 2.40 | 0.68 |
| F_BIA Biaya | 2.89 | 3.04 | 2.62 | 0.42 |
| F_PER Peraturan | 2.96 | 3.08 | 2.75 | 0.33 |
| F_PRI Privasi | 3.14 | 3.37 | 2.72 | 0.65 |
| F_KEA Keamanan | 3.91 | 4.12 | 3.54 | 0.58 |
| F_SOS Sosial | 3.78 | 3.94 | 3.49 | 0.45 |
| F_PEL Pelayanan | 3.49 | 3.64 | 3.21 | 0.43 |
| F_BEL Belajar/internet | 2.88 | 3.07 | 2.55 | 0.52 |

Korelasi antar-2 item per faktor berada di rentang 0.66 sampai 0.75 (semua "konsisten"). File: `reports/eda/item_correlations.csv`.

Grafik EDA: `ml-service/reports/eda/` (`target_distribution.png`, `factor_by_target.png`, `correlation_heatmap.png`).

## 5. Keterbatasan Data Dummy

1. Pola sengaja dibuat; tidak mencerminkan kondisi Crystal Dormitory.
2. Tidak ada missing value, jawaban asal-asalan yang realistis, atau bias responden.
3. Hanya dipakai untuk menguji kode, integrasi API, dan tampilan dashboard.

## 6. Data Asli (Diisi Setelah Pengumpulan)

| Item | Nilai |
|---|---|
| Periode pengumpulan | [TBD] |
| Populasi | 300 penghuni Crystal Dormitory |
| Respons masuk | [TBD] |
| Respons valid | [TBD] (target minimum 100, ideal 150) |
| Tingkat respons | [TBD] |
| Proporsi INGIN_KELUAR = 1 | [TBD] |
| Respons tidak valid dan alasannya | [TBD] |
| Segmen di bawah 5 responden | [TBD] |
| Korelasi antaritem per faktor | [TBD] |
| Catatan penting dari EDA | [TBD] |
| Nama file | `data/raw/survey_export_<tanggal>.csv` (tidak di-commit) |
