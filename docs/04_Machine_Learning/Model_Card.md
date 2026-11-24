# Model Card: DormInsight Retention Model

> Proyek: DormInsight
> Dokumen: Fase 4 - AI/ML Development
> Versi dokumen: 0.1
> Versi model: `lr-0.1.0-dummy`
> Penyusun: Gerald (ML Engineer)
> Terakhir diperbarui: 23 November 2026

## 1. Detail Model

| Item | Nilai |
|---|---|
| Nama | DormInsight Retention Model |
| Versi | `lr-0.1.0-dummy` (versi uji, dilatih dengan data sintetis) |
| Jenis | Klasifikasi biner |
| Algoritma | Logistic Regression (C = 0.1, class_weight balanced), dipilih dari perbandingan dengan Decision Tree dan Random Forest |
| Input | 10 skor faktor kepuasan (1.0 sampai 5.0), angkatan, lama tinggal |
| Output | Probabilitas keinginan keluar asrama (0 sampai 1) dan kategori risiko |
| Explainability | SHAP (LinearExplainer untuk LR, TreeExplainer untuk model pohon) |
| Framework | scikit-learn, SHAP, disajikan melalui FastAPI |
| File | `ml-service/models/model.joblib`, `model_meta.json` |
| Pengembang | Tim DormInsight, Universitas Klabat |

## 2. Tujuan Penggunaan

**Penggunaan yang dimaksud:**

- Membantu pengelola Crystal Dormitory memahami **faktor apa** yang secara umum berhubungan dengan keinginan mahasiswa meninggalkan asrama.
- Memperkirakan **proporsi** penghuni yang cenderung ingin keluar pada suatu periode.
- Mensimulasikan perkiraan dampak perbaikan suatu faktor.

**Penggunaan yang TIDAK diperbolehkan:**

- Menilai, menandai, atau memberi tindakan kepada mahasiswa tertentu.
- Menjadi dasar keputusan disipliner, akademik, atau keuangan.
- Diterapkan ke asrama atau kampus lain tanpa pelatihan ulang dan evaluasi.

## 3. Data Training

| Item | Versi saat ini | Versi produksi (rencana) |
|---|---|---|
| Sumber | Data dummy sintetis | Survei penghuni Crystal Dormitory |
| Jumlah | 145 respons valid | Target minimal 100, ideal 150+ dari 300 penghuni |
| Proporsi ingin keluar | 35.9% | [TBD] |
| Detail | [Dataset_Documentation.md](Dataset_Documentation.md) | |

## 4. Performa

Hasil nested stratified 5-fold CV pada data dummy (detail di [Model_Evaluation.md](Model_Evaluation.md)):

| Metrik | Nilai | Baseline mayoritas |
|---|---|---|
| F1 kelas ingin keluar | 0.655 ± 0.103 | 0.000 |
| Recall kelas ingin keluar | 0.696 | 0.000 |
| Precision kelas ingin keluar | 0.628 | 0.000 |
| ROC AUC | 0.817 | 0.500 |

Angka ini **tidak** menggambarkan performa pada data asli.

## 5. Keterbatasan

1. Versi saat ini dilatih dengan data sintetis.
2. Data asli akan kecil (maksimal 300 penghuni), sehingga peringkat faktor dapat berubah jika data sedikit berbeda. Gunakan kolom stabilitas bootstrap saat menafsirkan.
3. Faktor-faktor kepuasan saling berkorelasi; SHAP membagi pengaruh di antara faktor yang berkorelasi sehingga satu faktor bisa terlihat lebih kecil dari pengaruh sebenarnya.
4. Hubungan yang ditemukan bersifat asosiasi, **bukan sebab-akibat**. Simulasi skenario adalah perkiraan model.
5. Label adalah keinginan yang dinyatakan responden pada saat survei.
6. Responden yang tidak puas mungkin lebih (atau kurang) bersedia mengisi survei, sehingga sampel bisa bias.

## 6. Risiko dan Etika

| Risiko | Mitigasi |
|---|---|
| Re-identifikasi responden | Jawaban tidak terhubung ke akun (UUID, tanggal tanpa jam); segmen di bawah 5 responden disembunyikan |
| Hasil dipakai untuk menilai individu | Dashboard hanya agregat; larangan tercantum di Model Card dan User Manual |
| Keputusan diambil dari hasil yang tidak stabil | Tampilkan stabilitas dan metrik; label "eksperimental" jika kriteria penerimaan tidak terpenuhi |
| Model dari data dummy terbawa ke produksi | `data_source` di `/model/info`; dashboard wajib menampilkan peringatan jika `dummy` |
| Model usang | Latih ulang setiap periode survei baru; dicatat di Model Monitoring |

## 7. Pemeliharaan

| Item | Rencana |
|---|---|
| Pelatihan ulang | Setiap periode survei baru dengan data valid yang memadai |
| Pemantauan | Perubahan proporsi ingin keluar, perubahan peringkat faktor, metrik CV |
| Penanggung jawab | Gerald |
| Riwayat versi | `ml-service/reports/experiment_log.csv` dan [ML_Experiment_Log.md](ML_Experiment_Log.md) |
