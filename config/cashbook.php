<?php

declare(strict_types=1);

return [

    // Categories a new business starts with, per direction, with an icon.
    'default_categories' => [
        'out' => [
            'Kain' => 'layers',
            'Ongkos jahit' => 'shirt',
            'Sampel' => 'shirt',
            'Label & hangtag' => 'tag',
            'Packaging' => 'gift',
            'Foto & konten' => 'camera',
            'Iklan' => 'zap',
            'Endorse/KOL' => 'users',
            'Ongkir' => 'car',
            'Operasional' => 'briefcase',
            'Lainnya' => 'list',
        ],
        'in' => [
            'Penjualan' => 'shopping-bag',
            'Setoran modal' => 'piggy-bank',
            'Lainnya' => 'list',
        ],
    ],

    // Icons the owner can pick for a category.
    'icons' => [
        'layers', 'shirt', 'tag', 'gift', 'camera', 'zap', 'users', 'car', 'briefcase',
        'shopping-bag', 'piggy-bank', 'banknote', 'hand-coins', 'receipt', 'phone', 'globe', 'list',
    ],

    // A vendor payment shows on the dashboard this many days before it is due.
    'payment_lookahead_days' => 7,

    'export' => [
        'max_rows' => 50000,
        'chunk_size' => 1000,
    ],

];
