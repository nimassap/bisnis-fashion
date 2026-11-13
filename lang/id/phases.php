<?php

declare(strict_types=1);

return [

    'setup' => [
        'name' => 'Setup Brand',
        'summary' => 'Target market, kompetitor, nama brand, desain, akun, modal, serta daftar konveksi dan kain.',
        'tasks' => [],
    ],

    'model' => [
        'name' => 'Buat Model',
        'summary' => 'Tema, model cewek dan cowok, warna, size chart, aksesoris, dan rencana jumlah.',
        'tasks' => [],
    ],

    'maker' => [
        'name' => 'Tentukan Konveksi',
        'summary' => 'Pilih konveksi dari daftar, harga jahit per model, sampel, dan syarat bayar.',
        'tasks' => [],
    ],

    'fabric' => [
        'name' => 'Tentukan Kain',
        'summary' => 'Kain dan supplier per model, pemakaian, satuan harga, dan minimum beli.',
        'tasks' => [],
    ],

    'trims' => [
        'name' => 'Label, Hangtag & Kemasan',
        'summary' => 'Riset dan pilih supplier cetak, lalu pesan untuk produksi ini.',
        'tasks' => [],
    ],

    'sampling' => [
        'name' => 'Sampling',
        'summary' => 'Sampel per model, review dan revisi sampai ACC.',
        'tasks' => [],
    ],

    'costing' => [
        'name' => 'Costing & Pricing',
        'summary' => 'HPP final, harga satuan dan paket, biaya platform, margin, BEP, dan modal.',
        'tasks' => [],
    ],

    'production' => [
        'name' => 'Produksi',
        'summary' => 'PO, kirim bahan, pantau progres, QC, dan pengiriman balik.',
        'tasks' => [
            'send_golden_sample' => 'Kirim sampel acuan dan catatan revisi ke konveksi',
        ],
    ],

    'prelaunch' => [
        'name' => 'Pre-launching',
        'summary' => 'Foto produk, deskripsi dan listing per platform, konsep launching, setup toko.',
        'tasks' => [
            'store_decoration' => 'Dekorasi toko di tiap platform',
            'shipping_payment' => 'Atur ongkir dan metode pembayaran toko',
            'teaser' => 'Jalankan teaser sebelum launching',
        ],
    ],

    'launch' => [
        'name' => 'Launching',
        'summary' => 'Launching, iklan dan endorse, evaluasi, dan rencana restock.',
        'tasks' => [],
    ],

    // Screens
    'checklist' => 'Checklist tambahan',
    'checklist_hint' => 'Untuk hal yang tidak ada isiannya. Task bertanda gate wajib selesai sebelum step ditutup.',
    'brand_subtitle' => 'Dikerjakan sekali untuk seluruh brand. Step 1 di tiap koleksi baru bisa ditutup setelah setup ini selesai.',

    'status' => [
        'done' => 'Selesai',
        'active' => 'Berjalan',
        'upcoming' => 'Berikutnya',
    ],

    'progress' => ':done dari :total syarat',
    'gates_progress' => 'Gate :done/:total',
    'overdue_count' => '{1} :count task telat|[2,*] :count task telat',
    'target_on' => 'Target selesai step',
    'target_on_label' => 'Target :date',
    'completed_on' => 'Selesai :date',

    'gate' => 'Gate',
    'gate_hint' => 'Task gate wajib selesai sebelum step bisa ditutup.',
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
    'no_tasks' => 'Belum ada checklist tambahan.',
    'mark_done' => 'Tandai selesai',
    'mark_undone' => 'Tandai belum selesai',

    'complete_phase' => 'Tutup step',
    'complete_phase_body' => '":name" ditandai selesai, dan step berikutnya menjadi step berjalan.',
    'phase_completed' => '":name" selesai.',
    'reopen_phase' => 'Buka kembali',
    'phase_reopened' => '":name" dibuka kembali.',
    'blocked_previous' => 'Tutup ":name" dulu.',
    'blocked_requirements' => '{1} Masih ada 1 syarat yang belum terpenuhi.|[2,*] Masih ada :count syarat yang belum terpenuhi.',
    'blocked_gates' => '{1} Masih ada :count task gate yang belum selesai.|[2,*] Masih ada :count task gate yang belum selesai.',
    'blocked_brand_reopen' => 'Sudah ada koleksi yang menutup step 1, jadi Setup Brand tidak bisa dibuka kembali.',
    'blocked_next_done' => 'Step berikutnya sudah selesai, jadi step ini tidak bisa dibuka kembali.',
    'ready_to_complete' => 'Semua syarat terpenuhi. Step ini siap ditutup.',

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
    'no_decisions' => 'Belum ada keputusan yang dicatat di step ini.',

];
