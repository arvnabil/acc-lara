# DECISIONS

Keputusan yang sudah diambil (D) dan yang masih terbuka (O). Update tiap ada perubahan.

## Sudah diputuskan

| ID | Keputusan | Alasan |
|---|---|---|
| D-01 | Stack: Laravel + Inertia (React) untuk storefront, Filament panel untuk dashboard | Permintaan user |
| D-02 | Modul **native**, tanpa `nwidart/laravel-modules` | Permintaan user; ModuleManager ±100 baris |
| D-03 | Otorisasi mengasumsikan `spatie/laravel-permission` (v8.3.0) + `Gate::before` administrator | Tersinkron dengan Filament v4 dan command `module:permissions-sync` |
| D-04 | Aturan loyalitas = Revisi 4 (`SISTEM_LOYALTI_POIN.md`) | Sumber kebenaran tunggal |
| D-05 | Poin, harga, tier dihitung server saja | Keamanan; `localStorage` tidak boleh menyimpan poin |
| D-06 | "Saya Sudah Bayar" tidak memberi poin, status jadi menunggu verifikasi | Mencegah poin tanpa pembayaran |
| D-07 | Satu `PricingService` untuk Cart dan Checkout | Keduanya berbeda di React (ongkir, PPN) |
| D-08 | Query string katalog tetap berbahasa Indonesia (`halaman`, `kategori`, `stok`, `min`, `max`) | Paritas dengan React |
| D-09 | Kompatibilitas Laravel 13, Filament v4 (4.15.1), Inertia (3.5.1), Spatie Permission (8.3.0) | Terverifikasi dan 100% lulus tes |
| D-11 | Pilihan O-11: Policy manual per-modul (bukan Spatie Shield) | Lebih ringan, modular, terisolasi per modul tanpa overhead Spatie Shield plugin |

## Penutupan fase D

| O-09 | Selesai: versi paket terpasang diverifikasi | Laravel 13.x, Filament 4.15.1, Inertia Laravel 3.5.1, Spatie Permission 8.3.0, Laravel Boost 2.10.3, Pest 4.7.8, PHPUnit 12.5.33 |
| O-11 | Selesai: gunakan Policy manual per-modul, bukan Spatie Shield | Resource mengimplementasikan `canViewAny/canView/canCreate/canEdit/canDelete` berbasis permission modul; tidak menambahkan Spatie Shield |

## Masih terbuka (rekomendasi saya di kolom kanan)

| ID | Pertanyaan | Rekomendasi |
|---|---|---|
| O-01 | Pesanan berisi produk `is_dummy_price` dapat poin? | Tidak, sampai harga final dikonfirmasi di invoice |
| O-02 | Poin RFQ/proyek dari harga katalog atau invoice final? | Invoice final |
| O-03 | PPN dihitung sebelum atau sesudah diskon? | Tanya akuntan sebelum produksi |
| O-04 | `?sort=sale` di katalog: dukung atau hapus? | Dukung (`is_flash_sale` sudah ada) |
| O-05 | URL hash (`#/`) atau URL biasa? | URL biasa (Inertia); sediakan redirect untuk tautan lama |
| O-06 | Apakah `ForgotPasswordPage` ada? (`auth.md` vs inventory R-15) | Cek kode; di Laravel tetap pakai alur reset bawaan |
| O-07 | Sumber `isRental` / `isLicense` | Jadikan kolom `products.product_type` |
| O-08 | Metode bayar kartu/e-wallet (DAFTAR_FITUR) benar ada? | Cek `CheckoutPage.jsx`; blueprint hanya mencatat QRIS + VA |
| O-10 | Penggunaan `wc-data-export.json` | Konfirmasi: sumber impor atau sisa |

## Penutupan Slice 5

| ID | Keputusan | Alasan |
|---|---|---|
| O-16 | Plafon subsidi Gold, Platinum, dan Diamond sementara Rp50.000 per voucher | Nilai sementara untuk mengaktifkan benefit dan tes; belum keputusan bisnis final. Ditandai `BELUM DIPUTUSKAN, O-16` di konfigurasi. |
| D-12 | Poin pengembalian memiliki `expires_at` 24 bulan sejak tanggal pengembalian | Konsisten dengan masa kedaluwarsa poin standar dan mencegah poin refund tidak pernah kedaluwarsa. |
| D-13 | Service worker storefront memakai NetworkFirst untuk halaman publik dan tidak mencegat admin, akun, auth, cart, checkout, loyalty, review, atau request non-GET | Mencegah kebocoran state pengguna dan menjaga panel Filament tetap dinamis. |
| D-14 | CSP diterapkan sebagai `Content-Security-Policy-Report-Only` terlebih dahulu | Memungkinkan audit pelanggaran tanpa memutus Inertia, font, dan gambar katalog yang sudah berjalan. |
