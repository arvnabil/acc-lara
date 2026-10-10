# PRD Accommerce: Laravel + Inertia (Build Langsung)

Versi 2 · 10 Okt 2026 · Menggantikan PRD Frontend (SPA + PWA) tanggal 9 Okt 2026

## 1. Ringkasan dan tujuan

Fase frontend React sudah selesai (16 rute, 539 produk, 285 varian). Dokumen ini mendefinisikan fase berikutnya: membangun Accommerce.id langsung sebagai aplikasi Laravel + Inertia (React) dengan panel admin Filament, bukan lagi memindahkan SPA secara bertahap.

- **Tujuan produk:** toko B2B/B2C perangkat video conference dan audio visual, dengan harga, stok, pesanan, dan poin loyalitas yang dihitung server.
- **Prinsip:** semua angka uang dan poin dihitung di server. React hanya menampilkan props.
- **Paritas:** tampilan dan perilaku mengikuti frontend React, kecuali bagian yang sengaja berbeda (bagian 8).
- **Sumber kebenaran aturan bisnis:** `SISTEM_LOYALTI_POIN.md` (Revisi 4). Perilaku UI per halaman: blueprint di `docs/migration/pages/`.

## 2. Keputusan teknis

| ID | Keputusan | Status |
| --- | --- | --- |
| D-01 | Laravel + Inertia (React) untuk storefront; Filament untuk panel admin `/admin` | Tetap |
| D-02 | Modul native di `Modules/`, tanpa `nwidart/laravel-modules` | Tetap |
| D-03 | **Permission user memakai Spatie Laravel Permission** (menggantikan tabel `roles` + Gate buatan sendiri) | **Berubah 10 Okt 2026** |
| D-04 | Aturan loyalitas = Revisi 4 | Tetap |
| D-05 | Poin, harga, dan tier dihitung server saja; `localStorage` tidak boleh menyimpan poin | Tetap |
| D-06 | Tombol 'Saya Sudah Bayar' tidak memberi poin; status menjadi menunggu verifikasi | Tetap |
| D-07 | Satu `PricingService` untuk Cart dan Checkout | Tetap |
| D-08 | Query string katalog tetap berbahasa Indonesia (`halaman`, `kategori`, `stok`, `min`, `max`) | Tetap |

Stack: PHP 8.3+, Laravel 13, Inertia (React), Filament v4, Spatie Laravel Permission, Tailwind, Vite, `vite-plugin-pwa`. Kompatibilitas Spatie, Filament v4, dan Inertia dengan Laravel 13 **belum diverifikasi**; itu pekerjaan pertama (tahap M0).

## 3. Lingkup

**Termasuk**

- Storefront lengkap: beranda, katalog, kategori mobile, pencarian, detail produk dan varian, keranjang, wishlist, checkout, lacak pesanan, RFQ, bantuan.
- Akun: login, register, lupa password, alamat, dashboard, voucher, ulasan.
- Loyalitas Revisi 4: ledger poin FIFO, tier, penukaran, job terjadwal.
- Checkout dengan verifikasi pembayaran oleh admin (manual) di panel Filament.
- Panel admin Filament: Catalog, Orders, Loyalty, Users dan Roles.
- PWA: manifest, service worker, offline, banner pembaruan.

**Tidak termasuk (usulan, konfirmasi di bagian 14)**

- Integrasi payment gateway (webhook) dan pencairan e-wallet otomatis. Penukaran e-wallet dicatat berstatus pending dan dibayar manual.
- Termin NET 14/30 (fitur baru, butuh verifikasi kredit).
- Push notification.

## 4. Arsitektur modul

| Modul | Isi | requires | Status |
| --- | --- | --- | --- |
| Catalog | Product, Variant, Category, Brand, katalog, detail, pencarian, kategori | – | Kerangka ada |
| Accounts | users, companies (akun induk, sub-akun), alamat, login, register, lupa password | – | Baru |
| Cart | cart, cart\_items, wishlist, gabung keranjang tamu | Catalog, Accounts | Baru |
| Promo | kode promo dan voucher (ganti `PROMO_CODES` hardcode) | – | Baru |
| Orders | Order, OrderItem, `PricingService`, lacak pesanan, `pointsEligible` | Catalog, Accounts | Kerangka ada |
| Loyalty | `LoyaltyService`, ledger, penukaran, 4 job | Orders, Accounts | Kerangka ada |
| Review | ulasan produk, maks. 1 per produk | Orders, Catalog | Baru |
| Content | banner beranda, RFQ, bantuan, halaman statis | – | Baru |

Perubahan terhadap kerangka: `Orders` dan `Loyalty` perlu menambah `Accounts` di `requires` karena pesanan dimiliki user dan `loyalty_accounts` mengacu ke `companies`. Arah Promo terhadap `PricingService` diputuskan di M3 (usulan: lewat interface, tanpa dependensi balik).

Aturan modul:

1. Modul hanya boleh memakai modul di `requires`; `ModuleManager` menolak dependensi hilang atau melingkar.
2. Arah satu arah: Loyalty → Orders → Catalog. Catalog dan Orders tidak boleh mengenal Loyalty. Pemberitahuan balik memakai Event.
3. Angka harga hanya dihitung `PricingService`. Angka poin hanya diubah `LoyaltyService`.
4. Jadwal didaftarkan di `bootModule()`. `"enabled": false` mematikan route, jadwal, dan menu admin modul itu.
5. Halaman React dipanggil dengan `Inertia::render('Catalog::Index')`.

## 5. Permission dengan Spatie

Ada dua jenis peran yang tidak boleh tercampur:

- **Peran panel admin (Spatie):** siapa boleh apa di Filament.
- **Peran perusahaan (`users.company_role` = `admin` atau `member`):** akun induk atau sub-akun untuk penukaran poin. Ini bukan Spatie karena berlaku per perusahaan; dicek di `LoyaltyService` dan policy.

**Perubahan pada kerangka yang sudah ada**

- Hapus `app/Models/Role.php`, `Concerns/HasPermissions.php`, migrasi `create_roles_table` kustom, dan `Gate::define` dari `module.json`.
- Pasang `spatie/laravel-permission`, publish migrasi dan config. `User` memakai `HasRoles`.
- `module.json` tetap memuat `permissions[]` sebagai registri. Perintah baru `module:permissions-sync` membuat atau menghapus baris di tabel `permissions` untuk modul aktif (`Permission::findOrCreate`) lalu mereset cache permission. Dipanggil dari seeder dan saat deploy.
- Penamaan permission: `view_any_x`, `view_x`, `create_x`, `update_x`, `delete_x`, `delete_any_x` (pola ActivonCMS).
- Role `administrator` lolos semua lewat `Gate::before`. Role lain dibuat di panel Roles & Permissions.
- Pelanggan storefront tidak punya role. Akses panel hanya lewat `canAccessPanel()` yang memeriksa permission `access_panel`.
- Setiap Resource Filament punya Policy (atau override `canViewAny/canCreate/...`) yang memanggil `$user->can(...)`. Tanpa policy, Filament membiarkan akses terbuka.
- Filament Shield bersifat opsional (generate policy dan UI role). Putuskan setelah verifikasi kompatibilitas di M0.
- Panel `/admin`: rate limit login dan 2FA sebelum produksi.

## 6. Tahapan build

| Tahap | Isi | Keluaran dan uji |
| --- | --- | --- |
| M0 Setup dan verifikasi | Proyek Laravel 13 baru, pasang Inertia, Filament, Spatie; tempel kerangka `accommerce-modular`; jalankan di mesin yang bisa mengakses Packagist | `migrate` berhasil, `module:list` tampil, panel `/admin` terbuka, sintaks Filament v4 terverifikasi |
| M1 Accounts dan permission | Migrasi Spatie, `module:permissions-sync`, User + `company_id`, register (perorangan atau perusahaan), login, lupa password | Register membuat `companies` + `loyalty_accounts`; tes menu admin dan 403 |
| M2 Catalog | Seeder impor, katalog (filter, sort, paginasi), detail dan varian, kategori mobile, pencarian | Paritas `catalog.md`, `product-detail.md`, `search.md`; query string identik |
| M3 Cart, Pricing, Promo | Keranjang (DB dan session tamu), wishlist, `PricingService`, promo di server | Total Cart sama dengan Checkout; keranjang tamu digabung saat login |
| M4 Orders dan Checkout | Checkout 3 langkah, pesanan, verifikasi pembayaran di Filament, lacak pesanan | Tidak ada poin saat 'Saya Sudah Bayar'; nomor pesanan berurutan |
| M5 Loyalty | `LoyaltyService`, job, penukaran, dashboard poin | 12 kriteria penerimaan di dokumen 04 lulus |
| M6 Akun lengkap dan Review | Dashboard dipecah per URL, alamat, voucher, ulasan | Maks. 1 ulasan per produk; poin ulasan masuk saldo saja |
| M7 Content | Beranda, banner, RFQ, bantuan, halaman statis, 404 | Paritas `home.md` dan enam halaman yang blueprint-nya ditulis dari JSX |
| M8 PWA dan hardening | `vite-plugin-pwa`, uji cache respons Inertia, rate limit, 2FA admin, Lighthouse | Skor ≥ 90; offline tampil katalog yang pernah dibuka |

## 7. Pemetaan rute

| Rute React | URL Laravel | Modul | Halaman Inertia |
| --- | --- | --- | --- |
| `/` | `/` | Content | `Home` |
| `/katalog` | `/katalog` | Catalog | `Catalog::Index` |
| `/produk/:slug` | `/produk/{slug}` | Catalog | `Catalog::ProductDetail` |
| `/kategori` | `/kategori` | Catalog | `Catalog::Categories` (mobile) |
| `/cari` | `/cari` | Catalog | `Catalog::Search` |
| `/keranjang` | `/keranjang` | Cart | `Cart::Index` |
| `/wishlist` | `/wishlist` | Cart | `Cart::Wishlist` |
| `/checkout` | `/checkout`, `/pembayaran/{order}`, `/sukses/{order}` | Orders | `Orders::Checkout`, `Payment`, `Success` |
| `/akun?tab=` | `/akun/dashboard`, `/pesanan`, `/alamat`, `/poin`, `/voucher`, `/wishlist`, `/ulasan` | Accounts, Loyalty, Review | `Account::*` |
| `/login`, `/register`, `/lupa-password` | sama | Accounts | `Auth::*` |
| `/minta-penawaran` | sama | Content | `Rfq` |
| `/lacak-pesanan` | sama | Orders | `Orders::Track` |
| `/bantuan` | sama | Content | `Help` |
| `*` | 404 | – | `NotFound` |

URL memakai path biasa (bukan hash). Tautan lama `#/...` diberi redirect (rekomendasi O-05).

## 8. Aturan bisnis dan perbedaan yang disengaja dari React

- **Harga:** subtotal, PPN, ongkir, biaya admin, diskon, grand total, dan estimasi poin dihitung `PricingService`. Fallback subtotal Rp 52.400.000 dan keranjang demo dihapus; checkout dengan keranjang kosong diarahkan ke `/keranjang`.
- **Poin (Revisi 4):** dasar = `floor(nilai bersih / 10.000)`, nilai bersih tanpa PPN, ongkir, dan biaya admin. Poin masuk setelah pesanan Selesai + 7 hari, atau invoice Lunas + 7 hari. Bonus tier dan poin ulasan hanya ke saldo. Kedaluwarsa 24 bulan FIFO.
- **Penukaran:** e-wallet 500/1.000/2.500 poin; voucher 750/1.500/3.000 poin; 1 poin = Rp 100; maks. 5.000 poin per bulan per akun induk; poin berusia ≥ 30 hari; hanya admin akun induk (sub-akun mendapat 403); wajib idempotency key.
- **Tier:** hanya dari `current_tier`. Klien tidak pernah mengirim tier; akun baru mulai di Classic. Evaluasi 31 Des berjalan sebelum reset `tier_points` 1 Jan.
- **Pembayaran:** 'Saya Sudah Bayar' hanya mengubah status ke menunggu verifikasi.
- **Wishlist:** memakai `whereIn` (memperbaiki batas 50 produk di React).
- **Ulasan:** hanya dari pesanan Selesai, maks. 1 per produk, dicocokkan lewat `order_items.product_id`.
- **Data demo** tidak di-seed: user Platinum 16.800 poin, keranjang demo, wishlist awal.

## 9. Data dan seeder

| Sumber | Jumlah | Perlakuan |
| --- | --- | --- |
| `products-index.json` + `products.json` | 539 produk | Impor; 2 produk add-on dikeluarkan dari katalog; deskripsi HTML disanitasi |
| `variants.json` | 285 | Varian `is_price_outlier` dikecualikan dari tampilan |
| `categories.json` | 88 | Pohon via `parent_id`; pivot `category_product` |
| `brands.json` | 25 | Impor |

Catatan impor:

- `images` di `products-index.json` berupa array berisi satu string koma; pecah menjadi array URL.
- 137 produk berstatus `is_dummy_price`; 155 tanpa SKU. SKU boleh kosong dan tidak unik.
- Simpan `sort_order` dari urutan JSON agar tanpa sort hasil identik dengan React.
- Rental = kategori id 13 atau 14; lisensi = SKU `CFQ7TTC0LH18` atau `CFQ7TTC0LDPB`. Jadikan kolom `products.product_type`.
- Tidak ada produk `is_flash_sale` di data saat ini, jadi section Flash Sale beranda kosong sampai data diisi (lihat O-04).
- `wc-data-export.json` hanya bahan skrip transformasi, bukan bagian aplikasi.
- Gambar sumber di accommerce.id bisa putus; siapkan fallback placeholder dan rencana menyalin gambar ke storage sendiri.

## 10. Non-fungsional

- **Keamanan:** FormRequest di semua mutasi, `lockForUpdate` dan idempotency pada penukaran, rate limit login dan redeem, sanitasi HTML deskripsi, 2FA admin, HTTPS.
- **Performa (mobile 4G):** LCP ≤ 2,5 detik, INP ≤ 200 ms, CLS ≤ 0,1; Lighthouse Performance, Accessibility, PWA ≥ 90. Indeks DB pada `slug`, `sort_order`, dan pivot kategori; eager loading.
- **PWA:** `vite-plugin-pwa`, banner pembaruan, indikator offline. Cache respons Inertia diuji sejak M2, bukan di akhir.
- **SEO:** `<Head>` per halaman (title, deskripsi, Open Graph) — baru, tidak ada di React.
- **Aksesibilitas:** WCAG 2.1 AA, target sentuh 44 px, `100dvh`, safe area.

## 11. Pengujian (Pest)

- `ModuleManager`: urutan dependensi dan error melingkar.
- Permission: sync dari `module.json`, menu hilang tanpa izin, 403 lewat URL langsung, modul nonaktif tidak mendaftarkan izinnya.
- `PricingService`: Cart dan Checkout memberi angka sama.
- `LoyaltyService`: 12 kriteria dokumen 04 (FIFO, kuota 5.000, idempotensi, 403 sub-akun, retur, minus saldo).
- `Order::pointsEligible`, dan `evaluateYearEnd` sebelum `resetTierPoints`.
- Paritas query string katalog; checkout tidak memberi poin; gabung keranjang tamu.

## 12. Kriteria selesai

- [ ] Semua 16 rute punya halaman Inertia dan lolos kriteria paritas blueprint masing-masing
- [ ] 12 kriteria penerimaan loyalitas lulus sebagai tes otomatis
- [ ] Tidak ada poin, saldo, tier, atau total harga yang dihitung atau disimpan di klien
- [ ] Menu admin hanya tampil sesuai permission Spatie
- [ ] Seeder mengimpor 539 produk, 285 varian, 88 kategori, 25 brand tanpa data demo
- [ ] Lighthouse mobile ≥ 90 dan offline berfungsi untuk katalog yang pernah dibuka
- [ ] Layout benar di lebar 360, 390, 768, 1024, 1440 px

## 13. Risiko

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Spatie, Filament, atau Inertia belum cocok dengan Laravel 13 | Build tertunda | Verifikasi di M0 sebelum menulis fitur; siapkan versi Laravel cadangan |
| Sintaks Filament v4 ditulis tanpa paket terpasang | Resource gagal jalan | Perbaiki di M0 lewat `composer install` |
| Policy Filament tidak dipasang | Menu terbuka untuk semua user panel | Tes permission wajib per resource |
| Cache respons Inertia di service worker | Data usang atau offline rusak | Uji sejak M2 |
| Harga `is_dummy_price` masuk pesanan | Poin atau tagihan salah | Putuskan O-01 sebelum M4 |
| Gambar sumber putus | Kartu tanpa gambar | Placeholder; salin ke storage sendiri |

## 14. Pertanyaan terbuka

| ID | Pertanyaan | Status atau rekomendasi |
| --- | --- | --- |
| O-01 | Pesanan berisi produk `is_dummy_price` dapat poin? | Tidak, sampai harga final dikonfirmasi di invoice |
| O-02 | Poin RFQ/proyek dari harga katalog atau invoice final? | Invoice final |
| O-03 | PPN dihitung sebelum atau sesudah diskon? | Tanya akuntan sebelum produksi |
| O-04 | Dukung `?sort=sale`? | Dukung; data saat ini tidak punya produk `is_flash_sale` |
| O-05 | URL hash atau URL biasa? | URL biasa dengan redirect |
| O-06 | `ForgotPasswordPage` ada? | **Terjawab:** ada (440 baris, `#/lupa-password`); blueprint belum ditulis |
| O-07 | Sumber `isRental` / `isLicense` | **Terjawab:** kategori 13/14 dan dua SKU; jadikan `product_type` |
| O-08 | Metode bayar kartu dan e-wallet ada? | **Terjawab:** ada empat metode di React; Step 2 hanya menampilkan QRIS dan VA. Putuskan metode mana yang didukung tanpa gateway |
| O-09 | Kompatibilitas Filament, Inertia, Spatie dengan Laravel 13 | Verifikasi di M0 |
| O-10 | Penggunaan `wc-data-export.json` | **Terjawab:** hanya sumber `scripts/transform.mjs` |
| O-11 | Filament Shield dipakai atau policy manual? | Putuskan setelah M0 |
| O-12 | Payment gateway, Google login, dan termin NET masuk fase ini? | Usulan: tidak |
