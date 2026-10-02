<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Competition Profile
    |--------------------------------------------------------------------------
    */
    'name' => env('COMPETITION_NAME', 'Data Labeler Workspace'),
    'description' => env('COMPETITION_DESCRIPTION', 'Platform Anotasi Dataset Terdistribusi'),
    
    /*
    |--------------------------------------------------------------------------
    | Lease & Buffer Settings
    |--------------------------------------------------------------------------
    | lease_duration_minutes: Berapa lama gambar direservasi untuk satu annotator.
    | buffer_batch_size: Jumlah gambar yang di-prefetch sekaligus ke memori browser.
    */
    'lease_duration_minutes' => (int) env('LEASE_DURATION_MINUTES', 3),
    'buffer_batch_size' => (int) env('BUFFER_BATCH_SIZE', 6),

    /*
    |--------------------------------------------------------------------------
    | Multi-labeler / Inter-annotator Agreement (Butir 9)
    |--------------------------------------------------------------------------
    | Saat WorkspaceSetting::multi_labeler_mode = true, satu gambar harus
    | dikumpulkan dari `required_labelers` labeler berbeda sebelum consensus
    | dihitung. Kalau semua suara sama -> auto-approved. Kalau beda ->
    | status jadi 'dispute' dan masuk antrean resolusi admin.
    |
    | Mode ini hanya untuk mengukur agreement; kunci jawaban tim tetap
    | dihasilkan oleh consensus + resolusi admin.
    */
    'required_labelers' => (int) env('REQUIRED_LABELERS', 2),

    /*
    |--------------------------------------------------------------------------
    | Label Classes (Dinamis untuk Kompetisi Masa Depan)
    |--------------------------------------------------------------------------
    | Cukup ubah array ini untuk kompetisi apa pun tanpa perlu bongkar kode PHP/JS!
    */
    'classes' => [
        [
            'id' => 0,
            'name' => 'Aman',
            'badge' => 'Daur Ulang (Label: 0)',
            'shortcut_label' => 'Q / 1',
            'keys' => ['0', '1', 'q', 'Q'],
            'color' => 'emerald',
            'desc' => 'Sampah kering / aman untuk didaur ulang',
            'icon' => 'recycle',
        ],
        [
            'id' => 1,
            'name' => 'Rusak',
            'badge' => 'Elektronik (Label: 1)',
            'shortcut_label' => 'W / 2',
            'keys' => ['2', 'w', 'W'],
            'color' => 'blue',
            'desc' => 'Elektronik / beracun / rusak / B3',
            'icon' => 'bolt',
        ],
        [
            'id' => 2,
            'name' => 'Lainnya',
            'badge' => 'Organik (Label: 2)',
            'shortcut_label' => 'E / 3',
            'keys' => ['3', 'e', 'E'],
            'color' => 'amber',
            'desc' => 'Sampah organik / sisa makanan / daun',
            'icon' => 'sparkles',
        ],
    ],
];
