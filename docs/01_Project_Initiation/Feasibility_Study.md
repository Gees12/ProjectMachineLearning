# Feasibility Study

> Proyek: DormInsight
> Dokumen: Fase 1 - Project Initiation
> Versi: 0.2 (Draft)
> Terakhir diperbarui: 23 November 2026

## 1. Kelayakan Teknis

| Komponen | Teknologi | Penilaian |
|---|---|---|
| Frontend | Laravel Blade, Bootstrap | Layak. Dokumentasi lengkap dan umum dipakai |
| Backend | Laravel | Layak. Autentikasi dan role tersedia lewat paket bawaan/ekosistem |
| Database | MySQL | Layak. Cocok untuk data survei terstruktur |
| ML Service | Python, FastAPI, scikit-learn, SHAP | Layak. Library open-source dan ringan |
| Integrasi | REST API (JSON) antara Laravel dan FastAPI | Layak. Pola integrasi standar |
| Version Control | Git dan GitHub | Layak |

Kebutuhan perangkat minimum untuk pengembangan: laptop dengan RAM 8 GB, PHP, Composer, MySQL, Python 3.10 atau lebih baru, dan VS Code.

Risiko teknis: tim perlu memahami dua bahasa (PHP dan Python). Mitigasi: pembagian role yang jelas dan kontrak API yang ditetapkan di Fase 3.

**Kesimpulan: layak secara teknis.**

## 2. Kelayakan Operasional

| Aspek | Penilaian |
|---|---|
| Mahasiswa | Survei diakses lewat browser dan ponsel tanpa instalasi |
| Pengelola | Dashboard dirancang sederhana; disertai User Manual |
| Administrator | Pengelolaan survei melalui antarmuka web |
| Dukungan institusi | Perlu izin pengelola Crystal Dormitory untuk penyebaran survei |

Risiko operasional: tingkat partisipasi survei rendah. Mitigasi: koordinasi dengan pengelola asrama, survei singkat (target di bawah 10 menit), dan penjelasan bahwa jawaban anonim.

**Kesimpulan: layak secara operasional, dengan syarat izin penyebaran survei diperoleh.**

## 3. Kelayakan Waktu

Proyek dibagi menjadi 6 sprint (lihat [Project_Timeline.md](Project_Timeline.md)). Pengembangan ML dan web berjalan paralel setelah System Design, sehingga pengumpulan data tidak menunda pengembangan aplikasi.

Titik kritis: data survei harus terkumpul paling lambat akhir Sprint 3 agar training dan evaluasi model dapat dilakukan di Sprint 4.

**Kesimpulan: layak, jika survei mulai disebar paling lambat Sprint 3.**

## 4. Kelayakan Data (Machine Learning)

Bagian ini paling menentukan, karena seluruh fitur analisis AI bergantung pada data.

### 4.1 Kebutuhan Data

| Item | Kebutuhan |
|---|---|
| Jenis pembelajaran | Supervised learning (klasifikasi biner) |
| Fitur input | Penilaian faktor kepuasan (fasilitas, kebersihan, makanan, biaya, peraturan, privasi, keamanan, lingkungan sosial, pelayanan pengelola) dan data demografis non-identitas |
| Label target | `ingin_keluar` (1 = Ya, 0 = Tidak), ditanyakan langsung di survei |
| Sumber | Survei penghuni Crystal Dormitory |
| Populasi | 300 penghuni Crystal Dormitory |

### 4.2 Batas Minimum Data (Pedoman Awal)

Pedoman praktis yang sering dipakai untuk model klasifikasi adalah sekitar 10 sampai 20 sampel kelas minoritas untuk setiap fitur. Dengan sekitar 10 fitur dan proporsi "ingin keluar" diperkirakan 20 sampai 30 persen, idealnya dibutuhkan kira-kira 150 sampai 300 respons valid. Angka ini adalah pedoman awal, bukan aturan pasti, dan akan dievaluasi ulang setelah data terkumpul.

Karena populasi Crystal Dormitory hanya 300 penghuni, target ditetapkan sebagai berikut:

| Target | Jumlah Respons Valid | Tingkat Respons |
|---|---|---|
| Minimum | 100 | sekitar 33% |
| Ideal | 150 atau lebih | 50% atau lebih |

Konsekuensinya:

1. Hampir separuh penghuni perlu mengisi survei, sehingga dukungan pengelola asrama untuk penyebaran sangat penting.
2. Jumlah fitur perlu dijaga tetap ringkas (sekitar 8 sampai 12 faktor) agar sesuai dengan ukuran data.
3. Evaluasi wajib memakai stratified k-fold cross-validation, bukan hanya satu kali pembagian train/test, karena data relatif kecil.

### 4.3 Rencana Keputusan Berdasarkan Jumlah Data

| Kondisi Data | Keputusan |
|---|---|
| Respons valid memadai dan kelas tidak terlalu timpang | Lanjutkan perbandingan Logistic Regression, Decision Tree, Random Forest |
| Respons terbatas (misalnya di bawah 100) | Prioritaskan model sederhana (Logistic Regression), gunakan cross-validation, laporkan keterbatasan |
| Kelas sangat timpang | Gunakan class weighting atau resampling, utamakan F1-Score dan Recall kelas minoritas |
| Data sangat sedikit | Fokus pada analisis statistik deskriptif dan korelasi; prediksi ML ditandai eksperimental |

### 4.4 Baseline Evaluasi

Model dianggap bermanfaat jika F1-Score kelas "ingin keluar" lebih baik daripada baseline sederhana (misalnya model yang selalu menebak kelas mayoritas). Target angka pasti ditetapkan setelah distribusi label diketahui.

### 4.5 Privasi dan Etika

1. Survei anonim; data identitas tidak digunakan sebagai fitur.
2. Responden diberi penjelasan tujuan survei dan persetujuan (informed consent) sebelum mengisi.
3. Hasil hanya disajikan secara agregat.
4. Data mentah tidak diunggah ke repository publik.

**Kesimpulan: layak dengan syarat target respons minimum tercapai dan label target dikumpulkan sebagai pertanyaan tersendiri.**

## 5. Kelayakan Biaya

| Item | Estimasi |
|---|---|
| Perangkat lunak | Gratis (open-source) |
| Hosting | Gratis, menggunakan layanan hosting gratis untuk keperluan demo (penyedia dipilih di Fase 7) |
| Desain UI/UX | Figma versi gratis |

Catatan hosting gratis:

1. Laravel, MySQL, dan FastAPI kemungkinan perlu di-host di layanan terpisah, karena paket gratis biasanya terbatas pada satu jenis layanan.
2. Paket gratis umumnya memiliki batas sumber daya dan dapat "tidur" saat tidak digunakan, sehingga respons pertama bisa lambat. Hal ini cukup untuk demo, tetapi tidak untuk penggunaan produksi.
3. Untuk demo publik, gunakan data dummy atau data yang sudah dianonimkan, bukan data mentah survei mahasiswa.

**Kesimpulan: layak tanpa biaya untuk tahap demo.**

## 6. Kesimpulan Umum

DormInsight layak dikembangkan secara teknis, operasional, waktu, dan biaya. Risiko terbesar ada pada **ketersediaan dan kualitas data survei**, sehingga izin penyebaran survei dan perancangan instrumen harus diprioritaskan sejak Sprint 1 dan Sprint 2.
