# Deployment Guide

> Proyek: DormInsight
> Dokumen: Fase 7 - Deployment & Documentation
> Versi: 0.1
> Terakhir diperbarui: 24 November 2026

Panduan ini memasang DormInsight di internet **tanpa biaya** sehingga dapat diakses dari perangkat mana pun lewat link.

## 1. Arsitektur Hosting

```mermaid
flowchart LR
    U[Pengguna] -->|HTTPS| W["Render: dorminsight-web<br/>(Laravel, Docker)"]
    W -->|"HTTPS + X-API-Key"| M["Render: dorminsight-ml<br/>(FastAPI + model, Docker)"]
    W -->|PostgreSQL + SSL| D[("Neon PostgreSQL")]
```

| Komponen | Layanan | Paket | Batasan Penting |
|---|---|---|---|
| Aplikasi web | Render Web Service | Free (512 MB RAM) | Tidur setelah 15 menit tanpa pengunjung; bangun kembali sekitar 1 menit |
| ML service | Render Web Service | Free (512 MB RAM) | Sama; pemakaian RAM terukur sekitar 242 MB |
| Database | Neon PostgreSQL | Free | 1 GB per project, tidak kedaluwarsa; komputasi tidur setelah 5 menit tidak dipakai |

**Mengapa PostgreSQL, bukan MySQL?** Layanan MySQL gratis yang umum dipakai (Aiven) tidak lagi menerima pendaftaran baru sejak November 2026, sedangkan database gratis Render terhapus setelah 30 hari. Neon gratis tanpa batas waktu. Laravel mendukung MySQL dan PostgreSQL, sehingga di laptop tetap boleh memakai MySQL.

**Mengapa pakai `render.yaml`?** Kedua layanan dibuat sekaligus dengan pengaturan yang benar, dan API key antar-layanan dibuat serta disambungkan otomatis.

## 2. Prasyarat

| Kebutuhan | Keterangan |
|---|---|
| Kode di GitHub | Repository `Gees12/ProjectMachineLearning` berisi seluruh folder project, **termasuk** `web-app/composer.json`, `web-app/composer.lock`, dan `ml-service/models/model.joblib` |
| Akun Neon | https://neon.com (bisa login dengan GitHub) |
| Akun Render | https://render.com (bisa login dengan GitHub) |

Periksa sebelum push: file `.env` **tidak boleh** ikut. `.gitignore` sudah mengaturnya; pastikan lewat `git status`.

## 3. Langkah 1: Push Kode ke GitHub

Di terminal folder `C:\MachineLearning`:

```powershell
git init
git branch -M main
git add .
git status          # pastikan tidak ada .env, vendor/, .venv/
git commit -m "DormInsight siap deploy"
git remote add origin https://github.com/Gees12/ProjectMachineLearning.git
git push -u origin main
```

Jika repository sudah pernah di-push, cukup `git add .`, `git commit -m "..."`, `git push`.

## 4. Langkah 2: Buat Database di Neon

1. Login ke Neon, klik **New Project**.
2. Nama: `dorminsight`; Postgres version: bawaan; **Region: AWS Asia Pacific (Singapore)** agar dekat dengan Render Singapura.
3. Setelah project jadi, klik **Connect**. Pastikan opsi **Connection pooling dimatikan** (connection string TIDAK mengandung `-pooler`).
4. Salin connection string, bentuknya seperti:
   ```
   postgresql://neondb_owner:XXXX@ep-xxxx-xxxx.ap-southeast-1.aws.neon.tech/neondb?sslmode=require
   ```
   Simpan sementara; ini **rahasia**, jangan dibagikan atau di-commit.

## 5. Langkah 3: Deploy di Render dengan Blueprint

1. Login ke Render, klik **New → Blueprint**.
2. Hubungkan akun GitHub, pilih repository `ProjectMachineLearning`, branch `main`.
3. Render membaca `render.yaml` dan menampilkan dua layanan: `dorminsight-ml` dan `dorminsight-web`.
4. Isi variabel yang diminta:

| Variabel | Isi |
|---|---|
| `DB_URL` | Connection string Neon dari Langkah 2 |
| `APP_URL` | `https://dorminsight-web.onrender.com` (sesuaikan jika Render memberi nama lain) |
| `ML_SERVICE_URL` | `https://dorminsight-ml.onrender.com` (sesuaikan jika Render memberi nama lain) |

5. Klik **Apply** / **Deploy Blueprint**. Build pertama sekitar 5 sampai 15 menit.

Jika nama layanan sudah dipakai orang lain, Render menambahkan akhiran (misalnya `dorminsight-web-abc1`). Setelah deploy, buka tiap layanan, salin URL-nya, lalu perbarui `APP_URL` dan `ML_SERVICE_URL` di **dorminsight-web → Environment**, kemudian **Save, rebuild, and deploy**.

## 6. Langkah 4: Login Pertama

1. Buka **dorminsight-web → Environment**, lihat nilai `ADMIN_INITIAL_PASSWORD`.
2. Buka URL aplikasi, login dengan `admin@dorminsight.test` dan password tersebut.
3. Aplikasi meminta password baru. Simpan di password manager.

Di server, akun pengelola dan mahasiswa demo **tidak** dibuat. Buat akun pengelola lewat menu **Akun**; mahasiswa dapat mendaftar sendiri (menunggu persetujuan admin) atau diimpor dari CSV.

## 7. Langkah 5: Uji Coba Analisis ML

Karena `SEED_DEMO=true`, tersedia survei **[DEMO] Survei Kepuasan (data dummy)** dengan 150 respons.

1. Buka **https://dorminsight-ml.onrender.com/health** terlebih dahulu dan tunggu sampai muncul `{"status":"ok","model_loaded":true}` (membangunkan ML service).
2. Di aplikasi: **Survei & Data → [DEMO] → Analisis → Jalankan Analisis**.
3. Buka **Dashboard**, pilih survei demo.

Setelah uji coba selesai dan survei sungguhan akan dimulai, ubah `SEED_DEMO` menjadi `false`. Survei demo dapat dibiarkan atau dihapus lewat menu survei.

## 8. Setelah Deploy

### 8.1 Pembaruan aplikasi

Setiap `git push` ke `main` otomatis memicu deploy ulang di Render. Migrasi database dijalankan otomatis saat aplikasi menyala (`docker/entrypoint.sh`).

### 8.2 Memperbarui model ML

1. Latih ulang di laptop: `python -m src.train --data data/raw/<file>.csv --data-source real --version 1.0.0`.
2. Commit `ml-service/models/model.joblib` dan `model_meta.json`, lalu push.
3. Render membangun ulang `dorminsight-ml`; setelah itu jalankan **Analisis** lagi di aplikasi.

**Privasi:** `model.joblib` menyimpan contoh 100 baris fitur (tanpa identitas) untuk perhitungan SHAP. Setelah model dilatih dengan data asli, **jadikan repository GitHub private**.

### 8.3 Lupa password admin

Paket gratis Render tidak menyediakan shell. Cara mereset:
1. Di **dorminsight-web → Environment**, ubah `ADMIN_EMAIL` ke email baru (misalnya `admin2@dorminsight.test`) dan isi `ADMIN_INITIAL_PASSWORD` dengan password sementara.
2. Deploy ulang; akun admin baru dibuat otomatis.
3. Login dengan akun baru, lalu reset atau nonaktifkan akun admin lama lewat menu **Akun**.

### 8.4 Backup database

Neon → project → **Backup & Restore** menyediakan pemulihan ke titik waktu tertentu dalam batas paket gratis. Untuk backup manual, gunakan `pg_dump` dengan connection string Neon, dan simpan hasilnya **di luar** repository.

## 9. Pemecahan Masalah

| Gejala | Penyebab | Solusi |
|---|---|---|
| Halaman pertama lama terbuka (sekitar 1 menit) | Layanan gratis sedang tidur | Normal; buka ulang setelah layanan bangun |
| Deploy gagal di langkah `migrate` | `DB_URL` salah atau memakai koneksi `-pooler` | Salin ulang connection string langsung dari Neon |
| Error 500 setelah login | Konfigurasi belum lengkap | Render → dorminsight-web → **Logs**; periksa pesan error |
| "ML service tidak dapat dihubungi" | ML service tidur atau `ML_SERVICE_URL` salah | Buka `/health` ML service, tunggu, lalu ulangi analisis; periksa URL |
| "Invalid API key" | Kunci tidak sama | Pastikan `ML_API_KEY` web mengambil nilai dari `dorminsight-ml` (fromService) |
| Tautan/form mengarah ke `http://` | Proxy belum dipercaya | Sudah ditangani `trustProxies` di `bootstrap/app.php`; pastikan versi kode terbaru ter-deploy |
| Build ML gagal: `model.joblib tidak ada` | File model belum di-commit | Latih model, commit `ml-service/models/`, push |

## 10. Checklist Rilis

- [ ] Kode terbaru sudah di-push ke `main`
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Admin sudah mengganti password awal
- [ ] Akun pengelola dibuat; akun demo tidak ada di server
- [ ] `SEED_DEMO=false` sebelum survei sungguhan dibuka
- [ ] Analisis ML berhasil dijalankan dari server
- [ ] Repository dijadikan private setelah model dilatih dengan data asli
