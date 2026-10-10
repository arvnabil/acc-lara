# Fase 5: Arsitektur Modular Native (Laravel + Inertia React + Filament)

Stack final: **Laravel + Inertia (React) untuk storefront**, **Filament panel (`/admin`) untuk dashboard**, **modul native** (tanpa `nwidart/laravel-modules`, tanpa Spatie Permission). Kode contoh ada di `accommerce-modular/` (tempel di atas proyek Laravel baru).

Referensi pola: situs ActivonCMS (`MODULE_ANATOMY`, `PROJECT_MAPPING`, `FOLDER_STRUCTURE`, `Module_Features`). Pola foldernya dipertahankan, dua paketnya diganti kode native.

| Di situs referensi | Di Accommerce (native) |
|---|---|
| `nwidart/laravel-modules` | `App\Support\Modules\ModuleManager` + `ModuleServiceProvider` (scan `Modules/*/module.json`) |
| `modules.json` / `config/app.php` untuk daftar provider | Tidak ada. Provider ditemukan otomatis dari folder |
| Spatie Laravel Permission | Tabel `roles` (kolom JSON `permissions`) + trait `HasPermissions` + `Gate::define` dari `module.json` |
| View Blade per modul | Halaman React per modul: `Modules/X/resources/js/Pages`, dipanggil `Inertia::render('X::Page')` |
| Resource Filament per modul | Sama; `AdminPanelProvider` memanggil `discoverResources()` per modul |

## 1. Anatomi satu modul

```
Modules/Loyalty/
├── module.json                 # name, enabled, priority, requires[], permissions[]
├── LoyaltyServiceProvider.php  # extends BaseModuleServiceProvider
├── Database/Migrations/        # otomatis dimuat
├── Models/  Enums/  Services/  Jobs/  Http/Controllers/
├── Routes/web.php  (api.php)   # otomatis dimuat (web / api middleware)
├── Filament/Resources/         # otomatis ditemukan panel admin
└── resources/js/Pages/         # 'Loyalty::NamaHalaman'
```

Alur kerja: `php artisan module:make Promo` → `composer dump-autoload` → isi migration/model → `php artisan migrate`. `php artisan module:list` menampilkan status.

## 2. Modul contoh dan dependensinya

| Modul | Isi | requires | Padanan di dokumen |
|---|---|---|---|
| Catalog | Product, Category, Brand, `CatalogController`, `ProductResource` | – | `03-data-services`, `catalog.md`, `product-detail.md` |
| Orders | Order, OrderItem, `PricingService`, scope `pointsEligible` | Catalog | `checkout.md`, `cart.md` |
| Loyalty | `LoyaltyService`, ledger FIFO, 4 job terjadwal, `RedeemController` | Orders | `04-loyalty-system-migration.md` |

Modul berikutnya (belum dibuat): Cart & Wishlist, Auth/Company (akun induk, sub-akun), Promo (ganti `PROMO_CODES` hardcode), Review, Content (banner/beranda).

## 3. Aturan agar modul tetap terpisah

1. Modul hanya boleh memakai modul yang tercantum di `requires`. `ModuleManager` melempar error jika dependensi hilang atau melingkar.
2. Arah dependensi satu arah: Loyalty → Orders → Catalog. Catalog dan Orders tidak boleh tahu Loyalty. Jika Orders perlu memberi tahu Loyalty, pakai Event, bukan import.
3. Angka harga hanya dihitung `PricingService`. Angka poin hanya dimutasi `LoyaltyService`.
4. Modul mendaftarkan jadwalnya sendiri di `bootModule()` (bukan di `routes/console.php`). Menonaktifkan modul (`"enabled": false`) otomatis mematikan route, jadwal, dan menu admin-nya.
5. Resource Filament wajib mengecek `Gate::allows('…')` agar menu hilang untuk user tanpa permission.

## 4. Hubungan dengan Laravel 13

Memakai fitur yang relevan dari `features_laravel13.md`: PHP 8.3 (`readonly` property, enum), `#[Tries]/#[Timeout]` bisa dipakai di job Loyalty sebagai pengganti properti, `Cache::touch()` untuk cache keranjang tamu, dan `JsonApiResource` hanya jika nanti dibuka API publik. Inertia dan Filament **belum diverifikasi** kompatibel dengan Laravel 13 di sini (Packagist tidak terjangkau). Cek `composer require` di mesin Anda dulu.

## 5. Yang sudah dan belum diuji

- Sudah: seluruh file PHP lolos `php -l`; logika `Tier` (batas tier, turun 1 level) dan contoh poin (1.900.000 → 190, bonus Gold 19; 999.000 → 99) dijalankan dan sesuai kriteria penerimaan 1 dan 2 di dokumen 04.
- Belum: tidak ada Laravel, Filament, atau Inertia terpasang di lingkungan ini, sehingga migrasi, route, panel admin, dan job belum dijalankan. Syntax Filament v4 (`Schema`, `recordActions`, `toolbarActions`) ditulis dari pengetahuan API, bukan dari paket terpasang. Verifikasi setelah `composer install`.
- Uji yang perlu Anda tulis (Pest/PHPUnit): `ModuleManager` (urutan dependensi, error melingkar), `LoyaltyService::redeem` (FIFO, kuota 5.000, idempotensi, 403 sub-akun), `Order::pointsEligible`, `evaluateYearEnd` sebelum `resetTierPoints`.

## 6. Pekerjaan yang belum ditutup

- `User` perlu `use HasPermissions;`, `company_id`, dan relasi ke `loyalty_accounts` (saat register: buat `companies` + `loyalty_accounts`).
- `HandleInertiaRequests::share()` memanggil `LoyaltyService::sharedProps()` bila modul Loyalty aktif.
- Seeder: impor `products-index.json` ke Catalog (pecah `images` yang berupa string koma; buang user demo bersaldo poin).
- Rate limit dan 2FA admin Filament sebelum produksi.
