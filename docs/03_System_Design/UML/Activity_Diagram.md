# Activity Diagram

> Proyek: DormInsight | Fase 3 | Versi 0.1 | Penyusun: Prayshe

## AD-01 Mengisi Survei (Mahasiswa)

```mermaid
flowchart TD
    S((Mulai)) --> A[Login]
    A --> B{Ada survei aktif?}
    B -- Tidak --> B1[Tampilkan: belum ada survei aktif] --> E((Selesai))
    B -- Ya --> C{Sudah mengisi periode ini?}
    C -- Ya --> C1[Tampilkan: terima kasih, survei sudah diisi] --> E
    C -- Tidak --> D[Tampilkan halaman consent]
    D --> F{Setuju?}
    F -- Tidak --> E
    F -- Ya --> G[Sistem mencatat waktu mulai di session]
    G --> H[Isi pertanyaan per bagian faktor]
    H --> I[Isi angkatan, lama tinggal, pertanyaan target, saran]
    I --> J[Tinjau ringkasan jawaban]
    J --> K[Tekan Kirim]
    K --> L{Valid? semua wajib terjawab, survei masih buka, belum pernah kirim}
    L -- Tidak --> L1[Tampilkan pesan kesalahan] --> H
    L -- Ya --> M[Hitung durasi dan skor faktor]
    M --> N[Transaksi DB: responses, answers, response_features, survey_submissions]
    N --> O[Tandai respons mencurigakan bila ada]
    O --> P[Tampilkan halaman terima kasih]
    P --> E
```

## AD-02 Menjalankan Analisis ML (Admin)

```mermaid
flowchart TD
    S((Mulai)) --> A[Admin membuka survei]
    A --> B{Status survei = closed?}
    B -- Tidak --> B1[Tombol analisis tidak tersedia] --> E((Selesai))
    B -- Ya --> C{Respons valid cukup? minimal 100}
    C -- Tidak --> C1[Tampilkan peringatan jumlah data] --> C2{Tetap lanjut?}
    C2 -- Tidak --> E
    C2 -- Ya --> D
    C -- Ya --> D[Tekan Jalankan Analisis]
    D --> F[Ambil response_features valid]
    F --> G[GET /health dan /model/info]
    G --> H{Service aktif dan fitur cocok?}
    H -- Tidak --> H1[Catat analysis_runs status failed, tampilkan error] --> E
    H -- Ya --> I[POST /predict/batch]
    I --> J[POST /explain/global]
    J --> K{Kedua respons berhasil?}
    K -- Tidak --> H1
    K -- Ya --> L[Transaksi DB: analysis_runs, analysis_predictions, analysis_shap]
    L --> M[Tampilkan: Analisis selesai]
    M --> E
```

## AD-03 Melihat Dashboard dan Mengunduh Laporan (Pengelola)

```mermaid
flowchart TD
    S((Mulai)) --> A[Login sebagai pengelola]
    A --> B[Buka dashboard]
    B --> C{Sudah ada analisis untuk survei terpilih?}
    C -- Tidak --> C1[Tampilkan statistik deskriptif dan info analisis belum dijalankan]
    C -- Ya --> D[Tampilkan ringkasan, skor faktor, faktor dominan, distribusi risiko]
    C1 --> F
    D --> F{Pilih filter segmen?}
    F -- Ya --> G{Segmen minimal 5 responden?}
    G -- Tidak --> G1[Sembunyikan statistik segmen] --> H
    G -- Ya --> G2[Perbarui grafik] --> H
    F -- Tidak --> H{Unduh laporan?}
    H -- Ya --> I[Pilih PDF atau Excel] --> J[Sistem membuat dan mengunduh file] --> E((Selesai))
    H -- Tidak --> E
```
