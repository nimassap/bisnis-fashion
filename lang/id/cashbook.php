<?php

declare(strict_types=1);

return [

    'title' => 'Buku Kas',
    'subtitle' => 'Semua uang masuk dan keluar bisnis, bisa ditandai per koleksi.',

    'add_in' => 'Uang masuk',
    'add_out' => 'Uang keluar',
    'edit' => 'Ubah catatan',
    'type' => 'Jenis',

    'balance' => 'Saldo kas',
    'balance_hint' => 'Modal awal :capital + masuk − keluar, sepanjang waktu.',
    'balance_no_capital' => 'Masuk − keluar sepanjang waktu. Isi modal awal di Pengaturan > Bisnis.',
    'total_in' => 'Masuk',
    'total_out' => 'Keluar',
    'net' => 'Selisih',

    'all_collections' => 'Semua koleksi',
    'all_categories' => 'Semua kategori',
    'search' => 'Cari keterangan…',
    'entry_count' => '{0} Tidak ada catatan|{1} 1 catatan|[2,*] :count catatan',

    'amount' => 'Nominal',
    'date' => 'Tanggal',
    'description' => 'Keterangan',
    'description_out_placeholder' => 'Contoh: DP konveksi 50%',
    'description_in_placeholder' => 'Contoh: Penjualan Shopee minggu 1',
    'category' => 'Kategori',
    'collection' => 'Koleksi',
    'collection_hint' => 'Supaya pengeluaran terhitung di budget koleksi.',
    'no_collection' => 'Tidak terkait koleksi',
    'vendor' => 'Vendor',
    'receipt' => 'Foto nota',
    'notes' => 'Catatan',
    'uncategorised' => 'Tanpa kategori',
    'from_payment' => 'dari jadwal bayar',

    'saved' => 'Catatan disimpan.',
    'deleted' => 'Catatan dihapus.',
    'delete' => 'Hapus catatan',
    'delete_body' => 'Catatan ":description" akan dihapus permanen.',
    'delete_payment_body' => 'Catatan ":description" berasal dari pembayaran vendor. Kalau dihapus, pembayaran itu kembali berstatus belum lunas.',

    'manage_categories' => 'Kategori',
    'categories_hint' => 'Catatan di kategori yang dihapus tetap ada, menjadi tanpa kategori.',
    'category_name' => 'Nama kategori',
    'icon' => 'Ikon',
    'add_category' => 'Tambah kategori',
    'categories_saved' => 'Kategori disimpan.',

    'empty' => 'Belum ada catatan di periode ini',
    'empty_hint' => 'Catat setiap pembelian kain, DP konveksi, biaya foto, dan penjualan supaya modal terpantau.',

    'columns' => [
        'date' => 'Tanggal',
        'type' => 'Jenis',
        'category' => 'Kategori',
        'description' => 'Keterangan',
        'collection' => 'Koleksi',
        'vendor' => 'Vendor',
        'notes' => 'Catatan',
        'amount' => 'Nominal',
    ],

];
