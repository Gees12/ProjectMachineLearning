# Use Case Specification

> Proyek: DormInsight
> Dokumen: Fase 2 - Requirements Engineering
> Versi: 0.1 (Draft)
> Penyusun: Prayshe (Business/System Analyst)
> Terakhir diperbarui: 23 November 2026

## 1. Aktor

| Aktor | Jenis | Deskripsi |
|---|---|---|
| Mahasiswa | Primer | Penghuni Crystal Dormitory yang mengisi survei |
| Pengelola | Primer | Pengelola asrama yang melihat hasil analisis |
| Admin | Primer | Pengelola aplikasi, survei, dan data |
| ML Service | Sekunder (sistem) | Layanan FastAPI yang menyediakan prediksi dan SHAP |

## 2. Daftar Use Case

| ID | Use Case | Aktor | Prioritas |
|---|---|---|---|
| UC-01 | Login | Semua | M |
| UC-02 | Mengelola akun pengguna | Admin | M |
| UC-03 | Mengelola survei dan pertanyaan | Admin | M |
| UC-04 | Mengisi survei | Mahasiswa | M |
| UC-05 | Memantau dan mengekspor data respons | Admin | M |
| UC-06 | Menjalankan analisis ML | Admin, ML Service | M |
| UC-07 | Melihat dashboard hasil analisis | Pengelola | M |
| UC-08 | Mensimulasikan skenario perbaikan | Pengelola, ML Service | S |
| UC-09 | Mengunduh laporan | Pengelola | M |
| UC-10 | Mendaftar akun mandiri | Mahasiswa (calon pengguna), Admin | S |

## 3. Spesifikasi Use Case

### UC-01 Login

| Item | Keterangan |
|---|---|
| Aktor | Mahasiswa, Pengelola, Admin |
| Prakondisi | Pengguna memiliki akun aktif |
| Pascakondisi | Pengguna masuk ke halaman sesuai role |
| FR terkait | FR-001 sampai FR-005, NFR-005 |

**Alur utama:**
1. Pengguna membuka halaman login.
2. Pengguna memasukkan email/NIM dan password.
3. Sistem memverifikasi kredensial dan status akun.
4. Sistem mengarahkan pengguna ke halaman sesuai role.

**Alur alternatif:**
- 3a. Kredensial salah: sistem menampilkan pesan kesalahan umum dan menambah hitungan percobaan gagal.
- 3b. Lima kali gagal berturut-turut: sistem mengunci login sementara.
- 3c. Akun nonaktif: sistem menolak login.
- 4a. Login pertama: sistem meminta pengguna mengganti password.

### UC-02 Mengelola Akun Pengguna

| Item | Keterangan |
|---|---|
| Aktor | Admin |
| Prakondisi | Admin sudah login |
| Pascakondisi | Data akun tersimpan atau diperbarui |
| FR terkait | FR-010 sampai FR-014 |

**Alur utama:**
1. Admin membuka menu Manajemen Akun.
2. Admin memilih tambah akun, impor CSV, ubah, reset password, atau nonaktifkan.
3. Admin mengisi data atau mengunggah file.
4. Sistem memvalidasi dan menyimpan perubahan.

**Alur alternatif:**
- 4a. Email/NIM sudah terdaftar: sistem menolak dan menampilkan baris yang bermasalah.
- 4b. Format CSV salah: sistem menolak file dan menampilkan format yang benar.

### UC-03 Mengelola Survei dan Pertanyaan

| Item | Keterangan |
|---|---|
| Aktor | Admin |
| Prakondisi | Admin sudah login |
| Pascakondisi | Survei tersimpan dengan status draft, aktif, atau ditutup |
| FR terkait | FR-020 sampai FR-027 |

**Alur utama:**
1. Admin membuat survei baru (atau menduplikasi survei lama).
2. Admin mengisi nama periode, tanggal buka, dan tanggal tutup.
3. Admin menambah faktor dan pertanyaan, lalu menandai pertanyaan label target.
4. Admin menyimpan survei sebagai draft.
5. Admin membuka survei; status berubah menjadi aktif.
6. Setelah periode selesai, admin menutup survei.

**Alur alternatif:**
- 3a. Lebih dari satu pertanyaan ditandai label target: sistem menolak.
- 5a. Sudah ada survei aktif lain: sistem menolak pembukaan survei baru.
- 5b. Belum ada pertanyaan label target: sistem menolak pembukaan survei.
- Admin mencoba mengubah pertanyaan pada survei yang sudah memiliki respons: sistem menolak.

### UC-04 Mengisi Survei

| Item | Keterangan |
|---|---|
| Aktor | Mahasiswa |
| Prakondisi | Mahasiswa sudah login, ada survei aktif, mahasiswa belum mengisi survei tersebut |
| Pascakondisi | Jawaban tersimpan tanpa identitas; catatan "sudah mengisi" tersimpan terpisah |
| FR terkait | FR-030 sampai FR-037, NFR-010, NFR-040 |

**Alur utama:**
1. Mahasiswa memilih "Isi Survei".
2. Sistem menampilkan halaman consent.
3. Mahasiswa menyetujui.
4. Sistem menampilkan pertanyaan per bagian faktor dengan indikator progres.
5. Mahasiswa menjawab semua bagian, termasuk pertanyaan label target.
6. Sistem menampilkan ringkasan jawaban.
7. Mahasiswa menekan "Kirim".
8. Sistem memvalidasi, lalu dalam satu transaksi menyimpan respons dan jawaban (tanpa user_id) serta catatan submission (dengan user_id, tanpa jawaban).
9. Sistem menampilkan konfirmasi.

**Alur alternatif:**
- 3a. Mahasiswa tidak menyetujui consent: sistem kembali ke beranda tanpa menyimpan apa pun.
- 7a. Ada pertanyaan wajib yang belum dijawab: sistem menandai pertanyaan tersebut.
- 8a. Survei sudah ditutup saat dikirim: sistem menolak dan menampilkan pesan.
- 8b. Mahasiswa sudah pernah mengirim (misalnya dari tab lain): sistem menolak.

### UC-05 Memantau dan Mengekspor Data Respons

| Item | Keterangan |
|---|---|
| Aktor | Admin |
| Prakondisi | Admin sudah login, survei memiliki respons |
| Pascakondisi | Respons tinjauan ditandai; file CSV terunduh |
| FR terkait | FR-040 sampai FR-044 |

**Alur utama:**
1. Admin membuka menu Data Respons.
2. Sistem menampilkan jumlah respons, target 100, dan total penghuni 300.
3. Sistem menampilkan daftar respons anonim, menandai respons mencurigakan.
4. Admin meninjau dan mengecualikan respons tidak valid jika perlu.
5. Admin menekan "Ekspor CSV" dan sistem mengunduh data anonim.

### UC-06 Menjalankan Analisis ML

| Item | Keterangan |
|---|---|
| Aktor | Admin, ML Service |
| Prakondisi | Survei berstatus ditutup, ML service aktif, model sudah diunggah |
| Pascakondisi | Hasil analisis (prediksi, SHAP, versi model, metrik) tersimpan |
| FR terkait | FR-050 sampai FR-055 |

**Alur utama:**
1. Admin membuka survei yang sudah ditutup dan menekan "Jalankan Analisis".
2. Sistem menghitung skor faktor untuk setiap respons valid.
3. Sistem mengecek `/health` dan `/model/info` pada ML service.
4. Sistem mengirim data ke `/predict/batch` dan menerima probabilitas per respons.
5. Sistem mengirim data ke `/explain/global` dan menerima nilai SHAP per faktor.
6. Sistem menyimpan seluruh hasil dalam satu transaksi beserta versi model dan metrik.
7. Sistem menampilkan status "Analisis selesai".

**Alur alternatif:**
- 3a. ML service tidak merespons: sistem menampilkan pesan "ML service tidak tersedia, coba lagi" dan tidak menyimpan apa pun.
- 4a. Daftar fitur model tidak cocok dengan faktor survei: sistem membatalkan dan menampilkan detail ketidakcocokan.

### UC-07 Melihat Dashboard Hasil Analisis

| Item | Keterangan |
|---|---|
| Aktor | Pengelola |
| Prakondisi | Pengelola sudah login, minimal satu analisis telah dijalankan |
| Pascakondisi | Tidak ada perubahan data |
| FR terkait | FR-060 sampai FR-065, FR-068 |

**Alur utama:**
1. Pengelola membuka dashboard.
2. Sistem menampilkan ringkasan: jumlah responden, tingkat respons, kepuasan rata-rata, proporsi ingin keluar.
3. Sistem menampilkan grafik skor faktor dan peringkat faktor dominan beserta penjelasan.
4. Sistem menampilkan distribusi probabilitas (rendah, sedang, tinggi).
5. Pengelola memilih filter angkatan atau lama tinggal; sistem memperbarui tampilan.

**Alur alternatif:**
- 1a. Belum ada analisis: sistem menampilkan statistik deskriptif saja dan pesan bahwa analisis ML belum dijalankan.
- 5a. Segmen berisi kurang dari 5 responden: sistem menyembunyikan statistik segmen tersebut.

### UC-08 Mensimulasikan Skenario Perbaikan

| Item | Keterangan |
|---|---|
| Aktor | Pengelola, ML Service |
| Prakondisi | Pengelola sudah login, ML service aktif |
| Pascakondisi | Tidak ada data yang disimpan |
| FR terkait | FR-066, NFR-022 |

**Alur utama:**
1. Pengelola membuka menu Simulasi.
2. Sistem menampilkan slider skor setiap faktor dengan nilai awal rata-rata periode terakhir.
3. Pengelola mengubah satu atau beberapa nilai faktor.
4. Sistem mengirim skenario ke `/simulate` dan menampilkan probabilitas sebelum dan sesudah perubahan.

**Catatan:** hasil simulasi adalah perkiraan model, bukan jaminan. Sistem menampilkan catatan ini di halaman simulasi.

### UC-09 Mengunduh Laporan

| Item | Keterangan |
|---|---|
| Aktor | Pengelola |
| Prakondisi | Minimal satu analisis telah dijalankan |
| Pascakondisi | File laporan terunduh |
| FR terkait | FR-070 sampai FR-072 |

**Alur utama:**
1. Pengelola memilih periode survei dan menekan "Unduh Laporan".
2. Pengelola memilih format PDF atau Excel.
3. Sistem membuat laporan berisi periode, jumlah responden, tanggal analisis, versi model, ringkasan, skor faktor, dan faktor dominan.
4. Sistem mengunduh file.

### UC-10 Mendaftar Akun Mandiri

| Item | Keterangan |
|---|---|
| Aktor | Mahasiswa (belum memiliki akun), Admin |
| Prakondisi | Pendaftaran mandiri diaktifkan (`DORM_REGISTRATION_ENABLED=true`) |
| Pascakondisi | Akun Mahasiswa dibuat; aktif setelah disetujui admin |
| FR terkait | FR-015 sampai FR-018 |

**Alur utama:**
1. Calon pengguna membuka halaman login dan memilih "Daftar dengan NIM".
2. Calon pengguna mengisi nama, NIM, email, dan password.
3. Sistem memvalidasi data (NIM dan email unik, domain email bila dibatasi, kekuatan password).
4. Sistem membuat akun berperan Mahasiswa dengan status "Menunggu persetujuan".
5. Admin membuka Manajemen Akun, mencocokkan nama dan NIM dengan daftar penghuni, lalu menekan **Setujui**.
6. Mahasiswa login dan dapat mengisi survei.

**Alur alternatif:**
- 3a. NIM atau email sudah terdaftar: sistem menolak dan menampilkan pesan.
- 5a. Data tidak sesuai daftar penghuni: admin menekan **Tolak**; akun dihapus.
- 4a. Persetujuan dimatikan (`DORM_REGISTRATION_REQUIRE_APPROVAL=false`): akun langsung aktif dan pengguna langsung masuk.

**Catatan:** persetujuan admin disarankan tetap aktif. Tanpa persetujuan, satu orang dapat membuat beberapa akun dan mengisi survei lebih dari sekali, sehingga data analisis menjadi bias.

