# 📦 Fase 0 — Inventarisasi Otomatis
> Sumber kebenaran: kode. Tanggal inventarisasi: 2026-10-10.

---

## A. Seluruh Berkas di `src/`

| ID | Path | Ukuran (byte) | Baris | Jenis |
|---|---|---|---|---|
| F-S01 | `src/main.jsx` | 239 | 9 | Entry point |
| F-S02 | `src/App.jsx` | 3.366 | 81 | Router & Provider tree |
| F-S03 | `src/index.css` | 330 | 21 | Global CSS |
| F-S04 | `src/components/Footer.jsx` | 6.865 | 115 | Komponen |
| F-S05 | `src/components/InvoiceModal.jsx` | 11.097 | 214 | Komponen |
| F-S06 | `src/components/Layout.jsx` | 31.881 | 779 | Layout utama (nav, mega menu, bottom nav) |
| F-S07 | `src/components/LicenseConfigModal.jsx` | 14.816 | 299 | Komponen |
| F-S08 | `src/components/ProductCard.jsx` | 14.804 | 336 | Komponen |
| F-S09 | `src/components/ProtectedRoute.jsx` | 505 | 14 | Guard route |
| F-S10 | `src/components/PwaBadge.jsx` | 1.639 | 41 | Install prompt PWA |
| F-S11 | `src/components/RentalBookingModal.jsx` | 19.849 | 434 | Komponen |
| F-S12 | `src/components/ReviewModal.jsx` | 11.364 | 237 | Komponen |
| F-S13 | `src/components/Skeleton.jsx` | 3.551 | 86 | Loading skeleton |
| F-S14 | `src/context/AuthContext.jsx` | 10.593 | 304 | Context: auth + user state |
| F-S15 | `src/context/CartContext.jsx` | 3.567 | 130 | Context: cart + promo |
| F-S16 | `src/context/ToastContext.jsx` | 3.750 | 108 | Context: toast/notifikasi |
| F-S17 | `src/context/WishlistContext.jsx` | 1.164 | 49 | Context: wishlist |
| F-S18 | `src/data/brands.json` | 2.663 | 1 | Data: 25 brand |
| F-S19 | `src/data/categories.json` | 13.555 | 1 | Data: 88 kategori |
| F-S20 | `src/data/products.json` | 2.407.587 | 1 | Data: produk lengkap + deskripsi HTML |
| F-S21 | `src/data/products-index.json` | 341.951 | 1 | Data: index ringkas 539 produk |
| F-S22 | `src/data/produk-deskripsi-tinymce.json` | 2.180.641 | 7.423 | Data: deskripsi HTML TinyMCE |
| F-S23 | `src/data/variants.json` | 102.051 | 1 | Data: 285 varian |
| F-S24 | `src/data/wc-data-export.json` | 4.999.357 | 83.102 | Data mentah WooCommerce (OPEN-Q: apakah aktif dikonsumsi?) |
| F-S25 | `src/pages/BantuanPage.jsx` | 6.994 | 165 | Halaman Bantuan |
| F-S26 | `src/pages/CartPage.jsx` | 15.614 | 296 | Halaman Keranjang |
| F-S27 | `src/pages/CatalogPage.jsx` | 17.356 | 389 | Halaman Katalog |
| F-S28 | `src/pages/CategoryMenuPage.jsx` | 10.659 | 243 | Halaman Menu Kategori (mobile) |
| F-S29 | `src/pages/CheckoutPage.jsx` | 81.279 | 1.385 | Halaman Checkout |
| F-S30 | `src/pages/DashboardPage.jsx` | 115.261 | 2.057 | Halaman Akun / Dashboard |
| F-S31 | `src/pages/ForgotPasswordPage.jsx` | 21.965 | 407 | Halaman Lupa Password |
| F-S32 | `src/pages/HomePage.jsx` | 28.298 | 541 | Halaman Beranda |
| F-S33 | `src/pages/LacakPesananPage.jsx` | 8.710 | 175 | Halaman Lacak Pesanan |
| F-S34 | `src/pages/LoginPage.jsx` | 14.791 | 232 | Halaman Login |
| F-S35 | `src/pages/ProductDetailPage.jsx` | 96.408 | 1.988 | Halaman Detail Produk |
| F-S36 | `src/pages/RegisterPage.jsx` | 16.692 | 260 | Halaman Registrasi |
| F-S37 | `src/pages/RfqPage.jsx` | 5.555 | 96 | Halaman Minta Penawaran |
| F-S38 | `src/pages/SearchPage.jsx` | 5.320 | 119 | Halaman Pencarian (mobile) |
| F-S39 | `src/pages/WishlistPage.jsx` | 2.417 | 52 | Halaman Wishlist |
| F-S40 | `src/services/productService.js` | 6.059 | 208 | Service layer |

**Total: 40 berkas src/**

---

## B. Berkas Root & Config

| ID | Path | Keterangan |
|---|---|---|
| F-R01 | `vite.config.js` | Vite + @vitejs/plugin-react + vite-plugin-pwa (generateSW mode) |
| F-R02 | `tailwind.config.js` | 57 token warna, 9 font, 7 spacing, 2 animasi kustom; darkMode: "class" |
| F-R03 | `postcss.config.js` | autoprefixer |
| F-R04 | `package.json` | 3 deps runtime, 6 deps dev |
| F-R05 | `index.html` | Plus Jakarta Sans (Google Fonts), Material Symbols Outlined (Google CDN) |

**Tidak ada `.env` / `import.meta.env` yang dipakai.**

---

## C. Aset `public/`

| ID | Path | Ukuran | Dirujuk di |
|---|---|---|---|
| F-P01 | `public/favicon.png` | 7 KB | index.html, vite.config.js (PWA icon 192/512) |
| F-P02 | `public/accommerce-blue.png` | 19 KB | Layout.jsx (logo navbar) |
| F-P03 | `public/accommerce-white.png` | 18 KB | Layout.jsx (logo sidebar/mobile) |
| F-P04 | `public/login-illustration.jpg` | 699 KB | LoginPage.jsx, RegisterPage.jsx |
| F-P05 | `public/banner/diskon-yealink.png` | 1,5 MB | HomePage.jsx (hero carousel) |
| F-P06 | `public/banner/october-sale.png` | 1,3 MB | HomePage.jsx |
| F-P07 | `public/banner/potrait-e-wallet-1-2.png` | 305 KB | HomePage.jsx |
| F-P08 | `public/banner/zoom-educate.png` | 6,2 MB | HomePage.jsx |
| F-P09 | `public/banner/zoom.png` | 3,7 MB | HomePage.jsx |
| F-P10 | `public/method/qris-accommerce.png` | 604 KB | CheckoutPage.jsx |
| F-P11 | `public/products/Meeting-bar-yealink.webp` | 118 KB | HomePage.jsx / CheckoutPage.jsx |

**Font eksternal**: Plus Jakarta Sans (Google Fonts CDN — index.html).
**Ikon**: Material Symbols Outlined (Google CDN — index.html) — dipakai via `<span class="material-symbols-outlined">nama_ikon</span>`.

---

## D. Dependensi

### Runtime
| ID | Paket | Versi | Dipakai di | Padanan Laravel/Inertia |
|---|---|---|---|---|
| D-01 | `react` | ^19.3.0 | Semua berkas | Tetap |
| D-02 | `react-dom` | ^19.3.0 | `src/main.jsx` | Tetap |
| D-03 | `react-router-dom` | ^7.18.4 | App.jsx + semua halaman | **Dihapus** → `@inertiajs/react` (`Link`, `router`, `usePage`) + `ziggy-js` |

### DevDependencies
| ID | Paket | Versi | Padanan Laravel/Inertia |
|---|---|---|---|
| D-04 | `vite` | ^8.3.4 | Tetap (pakai laravel-vite-plugin) |
| D-05 | `@vitejs/plugin-react` | ^6.1.2 | Tetap |
| D-06 | `tailwindcss` | ^3.4.19 | Tetap |
| D-07 | `postcss` | ^8.5.29 | Tetap |
| D-08 | `autoprefixer` | ^10.6.1 | Tetap |
| D-09 | `vite-plugin-pwa` | ^2.0.0 | Tetap (kompatibel dengan Laravel Vite) |

---

## E. Semua Route React Router

**Router**: `HashRouter` (URL dengan `#/`). Di Laravel perlu keputusan: tetap hash atau ganti ke `BrowserRouter` → lihat `DECISIONS.md`.

| ID | Path (hash) | Komponen | Layout | Guard | Query Params |
|---|---|---|---|---|---|
| R-01 | `#/` | HomePage | `<Layout>` | — | — |
| R-02 | `#/katalog` | CatalogPage | `<Layout>` | — | `page`, `category`, `brand`, `q`, `sort`, `in_stock`, `min_price`, `max_price` |
| R-03 | `#/produk/:slug` | ProductDetailPage | `<Layout>` | — | — |
| R-04 | `#/keranjang` | CartPage | `<Layout>` | — | — |
| R-05 | `#/wishlist` | WishlistPage | `<Layout>` | — | — |
| R-06 | `#/checkout` | CheckoutPage | `<Layout>` | ProtectedRoute | — |
| R-07 | `#/minta-penawaran` | RfqPage | `<Layout>` | — | — |
| R-08 | `#/lacak-pesanan` | LacakPesananPage | `<Layout>` | — | — |
| R-09 | `#/bantuan` | BantuanPage | `<Layout>` | — | — |
| R-10 | `#/akun` | DashboardPage | `<Layout>` | ProtectedRoute | `tab` |
| R-11 | `#/cari` | SearchPage | Tanpa Layout | — | `q` |
| R-12 | `#/kategori` | CategoryMenuPage | Tanpa Layout | — | — |
| R-13 | `#/login` | LoginPage | Tanpa Layout | — | — |
| R-14 | `#/register` | RegisterPage | Tanpa Layout | — | — |
| R-15 | `#/lupa-password` | ForgotPasswordPage | Tanpa Layout | — | — |
| R-16 | `#/*` | NotFound (inline App.jsx:29) | `<Layout>` | — | — |

**Total: 16 rute (2 protected, 4 tanpa layout)**

---

## F. localStorage Keys

| ID | Key | File | Format | Default |
|---|---|---|---|---|
| LS-01 | `accommerce_user` | AuthContext.jsx:10,159,178,191,199,208 | JSON object user | null |
| LS-02 | `accommerce_cart` | CartContext.jsx:8,37,58 | JSON array CartItem[] | DEFAULT_DEMO_ITEMS (2 item) |
| LS-03 | `accommerce_promo` | CartContext.jsx:9,50,63 | JSON object promo | null |
| LS-04 | `accommerce_wishlist` | WishlistContext.jsx:9,30 | JSON array string[] (product IDs) | ['prod-1','prod-3'] |
| LS-05 | `accommerce_reviews_<key>` | DashboardPage.jsx | JSON array review[] | [] |

---

## G. Event Listeners (window/document)

| ID | Event | File | Baris | Tujuan |
|---|---|---|---|---|
| EV-01 | `beforeinstallprompt` | PwaBadge.jsx | — | Capture PWA install prompt |
| EV-02 | `appinstalled` | PwaBadge.jsx | — | Sembunyikan badge setelah install |
| EV-03 | `scroll` | Layout.jsx | — | Sticky header shadow (scrollY > 50px) |
| EV-04 | `resize` | Layout.jsx | — | Tutup mega-menu saat masuk mobile |
| EV-05 | `keydown` (Escape) | Layout.jsx | — | Tutup mega-menu |
| EV-06 | `online` / `offline` | Layout.jsx | — | Tampilkan/sembunyikan banner offline |

---

## H. Custom Hooks

| ID | Hook | File | Dikonsumsi di |
|---|---|---|---|
| CH-01 | `useAuth()` | AuthContext.jsx:301 | DashboardPage, CheckoutPage, LoginPage, RegisterPage, ForgotPasswordPage, Layout, ProtectedRoute |
| CH-02 | `useCart()` | CartContext.jsx:125 | CartPage, CheckoutPage, ProductDetailPage, Layout, RentalBookingModal |
| CH-03 | `useToast()` | ToastContext.jsx:104 | ProductDetailPage, CartPage, CheckoutPage, Layout, DashboardPage |
| CH-04 | `useWishlist()` | WishlistContext.jsx:46 | ProductDetailPage, WishlistPage, DashboardPage, ProductCard |

---

## I. Service Functions (productService.js)

| ID | Fungsi | Argumen | Return Shape | Catatan |
|---|---|---|---|---|
| SV-01 | `getCategories()` | — | `Category[]` | sync import |
| SV-02 | `getBrands()` | — | `Brand[]` | sync import |
| SV-03 | `getProducts(opts)` | page, per_page(24), category, brand, q, sort, in_stock, min_price, max_price | `{data,total,page,per_page,total_pages}` | async, lazy load index |
| SV-04 | `getProduct(slug)` | `slug: string` | `Product\|null` | async, lazy load products.json |
| SV-05 | `getVariants(productId)` | `productId: number` | `Variant[]` | async, lazy load variants.json |
| SV-06 | `getRelatedProducts(product, limit=6)` | `product, limit` | `Product[]` | sama kategori, bukan produk ini, urutan array |
| SV-07 | `getFeaturedProducts(limit=8)` | `limit` | `Product[]` | is_featured=true dulu, fallback slice pertama |
| SV-08 | `getFlashSaleProducts(limit=8)` | `limit` | `Product[]` | is_flash_sale && sale_price, sort % diskon desc |
| SV-09 | `getSaleProducts(limit=8)` | `limit` | `Product[]` | sale_price != null, sort % diskon desc |
| SV-10 | `formatPrice(amount)` | `number\|null` | `string` | Intl.NumberFormat id-ID, IDR, 0 fraction |

### Algoritma `getProducts` (pseudocode):
```
1. load products-index.json
2. filter: is_addon === false
3. if category: find cat by slug/full_path; getAllDescendantIds(cats, cat.id); filter by category_ids
4. if brand: find brand by slug; filter by brand_id
5. if q: qLower = q.toLowerCase(); filter name.lower.includes(q) OR sku.lower.includes(q)
6. if in_stock: filter stock > 0
7. if min_price: filter regular_price >= min_price
8. if max_price: filter regular_price <= max_price
9. sort: price_asc(a-b regular_price) | price_desc(b-a) | name_asc(localeCompare id) | featured(is_featured desc) | default(no sort)
10. total = items.length; total_pages = max(1, ceil(total/per_page))
11. safePage = min(max(1,page), total_pages); start = (safePage-1)*per_page
12. return { data: items.slice(start, start+per_page), total, page, per_page, total_pages }
```
**Tie-break**: tidak ada tie-break eksplisit — urutan array JSON asli dipertahankan.

### Algoritma `getAllDescendantIds` (pseudocode):
```
function getAllDescendantIds(cats, rootId):
  ids = [rootId]
  children = cats.filter(c => c.parent_id === rootId)
  for child in children:
    ids.push(...getAllDescendantIds(cats, child.id))
  return ids
```

---

## J. Struktur Data JSON (Field Nyata)

### products-index.json (539 produk, ringkas)
```jsonc
{
  "id": 536,                          // number, PK
  "sku": "",                          // string (nullable/empty)
  "name": "JETE CE8 TWS ...",         // string
  "slug": "jete-ce8-tws-...",         // string, URL-friendly, unique
  "type": "variable",                 // "simple" | "variable"
  "is_featured": false,               // boolean
  "regular_price": 1255000,           // number (IDR, integer)
  "sale_price": null,                 // number|null
  "is_dummy_price": true,             // boolean — harga estimasi, BUKAN harga resmi
  "stock": 43,                        // number (integer)
  "images": ["url1, url2, url3"],     // ⚠️ array berisi SATU STRING koma-separated
  "category_ids": [37, 87, 4],        // number[]
  "brand_id": 25,                     // number|null
  "is_addon": false,                  // boolean — produk add-on (asuransi, dll)
  "is_flash_sale": false,             // boolean (tidak selalu ada di semua record)
  "attributes": [
    { "name": "Color", "value": "Beige, Black" }  // array of {name, value} strings
  ]
}
```

### variants.json (285 varian)
```jsonc
{
  "id": 279,                          // number, PK
  "product_id": 538,                  // number, FK ke products
  "source_id": "15916",               // string, ID dari WooCommerce
  "sku": "981-001499",                // string
  "name": "Logitech Zone Wireless...",// string (nama lengkap + suffix varian)
  "regular_price": 3639016,           // number
  "sale_price": 3466016,              // number|null
  "is_dummy_price": false,            // boolean
  "is_price_outlier": false,          // boolean — harga outlier, dikecualikan dari tampilan
  "stock": 4,                         // number
  "images": ["url1"],                 // string[] (BENAR array of strings, beda dari products-index)
  "attributes": [
    { "name": "Tipe", "value": "UC" } // array of {name, value}
  ]
}
```

### categories.json (88 kategori)
```jsonc
{
  "id": 1,                            // number, PK
  "name": "Aksesoris",                // string
  "slug": "aksesoris",                // string
  "full_path": "Aksesoris",           // string — breadcrumb path
  "parent_id": null,                  // number|null — self-referencing
  "is_addon": false,                  // boolean
  "is_brand_group": false,            // boolean — grup brand (parent_id=2=Brand)
  "product_count": 52                 // number
}
```

### brands.json (25 brand)
```jsonc
{
  "id": 1,                            // number, PK
  "name": "Logitech",                 // string
  "slug": "logitech",                 // string
  "logo": "https://logo.clearbit.com/logitech.com",  // string URL
  "product_count": 129                // number
}
```

---

## K. Tailwind Token Kustom (Ringkasan)

### Warna (57 token penting)
| Token | Nilai | Peran |
|---|---|---|
| `primary` | #003599 | Tombol utama, link, aksen |
| `secondary` | #0049cb | Aksen sekunder |
| `text-primary` | #0B1C30 | Teks utama |
| `text-secondary` | #565E74 | Teks muted |
| `card-bg` | #FFFFFF | Background kartu |
| `surface` | #EFF4FF | Background ringan |
| `page-background` | #F8F9FF | Background halaman |
| `background` | #faf8ff | Background global |
| `border-subtle` | #E3E9F5 | Border kartu halus |
| `error` / `error-promo` | #ba1a1a | Error, harga coret |
| `outline` | #737686 | Outline, icon muted |
| `outline-variant` | #c3c5d7 | Divider |
| `secondary-container` | #085efe | Badge aktif |
| `on-primary` | #ffffff | Teks di atas primary |

*(Semua 57 token lihat `tailwind.config.js:10-66`)*

### Font (semua: Plus Jakarta Sans)
`font-headline-hero`, `font-title-card`, `font-body-md`, `font-headline-section`, `font-headline-section-mobile`, `font-label-sm`, `font-sku` (monospace), `font-headline-hero-mobile`, `font-price`

### Animasi
| Nama kelas | Durasi | Efek |
|---|---|---|
| `animate-slideUp` | 0.25s ease-out | Bottom sheet mobile masuk dari bawah |
| `animate-fade` | 0.3s ease-out | Toast notifikasi fade+slide dari atas |

### Dark mode: `class` — dikonfigurasi tapi **belum aktif** di kode saat ini.

---

## L. PWA (vite.config.js)

| Konfigurasi | Nilai |
|---|---|
| `registerType` | `'prompt'` — tidak auto-update, user dikonfirmasi |
| `manifest.name` | Accommerce.id |
| `manifest.short_name` | Accommerce |
| `manifest.theme_color` | #003599 |
| `manifest.display` | standalone |
| `manifest.orientation` | portrait-primary |
| PWA icons | favicon.png (192x192 & 512x512, any maskable) |
| `workbox.globPatterns` | `**/*.{js,css,html,ico,svg,json}` |
| `workbox.globIgnores` | `banner/**`, `products/**` (banner & produk tidak di-cache SW) |
| `workbox.maximumFileSizeToCacheInBytes` | 10 MiB |
| Runtime cache 1 | `picsum.photos` — CacheFirst, 50 entries, 30 hari |
| Runtime cache 2 | `images.unsplash.com` — CacheFirst, 20 entries, 30 hari |

---

## M. Ringkasan Jumlah (untuk audit Fase 8)

| Kategori | Jumlah |
|---|---|
| Berkas `src/` | 40 |
| Halaman React | 15 (13 pages + NotFound inline + ProtectedRoute) |
| Komponen | 10 |
| Context | 4 |
| Custom Hooks | 4 |
| Service functions | 10 |
| localStorage keys | 5 |
| Event listeners | 6 |
| Rute React Router | 16 |
| Token warna Tailwind | 57 |
| Token font Tailwind | 9 |
| Animasi kustom Tailwind | 2 |
| Aset `public/` | 11 |
| Produk (index) | 539 |
| Varian | 285 |
| Kategori | 88 |
| Brand | 25 |

---

> **Laporan Fase 0 selesai.** 40 berkas, 16 rute, 5 localStorage key, 6 event listener, 57 token warna, 10 service function. Hal yang belum jelas → OPEN-QUESTIONS.md.
