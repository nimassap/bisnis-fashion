<div align="center">

<img src="public/icons/icon-192.png" width="88" alt="Logo Bisnis Fashion">

# Bisnis Fashion

**Monitoring bisnis fashion couple, dari riset brand sampai pesanan pertama.**

[![CI](https://github.com/nimassap/bisnis-fashion/actions/workflows/ci.yml/badge.svg)](https://github.com/nimassap/bisnis-fashion/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![PWA](https://img.shields.io/badge/PWA-siap_dipasang-5A0FC8?logo=pwa&logoColor=white)

<img src="docs/screenshots/dashboard.png" alt="Dashboard" width="900">

</div>

---

## Tentang

Aplikasi web untuk pemilik brand fashion **couple**. Satu **koleksi** adalah satu
kali produksi berisi sepasang model, yaitu model cewek dan model cowok. Aplikasi
memandu tiap koleksi melalui 9 step yang diisi langsung di aplikasi, menghitung HPP dan harga jual,
mencatat uang masuk dan keluar, lalu memantau penjualan dan promosi setelah
launching.

Saat ini aplikasi dipakai oleh satu pemilik. Semua data menempel ke tabel
`businesses`, bukan langsung ke `users`, supaya anggota tim dengan role bisa
ditambahkan nanti tanpa migrasi besar.

```mermaid
flowchart LR
    B["Setup Brand<br/><small>sekali per bisnis</small>"] --> S1
    subgraph K["Per koleksi (sepasang model couple)"]
        direction LR
        S1["1 · Model"] --> S2["2 · Konveksi"] --> S3["3 · Kain"] --> S4["4 · Label &amp; kemasan"] --> S5["5 · Sampling"]
        S5 --> S6["6 · Costing &amp; pricing"] --> S7["7 · Produksi"] --> S8["8 · Pre-launching"] --> S9["9 · Launching"]
    end
```

Setup Brand berisi target pasar, kompetitor, positioning, nama brand, logo,
label, hangtag, desain kemasan, akun medsos dan toko, modal awal, target
margin, serta daftar semua konveksi dan kain. Step 2 dan 3 tinggal memilih
dari daftar itu.

Tiap step punya **syarat** yang dihitung dari data yang sudah diisi, misalnya
"kedua model punya size chart" atau "modal tersedia cukup". Step hanya bisa
ditutup kalau step sebelumnya selesai, semua syaratnya terpenuhi, dan task
**gate**-nya dicentang. Konveksi diasumsikan hanya menjahit (CMT); kain dan
aksesoris dibeli sendiri.

| Step | Isi |
|---|---|
| 1 · Model | Tema, moodboard, foto/sketsa, warna, aksesoris dan detail jahitan, catatan fit, rencana jumlah, pelajaran dari koleksi sebelumnya |
| 2 · Konveksi | Penawaran tiap konveksi (ongkos jahit cewek/cowok, sampel, DP, MOQ, kapasitas), pilih satu |
| 3 · Kain | Kain per model, pemakaian, supplier, harga, satuan, minimum order |
| 4 · Label & kemasan | Riset supplier label, hangtag, kemasan; pilih dan catat pesanan cetak |
| 5 · Sampling | Ronde sampel, ukur terhadap size chart, poin review, foto, golden sample |
| 6 · Costing & pricing | HPP, harga satuan, coret, reseller, paket couple, biaya promo, cek modal |
| 7 · Produksi | PO per ukuran × warna, tahap produksi, QC jadi stok, tagihan DP dan pelunasan otomatis |
| 8 · Pre-launching | Foto produk, shot list, listing per channel, konsep dan tanggal launching |
| 9 · Launching | Evaluasi otomatis dari penjualan, ROAS, saran restock, catatan pelajaran |

## Fitur

| Modul | Isi |
|---|---|
| **Setup Brand & Step** | Form Setup Brand, 9 step per koleksi dengan syarat otomatis, task tambahan dan gate, tenggat dan tanda telat |
| **Koleksi** | Satu koleksi = satu pasang model couple, nomor otomatis, target launching, progres 9 step |
| **Produk** | Tech pack, foto/sketsa, size chart per titik ukur (cewek dan cowok berbeda), riwayat sampel per putaran |
| **Kain & Vendor** | Library kain dengan hasil tes susut/luntur dan foto swatch; perbandingan penawaran konveksi (harga, MOQ, lead time, rating) dan tombol WhatsApp |
| **HPP & Harga** | HPP per pcs (kain + kain terbuang + ongkos jahit + biaya lain + overhead), harga satuan dan **paket couple**, margin per channel, harga saran sesuai target margin, modal, dan titik balik modal |
| **Buku Kas & Budget** | Uang masuk/keluar dengan foto nota, kategori, ekspor CSV/Excel; budget per koleksi per kategori; jadwal pembayaran vendor yang otomatis tercatat saat lunas |
| **Penjualan** | Pencatatan per pesanan (satuan dan paket couple), potongan channel dan HPP disimpan saat transaksi, stok per ukuran × warna, alert restock, laporan per model, channel, ukuran, dan warna |
| **Promosi** | Campaign iklan, endorse, dan affiliate dengan ROAS dari pesanan; kalender konten bulanan |
| **PWA** | Bisa dipasang di layar utama HP, halaman offline, banner saat ada versi baru |

<table>
  <tr>
    <td><img src="docs/screenshots/koleksi.png" alt="Halaman koleksi"></td>
    <td><img src="docs/screenshots/hpp-harga.png" alt="HPP dan harga"></td>
  </tr>
  <tr>
    <td align="center"><sub>Koleksi: pasangan model dan step</sub></td>
    <td align="center"><sub>HPP, harga per channel, dan paket couple</sub></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/penjualan.png" alt="Penjualan"></td>
    <td><img src="docs/screenshots/kalender-konten.png" alt="Kalender konten"></td>
  </tr>
  <tr>
    <td align="center"><sub>Penjualan, stok menipis, dan laporan</sub></td>
    <td align="center"><sub>Kalender konten</sub></td>
  </tr>
</table>

<p align="center">
  <img src="docs/screenshots/hp-dashboard.png" alt="Dashboard di HP" width="260">
  &nbsp;&nbsp;
  <img src="docs/screenshots/hp-penjualan.png" alt="Penjualan di HP" width="260">
</p>

## Teknologi

- **Backend:** Laravel 13, PHP 8.4+, MySQL 8 / MariaDB 10.4+
- **Antarmuka:** Livewire 4 + Alpine.js, Tailwind CSS 4, Chart.js
- **Uang:** semua nominal disimpan sebagai `DECIMAL(18,2)` dan dihitung dengan `bcmath`, tanpa float
- **Foto:** disimpan privat di `storage/app/private/businesses/{id}/` dan hanya dikirim ke pemilik bisnisnya
- **Ekspor:** CSV dan Excel (OpenSpout), aman dari formula injection

Sebagian fondasi diambil dari project *monitoring-keuangan*: format uang, rentang
periode, sistem tema, komponen Blade, ekspor, dan PWA.

## Menjalankan secara lokal

**Prasyarat:** PHP 8.4+ dengan ekstensi `intl`, `bcmath`, `pdo_mysql`, `zip`;
Composer; Node.js 20.19+ atau 22.12+; MySQL 8 atau MariaDB.

```bash
git clone https://github.com/nimassap/bisnis-fashion.git
cd bisnis-fashion

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Buat dua database, satu untuk aplikasi dan satu untuk tes:

```sql
CREATE DATABASE bisnis_fashion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE bisnis_fashion_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sesuaikan `DB_*` di `.env`, lalu:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Buka http://127.0.0.1:8000 lalu masuk dengan akun contoh:

| Email | Kata sandi |
|---|---|
| `owner@example.test` | `password` |

Data contoh berisi satu koleksi lengkap: sepasang model, kain, vendor, harga,
catatan kas, pesanan, campaign, dan konten.

Untuk database kosong tanpa data contoh, jalankan `php artisan migrate` saja.
Aplikasi akan membuka halaman **setup** untuk membuat akun pemilik. Halaman itu
tertutup permanen setelah akun pertama dibuat.

> **PWA di HP** hanya aktif lewat HTTPS atau `localhost`. Membuka server lokal
> lewat IP Wi-Fi (`http://192.168.x.x`) menampilkan aplikasinya, tapi tidak
> menampilkan tombol pasang dan tidak mengaktifkan mode offline.

## Tes

```bash
php artisan test            # seluruh tes, memakai database bisnis_fashion_test
./vendor/bin/pint --test    # cek format kode
```

Tes memakai MySQL sungguhan, bukan SQLite, karena kolom nominal bertipe
`DECIMAL` dan beberapa laporan memakai fungsi tanggal. GitHub Actions menjalankan
tes, build aset, cek format, dan audit dependensi di setiap push.

## Struktur

```
app/
  Actions/       Operasi sekali jalan (seed step, tutup step, bayar vendor)
  Enums/         Status dan jenis: produk, vendor, kain, pesanan, campaign, …
  Livewire/      Halaman per modul
  Models/        Model Eloquent
  Services/      Perhitungan lintas model: PhaseBoard, laporan penjualan, stok, budget
  Support/       Money, DateRange, MediaStore, kalkulator HPP (Pricing/Calculator)
config/          phases, pricing, cashbook, sales, products, theme
lang/id/         Seluruh teks antarmuka
resources/views/ Komponen Blade dan template halaman
tests/           Tes fitur dan unit
docs/            Screenshot README
```

## Kontribusi

- Kerjakan di branch terpisah, lalu buka pull request ke `main`.
- Pesan commit mengikuti [Conventional Commits](https://www.conventionalcommits.org):
  `<tipe>(<modul>): <ringkasan>`, misalnya `feat(penjualan): catat pesanan paket couple`.
  Template lengkapnya ada di [`.gitmessage`](.gitmessage); aktifkan dengan
  `git config commit.template .gitmessage`.
- Laporan bug dan usulan fitur memakai template di tab **Issues**.

## Rencana berikutnya

- Role tim: desainer, admin, marketing
- Pengingat jatuh tempo lewat email atau notifikasi HP
- Import pesanan dari file ekspor Shopee dan TikTok Shop
- Scan nota pakai AI
