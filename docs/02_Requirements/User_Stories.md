# User Stories

> Proyek: DormInsight
> Dokumen: Fase 2 - Requirements Engineering
> Versi: 0.1 (Draft)
> Penyusun: Prayshe (Business/System Analyst)
> Terakhir diperbarui: 23 November 2026

Format: **Sebagai** [role], **saya ingin** [tujuan] **agar** [manfaat].
Estimasi memakai story point (1, 2, 3, 5, 8). Setiap story menjadi issue di GitHub Projects.

## Epic 1: Autentikasi dan Akun

| ID | User Story | Prioritas | SP | PIC | FR |
|---|---|---|---|---|---|
| US-01 | Sebagai pengguna, saya ingin login dengan akun saya agar dapat mengakses fitur sesuai peran saya. | M | 3 | Geraldo | FR-001, FR-003 |
| US-02 | Sebagai pengguna, saya ingin logout agar akun saya aman saat memakai perangkat bersama. | M | 1 | Geraldo | FR-002 |
| US-03 | Sebagai mahasiswa, saya ingin mengganti password awal agar akun saya tidak diketahui orang lain. | S | 2 | Geraldo | FR-005 |
| US-04 | Sebagai admin, saya ingin mengimpor daftar penghuni dari CSV agar tidak perlu membuat 300 akun satu per satu. | S | 5 | Geraldo | FR-011 |
| US-05 | Sebagai admin, saya ingin menonaktifkan akun penghuni yang sudah keluar agar mereka tidak dapat mengisi survei. | M | 2 | Geraldo | FR-013 |

| US-26 | Sebagai mahasiswa, saya ingin mendaftar akun sendiri dengan NIM dan email agar dapat mengisi survei tanpa menunggu akun dibuatkan. | S | 3 | Geraldo | FR-015, FR-016 |
| US-27 | Sebagai admin, saya ingin menyetujui atau menolak pendaftaran agar hanya penghuni Crystal Dormitory yang dapat mengisi survei dan tidak ada akun ganda. | S | 2 | Geraldo | FR-017, FR-018 |

**Acceptance criteria US-26 dan US-27:**
- NIM dan email yang sudah terdaftar ditolak; password minimal 8 karakter berisi huruf dan angka.
- Akun baru berperan Mahasiswa (tidak bisa memilih peran lain) dan berstatus "Menunggu persetujuan".
- Akun yang belum disetujui tidak dapat login dan mendapat pesan yang jelas.
- Admin melihat jumlah pendaftaran tertunda dan dapat menyetujui atau menolaknya.

**Acceptance criteria US-01:**
- Given pengguna aktif dengan kredensial benar, when login, then diarahkan ke halaman sesuai role.
- Given kredensial salah, when login, then muncul pesan "Email/NIM atau password salah" tanpa menyebut bagian mana yang salah.
- Given 5 kali gagal berturut-turut, when mencoba lagi, then login dikunci sementara.

## Epic 2: Manajemen Survei

| ID | User Story | Prioritas | SP | PIC | FR |
|---|---|---|---|---|---|
| US-06 | Sebagai admin, saya ingin membuat periode survei dengan tanggal buka dan tutup agar survei berjalan sesuai jadwal. | M | 3 | Geraldo | FR-020, FR-026 |
| US-07 | Sebagai admin, saya ingin mengatur faktor dan pertanyaan survei agar instrumen sesuai kebutuhan asrama. | M | 5 | Geraldo | FR-021 sampai FR-024 |
| US-08 | Sebagai admin, saya ingin pertanyaan terkunci setelah ada respons agar data antarresponden tetap konsisten. | M | 2 | Geraldo | FR-025 |
| US-09 | Sebagai admin, saya ingin menduplikasi survei lama agar periode berikutnya cepat disiapkan. | S | 3 | Geraldo | FR-027 |

**Acceptance criteria US-07:**
- Admin dapat menambah, mengubah, mengurutkan, dan menghapus pertanyaan selama survei belum memiliki respons.
- Tepat satu pertanyaan Ya/Tidak dapat ditandai sebagai label target; sistem menolak jika lebih dari satu.

## Epic 3: Pengisian Survei

| ID | User Story | Prioritas | SP | PIC | FR |
|---|---|---|---|---|---|
| US-10 | Sebagai mahasiswa, saya ingin membaca penjelasan tujuan dan jaminan anonimitas agar saya merasa aman menjawab dengan jujur. | M | 2 | Cheryl | FR-030 |
| US-11 | Sebagai mahasiswa, saya ingin mengisi survei kepuasan per bagian dengan indikator progres agar saya dapat menyampaikan pengalaman tinggal di asrama tanpa merasa terbebani. | M | 5 | Cheryl, Geraldo | FR-031, FR-032, FR-034 |
| US-12 | Sebagai mahasiswa, saya ingin meninjau jawaban sebelum mengirim agar tidak ada jawaban yang salah pilih. | S | 2 | Cheryl | FR-033 |
| US-13 | Sebagai mahasiswa, saya ingin mendapat konfirmasi bahwa survei sudah terkirim agar saya tahu kewajiban saya selesai. | M | 1 | Cheryl | FR-035 |

**Acceptance criteria US-11:**
- Survei tampil rapi di layar ponsel 360 px.
- Tombol kirim hanya aktif jika semua pertanyaan wajib terjawab.
- Setelah terkirim, mahasiswa yang sama tidak dapat membuka formulir survei periode itu lagi.
- Di database, tabel `responses` dan `answers` tidak berisi `user_id`.

## Epic 4: Manajemen Data dan Analisis ML

| ID | User Story | Prioritas | SP | PIC | FR |
|---|---|---|---|---|---|
| US-14 | Sebagai admin, saya ingin memantau jumlah respons terhadap target 100 agar tahu kapan data cukup untuk dianalisis. | M | 2 | Geraldo | FR-040 |
| US-15 | Sebagai admin, saya ingin respons mencurigakan ditandai otomatis agar kualitas data training terjaga. | S | 3 | Geraldo | FR-042, FR-043 |
| US-16 | Sebagai ML engineer, saya ingin mengekspor respons anonim ke CSV agar dapat melakukan EDA dan training model. | M | 2 | Geraldo | FR-044 |
| US-17 | Sebagai admin, saya ingin menjalankan analisis ML dengan satu tombol agar hasil prediksi dan faktor dominan tersimpan untuk dashboard. | M | 8 | Gerald, Geraldo | FR-050 sampai FR-055 |

**Acceptance criteria US-17:**
- Tombol analisis hanya tersedia untuk survei berstatus ditutup.
- Jika ML service tidak merespons, muncul pesan kesalahan dan tidak ada hasil parsial yang tersimpan.
- Hasil yang tersimpan mencakup versi model, metrik evaluasi, probabilitas per respons, dan SHAP global.

## Epic 5: Dashboard dan Laporan

| ID | User Story | Prioritas | SP | PIC | FR |
|---|---|---|---|---|---|
| US-18 | Sebagai pengelola asrama, saya ingin melihat ringkasan jumlah responden, kepuasan rata-rata, dan proporsi ingin keluar agar memahami kondisi asrama secara cepat. | M | 3 | Cheryl | FR-060 |
| US-19 | Sebagai pengelola asrama, saya ingin melihat faktor dominan keinginan mahasiswa keluar agar saya dapat menentukan prioritas perbaikan. | M | 5 | Cheryl, Gerald | FR-062 |
| US-20 | Sebagai pengelola asrama, saya ingin melihat skor rata-rata tiap faktor agar tahu aspek mana yang dinilai rendah. | M | 3 | Cheryl | FR-061 |
| US-21 | Sebagai pengelola asrama, saya ingin memfilter hasil berdasarkan angkatan dan lama tinggal agar dapat melihat perbedaan antarkelompok. | S | 3 | Cheryl, Geraldo | FR-064, FR-065 |
| US-22 | Sebagai pengelola asrama, saya ingin mensimulasikan perbaikan suatu faktor agar dapat memperkirakan dampaknya sebelum mengalokasikan anggaran. | S | 5 | Gerald, Cheryl | FR-066 |
| US-23 | Sebagai pengelola asrama, saya ingin mengunduh laporan PDF agar dapat dibawa ke rapat pimpinan. | M | 3 | Geraldo | FR-070, FR-072 |
| US-24 | Sebagai pengelola asrama, saya ingin mengunduh data agregat dalam Excel agar dapat diolah lebih lanjut. | M | 2 | Geraldo | FR-071 |
| US-25 | Sebagai pengelola asrama, saya ingin membandingkan dua periode survei agar dapat menilai apakah perbaikan berhasil. | S | 5 | Cheryl, Geraldo | FR-067 |

**Acceptance criteria US-19:**
- Grafik menampilkan peringkat 10 faktor berdasarkan rata-rata nilai SHAP absolut.
- Tiga faktor teratas disertai kalimat penjelasan sederhana, misalnya "Kepuasan terhadap makanan paling berpengaruh terhadap keinginan keluar asrama."
- Versi model dan F1-Score ditampilkan di bagian bawah grafik.

## Ringkasan Backlog

| Prioritas | Jumlah Story | Total SP |
|---|---|---|
| Must | 17 | 52 |
| Should | 10 | 33 |
| **Total** | **27** | **85** |
