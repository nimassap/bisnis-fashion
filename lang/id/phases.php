<?php

declare(strict_types=1);

return [

    'foundation' => [
        'name' => 'Fondasi Brand',
        'summary' => 'Riset target market, positioning, nama brand, dan brand kit.',
        'tasks' => [
            'research_market' => 'Riset target market: usia, gaya, daya beli',
            'research_competitors' => 'Analisis minimal 5 kompetitor: harga, gaya, channel',
            'positioning' => 'Tentukan positioning dan rentang harga',
            'name_candidates' => 'Kumpulkan kandidat nama brand',
            'name_check' => 'Cek ketersediaan nama: IG, TikTok, domain, merek DJKI',
            'name_final' => 'Putuskan nama brand final',
            'logo' => 'Buat logo',
            'brand_kit' => 'Tetapkan warna, font, dan tone brand',
            'label_design' => 'Desain label brand',
            'hangtag_design' => 'Desain hangtag brand',
            'trademark' => 'Daftarkan merek ke DJKI',
        ],
    ],

    'design' => [
        'name' => 'Desain & Material',
        'summary' => 'Model cewek dan cowok, tech pack, riset kain, size chart, deskripsi.',
        'tasks' => [
            'moodboard' => 'Buat moodboard tema couple',
            'models' => 'Tentukan model cewek dan cowok',
            'matching' => 'Pastikan kedua model serasi: warna, motif, detail',
            'tech_pack' => 'Lengkapi tech pack model cewek dan cowok',
            'fabric_research' => 'Riset kain dan kumpulkan swatch',
            'fabric_test' => 'Tes kain: susut, luntur, kenyamanan',
            'fabric_final' => 'Pilih kain final',
            'size_chart' => 'Buat size chart cewek dan cowok',
            'description' => 'Tulis deskripsi produk dan cerita couple-nya',
        ],
    ],

    'sourcing' => [
        'name' => 'Sourcing & Sampel',
        'summary' => 'Cari konveksi, tracking sampel, vendor label dan hangtag.',
        'tasks' => [
            'maker_shortlist' => 'Shortlist minimal 3 konveksi',
            'maker_quotes' => 'Minta penawaran: MOQ, ongkos jahit, lead time',
            'sample_order' => 'Pesan sampel model cewek dan cowok',
            'sample_review' => 'Review dan revisi sampel',
            'sample_approved' => 'Sampel cewek dan cowok disetujui',
            'maker_deal' => 'Deal dengan konveksi',
            'label' => 'Pesan label untuk produksi ini',
            'hangtag' => 'Pesan hangtag untuk produksi ini',
            'packaging' => 'Siapkan packaging, termasuk kemasan paket couple',
        ],
    ],

    'costing' => [
        'name' => 'Costing & Pricing',
        'summary' => 'Hitung modal/HPP, harga jual per channel, BEP.',
        'tasks' => [
            'cogs' => 'Hitung HPP model cewek dan cowok',
            'channel_fees' => 'Catat fee tiap channel penjualan',
            'price' => 'Tentukan harga satuan dan harga paket couple',
            'margin_check' => 'Pastikan margin memenuhi target',
            'break_even' => 'Hitung BEP dan total kebutuhan modal',
            'capital_ready' => 'Modal produksi tersedia',
        ],
    ],

    'production' => [
        'name' => 'Produksi & Pre-launch',
        'summary' => 'PO konveksi, QC, foto produk, setup platform promosi.',
        'tasks' => [
            'purchase_order' => 'Kirim PO ke konveksi: jumlah per ukuran cewek dan cowok',
            'production_tracking' => 'Pantau progres produksi',
            'quality_check' => 'QC hasil produksi',
            'photoshoot' => 'Foto produk couple',
            'launch_content' => 'Siapkan konten launching',
            'platform_setup' => 'Setup toko online dan akun media sosial',
            'teaser' => 'Jalankan teaser campaign',
        ],
    ],

    'launch' => [
        'name' => 'Launch & Operasional',
        'summary' => 'Promosi berjalan, penjualan, stok, monitoring KPI.',
        'tasks' => [
            'launch_day' => 'Launching koleksi',
            'ads' => 'Jalankan iklan',
            'endorsement' => 'Kerja sama endorse/KOL',
            'first_review' => 'Evaluasi penjualan 2 minggu pertama',
            'restock_plan' => 'Rencanakan restock atau koleksi berikutnya',
        ],
    ],

    // Screens
    'checklist' => 'Checklist',
    'brand_subtitle' => 'Dikerjakan sekali untuk seluruh brand. Fase Desain di tiap koleksi baru bisa ditutup setelah fase ini selesai.',

    'status' => [
        'done' => 'Selesai',
        'active' => 'Berjalan',
        'upcoming' => 'Berikutnya',
    ],

    'progress' => ':done dari :total task',
    'gates_progress' => 'Gate :done/:total',
    'overdue_count' => '{1} :count task telat|[2,*] :count task telat',
    'target_on' => 'Target selesai fase',
    'target_on_label' => 'Target :date',
    'completed_on' => 'Selesai :date',

    'gate' => 'Gate',
    'gate_hint' => 'Task gate wajib selesai sebelum fase bisa ditutup.',
    'overdue' => 'Telat',
    'due_label' => 'Tenggat :date',

    'add_task' => 'Tambah task',
    'edit_task' => 'Ubah task',
    'task_title' => 'Task',
    'task_notes' => 'Catatan',
    'task_due_on' => 'Tenggat',
    'task_is_gate' => 'Jadikan gate',
    'task_saved' => 'Task disimpan.',
    'task_deleted' => 'Task dihapus.',
    'delete_task' => 'Hapus task',
    'delete_task_body' => 'Task ":title" akan dihapus permanen.',
    'no_tasks' => 'Belum ada task di fase ini.',
    'mark_done' => 'Tandai selesai',
    'mark_undone' => 'Tandai belum selesai',

    'complete_phase' => 'Tutup fase',
    'complete_phase_body' => 'Fase ":name" ditandai selesai, dan fase berikutnya menjadi fase berjalan.',
    'phase_completed' => 'Fase ":name" selesai.',
    'reopen_phase' => 'Buka kembali fase',
    'phase_reopened' => 'Fase ":name" dibuka kembali.',
    'blocked_previous' => 'Tutup fase ":name" dulu.',
    'blocked_gates' => '{1} Masih ada :count task gate yang belum selesai.|[2,*] Masih ada :count task gate yang belum selesai.',
    'blocked_brand_reopen' => 'Sudah ada koleksi yang menutup fase Desain, jadi fase brand tidak bisa dibuka kembali.',
    'blocked_next_done' => 'Fase berikutnya sudah selesai, jadi fase ini tidak bisa dibuka kembali.',
    'ready_to_complete' => 'Semua gate selesai. Fase ini siap ditutup.',

    'decisions' => 'Catatan keputusan',
    'decisions_hint' => 'Simpan alasan di balik pilihan penting, supaya bisa ditelusuri nanti.',
    'add_decision' => 'Catat keputusan',
    'edit_decision' => 'Ubah keputusan',
    'decision_title' => 'Keputusan',
    'decision_title_placeholder' => 'Contoh: Pakai linen dari Supplier B',
    'decision_reason' => 'Alasan',
    'decision_reason_placeholder' => 'Contoh: Supplier A susut 6% setelah dicuci, B hanya 2%.',
    'decided_on' => 'Tanggal',
    'decision_saved' => 'Keputusan dicatat.',
    'decision_deleted' => 'Keputusan dihapus.',
    'delete_decision' => 'Hapus keputusan',
    'delete_decision_body' => 'Keputusan ":title" akan dihapus permanen.',
    'no_decisions' => 'Belum ada keputusan yang dicatat di fase ini.',

];
