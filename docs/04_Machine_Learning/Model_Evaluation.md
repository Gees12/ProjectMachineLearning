# Model Evaluation Report

> Proyek: DormInsight
> Dokumen: Fase 4 - AI/ML Development
> Versi: 0.1 (hasil data dummy)
> Penyusun: Gerald (ML Engineer)
> Terakhir diperbarui: 23 November 2026

> **Penting:** angka di dokumen ini berasal dari **data dummy** (EXP-001) dan hanya membuktikan bahwa metode evaluasi berjalan. Ganti seluruh angka setelah training dengan data asli.

## 1. Metode Evaluasi

| Aspek | Pilihan | Alasan |
|---|---|---|
| Validasi | Nested stratified 5-fold CV (inner 3-fold grid search) | Data kecil; nested CV mencegah skor terlalu optimis akibat tuning pada data yang sama dengan evaluasi |
| Metrik utama | F1-Score kelas "ingin keluar" | Kelas minoritas adalah fokus; accuracy menyesatkan jika kelas tidak seimbang |
| Metrik pendukung | Precision, Recall, Balanced Accuracy, ROC AUC, Accuracy | Gambaran lengkap |
| Pembanding | Baseline "selalu tebak mayoritas" dan baseline acak | Bukti bahwa model lebih baik daripada tebakan |
| Class imbalance | `class_weight="balanced"` | Tanpa membuat data sintetis |
| Pemilihan model | F1 tertinggi; jika selisih kurang dari 0.02, pilih model lebih sederhana (LR, lalu DT, lalu RF) | Model sederhana lebih mudah dijelaskan dan lebih tahan overfitting |
| Explainability | SHAP global + uji stabilitas bootstrap (30 kali) | Menunjukkan faktor dominan dan seberapa yakin kita |

## 2. Hasil Perbandingan Model

| Model | Accuracy | Balanced Acc. | Precision | Recall | F1 kelas 1 | ROC AUC |
|---|---|---|---|---|---|---|
| Baseline mayoritas | 0.641 | 0.500 | 0.000 | 0.000 | 0.000 | 0.500 |
| Baseline acak | 0.566 | 0.492 | 0.343 | 0.231 | 0.276 | 0.492 |
| **Logistic Regression** | **0.738** | **0.730** | **0.628** | **0.696** | **0.655** | **0.817** |
| Decision Tree | 0.607 | 0.604 | 0.454 | 0.584 | 0.500 | 0.614 |
| Random Forest | 0.724 | 0.703 | 0.625 | 0.620 | 0.616 | 0.783 |

Grafik: `ml-service/reports/figures/model_comparison.png`.

Perhatikan baseline mayoritas: accuracy-nya 0.641 padahal tidak pernah mendeteksi satu pun mahasiswa yang ingin keluar. Ini alasan accuracy tidak dipakai sebagai metrik utama.

## 3. Model Terpilih: Logistic Regression (`lr-0.1.0-dummy`)

### 3.1 Confusion Matrix (prediksi out-of-fold, 145 respons)

| | Prediksi: tidak keluar | Prediksi: ingin keluar |
|---|---|---|
| **Aktual: tidak keluar (93)** | 71 (TN) | 22 (FP) |
| **Aktual: ingin keluar (52)** | 16 (FN) | 36 (TP) |

Grafik: `ml-service/reports/figures/confusion_matrix.png`.

### 3.2 Classification Report

| Kelas | Precision | Recall | F1 | Support |
|---|---|---|---|---|
| Tidak keluar | 0.816 | 0.763 | 0.789 | 93 |
| Ingin keluar | 0.621 | 0.692 | 0.655 | 52 |

Interpretasi: model menemukan sekitar 7 dari 10 mahasiswa yang ingin keluar (recall 0.69), dan sekitar 6 dari 10 mahasiswa yang diprediksi ingin keluar memang ingin keluar (precision 0.62).

## 4. Explainability (SHAP)

| Rank | Faktor | Rata-rata \|SHAP\| | Arah | Masuk 3 besar (bootstrap) |
|---|---|---|---|---|
| 1 | Kebersihan | 0.402 | negatif | 60% |
| 2 | Privasi | 0.372 | negatif | 63% |
| 3 | Makanan / kafetaria | 0.340 | negatif | 67% |
| 4 | Keamanan | 0.261 | negatif | 37% |
| 5 | Biaya | 0.219 | negatif | 13% |
| 6 | Fasilitas kamar | 0.205 | negatif | 30% |
| 7 | Peraturan asrama | 0.167 | negatif | 13% |
| 8 | Lingkungan sosial | 0.163 | negatif | 10% |
| 9 | Pelayanan pengelola | 0.145 | negatif | 3% |
| 10 | Kenyamanan belajar | 0.093 | negatif | 3% |
| 11 | Lama tinggal | 0.071 | positif | 0% |
| 12 | Angkatan | 0.017 | campuran | 0% |

"Negatif" berarti semakin tinggi kepuasan, semakin rendah kecenderungan keluar. Grafik: `ml-service/reports/figures/shap_global.png`.

**Catatan stabilitas:** pada data dummy, pola yang sebenarnya ditanamkan adalah makanan, biaya, privasi, peraturan. Model menemukan makanan dan privasi, tetapi biaya hanya masuk 3 besar pada 13% bootstrap. Dengan 3.000 data dummy, urutan yang benar ditemukan dengan tepat. Kesimpulan: dengan ukuran data seperti Crystal Dormitory, peringkat faktor harus disajikan bersama tingkat stabilitasnya.

## 5. Pemeriksaan Kualitas

| Pemeriksaan | Hasil | Bukti |
|---|---|---|
| Data leakage: KEP_UMUM dan target tidak menjadi fitur | Lulus | `tests/test_features.py` |
| Preprocessing training sama dengan prediksi | Lulus (satu sklearn Pipeline) | `src/modeling.py` |
| Tuning tidak bocor ke evaluasi | Lulus (nested CV) | `src/train.py` |
| Respons tidak valid dibuang | Lulus | `tests/test_features.py` |
| Model lebih baik dari baseline | Lulus (F1 0.655 vs 0.000) | Tabel 2 |
| Kontrak API | Lulus (9 test) | `tests/test_api.py` |

## 6. Kriteria Penerimaan Model (untuk Data Asli)

Model dapat dipakai di dashboard jika:

1. Respons valid minimal 100 dan setiap kelas minimal 10 respons (`data_sufficient = true`).
2. F1 kelas "ingin keluar" lebih tinggi daripada baseline acak secara jelas (selisih minimal 0.15).
3. ROC AUC minimal 0.65.
4. Minimal satu faktor memiliki stabilitas masuk 3 besar di atas 50%.

Jika tidak terpenuhi, dashboard tetap menampilkan statistik deskriptif, dan bagian prediksi diberi label "eksperimental" (sesuai Feasibility Study 4.3).

## 7. Keterbatasan

1. Angka di dokumen ini dari data dummy.
2. Ambang risiko 0.40 dan 0.70 belum dikalibrasi.
3. Hasil berlaku untuk Crystal Dormitory pada periode survei tersebut saja.
4. Model memprediksi **keinginan** keluar yang dinyatakan, bukan keputusan keluar sebenarnya.
