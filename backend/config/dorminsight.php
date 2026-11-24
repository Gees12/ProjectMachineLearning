<?php

return [
    // Jumlah penghuni Crystal Dormitory (dipakai untuk tingkat respons)
    'population' => (int) env('DORM_POPULATION', 300),

    // Target minimum respons valid sebelum analisis ML (Feasibility Study 4.2)
    'min_valid_responses' => 100,

    // Segmen dengan responden di bawah angka ini disembunyikan (FR-065)
    'min_segment_size' => 5,

    // Respons lebih cepat dari ini ditandai mencurigakan (FR-042)
    'min_duration_seconds' => 120,

    // Ambang kategori risiko (Data Dictionary B.6)
    'risk_thresholds' => ['sedang' => 0.40, 'tinggi' => 0.70],

    // Stabilitas SHAP minimal agar faktor disebut "konsisten berpengaruh"
    'stable_top3_frequency' => 0.5,

    // Akun admin awal (dipakai DatabaseSeeder). Di server, isi ADMIN_INITIAL_PASSWORD.
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@dorminsight.test'),
        'initial_password' => env('ADMIN_INITIAL_PASSWORD'),
    ],

    // Pendaftaran mandiri mahasiswa (FR-015 sampai FR-018)
    'registration' => [
        'enabled' => (bool) env('DORM_REGISTRATION_ENABLED', true),
        // true: akun baru menunggu persetujuan admin sebelum bisa login (disarankan,
        // mencegah satu orang membuat banyak akun dan mengisi survei berkali-kali)
        'require_approval' => (bool) env('DORM_REGISTRATION_REQUIRE_APPROVAL', true),
        // Batasi domain email, mis. "unklab.ac.id". Kosongkan untuk mengizinkan semua.
        'email_domain' => env('DORM_REGISTRATION_EMAIL_DOMAIN'),
    ],

    'ml' => [
        'url' => rtrim(env('ML_SERVICE_URL', 'http://127.0.0.1:8001'), '/'),
        'key' => env('ML_API_KEY', ''),
        'timeout' => (int) env('ML_TIMEOUT', 60),
        'max_records' => 1000,
    ],
];
