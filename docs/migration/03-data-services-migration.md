# Fase 3: Panduan Migrasi Data & Layanan (Services)

Aplikasi React saat ini sepenuhnya bersifat statis dalam pengambilan data. Semua fungsi "API" disimulasikan melalui `src/services/productService.js` yang mem-filter dan menyortir file JSON raksasa yang berada di direktori `src/data/`.

Dalam migrasi ke Laravel, file `.json` ini akan diimpor (seeding) ke dalam database MySQL/PostgreSQL, dan fungsi di `productService.js` akan digantikan oleh `ProductController` dengan kueri Eloquent.

---

## 1. Skema Database yang Dibutuhkan

Berdasarkan analisis file JSON di `src/data/`, berikut adalah model Eloquent yang harus dibuat di backend:

> Nama field di bawah disesuaikan dengan struktur JSON nyata di `00-inventory.md` bagian J. Versi sebelumnya memakai nama yang tidak ada di data (`level`, `logo_url`, `price`).

1. **Category** (`categories.json`)
   - Atribut: `id`, `parent_id`, `name`, `slug`, `full_path`, `is_addon`, `is_brand_group`, `product_count`
   - Tidak ada field `level` di JSON. Jika perlu, hitung saat seeding.
   - Relasi: `parent`, `children`, `products` (Many-to-Many via `category_product`)
2. **Brand** (`brands.json`)
   - Atribut: `id`, `name`, `slug`, `logo` (URL), `product_count`
   - Relasi: `products` (One-to-Many)
3. **Product** (`products.json`, `products-index.json`)
   - Atribut: `id`, `brand_id` (nullable), `name`, `slug` (unique), `sku` (boleh kosong), `type` (`simple`|`variable`), `regular_price`, `sale_price` (nullable), `is_dummy_price`, `is_flash_sale`, `stock`, `is_featured`, `is_addon`, `description` (HTML, dari `products.json`), `images`, `attributes` (JSON array `{name, value}`), `sort_order`
   - `images` di `products-index.json` adalah **array berisi satu string yang dipisahkan koma** (`["url1, url2, url3"]`). Seeder harus memecahnya menjadi array URL yang benar.
   - `sort_order` = urutan asli di JSON. Diperlukan karena `getProducts` mempertahankan urutan array JSON saat tidak ada sort (lihat bagian 2).
   - Relasi: `categories` (via `category_product`), `brand`, `variants`
4. **Variant** (`variants.json`)
   - Atribut: `id`, `product_id`, `source_id`, `sku`, `name`, `regular_price`, `sale_price` (nullable), `is_dummy_price`, `is_price_outlier`, `stock`, `images` (array URL), `attributes` (JSON array `{name, value}`)
   - Varian dengan `is_price_outlier = true` dikecualikan dari tampilan.
5. **Loyalitas** (tabel baru, tidak berasal dari JSON): lihat `04-loyalty-system-migration.md`.

**Catatan harga:** `is_dummy_price = true` berarti harga estimasi, bukan harga resmi. Pertahankan flag ini sampai ke checkout, dan putuskan apakah boleh menghasilkan poin (pertanyaan terbuka di dokumen 04).

---

## 2. Pemetaan Fungsi `productService.js` ke Eloquent Laravel

File `productService.js` berisi beberapa fungsi asinkron utama. Di bawah ini adalah ekuivalen Eloquent-nya:

### A. Kueri Katalog (Pagination, Filter, Search, Sort)
**Di React:** `getProducts({ page, per_page, category, brand, q, sort, in_stock, min_price, max_price })`
**Di Laravel (Contoh Controller):**
```php
public function index(Request $request) {
    $query = Product::with(['brand', 'categories'])->where('is_addon', false);

    // Kategori
    if ($request->category) {
        $query->whereHas('categories', function($q) use ($request) {
            $q->where('full_path', 'like', $request->category . '%');
        });
    }

    // Brand
    if ($request->brand) {
        $query->whereHas('brand', function($q) use ($request) {
            $q->where('slug', $request->brand);
        });
    }

    // Search
    if ($request->q) {
        $query->where(function($q) use ($request) {
            $q->where('name', 'like', "%{$request->q}%")
              ->orWhere('sku', 'like', "%{$request->q}%");
        });
    }

    // In Stock
    if ($request->in_stock) $query->where('stock', '>', 0);
    // Price
    if ($request->min_price) $query->where('regular_price', '>=', $request->min_price);
    if ($request->max_price) $query->where('regular_price', '<=', $request->max_price);

    // Sort
    match($request->sort) {
        'price_asc' => $query->orderBy('regular_price', 'asc'),
        'price_desc' => $query->orderBy('regular_price', 'desc'),
        'name_asc' => $query->orderBy('name', 'asc'),
        'featured' => $query->orderByDesc('is_featured'),
        default => $query->latest()
    };

    $products = $query->paginate($request->get('per_page', 24))->withQueryString();
    
    // Kirim via Inertia
    return Inertia::render('Catalog', [
        'products' => $products,
        'filters' => $request->all(['category', 'brand', 'q', 'sort', 'in_stock', 'min_price', 'max_price'])
    ]);
}
```

### B. Detail Produk Tunggal
**Di React:** `getProduct(slug)`, `getVariants(productId)`, `getRelatedProducts(product)`
**Di Laravel:**
```php
public function show($slug) {
    $product = Product::with(['brand', 'categories', 'variants'])->where('slug', $slug)->firstOrFail();
    
    $related = Product::whereHas('categories', function($q) use ($product) {
        $q->whereIn('id', $product->categories->pluck('id'));
    })->where('id', '!=', $product->id)->take(4)->get();

    return Inertia::render('ProductDetail', [
        'product' => $product,
        'relatedProducts' => $related,
        'reviews' => $product->reviews()->with('user')->latest()->get()
    ]);
}
```

### C. Ketidaksesuaian dengan Perilaku React (perbaiki agar benar-benar 1:1)

Kode contoh di atas belum identik dengan `productService.js` (lihat algoritma di `00-inventory.md` bagian I):

| Hal | Di React | Contoh Laravel di atas | Perbaikan |
|---|---|---|---|
| Filter kategori | Cari kategori berdasarkan `slug` atau `full_path`, ambil **semua keturunan** (`getAllDescendantIds`), filter `category_ids` | `full_path like '{category}%'` (bisa salah cocok, misal "Aksesoris" ikut mencakup "Aksesoris Kabel Lain") | Cari kategori, kumpulkan id keturunan (recursive CTE atau method), lalu `whereIn` pada pivot |
| Sort default | Tanpa sort, urutan array JSON | `latest()` | `orderBy('sort_order')` |
| Sort `featured` / harga / nama | Tanpa tie-break (urutan JSON dipertahankan) | Tanpa tie-break | Tambahkan `->orderBy('sort_order')` sebagai tie-break |
| Respons paginasi | `{ data, total, page, per_page, total_pages }` | Paginator Laravel (`links`, `meta`, dst.) | Sesuaikan props halaman Katalog, atau bungkus agar bentuknya sama |
| Produk terkait | Kategori sama, bukan produk ini, urutan array. Default fungsi **limit 6**, tetapi halaman Detail Produk memanggil dengan **4** | `take(4)` tanpa urutan | Jadikan limit sebuah parameter (default 6; Detail Produk meminta 4) + `orderBy('sort_order')` |
| Nama query string Katalog | URL memakai bahasa Indonesia: `halaman`, `kategori`, `brand`, `q`, `sort`, `stok`, `min`, `max` (lihat `pages/catalog.md`). `00-inventory.md` bagian E mencatat nama Inggris (`page`, `category`, `in_stock`, `min_price`, `max_price`) sebagai nama argumen service, bukan URL | Controller membaca `$request->category`, `in_stock`, `min_price`, `max_price`, dan paginator memakai `page` | Pemetaan eksplisit di controller: `kategori`→category, `stok`→in_stock, `min`→min_price, `max`→max_price, dan `->paginate(24, ['*'], 'halaman')` agar URL tetap identik |
| Nilai sort | Katalog mendukung `price_asc \| price_desc \| name_asc \| featured`; link Beranda "Lihat Semua Flash Sale" memakai `?sort=sale` yang **tidak ditangani** `getProducts` (jatuh ke default) | `match` tanpa `sale` | Putuskan: replikasi perilaku itu (1:1) atau tambah sort/filter `sale`. Catat sebagai bug yang sudah ada |
| Ulasan | Disimpan di `localStorage` (`accommerce_reviews_<key>`), **tidak ada model Review** | `$product->reviews()` (relasi tidak ada) | Buat model `Review` dan tabelnya dulu, atau hapus baris itu sampai Fase ulasan |
| Fungsi lain | `getFeaturedProducts`, `getFlashSaleProducts`, `getSaleProducts`, `formatPrice` | Tidak dicakup | Tambahkan: featured (fallback ke produk pertama), flash sale (`is_flash_sale` + `sale_price`, urut % diskon desc), sale (`sale_price` tidak null, urut % diskon desc), format harga Intl id-ID |

## 3. Strategi Migrasi Data Awal (Seeding)
Jangan buat data dari nol. Gunakan data yang sudah ada di `src/data/` sebagai sumber Seeder Laravel.

1. Buat *Command* atau `DatabaseSeeder` di Laravel.
2. Baca file `categories.json`, masukkan ke tabel `categories`.
3. Baca `brands.json`, masukkan ke `brands`.
4. Baca `products.json`, loop setiap produk, masukkan ke tabel `products`. Jangan lupa menyambungkan relasi Many-to-Many ke tabel pivot `category_product` (karena di JSON ada properti array `category_ids`).
5. Baca `variants.json`, masukkan ke `variants`.

Dengan melakukan seeder ini, tampilan halaman akan 100% konsisten dengan versi React yang sekarang.
