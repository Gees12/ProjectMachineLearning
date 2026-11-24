# Data Dictionary (Machine Learning)

> Proyek: DormInsight
> Dokumen: Fase 4 - AI/ML Development
> Versi: 0.1 (Draft)
> Penyusun: Gerald (ML Engineer)
> Terakhir diperbarui: 23 November 2026

Definisi lengkap variabel survei dan ML ada di [Data Dictionary Fase 3, bagian B](../03_System_Design/Data_Dictionary.md#b-kamus-variabel-survei-dan-ml). Dokumen ini hanya merangkum bagaimana variabel tersebut diolah di pipeline.

## 1. Dari CSV ke Fitur Model

| Tahap | File Kode | Proses |
|---|---|---|
| Validasi | `src/features.py` `validate_export` | Kolom wajib, Likert 1 sampai 5, kategori dikenal, label 0/1 |
| Filter | `src/features.py` `build_features` | Hanya `is_valid = 1` |
| Skor faktor | `src/features.py` `build_features` | Rata-rata 2 item per faktor, dibulatkan 2 desimal |
| Preprocessing | `src/modeling.py` `build_preprocessor` | Lihat tabel 2 |

## 2. Preprocessing per Fitur

| Fitur | Tipe | Transformasi | Hasil Kolom |
|---|---|---|---|
| F_FAS ... F_BEL (10) | Numerik 1.0 sampai 5.0 | StandardScaler | 10 kolom |
| ANGKATAN | Kategorikal | OneHotEncoder (kategori tetap, nilai baru diabaikan) | 4 kolom |
| LAMA_TINGGAL | Ordinal | OrdinalEncoder: `lt1` = 0, `1_2` = 1, `gt2` = 2 | 1 kolom |
| **Total** | | | **15 kolom** |

Untuk laporan SHAP, 4 kolom ANGKATAN dijumlahkan kembali menjadi satu fitur.

## 3. Target

| Kode | Nilai | Keterangan |
|---|---|---|
| INGIN_KELUAR | 0 = tidak, 1 = ingin keluar | Kelas positif (yang menjadi fokus metrik) adalah 1 |

## 4. Kolom yang Dikecualikan

| Kolom | Alasan |
|---|---|
| KEP_UMUM | Berisiko data leakage karena sangat dekat dengan target |
| SARAN | Teks bebas, di luar ruang lingkup |
| duration_seconds, is_valid | Metadata kualitas data |
| response_id | Pengenal, bukan karakteristik |

Pengecualian ini diuji otomatis di `tests/test_features.py::test_kolom_terlarang_bukan_fitur`.
