<?php

declare(strict_types=1);

return [

    'title' => 'Pembayaran Vendor',
    'subtitle' => 'Jadwal DP dan pelunasan ke konveksi dan supplier. Saat ditandai lunas, otomatis tercatat di Buku Kas.',

    'add' => 'Jadwalkan pembayaran',
    'edit' => 'Ubah pembayaran',
    'title_field' => 'Untuk',
    'title_placeholder' => 'Contoh: DP 50% produksi Linen Senja',
    'amount' => 'Nominal',
    'due_on' => 'Jatuh tempo',
    'vendor' => 'Vendor',
    'collection' => 'Koleksi',
    'category' => 'Kategori',
    'notes' => 'Catatan',

    'unpaid_total' => 'Belum dibayar',
    'overdue_total' => 'Lewat jatuh tempo',
    'overdue_hint' => 'Total yang sudah lewat tanggalnya.',
    'count' => '{0} Tidak ada pembayaran|{1} 1 pembayaran|[2,*] :count pembayaran',
    'tab_unpaid' => 'Belum lunas',
    'tab_paid' => 'Lunas',

    'due_label' => 'Jatuh tempo :date',
    'due_today' => 'Jatuh tempo hari ini',
    'due_in' => '{1} Jatuh tempo besok|[2,*] Jatuh tempo :count hari lagi',
    'overdue_by' => '{1} Lewat 1 hari|[2,*] Lewat :count hari',
    'paid_on_label' => 'Lunas :date',

    'pay' => 'Tandai lunas',
    'pay_hint' => 'Pembayaran ini akan dicatat sebagai uang keluar di Buku Kas.',
    'paid_on' => 'Tanggal bayar',
    'confirm_pay' => 'Lunas',
    'undo_pay' => 'Batalkan lunas',
    'paid' => 'Pembayaran lunas dan tercatat di Buku Kas.',
    'unpaid' => 'Pembayaran kembali belum lunas. Catatannya di Buku Kas dihapus.',

    'saved' => 'Pembayaran disimpan.',
    'deleted' => 'Pembayaran dihapus.',
    'delete' => 'Hapus pembayaran',
    'delete_body' => 'Jadwal pembayaran ":title" akan dihapus.',
    'delete_paid_body' => 'Jadwal ":title" akan dihapus. Catatannya di Buku Kas tetap ada karena uangnya sudah keluar.',

    'empty' => 'Tidak ada pembayaran yang menunggu',
    'empty_hint' => 'Jadwalkan DP dan pelunasan ke konveksi supaya tidak terlewat.',
    'empty_paid' => 'Belum ada pembayaran yang lunas.',

];
