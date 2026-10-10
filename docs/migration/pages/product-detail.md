# P-003 — Detail Produk (ProductDetailPage)

**ID**: P-003 | **Path rute**: `#/produk/:slug` | **Nama rute**: ProductDetail | **File sumber**: `src/pages/ProductDetailPage.jsx` (2090 baris)
**Layout**: `<Layout>` (menyediakan header, mega menu, footer, bottom nav mobile)

---

## 1. Parameter & Query String
- Parameter URL: `slug` (identifikator produk).
- Query String: Tidak terikat pada data produk, tapi mungkin membawa tracking.

## 2. Komponen Anak (Pohon)

```
ProductDetailPage (P-003)
├── Skeleton UI (ketika loading)
├── Gallery (inline)
│   ├── Gambar Utama dengan Badges (kiri/kanan overlay)
│   ├── Thumbnail List (overflow-x-auto)
│   └── Share / Copy Link actions
├── ReviewSection (inline)
│   ├── Ulasan Header (Rata-rata rating, total ulasan, terjual)
│   ├── Form Tulis Ulasan (Photo, Video, Anonim, Rating Bintang, Komentar)
│   ├── Lightbox (SelectedImageModal)
│   └── Review List (Menampilkan history review)
├── ProductCard (komponen eksternal, digunakan untuk "Produk Terkait")
└── [Mobile sticky Add-to-Cart bar] (Tampil khusus ukuran mobile di bawah layar)
```

## 3. Kebutuhan Data

| # | Fungsi | Argumen | Bentuk Hasil | Urutan |
|---|---|---|---|---|
| 1 | `getProduct(slug)` | `slug` param | `Product\|null` | 1 (Primary) |
| 2 | `getVariants(productId)` | `product.id` | `Variant[]` | Setelah #1 selesai |
| 3 | `getBrands()`, `getCategories()` | — | `Brand[]`, `Category[]` | Setelah #1 selesai (Paralel dengan #2) |
| 4 | `getRelatedProducts(product, 4)` | `product`, 4 | `Product[]` | Setelah #1 selesai (Paralel) |

**Penanganan Not Found**: Jika `getProduct` mereturn `null`, maka merender state "Produk Tidak Ditemukan" dengan tombol kembali ke katalog.

## 4. State Lokal Halaman

| ID | Nama | Awal | Diubah oleh | Persisten |
|---|---|---|---|---|
| ST-001 | `product` | `null` | `useEffect` load awal | Tidak |
| ST-002 | `variants` | `[]` | `useEffect` load kedua | Tidak |
| ST-003 | `relatedProducts` | `[]` | `useEffect` load kedua | Tidak |
| ST-004 | `brand`, `categories` | `null`, `[]` | `useEffect` load kedua | Tidak |
| ST-005 | `loading` | `true` | setelah fetch product | Tidak |
| ST-006 | `quantity` | `1` | stepper plus/minus | Tidak |
| ST-007 | `selectedVariant` | `null` | diklik oleh user pada opsi varian | Tidak |
| ST-008 | `added` | `false` | saat klik "+ Keranjang" (lalu timeout revert) | Tidak |
| ST-009 | `showRentalModal` | `false` | klik "Booking & Bayar" (jika isRental) | Tidak |
| ST-010 | `showLicenseModal` | `false` | klik "Aktifkan Lisensi" (jika isLicense) | Tidak |
| ST-011 | `activeTab` | `"deskripsi"` | tab review vs deskripsi | Tidak |

## 5. Efek Samping (useEffect)

| ID | Dependency | Tujuan | Cleanup |
|---|---|---|---|
| EF-001 | `[slug]` | Memanggil `getProduct(slug)`. Reset state loading. Scroll ke top. | — |
| EF-002 | `[product]` | Jika `product` ada, fetch variants, related, brands, dan categories secara paralel. Memilih default `selectedVariant` (varian pertama). | — |
| EF-003 | `[]` dalam ReviewSection | Ambil localStorage ulasan | — |

## 6. Integrasi Context

| Context | Hook | Penggunaan |
|---|---|---|
| `AuthContext` | `useAuth()` | `user` (Cek login), `updateUser` (tambah poin setelah review) |
| `CartContext` | `useCart()` | `addToCart(item)` |
| `WishlistContext` | `useWishlist()` | `wishlist`, `toggleWishlist` |
| `ToastContext` | `useToast()` | `showToast()` untuk notifikasi copy link, login, dsb |

## 7. Interaksi & Logika Khusus

1. **Harga & Diskon**: Dihitung dinamis tergantung apakah `selectedVariant` ada atau menggunakan default `product`. Harga asli/diskon diambil dari properti tersebut.
2. **Ketersediaan Stok**: Diturunkan dari variant jika terpilih, atau produk utama.
3. **Form Ulasan (ReviewSection)**:
   - Akses terkunci jika: belum login, atau tidak memiliki pesanan (dummy orders array di `user`) yang cocok dengan nama produk, atau sudah direview semua pesanannya.
   - **Revisi 4:** ulasan hanya untuk produk dari pesanan berstatus **Selesai**, **maksimal 1 ulasan per produk** (bukan per pesanan). Pencocokan pesanan dengan produk di Laravel harus lewat `order_items.product_id` (bukan pencocokan nama produk seperti di React).
   - Menyimpan ulasan di localStorage dengan key `accommerce_reviews_{product.id}`.
   - Terdapat upload dummy untuk foto (base64 ImageReader) dan video.
   - Bonus +50 poin diberikan kepada `user` melalui `updateUser()` dari AuthContext. **Di Laravel:** poin diberikan oleh server setelah ulasan tersimpan, masuk ke **saldo poin** (bukan `tier_points`), lewat `LoyaltyService::awardForReview()` (lihat `04-loyalty-system-migration.md`). Klien tidak memanggil `updateUser()` untuk poin.
4. **Varian Selection**: Render kotak-kotak pilihan dari atribut. Menentukan URL gambar `Gallery` berdasarkan pilihan.
5. **Mobile Sticky Bar**: Pada layar < lg, muncul barisan tombol fixed di bawah (Stepper, Add to Cart, Buy Now). Desktop merender card lengket di sebelah kanan.
6. **Buy Now Action**: `handleBuyNow()` menambahkan produk ke cart jika belum ada, lalu langsung `navigate("/checkout")`.

## 8. Target Laravel + Inertia

| Item | Nilai |
|---|---|
| Inertia page | `resources/js/Pages/ProductDetail.jsx` |
| Route Laravel | `GET /produk/{slug}` → `ProductController@show`, nama `product.show` |
| Controller props | `product: Product`, `variants: Variant[]`, `relatedProducts: Product[]`, `reviews: Review[]`, `userOrders: Order[]` |
| Partial reload | Tidak diperlukan (statis page render) |
| SEO | Menerapkan `meta` tags title, description, dan image (OpenGraph) berdasarkan atribut `product`. |
| Perilaku Ulasan | Diubah dari localStorage ke HTTP request `POST /reviews` dengan Form request validasi file upload. Foto/video dari base64 `FileReader` (dummy) menjadi upload file ke storage. Server menegakkan: pesanan Selesai, maks. 1 ulasan per produk. |
| Produk terkait | `getRelatedProducts(product, 4)`: halaman ini meminta **4** produk (default fungsi 6, lihat `03-data-services-migration.md`). |
| Belum terdefinisi | Field `isRental` / `isLicense` (memicu `RentalBookingModal` / `LicenseConfigModal`) tidak ada di skema produk `03`/`00-inventory`. Tentukan asalnya (kategori? atribut?) sebelum migrasi. Angka "terjual" di header ulasan juga belum jelas sumbernya. |

## 9. Kriteria Penerimaan Paritas

- **Given** berada di halaman Detail Produk, **When** tidak menemukan data slug, **Then** tampilkan UI Not Found khusus.
- **Given** melihat detail dengan varian, **When** mengklik salah satu opsi varian, **Then** harga, stok, dan gambar Gallery terupdate tanpa reload page.
- **Given** user login dan belum pernah membeli, **When** melihat tab ulasan, **Then** muncul warning "Ulasan hanya dapat ditulis setelah Anda memesan...".
- **Given** user login dan telah membeli (pesanan Selesai), **When** berhasil mengirim ulasan pertama untuk produk itu, **Then** saldo poin bertambah +50 (tier_points tidak berubah) dan ulasan masuk di atas list.
- **Given** user sudah pernah mengulas produk yang sama, **When** membeli lagi dan membuka form ulasan, **Then** form terkunci (maks. 1 ulasan per produk).
- **Given** lebar layar mobile, **When** melihat halaman detail, **Then** muncul panel Add to Cart sticky (tetap berada di layar terbawah).
- **Given** menekan "+ Keranjang", **Then** keranjang Context bertambah, teks berubah "Ditambahkan!", lalu kembali normal.
