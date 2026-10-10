# P-001 — Beranda (HomePage)

**ID**: P-001 | **Path rute**: `#/` | **Nama rute**: Beranda | **File sumber**: `src/pages/HomePage.jsx` (569 baris)
**Layout**: `<Layout>` (header, footer, bottom nav)

---

## 1. Parameter & Query String
Tidak ada parameter path. Tidak ada query string yang dikonsumsi halaman ini.

## 2. Komponen Anak (Pohon)

```
HomePage (P-001)
├── HeroCarousel (inline — HomePage.jsx:82)
│   └── [slide loop] Link → /katalog?...  (7 slide: 4 banner-only + 3 default)
├── [Promo Side Card] (inline, konstan — Yealink MeetingBar A40)
├── [Benefits Bar] (inline — 4 item statis: BENEFITS array)
├── [Flash Sale Section]
│   ├── useCountdown() (custom hook inline, setInterval 1s)
│   └── FlashSaleCard (inline, per produk — HomePage.jsx:224)
├── [Category Grid]
│   └── Link → /katalog?kategori={slug} (maks 8 kategori top-level)
├── [Brand Logos]
│   └── Link → /katalog?brand={slug} (maks 9 brand)
├── [Featured Products Section]
│   ├── ProductGridSkeleton (Skeleton.jsx) — saat loading
│   └── ProductCard (ProductCard.jsx) × maks 8
└── [Sale Products Section]
    ├── ProductGridSkeleton (Skeleton.jsx) — saat loading
    └── ProductCard (ProductCard.jsx) × maks 8
```

## 3. Kebutuhan Data

| # | Fungsi | Argumen | Bentuk Hasil | Urutan |
|---|---|---|---|---|
| 1 | `getFeaturedProducts(8)` | limit=8 | `Product[]` | Paralel — Promise.all |
| 2 | `getSaleProducts(8)` | limit=8 | `Product[]` | Paralel |
| 3 | `getFlashSaleProducts(10)` | limit=10 | `Product[]` | Paralel |
| 4 | `getCategories()` | — | `Category[]` | Paralel |
| 5 | `getBrands()` | — | `Brand[]` | Paralel |

Setelah data diterima:
- Categories: filter `!parent_id && !is_addon && !is_brand_group`, ambil `.slice(0,8)`
- Brands: `.slice(0,9)`
- Semua dipanggil sekali saat mount (`[]` dependency)

## 4. State

| ID | Nama | Awal | Diubah oleh | Persisten |
|---|---|---|---|---|
| ST-001 | `featured` | `propFeatured \|\| []` | useEffect mount | Tidak |
| ST-002 | `sale` | `propSale \|\| []` | useEffect mount | Tidak |
| ST-003 | `flashSale` | `[]` | useEffect mount | Tidak |
| ST-004 | `cats` | `propCats \|\| []` | useEffect mount + propCats effect | Tidak |
| ST-005 | `brands` | `[]` | useEffect mount | Tidak |
| ST-006 | `loading` | `!propFeatured` (true jika tidak ada props) | setLoading(false) di finally | Tidak |
| ST-007 | `active` (carousel) | `0` | goTo(idx), setInterval | Tidak |
| ST-008 | `countdown` (flash sale) | `{h,m,s}` dihitung dari midnight | setInterval 1s | Tidak |

## 5. Efek Samping (useEffect)

| ID | Dependency | Tujuan | Cleanup |
|---|---|---|---|
| EF-001 | `[]` (mount) | Load 5 data: featured, sale, flash, cats, brands paralel | — |
| EF-002 | `[propCats]` | Saat Inertia kirim propCats → filter & set cats | — |
| EF-003 | `[propFeatured]` | Saat Inertia kirim propFeatured → load brands | — |
| EF-004 | `[]` dalam HeroCarousel | setInterval 5000ms auto-slide | `clearInterval` |
| EF-005 | `[]` dalam useCountdown | setInterval 1000ms countdown | `clearInterval` |

## 6. Interaksi & Event Handler

| Interaksi | Handler | Hasil |
|---|---|---|
| Klik dot carousel | `goTo(idx)` | Ganti slide, reset timer |
| Klik tombol prev/next carousel | `goTo((active±1) % n)` | Ganti slide, reset timer |
| Auto-advance carousel | setInterval 5000ms | Slide berikutnya |
| Klik banner slide | `<Link to={slide.link}>` | Navigasi ke katalog |
| Klik tombol CTA slide default | `<Link to={slide.cta.href}>` | Navigasi ke katalog |
| Klik kategori | `<Link to={/katalog?kategori=slug}>` | Navigasi ke katalog |
| Klik brand | `<Link to={/katalog?brand=slug}>` | Navigasi ke katalog |
| Klik produk (ProductCard) | `<Link to={/produk/slug}>` | Navigasi ke detail produk |
| Klik "Lihat Semua Flash Sale" | `<Link to="/katalog?sort=sale">` | Navigasi ke katalog |
| Klik "Lihat Semua Featured" | `<Link to="/katalog">` | Navigasi ke katalog |

## 7. Keadaan UI

| Keadaan | Komponen | Perilaku |
|---|---|---|
| Loading data | Featured & Sale section | `ProductGridSkeleton` ditampilkan, Flash Sale section disembunyikan |
| Flash sale kosong | Flash Sale section | Section tidak dirender (kondisi: `loading \|\| flashSale.length > 0`) |
| Gambar produk gagal | ProductCard | Fallback ke placeholder (lihat ProductCard) |
| Gambar promo side card gagal | `onError` handler | Fallback ke `picsum.photos/seed/yealink-a40/600/300` |
| Flash sale berakhir (countdown 0) | countdown display | Tetap tampil `00:00:00` |

## 8. Perbedaan Mobile vs Desktop

| Aspek | Mobile | Desktop |
|---|---|---|
| Hero layout | 1 kolom penuh | 12-col grid: hero 8 col + promo side 4 col |
| Hero tinggi | min-h-[300px] | min-h-[460px] |
| Flash sale header | countdown + "Semua" link inline | countdown + "Lihat Semua" di kanan |
| Flash sale scroll | horizontal scroll | horizontal scroll |
| Category grid | 2 kolom | 4-6 kolom |
| Benefits bar | 1 kolom, dengan divider horizontal | 4 kolom |
| Brand logo | grid scroll | grid responsif |
| Bottom nav | tampil (via Layout) | tersembunyi |

## 9. SEO/Head

Tidak ada `document.title` assignment eksplisit — pakai default dari `index.html` ("Accommerce.id").
Tidak ada library head manager.

## 10. Navigasi

**Masuk dari**: `<Layout>` link beranda, tombol Back dari halaman lain
**Keluar ke**: `/katalog` (berbagai filter), `/produk/:slug`, `/checkout`

---

## 11. Target Laravel + Inertia

| Item | Nilai |
|---|---|
| Inertia page | `resources/js/Pages/Home.jsx` |
| Route Laravel | `GET /` → `HomeController@index`, nama `home` |
| Controller props | `featuredProducts: Product[]`, `saleProducts: Product[]`, `flashSaleProducts: Product[]`, `categories: Category[]`, `brands: Brand[]` |
| Shared props | `cartCount` (via HandleInertiaRequests) |
| Partial reload | Tidak perlu (halaman statis) |
| preserveState | false |
| Head | `<Head title="Beranda — Accommerce.id" />` |
| HERO_SLIDES | Tetap hardcoded di komponen (bukan dari DB) |

### Kontrak Props
```typescript
interface HomeProps {
  featuredProducts: ProductIndex[];  // maks 8
  saleProducts: ProductIndex[];      // maks 8
  flashSaleProducts: ProductIndex[]; // maks 10, harus punya sale_price
  categories: Category[];            // semua 88
  brands: Brand[];                   // semua 25
}
```

---

## 12. Kriteria Penerimaan Paritas

- **Given** halaman Beranda dimuat, **When** tidak ada data prop, **Then** skeleton muncul selama loading
- **Given** halaman Beranda dimuat, **When** data tersedia, **Then** max 8 featured, max 10 flash sale dengan `sale_price != null`, max 9 brand tampil
- **Given** carousel aktif, **When** tidak ada interaksi 5 detik, **Then** slide berpindah otomatis
- **Given** gambar banner gagal load, **When** `onError` terpicu, **Then** fallback picsum muncul
- **Given** mobile 390px, **When** halaman dimuat, **Then** hero 1 kolom penuh, benefits 1 kolom, bottom nav tampil
- **Given** `flashSaleProducts.length === 0`, **When** halaman dimuat, **Then** section Flash Sale tidak dirender sama sekali
