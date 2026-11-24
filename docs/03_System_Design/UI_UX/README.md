# UI/UX Design

> Proyek: DormInsight
> Dokumen: Fase 3 - System & Data Design
> Versi: 0.1 (Draft)
> Penyusun: Cheryl (UI/UX Designer)
> Terakhir diperbarui: 23 November 2026

Dokumen ini adalah spesifikasi awal untuk prototype Figma. Wireframe di bawah berupa sketsa teks low-fidelity sebagai acuan; versi final dibuat di Figma.

| Item | Nilai |
|---|---|
| Link Figma | [Isi setelah prototype dibuat] |
| Status | Wireframe low-fidelity |

## 1. Prinsip Desain

1. **Mobile-first untuk mahasiswa.** Survei dirancang untuk layar ponsel 360 px terlebih dahulu.
2. **Desktop-first untuk pengelola dan admin.** Dashboard dan tabel dirancang untuk laptop.
3. **Rasa aman.** Pernyataan anonimitas selalu terlihat di halaman survei.
4. **Bahasa sederhana.** Istilah teknis ML selalu disertai penjelasan (NFR-033).
5. **Konsisten.** Memakai komponen Bootstrap 5 tanpa banyak kustomisasi.

## 2. Design System

| Elemen | Pilihan |
|---|---|
| Framework | Bootstrap 5 |
| Grafik | Chart.js |
| Ikon | Bootstrap Icons |
| Font | System font stack bawaan Bootstrap |
| Warna utama | Biru tua `#1F4E79` (navbar, tombol utama) |
| Warna sekunder | Abu-abu `#6C757D` |
| Status risiko | Rendah `#198754` (hijau), Sedang `#FFC107` (kuning), Tinggi `#DC3545` (merah) |
| Latar | Putih dan abu muda `#F8F9FA` |

Warna status tidak boleh menjadi satu-satunya penanda; selalu disertai label teks (aksesibilitas).

## 3. Sitemap

```
Login
├── Mahasiswa
│   ├── Beranda Survei (status)
│   ├── Consent
│   ├── Formulir Survei (per bagian)
│   ├── Tinjau Jawaban
│   └── Terima Kasih
├── Pengelola
│   ├── Dashboard
│   ├── Perbandingan Periode
│   ├── Simulasi Skenario
│   └── Laporan (unduh PDF/Excel)
└── Admin
    ├── semua menu Pengelola
    ├── Manajemen Akun
    ├── Manajemen Survei → Faktor → Pertanyaan
    ├── Data Respons
    └── Riwayat Analisis
```

## 4. Wireframe Low-Fidelity

### W-01 Login

```
┌──────────────────────────────┐
│          DormInsight         │
│   Crystal Dormitory UNKLAB   │
│                              │
│  Email / NIM                 │
│  [__________________________]│
│  Password                    │
│  [__________________________]│
│                              │
│  [          Masuk          ] │
│                              │
│  Lupa password? Hubungi admin│
└──────────────────────────────┘
```

### W-02 Consent (Ponsel)

```
┌────────────────────────┐
│ ☰ DormInsight          │
├────────────────────────┤
│ Survei Kepuasan Asrama │
│ Semester II 2026/2027  │
│                        │
│ Tujuan survei: ...     │
│                        │
│ 🔒 Jawaban Anda ANONIM │
│ Pengelola tidak dapat  │
│ melihat siapa menjawab │
│ apa.                   │
│                        │
│ Estimasi: 5-8 menit    │
│                        │
│ [✓] Saya memahami dan  │
│     bersedia mengisi   │
│                        │
│ [   Mulai Survei   ]   │
└────────────────────────┘
```

### W-03 Formulir Survei per Bagian (Ponsel)

```
┌────────────────────────┐
│ Bagian 3 dari 12       │
│ ███████░░░░░░░░  25%   │
├────────────────────────┤
│ 🍽 Makanan / Kafetaria │
│                        │
│ Kualitas dan variasi   │
│ makanan memenuhi       │
│ kebutuhan saya.        │
│ (1) (2) (3) (4) (5)    │
│ Sangat       Sangat    │
│ tidak puas   puas      │
│                        │
│ Jadwal dan porsi makan │
│ sesuai kebutuhan saya. │
│ (1) (2) (3) (4) (5)    │
│                        │
│ [◀ Kembali] [Lanjut ▶] │
│ 🔒 Jawaban anonim      │
└────────────────────────┘
```

Bagian: 10 faktor, 1 bagian profil (angkatan, lama tinggal), 1 bagian akhir (kepuasan keseluruhan, pertanyaan target, saran). Total 12 bagian.

### W-04 Dashboard Pengelola (Desktop)

```
┌──────────────────────────────────────────────────────────────────┐
│ DormInsight   Dashboard | Perbandingan | Simulasi | Laporan  [⎋] │
├──────────────────────────────────────────────────────────────────┤
│ Survei: [Semester II 2026/2027 ▼]  Angkatan: [Semua ▼]           │
│ Lama tinggal: [Semua ▼]                    [⬇ PDF] [⬇ Excel]     │
├───────────────┬───────────────┬───────────────┬──────────────────┤
│ Responden     │ Tingkat resp. │ Kepuasan rata │ Ingin keluar     │
│ 142           │ 47%           │ 3.4 / 5       │ 26%              │
├───────────────┴───────────────┴───────────────┴──────────────────┤
│ Faktor Paling Berpengaruh          │ Skor Rata-rata per Faktor   │
│ 1. Makanan       ██████████        │ Fasilitas    ███████ 3.6    │
│ 2. Biaya         ████████          │ Kebersihan   ████████ 4.0   │
│ 3. Privasi       ██████            │ Makanan      ████ 2.1       │
│ ...                                │ ...                         │
│ ℹ Makanan paling memengaruhi       │                             │
│   keinginan keluar: semakin rendah │                             │
│   kepuasan makanan, semakin tinggi │                             │
│   kecenderungan keluar.            │                             │
├────────────────────────────────────┴─────────────────────────────┤
│ Distribusi Kecenderungan Keluar                                  │
│ Rendah ████████████ 61%   Sedang █████ 24%   Tinggi ███ 15%      │
├──────────────────────────────────────────────────────────────────┤
│ Model: rf-1.0.0 · F1 kelas keluar: 0.60 · Analisis: 10 Des 2026  │
│ ℹ Hasil bersifat agregat dan perkiraan model.                    │
└──────────────────────────────────────────────────────────────────┘
```

Angka pada wireframe hanya contoh.

### W-05 Simulasi Skenario

```
┌──────────────────────────────────────────────────────────┐
│ Simulasi Skenario Perbaikan                              │
│ Geser nilai faktor untuk melihat perkiraan dampaknya.    │
│                                                          │
│ Makanan     2.1 ──────●────────── 3.5                    │
│ Biaya       3.0 ───────●───────── 3.0                    │
│ Privasi     2.8 ─────────●─────── 3.2                    │
│ ...                                                      │
│                                [ Hitung ]                │
│                                                          │
│ Sebelum: 42%   →   Sesudah: 31%   (turun 11 poin)        │
│ ⚠ Perkiraan model, bukan jaminan.                        │
└──────────────────────────────────────────────────────────┘
```

### W-06 Admin: Data Respons

```
┌──────────────────────────────────────────────────────────────┐
│ Data Respons: Semester II 2026/2027          [⬇ Ekspor CSV]  │
│ Masuk: 142 / target 100 / penghuni 300   ████████████░ 47%   │
├──────────────────────────────────────────────────────────────┤
│ ID (singkat) │ Tanggal  │ Durasi │ Status     │ Aksi         │
│ 8f6c…9e10    │ 02 Des   │ 6m 12s │ ✓ Valid    │ [Kecualikan] │
│ 0b2d…9c0d    │ 02 Des   │ 1m 05s │ ⚠ Terlalu  │ [Validkan]   │
│              │          │        │   cepat    │              │
└──────────────────────────────────────────────────────────────┘
```

## 5. Daftar Halaman dan Prioritas Prototype

| Kode | Halaman | Role | Prioritas Figma |
|---|---|---|---|
| W-01 | Login | Semua | Tinggi |
| W-02 | Consent | Mahasiswa | Tinggi |
| W-03 | Formulir survei | Mahasiswa | Tinggi |
| W-03b | Tinjau jawaban, terima kasih | Mahasiswa | Sedang |
| W-04 | Dashboard | Pengelola | Tinggi |
| W-05 | Simulasi | Pengelola | Sedang |
| W-06 | Data respons | Admin | Sedang |
| W-07 | Manajemen survei dan pertanyaan | Admin | Sedang |
| W-08 | Manajemen akun | Admin | Rendah |
| W-09 | Perbandingan periode | Pengelola | Rendah |

## 6. Rencana Uji Usability Prototype

1. Uji prototype W-02 dan W-03 pada 5 mahasiswa: catat waktu pengisian dan bagian yang membingungkan.
2. Uji W-04 pada pengelola asrama: minta menyebutkan 3 faktor dominan tanpa bantuan (SM-05).
3. Catat temuan dan perbaikan di bagian ini sebelum implementasi.
