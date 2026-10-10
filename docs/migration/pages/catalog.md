# P-002 — Katalog (CatalogPage)

**ID**: P-002 | **Path rute**: `#/katalog` | **Nama rute**: Katalog | **File sumber**: `src/pages/CatalogPage.jsx` (426 baris)
**Layout**: `<Layout>`

---

## 1. Parameter & Query String
Tidak ada parameter path. Query string (tersinkronisasi penuh dengan state halaman lewat `useSearchParams`):
- `halaman`: `string` (default '1') → page aktif
- `kategori`: `string` (default null) → slug kategori (full_path)
- `brand`: `string` (default null) → slug brand
- `q`: `string` (default empty) → kata kunci pencarian
- `sort`: `string` (default 'default') → `price_asc | price_desc | name_asc | featured`
- `stok`: `string` (jika '1' = in_stock true)
- `min`: `string` (cast to integer) → harga minimal
- `max`: `string` (cast to integer) → harga maksimal
**Cara sinkron ke URL**: `setSearchParams(next)` — replace default (push state ke history browser sehingga tombol Back berfungsi antar perubahan filter).
Saat ada filter baru, parameter `halaman` dihapus (kembali ke 1).

## 2. Komponen Anak (Pohon)

```
CatalogPage (P-002)
├── FilterPanel (desktop sidebar & mobile sheet)
│   ├── Sort radio buttons
│   ├── Category list
│   ├── Brand list
│   ├── Rentang Harga (Min/Max inputs)
│   ├── Hanya Stok checkbox
│   └── Reset button
├── FilterSheet (mobile)
│   └── FilterPanel (isSheet = true)
├── ProductGridSkeleton (Skeleton.jsx) — saat loading
├── ProductCard (ProductCard.jsx) — dirender sebanyak result.data
└── Pagination
    └── Tombol Next/Prev & nomor halaman (dengan ellipsis)
```

## 3. Kebutuhan Data

| # | Fungsi | Argumen | Bentuk Hasil | Urutan |
|---|---|---|---|---|
| 1 | `getCategories()` | — | `Category[]` | Paralel, sekali saat mount |
| 2 | `getBrands()` | — | `Brand[]` | Paralel, sekali saat mount |
| 3 | `getProducts(params)` | `{page, category, brand, q, sort, in_stock, min_price, max_price}` | `{data:Product[], total, page, per_page, total_pages}` | Tiap URL parameter berubah |

- **Categories**: difilter lokal `!c.is_addon` untuk sidebar/panel.
- **Top Categories**: di FilterPanel, dikategorikan lagi `!c.parent_id && !c.is_addon`.

## 4. State (React State & URL Params)

| ID | Nama | Awal | Diubah oleh | Persisten |
|---|---|---|---|---|
| ST-001 | `result` | `{data:[], total:0, page:1, per_page:24, total_pages:0}` | hasil dari `getProducts()` | Tidak |
| ST-002 | `categories` | `[]` | hasil dari `getCategories()` | Tidak |
| ST-003 | `brands` | `[]` | hasil dari `getBrands()` | Tidak |
| ST-004 | `loading` | `true` | setLoading (saat fetch) | Tidak |
| ST-005 | `showFilter` | `false` | tombol Filter mobile | Tidak |
| ST-URL | `searchParams` | dari URL query | interaksi FilterPanel & Pagination | Ya (URL history) |

## 5. Efek Samping (useEffect)

| ID | Dependency | Tujuan | Cleanup |
|---|---|---|---|
| EF-001 | `[]` | Ambil categories & brands awal | — |
| EF-002 | `[searchParams.toString()]` | Set loading, ambil getProducts dengan parameter baru, lalu set result, dan `window.scrollTo({top:0, behavior:'smooth'})` | — |
| EF-003 | `[open]` (dalam FilterSheet) | Jika open true: `document.body.style.overflow = 'hidden'`. Jika false: hapus overflow style. | `document.body.style.overflow = ''` |

## 6. Interaksi & Event Handler

| Interaksi | Handler | Hasil |
|---|---|---|
| Pilih sort/kategori/brand | `set(key, val)` | set key di URL, delete 'halaman', `setSearchParams` |
| Isi input min/max harga | `set('min', val)` | (onChange real-time ketik) set URL param |
| Ceklis "Hanya stok tersedia" | `set('stok', '1'\|'')` | set URL param |
| Klik "Reset Filter" | `reset()` | `setSearchParams(new URLSearchParams())` + close sheet |
| Ganti halaman (Pagination) | `handlePageChange(p)` | set `halaman` param di URL |
| Klik tombol Filter mobile | `setShowFilter(true)` | Buka bottom sheet |
| Klik luar sheet / X button | `onClose` | Tutup bottom sheet |

## 7. Keadaan UI

| Keadaan | Komponen | Perilaku |
|---|---|---|
| Loading | List Produk | Tampilkan `ProductGridSkeleton` jumlah 12, hide total result counter |
| Kosong (0 hasil) | Main Area | Tampilkan state kosong (ikon search_off, "Produk tidak ditemukan", "Coba ubah filter", tombol "Reset Semua Filter") |
| Pagination di ujung | `Pagination` | Tombol `Prev` disabled jika page <= 1, tombol `Next` disabled jika page >= total_pages |
| Filter Mobile aktif | Tombol "Filter & Urut" | Tampilkan badge merah jumlah filter aktif di atas tombol (dihitung dari jumlah query param yang terisi) |

## 8. Perbedaan Mobile vs Desktop

| Aspek | Mobile | Desktop |
|---|---|---|
| Layout Utama | 1 kolom | 2 kolom (sidebar kiri 240px, produk di kanan) |
| Filter UI | Modal Bottom Sheet (`FilterSheet`) | Panel sticky kiri statis |
| Title Bar | Sticky atas + tombol filter | Tergabung di atas grid produk |
| Result Count | Ditampilkan kecil di bawah title | Sebaris dengan title |
| Scroll body | Dikunci saat FilterSheet terbuka | Normal, filter panel pakai `overflow-y-auto` di dalam container max-h |

## 9. SEO/Head
Title halaman (H1 visual) berubah dinamis: "Hasil pencarian {q}" / "{kategori.name}" / "Brand: {brand.name}" / "Semua Produk".
Tidak ada modifikasi `<title>` tag di head.

## 10. Navigasi
**Masuk dari**: Header search, Mega menu, Beranda (banner/brand)
**Keluar ke**: Detail Produk (`/produk/:slug`)

---

## 11. Target Laravel + Inertia

| Item | Nilai |
|---|---|
| Inertia page | `resources/js/Pages/Catalog.jsx` |
| Route Laravel | `GET /katalog` → `CatalogController@index`, nama `catalog` |
| Controller props | `products: LengthAwarePaginator`, `categories: Category[]`, `brands: Brand[]`, `filters: object` |
| Partial reload | **YA**. Filter berubah -> Inertia visit dengan `only: ['products', 'filters']`, `preserveState: true`, `preserveScroll: false` (ganti scrollTo custom). |
| Kunci debounce | Input rentang harga min/max (jika langsung dikirim ke server per huruf, perlu didelay/debounce agar tidak memborbardir server). |
| Pagination | Bentuk standar paginator Laravel. Meta pagination (`links`, `current_page`, `last_page`) perlu dipetakan ke komponen Pagination kustom atau diubah. |

---

## 12. Kriteria Penerimaan Paritas

- **Given** berada di halaman Katalog, **When** saya pilih kategori A, **Then** URL berubah menjadi `?kategori=A` dan produk yang tampil hanya kategori A (tanpa reload halaman).
- **Given** saya berada di page 3 filter X, **When** saya pilih filter brand Y, **Then** query parameter `halaman` terhapus (kembali ke page 1) dan filter brand ditambahkan.
- **Given** saya berada di mobile, **When** saya tekan tombol Filter, **Then** bottom sheet muncul dari bawah dan scroll body terkunci.
- **Given** saya mengisi input harga maksimal, **When** mengetik angka, **Then** UI langsung merespons dan list produk ter-filter sesuai rentang.
- **Given** filter menghasilkan 0 produk, **When** fetch selesai, **Then** komponen error "Produk tidak ditemukan" muncul dengan tombol reset.
- **Given** tombol "Reset Semua Filter" diklik pada state kosong, **Then** seluruh parameter URL dihapus dan halaman kembali ke state "Semua Produk".
