<?php

declare(strict_types=1);

return [

    'setup_label' => 'Setup brand',
    'setup_short' => 'Setup',
    'position' => 'Step :current dari :total',
    'requirements_title' => 'Syarat menutup step',
    'coming_soon' => 'Isian lengkap step ini menyusul.',

    'requirements' => [
        // Setup
        'target_market' => 'Target market terisi (persona, atau usia dan gaya)',
        'competitors' => 'Minimal :count kompetitor dicatat',
        'price_range' => 'Rentang harga satuan ditentukan',
        'final_name' => 'Nama brand final dipilih',
        'logo' => 'Logo diupload',
        'label_hangtag' => 'Desain label dan hangtag diupload',
        'packaging' => 'Desain kemasan diupload',
        'capital' => 'Modal awal diisi',
        'makers_listed' => 'Minimal 1 konveksi ada di daftar vendor',
        'fabrics_listed' => 'Minimal 1 kain ada di library kain',

        // 1. Model
        'theme' => 'Tema koleksi diisi',
        'both_models' => 'Model cewek dan cowok sudah dibuat',
        'model_photos' => 'Kedua model punya foto atau sketsa',
        'model_colors' => 'Kedua model punya variasi warna',
        'size_charts' => 'Size chart kedua model terisi',
        'planned_quantity' => 'Rencana jumlah produksi kedua model diisi',

        // 2. Konveksi
        'maker_chosen' => 'Konveksi dipilih untuk koleksi ini',
        'sewing_prices' => 'Ongkos jahit kedua model diisi',
        'payment_terms' => 'Syarat bayar (DP) diisi',

        // 3. Kain
        'fabrics_chosen' => 'Kain kedua model dipilih',
        'fabric_consumption' => 'Pemakaian kain kedua model diisi',
        'fabric_prices' => 'Harga kain yang dipilih diketahui',

        // 4. Label, hangtag, kemasan
        'trims_suppliers' => 'Supplier label, hangtag, dan kemasan dipilih',
        'trims_ordered' => 'Pesanan cetak label, hangtag, dan kemasan dicatat',

        // 5. Sampling
        'women_sample_approved' => 'Sampel model cewek di-ACC',
        'men_sample_approved' => 'Sampel model cowok di-ACC',

        // 6. Costing
        'single_prices' => 'Harga satuan kedua model ditentukan',
        'bundle_price' => 'Harga paket couple ditentukan',
        'capital_enough' => 'Modal tersedia mencukupi kebutuhan modal produksi',

        // 7. Produksi
        'purchase_order' => 'PO per ukuran dan warna dibuat',
        'production_done' => 'Semua tahap produksi selesai',
        'qc_recorded' => 'Hasil QC dicatat',
        'goods_received' => 'Barang sudah diterima kembali',

        // 8. Pre-launch
        'product_photos' => 'Foto produk diupload',
        'listings' => 'Minimal 1 listing marketplace aktif',
        'launch_concept' => 'Konsep dan tanggal launching diisi',

        // 9. Launch
        'launched_on' => 'Tanggal launching sebenarnya diisi',
        'first_order' => 'Pesanan pertama tercatat',
        'evaluation' => 'Evaluasi launching ditulis',
    ],

    'needs_models' => 'Buat model cewek dan cowok dulu di step 1.',

    'model' => [
        'theme_title' => 'Tema & moodboard',
        'theme_hint' => 'Ide besar koleksi ini dan gambar-gambar referensinya.',
        'theme' => 'Tema',
        'theme_placeholder' => 'Contoh: Fiore — bunga pastel musim semi',
        'theme_notes' => 'Cerita tema',
        'theme_notes_placeholder' => 'Suasana, warna dominan, untuk acara apa…',
        'moodboard' => 'Moodboard',
        'no_moodboard' => 'Belum ada gambar referensi.',
        'tap_to_upload' => 'Ketuk foto untuk upload',
        'tap_to_change' => 'Ketuk foto untuk mengganti',
        'colors' => 'Variasi warna',
        'color_name' => 'Nama warna',
        'color_hex' => 'Kode warna',
        'pick_color' => 'Ketuk untuk memilih warna',
        'add_color' => 'Warna',
        'fit_notes' => 'Catatan fit',
        'fit_placeholder' => 'Oversize, regular, slim…',
        'open_tech_pack' => 'Size chart, detail jahitan, dan tech pack lengkap',
    ],

    'maker' => [
        'title' => 'Penawaran konveksi',
        'hint' => 'Konveksi hanya menjahit; kain dibeli sendiri. Bandingkan penawaran lalu pilih satu. Ongkos jahit yang dipilih otomatis masuk HPP.',
        'add' => 'Tambah penawaran',
        'edit' => 'Ubah penawaran',
        'empty' => 'Belum ada penawaran',
        'empty_hint' => 'Minta penawaran dari beberapa konveksi di daftarmu.',
        'chosen' => 'Dipilih',
        'choose' => 'Pilih',
        'chosen_toast' => ':name dipilih. Ongkos jahit masuk ke HPP.',
        'delete_confirm' => 'Hapus penawaran ini?',
        'new_maker' => 'Konveksi baru',
        'pick_from_list' => 'Pilih dari daftar',
        'terms_placeholder' => 'Contoh: DP 50% saat PO, pelunasan saat barang diambil',
        'fields' => [
            'vendor_id' => 'Konveksi',
            'sewing_price_women' => 'Ongkos jahit model cewek',
            'sewing_price_men' => 'Ongkos jahit model cowok',
            'sample_cost' => 'Biaya sampel',
            'sample_days' => 'Lama buat sampel (hari)',
            'production_days' => 'Lama produksi (hari)',
            'moq' => 'MOQ (pcs)',
            'dp_percent' => 'DP',
            'payment_terms' => 'Syarat bayar',
            'capacity_per_week' => 'Kapasitas per minggu (pcs)',
            'notes' => 'Catatan',
        ],
    ],

    'fabric' => [
        'title' => 'Kain per model',
        'hint' => 'Kain dibeli sendiri lalu dikirim ke konveksi. Pilih dari library kain, isi pemakaian per pcs, dan lengkapi data supplier.',
        'consumption' => 'Pemakaian kain',
        'consumption_per' => 'Pemakaian (:unit/pcs)',
        'price' => 'Harga kain',
        'unit' => 'Satuan',
        'min_order' => 'Minimum beli',
        'min_order_placeholder' => '1 roll, 5 kg…',
        'delivery_days' => 'Lama kirim (hari)',
        'contact_supplier' => 'Hubungi supplier',
        'added' => 'Kain ditambahkan ke library. Pilih untuk model di bawah.',
    ],

    'sampling' => [
        'empty' => 'Belum ada sampel.',
        'first' => 'Pesan sampel pertama',
        'next' => 'Sampel berikutnya',
        'round' => 'Sampel #:round',
        'size_badge' => 'Ukuran :size',
        'golden_round' => 'ACC di sampel #:round',
        'review' => 'Review',
        'form_title' => 'Sampel :name',
        'size' => 'Ukuran sampel',
        'measurements' => 'Ukur sampel',
        'measurements_hint' => 'Isi ukuran asli sampel. Selisih lebih dari :tolerance cm dari size chart ditandai untuk revisi.',
        'spec' => 'Size chart',
        'actual' => 'Sampel',
        'diff' => 'Selisih',
        'review_points' => 'Poin review',
        'points' => [
            'fit' => 'Ukuran & fit',
            'stitching' => 'Jahitan',
            'color' => 'Warna',
            'comfort' => 'Kenyamanan',
        ],
        'ok' => 'Sesuai',
        'revise' => 'Revisi',
        'photo' => 'Foto sampel',
        'off_points' => 'Perlu revisi ukuran: :points',
        'all_within' => 'Semua ukuran sesuai size chart.',
    ],

    'trims' => [
        'hint' => 'Bandingkan penawaran supplier, pilih satu, lalu catat pesanannya. Harga per pcs otomatis masuk HPP kedua model.',
        'kinds' => [
            'label' => 'Label',
            'hangtag' => 'Hangtag',
            'packaging' => 'Kemasan',
        ],
        'add' => 'Tambah penawaran',
        'empty' => 'Belum ada penawaran.',
        'form_title' => 'Penawaran :kind',
        'new_vendor' => 'Supplier baru',
        'description_placeholder' => 'Contoh: woven label 5×2 cm, satin',
        'chosen_toast' => ':name dipilih. Harga per pcs masuk ke HPP.',
        'order_title' => 'Pesanan ke :name',
        'order_quantity' => 'Jumlah pesan (pcs)',
        'ordered_on' => 'Tanggal pesan',
        'received_on' => 'Tanggal terima',
        'order_total' => 'Total pesanan',
        'planned_hint' => 'Rencana produksi :count pcs.',
        'fields' => [
            'vendor_id' => 'Supplier',
            'description' => 'Spesifikasi',
            'unit_price' => 'Harga per pcs',
            'moq' => 'MOQ (pcs)',
            'lead_time_days' => 'Lama pengerjaan (hari)',
            'notes' => 'Catatan',
        ],
    ],

    'setup' => [
        'saved' => 'Tersimpan.',

        'target_title' => 'Target market',
        'target_hint' => 'Untuk siapa brand ini dibuat.',
        'persona_hint' => 'Ceritakan satu pasangan pembeli ideal: siapa, di mana, kapan memakai baju couple.',

        'competitors_title' => 'Kompetitor',
        'competitors_hint' => 'Brand couple atau fashion lain yang menyasar pembeli yang sama. Minimal 3.',
        'add_competitor' => 'Tambah kompetitor',
        'price_from' => 'Harga dari',
        'price_to' => 'Harga sampai',

        'positioning_title' => 'Positioning & rentang harga',
        'positioning_hint' => 'Di mana brand ini berdiri dibanding kompetitor, dan kisaran harganya.',

        'names_title' => 'Nama brand',
        'names_hint' => 'Kumpulkan kandidat, cek ketersediaannya, lalu pilih satu sebagai nama final. Nama final otomatis jadi nama brand di aplikasi.',
        'add_name' => 'Tambah kandidat',
        'final' => 'Final',
        'unchecked' => 'Belum dicek',
        'free' => 'Tersedia',
        'taken' => 'Terpakai',
        'checks' => [
            'instagram_free' => 'Instagram',
            'tiktok_free' => 'TikTok',
            'domain_free' => 'Domain',
            'trademark_free' => 'Merek DJKI',
        ],

        'assets_title' => 'Desain brand',
        'assets_hint' => 'Upload logo, label, hangtag, dan kemasan. Boleh beberapa versi.',
        'assets' => [
            'logo' => 'Logo',
            'label' => 'Label',
            'hangtag' => 'Hangtag',
            'packaging' => 'Kemasan',
        ],
        'upload' => 'Upload',
        'no_assets' => 'Belum ada file.',
        'delete_asset' => 'Hapus file ini?',

        'kit_title' => 'Brand kit',
        'kit_hint' => 'Tagline, warna, font, dan gaya bahasa brand.',
        'brand_colors' => 'Warna brand',
        'color_name' => 'Nama warna',
        'color_picker' => 'Pilih warna',
        'add_color' => 'Tambah warna',

        'accounts_title' => 'Akun media sosial & toko',
        'accounts_hint' => 'Username atau link akun brand.',
        'accounts_field' => 'Akun',
        'platforms' => [
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'shopee' => 'Shopee',
            'tiktok_shop' => 'TikTok Shop',
            'tokopedia' => 'Tokopedia',
            'website' => 'Website',
            'whatsapp' => 'WhatsApp bisnis',
        ],
        'platform_placeholders' => [
            'instagram' => '@namabrand',
            'tiktok' => '@namabrand',
            'shopee' => 'shopee.co.id/namabrand',
            'tiktok_shop' => 'Nama toko',
            'tokopedia' => 'tokopedia.com/namabrand',
            'website' => 'namabrand.com',
            'whatsapp' => '0812…',
        ],

        'capital_title' => 'Modal & target margin',
        'capital_hint' => 'Dipakai untuk saldo kas, harga saran, dan cek kecukupan modal tiap koleksi.',

        'lists_title' => 'Daftar konveksi & kain',
        'lists_hint' => 'Catat semua konveksi dan kain yang dijajaki. Pilihan untuk tiap koleksi ditentukan di step 2 dan 3.',
        'makers' => 'Konveksi',
        'fabrics' => 'Kain & supplier',
        'listed' => '{0} Belum ada|[1,*] :count dicatat',
        'fabrics_count' => ':fabrics kain · :suppliers supplier',

        'placeholders' => [
            'target_age' => '20–30 tahun',
            'target_gender' => 'Pasangan muda',
            'target_cities' => 'Jabodetabek, Bandung',
            'target_style' => 'Casual, earth tone',
            'target_budget' => 'Rp200–400 rb per baju',
            'persona' => 'Contoh: pasangan baru menikah, suka jalan-jalan dan foto di kafe…',
            'link' => 'Link toko atau Instagram',
            'positioning' => 'Contoh: baju couple linen yang nyaman dipakai harian, harga menengah.',
            'fonts' => 'Contoh: Playfair Display, Inter',
            'tone' => 'Contoh: hangat, santai, pakai "kamu"',
        ],

        'fields' => [
            'target' => [
                'target_age' => 'Usia',
                'target_gender' => 'Gender / tipe pasangan',
                'target_cities' => 'Kota',
                'target_style' => 'Gaya',
                'target_budget' => 'Daya beli',
                'persona' => 'Persona pembeli',
            ],
            'competitors' => [
                '*' => [
                    'name' => 'Nama kompetitor',
                    'link' => 'Link',
                    'price_min' => 'Harga dari',
                    'price_max' => 'Harga sampai',
                    'strengths' => 'Kelebihan',
                    'weaknesses' => 'Kekurangan',
                ],
            ],
            'positioning' => [
                'positioning' => 'Kalimat positioning',
                'price_single_min' => 'Harga satuan dari',
                'price_single_max' => 'Harga satuan sampai',
                'price_bundle_min' => 'Harga paket couple dari',
                'price_bundle_max' => 'Harga paket couple sampai',
            ],
            'names' => [
                '*' => [
                    'name' => 'Kandidat nama',
                    'notes' => 'Catatan',
                ],
            ],
            'kit' => [
                'tagline' => 'Tagline',
                'tone' => 'Gaya bahasa',
                'fonts' => 'Font',
            ],
            'colors' => [
                '*' => [
                    'name' => 'Nama warna',
                    'hex' => 'Kode warna',
                ],
            ],
        ],
    ],

];
