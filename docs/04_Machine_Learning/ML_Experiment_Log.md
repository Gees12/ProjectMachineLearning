# ML Experiment Log

> Proyek: DormInsight
> Dokumen: Fase 4 - AI/ML Development
> Versi: 0.1
> Penyusun: Gerald (ML Engineer)
> Terakhir diperbarui: 23 November 2026

Log otomatis setiap training tersimpan di `ml-service/reports/experiment_log.csv`. Dokumen ini berisi ringkasan dan catatan analisis yang ditulis manual untuk setiap eksperimen penting.

## Format Entri

| Item | Isi |
|---|---|
| ID | EXP-xxx |
| Tanggal | |
| Data | dummy / real, nama file, jumlah respons valid, proporsi kelas 1 |
| Tujuan | Apa yang ingin diuji |
| Konfigurasi | Model, grid hyperparameter, metode validasi |
| Hasil | Metrik utama per model |
| Model terpilih | Versi, parameter |
| Temuan | Apa yang dipelajari |
| Tindak lanjut | Langkah berikutnya |

---

## EXP-001 Uji Pipeline dengan Data Dummy

| Item | Isi |
|---|---|
| Tanggal | 23 November 2026 |
| Data | Dummy, `data/dummy/dummy_survey.csv`, 145 respons valid, 35.9% ingin keluar |
| Tujuan | Memastikan pipeline (validasi, fitur, nested CV, pemilihan model, SHAP, penyimpanan, API) berjalan benar sebelum data asli tersedia |
| Konfigurasi | Nested CV: outer stratified 5-fold, inner 3-fold grid search, skor F1 kelas 1. `class_weight` seimbang untuk semua model |
| Grid | LR: C ∈ {0.1, 1, 10}. DT: max_depth ∈ {2, 3, 4, 5}, min_samples_leaf ∈ {5, 10}. RF (300 pohon): max_depth ∈ {3, 5, None}, min_samples_leaf ∈ {2, 5} |

**Hasil (rata-rata ± simpangan baku 5 fold):**

| Model | Accuracy | Balanced Acc. | Precision | Recall | F1 kelas 1 | ROC AUC |
|---|---|---|---|---|---|---|
| Baseline mayoritas | 0.641 | 0.500 | 0.000 | 0.000 | 0.000 | 0.500 |
| Baseline acak | 0.566 | 0.492 | 0.343 | 0.231 | 0.276 ± 0.119 | 0.492 |
| **Logistic Regression** | **0.738** | **0.730** | **0.628** | **0.696** | **0.655 ± 0.103** | **0.817** |
| Decision Tree | 0.607 | 0.604 | 0.454 | 0.584 | 0.500 ± 0.109 | 0.614 |
| Random Forest | 0.724 | 0.703 | 0.625 | 0.620 | 0.616 ± 0.133 | 0.783 |

| Item | Isi |
|---|---|
| Model terpilih | `lr-0.1.0-dummy`, LogisticRegression, C = 0.1 |
| 3 besar SHAP | Kebersihan, Privasi, Makanan (semua arah negatif) |

**Temuan:**

1. Pipeline berjalan dari awal sampai akhir; 14 test otomatis lulus; API dapat memuat model dan melayani semua endpoint.
2. Logistic Regression mengungguli Random Forest pada data ini. Dengan sekitar 150 respons, model sederhana sering setara atau lebih baik daripada model kompleks.
3. **Peringkat faktor tidak stabil pada data sekecil ini.** Pola yang ditanamkan adalah makanan, biaya, privasi, peraturan. Model menempatkan makanan dan privasi di 3 besar, tetapi kebersihan (bobot kecil) di peringkat 1 dan biaya di peringkat 5.
4. Uji pembanding: dengan 3.000 data dummy, LR dan RF sama-sama menemukan urutan yang benar (makanan, biaya, privasi, peraturan). Artinya kode benar; ketidakstabilan disebabkan ukuran data dan korelasi antarfaktor.
5. Uji stabilitas bootstrap (30 kali): makanan masuk 3 besar 67%, privasi 63%, kebersihan 60%, biaya hanya 13%.

**Tindak lanjut:**

1. Dashboard dan laporan wajib menampilkan tingkat stabilitas (frekuensi masuk 3 besar), bukan hanya peringkat.
2. Penyajian ke pengelola memakai kelompok "faktor yang konsisten berpengaruh" (misalnya stabilitas di atas 50%), bukan urutan pasti 1, 2, 3.
3. Target respons ideal (150 atau lebih) dipertahankan; semakin banyak data, semakin stabil hasilnya.

---

## EXP-002 Data Asli Periode Pertama

| Item | Isi |
|---|---|
| Tanggal | [TBD] |
| Data | real, [nama file], [n] respons valid, [x]% ingin keluar |
| Tujuan | Model pertama dari data Crystal Dormitory |
| Hasil | [salin dari `reports/model_comparison.csv`] |
| Model terpilih | [versi] |
| Stabilitas SHAP | [salin dari `reports/shap_stability.csv`] |
| Temuan | [TBD] |
| Tindak lanjut | [TBD] |
