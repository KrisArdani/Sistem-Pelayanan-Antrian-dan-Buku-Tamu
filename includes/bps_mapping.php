<?php
// SPST BPS Kota Tegal - Konfigurasi Pemetaan Variabel Web API BPS ke WebGIS

return [
    'bps_kemiskinan' => [
        'name' => 'Kemiskinan & Kesejahteraan (BPS Live)',
        'icon' => 'shield',
        'color' => '#dc2626', // Red
        'source' => 'bps_api',
        'bps_var_id' => 33,
        'subject_name' => 'Kemiskinan',
        'default_indicator' => 'persen_miskin',
        'indicators' => [
            'persen_miskin' => [
                'name' => 'Persentase Penduduk Miskin (P0)',
                'unit' => '%',
                'bps_var_id' => 33,
                'turvar' => 44,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Persentase penduduk Kota Tegal yang berada di bawah Garis Kemiskinan'
            ],
            'garis_kemiskinan' => [
                'name' => 'Garis Kemiskinan',
                'unit' => 'Rp/Kapita/Bulan',
                'bps_var_id' => 33,
                'turvar' => 42,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Nilai pengeluaran minimum kebutuhan makanan dan non-makanan per kapita per bulan'
            ],
            'penduduk_miskin' => [
                'name' => 'Jumlah Penduduk Miskin',
                'unit' => 'Ribu Orang',
                'bps_var_id' => 33,
                'turvar' => 43,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Jumlah penduduk dengan pengeluaran per kapita di bawah Garis Kemiskinan'
            ],
            'gini_rasio' => [
                'name' => 'Gini Rasio (Ketimpangan)',
                'unit' => 'Poin',
                'bps_var_id' => 33,
                'turvar' => 47,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Ukuran ketimpangan pengeluaran penduduk (rentang 0 sampai 1)'
            ],
            'kedalaman_kemiskinan' => [
                'name' => 'Indeks Kedalaman Kemiskinan (P1)',
                'unit' => 'Poin',
                'bps_var_id' => 33,
                'turvar' => 45,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Ukuran rata-rata kesenjangan pengeluaran masing-masing penduduk miskin terhadap garis kemiskinan'
            ],
            'keparahan_kemiskinan' => [
                'name' => 'Indeks Keparahan Kemiskinan (P2)',
                'unit' => 'Poin',
                'bps_var_id' => 33,
                'turvar' => 46,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Gambaran mengenai penyebaran pengeluaran di antara penduduk miskin'
            ],
        ]
    ],

    'bps_ipm' => [
        'name' => 'Indeks Pembangunan Manusia (BPS Live)',
        'icon' => 'emoji_events',
        'color' => '#7c3aed', // Violet
        'source' => 'bps_api',
        'bps_var_id' => 104,
        'subject_name' => 'Indeks Pembangunan Manusia',
        'default_indicator' => 'ipm',
        'indicators' => [
            'ipm' => [
                'name' => 'Indeks Pembangunan Manusia (IPM)',
                'unit' => 'Poin',
                'bps_var_id' => 104,
                'turvar' => 74,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Capaian komprehensif pembangunan kualitas hidup manusia di Kota Tegal'
            ],
            'ahh' => [
                'name' => 'Angka Harapan Hidup saat Lahir (AHH)',
                'unit' => 'Tahun',
                'bps_var_id' => 104,
                'turvar' => 70,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Rata-rata perkiraan usia yang dapat ditempuh oleh seorang bayi baru lahir'
            ],
            'hls' => [
                'name' => 'Harapan Lama Sekolah (HLS)',
                'unit' => 'Tahun',
                'bps_var_id' => 104,
                'turvar' => 71,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Lama sekolah yang diharapkan dapat dirasakan oleh anak usia 7 tahun ke atas'
            ],
            'rls' => [
                'name' => 'Rata-rata Lama Sekolah (RLS)',
                'unit' => 'Tahun',
                'bps_var_id' => 104,
                'turvar' => 72,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Jumlah rata-rata tahun yang dihabiskan oleh penduduk usia 25+ tahun dalam pendidikan'
            ],
            'pengeluaran_kapita' => [
                'name' => 'Pengeluaran Per Kapita Disesuaikan',
                'unit' => 'Ribu Rp/Tahun',
                'bps_var_id' => 104,
                'turvar' => 73,
                'vervar_kota' => 3376,
                'vervar_jateng' => 3399,
                'desc' => 'Kemampuan daya beli riil per kapita masyarakat Kota Tegal dalam setahun'
            ],
        ]
    ],

    'bps_ketenagakerjaan' => [
        'name' => 'Ketenagakerjaan (BPS Live)',
        'icon' => 'work',
        'color' => '#0891b2', // Cyan
        'source' => 'bps_api',
        'default_indicator' => 'tpt',
        'indicators' => [
            'tpt' => [
                'name' => 'Tingkat Pengangguran Terbuka (TPT)',
                'unit' => '%',
                'bps_var_id' => 142,
                'turvar' => 27,
                'vervar_kota' => 1,
                'desc' => 'Persentase angkatan kerja di Kota Tegal yang sedang mencari pekerjaan'
            ],
            'tpak' => [
                'name' => 'Tingkat Partisipasi Angkatan Kerja (TPAK)',
                'unit' => '%',
                'bps_var_id' => 141,
                'turvar' => 27,
                'vervar_kota' => 1,
                'desc' => 'Persentase penduduk usia kerja di Kota Tegal yang aktif secara ekonomi'
            ],
            'jumlah_penganggur' => [
                'name' => 'Banyaknya Pengangguran Terbuka',
                'unit' => 'Jiwa',
                'bps_var_id' => 143,
                'turvar' => 27,
                'vervar_kota' => 1,
                'desc' => 'Jumlah riil orang yang menganggur di Kota Tegal'
            ],
        ]
    ],

    'bps_ekonomi' => [
        'name' => 'PDRB & Pertumbuhan Ekonomi (BPS Live)',
        'icon' => 'account_balance',
        'color' => '#ca8a04', // Yellow-gold
        'source' => 'bps_api',
        'default_indicator' => 'laju_pdrb',
        'indicators' => [
            'laju_pdrb' => [
                'name' => 'Laju Pertumbuhan Ekonomi (PDRB)',
                'unit' => '%',
                'bps_var_id' => 79,
                'turvar' => 0,
                'vervar_kota' => 18,
                'desc' => 'Kecepatan pertumbuhan ekonomi Kota Tegal dibandingkan tahun sebelumnya'
            ],
            'pdrb_adhb' => [
                'name' => 'PDRB Atas Dasar Harga Berlaku (ADHB)',
                'unit' => 'Milyar Rupiah',
                'bps_var_id' => 178,
                'turvar' => 0,
                'vervar_kota' => 18,
                'desc' => 'Total nilai tambah seluruh barang dan jasa di Kota Tegal atas dasar harga pasar berjalan'
            ],
            'pdrb_adhk' => [
                'name' => 'PDRB Atas Dasar Harga Konstan (ADHK)',
                'unit' => 'Milyar Rupiah',
                'bps_var_id' => 179,
                'turvar' => 0,
                'vervar_kota' => 18,
                'desc' => 'Total nilai tambah seluruh barang dan jasa di Kota Tegal atas dasar harga konstan tahun 2010'
            ],
        ]
    ],
];
