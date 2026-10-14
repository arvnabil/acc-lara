# Status Audit Slicing Front/src ke Inertia

Tanggal audit: 2026-10-11  
Scope: audit read-only. Tidak ada kode atau commit yang diubah oleh audit ini. Satu-satunya berkas yang dibuat adalah dokumen ini.

## Ringkasan

Build entry Inertia lulus, tetapi migrasi Slice 1 belum selesai. Dari 16 route di `Front/src/App.jsx`, route Laravel dan resolver Inertia saat ini hanya tersedia untuk `/`, `/katalog`, `/produk/{slug}`, `/kategori`, `/cari`, serta `/login` yang baru ditambahkan. Route client lain belum tersedia. Permintaan HTTP tidak dapat dibuktikan dari sandbox karena koneksi ke `127.0.0.1:8000` ditolak oleh lingkungan; hasil probe dicatat apa adanya di bawah.

### Bukti build

Perintah:

```text
npm run build
```

Keluaran penting:

```text
vite v8.3.4 building client environment for production...
✓ 582 modules transformed.
✓ built in 1.33s
app-DsC-jLjB.js 522.73 kB
(!) Some chunks are larger than 500 kB after minification.
EXIT=0
```

Peringatan ukuran chunk bukan import error. Build hanya mengimpor entry yang dipetakan di `resources/js/app.jsx`; berkas yang tidak dipetakan tetap dapat memiliki import rusak, terutama `DashboardPage.jsx`.

### Bukti test list

Perintah:

```text
php artisan test --list-tests --compact
```

Keluaran:

```text
PHPUnit 12.5.33 by Sebastian Bergmann and contributors.
Cannot open bootstrap script "C:\development\accommerce-laravel\vendor/autoload.php"
```

Karena perintah gagal sebelum discovery, daftar nama test dan pemetaan test per halaman tidak dapat dibuktikan lewat `--list-tests` pada audit ini. Berkas test yang terlihat dari inventaris adalah `tests/Feature/CatalogStorefrontTest.php`, `AccountsPromoResourcesTest.php`, `AccountsTest.php`, `AdminAccessTest.php`, `D3SchemaTest.php`, `ExampleTest.php`, `ModuleManagerTest.php`, `PermissionsSyncTest.php`, `RegistrationTest.php`, dan `TimezoneTest.php`.

## A. Inventaris sumber

### A.1 Seluruh JSX/JS di Front/src, tanpa data/*.json

Perintah yang menghasilkan daftar dan jumlah baris:

```powershell
$files = rg --files Front/src | Where-Object { $_ -notmatch '^Front/src/data/' -and $_ -match '\.(jsx|js)$' }
foreach ($f in $files) { (Get-Content $f | Measure-Object -Line).Lines }
```

Keluaran:

```text
Front/src/services/productService.js                 182
Front/src/pages/WishlistPage.jsx                      52
Front/src/pages/SearchPage.jsx                       119
Front/src/pages/RfqPage.jsx                           96
Front/src/pages/RegisterPage.jsx                     260
Front/src/pages/ProductDetailPage.jsx              1988
Front/src/pages/LoginPage.jsx                        232
Front/src/pages/LacakPesananPage.jsx                 175
Front/src/pages/HomePage.jsx                         541
Front/src/pages/ForgotPasswordPage.jsx               407
Front/src/pages/DashboardPage.jsx                   1943
Front/src/pages/CheckoutPage.jsx                    1385
Front/src/pages/CategoryMenuPage.jsx                 243
Front/src/pages/CatalogPage.jsx                      389
Front/src/pages/CartPage.jsx                         296
Front/src/pages/BantuanPage.jsx                      165
Front/src/main.jsx                                     9
Front/src/context/WishlistContext.jsx                 41
Front/src/context/ToastContext.jsx                    95
Front/src/context/CartContext.jsx                    116
Front/src/context/AuthContext.jsx                    285
Front/src/components/Skeleton.jsx                     86
Front/src/components/ReviewModal.jsx                 237
Front/src/components/RentalBookingModal.jsx          434
Front/src/components/PwaBadge.jsx                     41
Front/src/components/ProtectedRoute.jsx                14
Front/src/components/ProductCard.jsx                 336
Front/src/components/LicenseConfigModal.jsx          299
Front/src/components/Layout.jsx                      739
Front/src/components/InvoiceModal.jsx                214
Front/src/components/Footer.jsx                      115
Front/src/App.jsx                                      76
```

### A.2 Route map Front/src/App.jsx

Perintah:

```text
Get-Content Front/src/App.jsx
```

Keluaran route yang dibaca dari berkas:

| Route | Komponen | Layout | Guard |
|---|---|---|---|
| `/cari` | SearchPage | tanpa Layout | none |
| `/kategori` | CategoryMenuPage | tanpa Layout | none |
| `/login` | LoginPage | tanpa Layout | none |
| `/register` | RegisterPage | tanpa Layout | none |
| `/lupa-password` | ForgotPasswordPage | tanpa Layout | none |
| `/` | HomePage | Layout | none |
| `/katalog` | CatalogPage | Layout | none |
| `/produk/:slug` | ProductDetailPage | Layout | none |
| `/keranjang` | CartPage | Layout | none |
| `/wishlist` | WishlistPage | Layout | none |
| `/checkout` | CheckoutPage | Layout | ProtectedRoute |
| `/minta-penawaran` | RfqPage | Layout | none |
| `/lacak-pesanan` | LacakPesananPage | Layout | none |
| `/bantuan` | BantuanPage | Layout | none |
| `/akun` | DashboardPage | Layout | ProtectedRoute |
| `*` | NotFound | Layout | none |

Import pembuktiannya juga menunjukkan `HashRouter`, seluruh Provider context lama, dan `ProtectedRoute` masih merupakan arsitektur sumber.

## B. Status berkas dan route

Tidak ada padanan di `Modules/*/resources/js`; perintah berikut tidak mengeluarkan apa pun:

```text
rg --files Modules | rg 'resources[\\/]js|\.jsx$|\.js$'
```

Semua padanan yang ada berada di `resources/js`.

### B.1 Bukti diff --no-index

Perintah:

```powershell
git diff --no-index --numstat -- Front/src/<path> resources/js/<path>
```

Keluaran angka untuk setiap berkas:

| Berkas | Padanan | Tambah | Hapus | Kesetiaan |
|---|---|---:|---:|---|
| pages/BantuanPage.jsx | resources/js/pages/BantuanPage.jsx | 1 | 1 | SETIA: import |
| pages/CartPage.jsx | resources/js/pages/CartPage.jsx | 3 | 3 | SETIA: import |
| pages/CatalogPage.jsx | resources/js/pages/CatalogPage.jsx | 7 | 19 | SETIA: import + data Inertia/loading |
| pages/CategoryMenuPage.jsx | resources/js/pages/CategoryMenuPage.jsx | 5 | 11 | SETIA: import + data Inertia |
| pages/CheckoutPage.jsx | resources/js/pages/CheckoutPage.jsx | 4 | 4 | SETIA: import |
| pages/DashboardPage.jsx | resources/js/pages/DashboardPage.jsx | 4 | 4 | SETIA: import, tetapi tidak ter-render |
| pages/ForgotPasswordPage.jsx | resources/js/pages/ForgotPasswordPage.jsx | 1 | 1 | SETIA: import |
| pages/HomePage.jsx | resources/js/pages/HomePage.jsx | 4 | 40 | SETIA: import + data Inertia |
| pages/LacakPesananPage.jsx | resources/js/pages/LacakPesananPage.jsx | 2 | 2 | SETIA: import |
| pages/LoginPage.jsx | resources/js/pages/LoginPage.jsx | 3 | 3 | SETIA: import; auth masih stub |
| pages/ProductDetailPage.jsx | resources/js/pages/ProductDetailPage.jsx | 21 | 46 | SETIA: import + data Inertia/loading |
| pages/RegisterPage.jsx | resources/js/pages/RegisterPage.jsx | 3 | 3 | SETIA: import; auth masih stub |
| pages/RfqPage.jsx | resources/js/pages/RfqPage.jsx | 0 | 0 | SETIA |
| pages/SearchPage.jsx | resources/js/pages/SearchPage.jsx | 1 | 1 | SETIA: import |
| pages/WishlistPage.jsx | resources/js/pages/WishlistPage.jsx | 3 | 3 | SETIA: import; wishlist stub |
| components/Footer.jsx | resources/js/components/Footer.jsx | 1 | 1 | SETIA: import |
| components/InvoiceModal.jsx | resources/js/components/InvoiceModal.jsx | 0 | 0 | SETIA |
| components/Layout.jsx | resources/js/components/Layout.jsx | 5 | 11 | SETIA: import + category props |
| components/LicenseConfigModal.jsx | resources/js/components/LicenseConfigModal.jsx | 4 | 4 | SETIA: import |
| components/ProductCard.jsx | resources/js/components/ProductCard.jsx | 6 | 6 | SETIA: import |
| components/ProtectedRoute.jsx | resources/js/components/ProtectedRoute.jsx | 2 | 2 | SETIA: import |
| components/PwaBadge.jsx | resources/js/components/PwaBadge.jsx | 0 | 0 | SETIA |
| components/RentalBookingModal.jsx | resources/js/components/RentalBookingModal.jsx | 4 | 4 | SETIA: import |
| components/ReviewModal.jsx | resources/js/components/ReviewModal.jsx | 0 | 0 | SETIA |
| components/Skeleton.jsx | resources/js/components/Skeleton.jsx | 0 | 0 | SETIA |
| context/AuthContext.jsx | resources/js/context/AuthContext.jsx | 0 | 0 | SETIA copy, tidak dipakai runtime |
| context/CartContext.jsx | resources/js/context/CartContext.jsx | 0 | 0 | SETIA copy, tidak dipakai runtime |
| context/ToastContext.jsx | resources/js/context/ToastContext.jsx | 1 | 1 | SETIA: import |
| context/WishlistContext.jsx | resources/js/context/WishlistContext.jsx | 0 | 0 | SETIA copy, tidak dipakai runtime |
| services/productService.js | resources/js/services/productService.js | 0 | 0 | SETIA copy, referensi saja |

Contoh keluaran mentah:

```text
pages/CatalogPage.jsx       add=7  del=19
pages/HomePage.jsx          add=4  del=40
pages/ProductDetailPage.jsx add=21 del=46
components/Layout.jsx       add=5  del=11
services/productService.js  add=0  del=0
```

Klasifikasi SETIA di atas mengikuti batas yang diminta: perubahan hanya import, pengambilan data dari props Inertia, loading Inertia, dan adapter. Tidak ada berkas yang teridentifikasi sebagai DIUBAH BANYAK dari diff tersebut.

### B.2 Sisa dependency lama

Perintah:

```text
rg -n "react-router-dom|productService|\.\./data/|localStorage|window\.location\.hash|HashRouter|useAuth|useCart|useWishlist" resources/js
```

Keluaran penting:

```text
resources/js/services/productService.js:7-9  import ../data/*.json
resources/js/services/productService.js:21,29     import ../data/*.json secara dinamis
resources/js/pages/DashboardPage.jsx:6            import ../data/products-index.json
resources/js/context/AuthContext.jsx              localStorage accommerce_user
resources/js/context/CartContext.jsx              localStorage cart/promo
resources/js/context/WishlistContext.jsx          localStorage accommerce_wishlist
resources/js/pages/DashboardPage.jsx               localStorage review
resources/js/pages/ProductDetailPage.jsx           localStorage review
resources/js/pages/SearchPage.jsx                  localStorage accommerce_search_history
resources/js/compat/context.jsx                    useAuth/useCart/useWishlist adalah stub
```

Tidak ada keluaran untuk `react-router-dom`, `HashRouter`, atau `window.location.hash` di `resources/js`. Import lama pada halaman yang sudah dipakai runtime diarahkan ke `compat`; `services/productService.js` dan `DashboardPage.jsx` tidak masuk entry build saat ini.

Isi `resources/js/compat/context.jsx` yang membuktikan stub:

```text
useAuth() -> user dari usePage().props.auth, login/logout/register async kosong
useCart() -> items kosong dan fungsi kosong
useWishlist() -> wishlist kosong dan fungsi kosong
```

### B.3 Route Laravel, controller, resolver, dan HTTP

Perintah route JSON:

```text
php artisan route:list --json --except-vendor
```

Entri yang relevan:

```text
GET|HEAD /             home       Modules\Catalog\Http\Controllers\CatalogController@home
GET|HEAD katalog       catalog    Modules\Catalog\Http\Controllers\CatalogController@index
GET|HEAD produk/{slug} product.show Modules\Catalog\Http\Controllers\CatalogController@show
GET|HEAD kategori      categories Modules\Catalog\Http\Controllers\CatalogController@categories
GET|HEAD cari          search     Modules\Catalog\Http\Controllers\CatalogController@search
GET|HEAD login         login       Closure in routes/web.php:7
POST     login         login.submit Modules\Accounts\Http\Controllers\Storefront\AuthController@login
```

`resources/js/app.jsx` hanya memetakan:

```text
Catalog::HomePage
Catalog::CatalogPage
Catalog::ProductDetailPage
Catalog::CategoryMenuPage
Catalog::SearchPage
Catalog::LoginPage
```

HTTP probe yang diwajibkan:

```powershell
Invoke-WebRequest -UseBasicParsing -Uri <url> -TimeoutSec 5
```

Keluaran untuk `/`, `/katalog`, `/kategori`, `/cari`, `/login`, `/produk/<slug>`, dan `/admin/login` semuanya:

```text
ERROR  An attempt was made to access a socket in a way forbidden by its access permissions. (127.0.0.1:8000)
```

Jadi tidak ada status HTTP sukses yang dapat dibuktikan dari environment audit. Akibat aturan audit, tidak ada route yang boleh disebut SELESAI hanya berdasarkan build.

## Tabel utama route

| Rute / komponen | Slice | Status | Kesetiaan | Rute Laravel | Sisa TODO | Bukti |
|---|---|---|---|---|---|---|
| `/` HomePage | 1 | SEBAGIAN | SETIA | ada, CatalogController@home | HTTP belum teruji | route JSON + HTTP socket error |
| `/katalog` CatalogPage | 1 | SEBAGIAN | SETIA | ada, CatalogController@index | HTTP belum teruji | route JSON + diff 7/19 |
| `/produk/:slug` ProductDetailPage | 1 | SEBAGIAN | SETIA | ada, CatalogController@show | HTTP belum teruji | route JSON + diff 21/46 |
| `/kategori` CategoryMenuPage | 1 | SEBAGIAN | SETIA | ada, CatalogController@categories | HTTP belum teruji | route JSON + diff 5/11 |
| `/cari` SearchPage | 1 | SEBAGIAN | SETIA | ada, CatalogController@search | HTTP belum teruji | route JSON + diff 1/1 |
| `/login` LoginPage | 1 | SEBAGIAN | SETIA | GET closure; POST AuthController | `compat.useAuth` stub; HTTP belum teruji | route JSON + diff 3/3 |
| `/register` RegisterPage | 1 | BELUM | SETIA | BELUM ADA GET; POST saja | resolver Inertia dan GET belum ada; auth stub | App.jsx + route JSON |
| `/lupa-password` ForgotPasswordPage | 1 | BELUM | SETIA | BELUM ADA GET; POST saja | resolver Inertia dan GET belum ada | App.jsx + route JSON |
| `/keranjang` CartPage | 3 | BELUM | SETIA | BELUM ADA | resolver/route; cart stub | App.jsx + compat context |
| `/wishlist` WishlistPage | 3 | BELUM | SETIA | BELUM ADA | resolver/route; wishlist stub | App.jsx + compat context |
| `/checkout` CheckoutPage | 3 | BELUM | SETIA | BELUM ADA | resolver/route; auth/cart stub | App.jsx + compat context |
| `/minta-penawaran` RfqPage | 5 | BELUM | SETIA | BELUM ADA | controller/route/resolver | App.jsx + route JSON |
| `/lacak-pesanan` LacakPesananPage | 4 | BELUM | SETIA | BELUM ADA | resolver/route; auth stub | App.jsx + compat context |
| `/bantuan` BantuanPage | 8 | BELUM | SETIA | BELUM ADA | resolver/route | App.jsx + route JSON |
| `/akun` DashboardPage | 4 | BELUM | SETIA | BELUM ADA | resolver/route; import JSON tidak resolve; auth/wishlist stub | build tidak mengimpor berkas ini |
| `*` NotFound | 1 | BELUM | n.a. | BELUM ADA fallback Laravel | resolver fallback belum ada | App.jsx |
| Layout | 1 | SEBAGIAN | SETIA | dipakai Home/Catalog/Product | cart/auth stub | diff 5/11 |
| ProductCard | 1 | SEBAGIAN | SETIA | dipakai halaman Catalog | cart/auth/wishlist stub | diff 6/6 |
| Skeleton | 1 | SELESAI secara salinan | SETIA | n.a. | tidak ada | diff 0/0 |
| Footer | 1 | SEBAGIAN | SETIA | dipakai Layout | route tujuan belum lengkap | diff 1/1 |
| ToastContext | 1 | SELESAI secara salinan | SETIA | n.a. | tidak ada terdeteksi | diff 1/1 |
| PwaBadge | 1 | SELESAI secara salinan | SETIA | n.a. | tidak ada | diff 0/0 |
| Modal components | 1/3 | SEBAGIAN | SETIA | belum ada semua pemakai route | cart/auth stub | diff 0-4/0-4 |
| Auth/Cart/Wishlist contexts | 3 | SEBAGIAN | SETIA copy | tidak dipakai runtime | compat mengembalikan stub | diff 0/0 |
| productService | 1 | BELUM runtime | SETIA copy | tidak dipakai runtime | import JSON resources/js/data yang tidak ada | diff 0/0 |

## C. Ketergantungan yang belum terpenuhi

### C.1 Import yang tidak ter-resolve

Build entry saat ini lulus, tetapi berkas yang belum dipetakan tidak ikut dianalisis oleh bundler. `DashboardPage.jsx` terbukti memiliki import yang tidak ada:

```text
resources/js/pages/DashboardPage.jsx:6: import productsIndex from '../data/products-index.json';
```

`resources/js/services/productService.js` juga memiliki:

```text
import productsIndex from '../data/products-index.json';
import categoriesData from '../data/categories.json';
import brandsData from '../data/brands.json';
import('../data/products.json');
import('../data/variants.json');
```

Perintah grep tersebut adalah bukti bahwa build lulus bukan bukti semua berkas source dapat di-resolve.

### C.2 TODO atau stub nomor slice

Perintah:

```text
rg -n -i "TODO|FIXME|slice\s*[0-9]|slice\s*[1-8]" Front/src resources/js Modules/Catalog app/Http routes
```

Keluaran:

```text
(tidak ada keluaran)
```

Walaupun tidak ada komentar bernomor Slice, stub fungsional terdeteksi di `resources/js/compat/context.jsx` sebagaimana dicatat pada B.2.

### C.3 Aset

Perintah referensi aset:

```text
rg -n -o "(?:/|\.\.?/)?[A-Za-z0-9_./-]+\.(?:png|jpg|jpeg|svg|webp|gif|ico|avif)" Front/src -g '*.jsx' -g '*.js'
```

Referensi file statis yang dapat diverifikasi ada di `public/`:

```text
public/accommerce-blue.png
public/favicon.png
public/banner/diskon-yealink.png
public/banner/october-sale.png
public/banner/zoom.png
public/banner/zoom-educate.png
public/products/Meeting-bar-yealink.webp
public/method/qris-accommerce.png
```

Referensi `.ico` pada hasil grep adalah nilai ikon Material Symbols/emoji dari object runtime (`item.icon`, `section.icon`, `lv.icon`, `m.icon`, `b.icon`), bukan path aset. Tidak ada path statis yang terbukti hilang dari daftar tersebut. `favicon.ico` juga ada di `public/`.

### C.4 JSON langsung di resources/js

Perintah:

```text
rg -n "Front/src/data|from ['\"].*\.json|import\(.*\.json" resources/js
```

Keluaran:

```text
resources/js/services/productService.js:7-9  ../data/products-index.json, categories.json, brands.json
resources/js/services/productService.js:21,29     ../data/products.json, variants.json
resources/js/pages/DashboardPage.jsx:6            ../data/products-index.json
```

Ini adalah ketergantungan yang belum terpenuhi; dataset sudah dipindah ke `Modules/Catalog/Database/data`, bukan ke `resources/js/data`.

## D. Backend per halaman

| Halaman | Controller/service | Model/tabel | Filament terkait | Test terlihat |
|---|---|---|---|---|
| HomePage | CatalogController@home + CatalogQuery | Product, Category, Brand, ProductImage, ProductAttribute | Product, Category, Brand resource ada | CatalogStorefrontTest terlihat; discovery gagal |
| CatalogPage | CatalogController@index + CatalogQuery | Product, Variant, Category, Brand, images, attributes | Product/Category/Brand ada | CatalogStorefrontTest terlihat |
| ProductDetailPage | CatalogController@show + CatalogQuery | Product, Variant, VariantImage, Brand, Category | Product/Brand/Category ada | CatalogStorefrontTest terlihat |
| CategoryMenuPage | CatalogController@categories + CatalogQuery | Category | CategoryResource ada | CatalogStorefrontTest terlihat |
| SearchPage | CatalogController@search + CatalogQuery.categories | Category | CategoryResource ada | tidak dapat dibuktikan via list-tests |
| LoginPage | Accounts AuthController login untuk POST; GET closure | User, Company | UserResource, CompanyResource, RoleResource ada | AccountsTest/RegistrationTest terlihat; discovery gagal |
| RegisterPage | AuthController register untuk POST | User, Company | UserResource, CompanyResource ada | RegistrationTest terlihat |
| ForgotPasswordPage | AuthController forgot/reset untuk POST | User/password broker | tidak relevan | tidak dapat dibuktikan via list-tests |
| CartPage | BELUM ADA controller/storefront route | Cart, CartItem, PromoCode/Voucher model ada | tidak terkait halaman client | tidak dapat dibuktikan |
| WishlistPage | BELUM ADA controller/storefront route | Wishlist model ada | tidak terkait halaman client | tidak dapat dibuktikan |
| CheckoutPage | BELUM ADA controller/storefront route | Order, OrderItem, PaymentProof, Cart, Voucher ada | OrderResource read-only ada | D3SchemaTest hanya skema, discovery gagal |
| RfqPage | BELUM ADA controller/storefront route | RfqRequest model ada | RfqRequestResource ada | tidak dapat dibuktikan |
| LacakPesananPage | BELUM ADA controller/storefront route | Order model ada | OrderResource ada | tidak dapat dibuktikan |
| BantuanPage | BELUM ADA controller/storefront route | tidak dibutuhkan untuk konten statis | tidak ada | tidak dapat dibuktikan |
| DashboardPage | BELUM ADA controller/storefront route; import JSON rusak | User, Company, LoyaltyAccount, Order, Review ada | Accounts/Loyalty/Orders/Review resources ada | tidak dapat dibuktikan |

Bukti inventaris backend:

```text
Modules/Catalog/Http/Controllers/CatalogController.php
Modules/Catalog/Services/CatalogQuery.php
Modules/Accounts/Http/Controllers/Storefront/AuthController.php
Modules/Catalog/Models/{Product,Variant,Category,Brand,ProductImage,ProductAttribute}.php
Modules/Cart/Models/{Cart,CartItem,Wishlist}.php
Modules/Orders/Models/{Order,OrderItem,OrderStatusHistory,PaymentProof}.php
Modules/Loyalty/Models/{LoyaltyAccount,PointLedger,Redemption}.php
Modules/Review/Models/Review.php
Modules/Content/Models/{Banner,RfqRequest}.php
```

Bukti Filament resources yang ditemukan:

```text
Accounts: UserResource, CompanyResource, RoleResource
Catalog: ProductResource, CategoryResource, BrandResource
Promo: PromoCodeResource, VoucherResource
Orders: OrderResource
Loyalty: LoyaltyAccountResource, PointLedgerResource, RedemptionResource
Review: ReviewResource
Content: BannerResource, RfqRequestResource
```

## Daftar status

### SELESAI

- Salinan berkas `Skeleton`, `PwaBadge`, `InvoiceModal`, `ReviewModal`, dan `RfqPage` identik secara `--numstat`.
- Build entry Inertia berhasil.
- Lima halaman Catalog mempunyai controller, route Laravel, resolver Inertia, dan adapter data: Home, Catalog, ProductDetail, CategoryMenu, Search. Namun status route keseluruhan tetap SEBAGIAN karena HTTP tidak dapat dibuktikan.
- GET `/login` sudah ada dan tidak lagi bertabrakan dengan POST login.

### SEBAGIAN

- Home, Catalog, ProductDetail, CategoryMenu, Search: backend dan resolver ada, tetapi HTTP probe belum berhasil dari environment audit.
- Login: GET route ada, tetapi `useAuth()` runtime masih stub dan form belum terbukti melakukan autentikasi server.
- Layout/ProductCard/modal: salinan ada, tetapi memakai cart/auth/wishlist compatibility stub.
- Context source lama: tersalin, tetapi tidak dipakai runtime; compat context menggantikan provider dengan nilai kosong.
- `productService.js`: salinan ada sebagai referensi, tetapi masih mengimpor JSON yang tidak tersedia di `resources/js/data`.

### BELUM

- Register dan forgot-password GET/Inertia resolver.
- Cart, wishlist, checkout, RFQ, tracking order, bantuan, dan akun/dashboard routes/resolvers.
- DashboardPage runtime karena import `../data/products-index.json` tidak ada.
- Fallback `*` versi Inertia/Laravel.
- Test HTTP/feature penuh untuk seluruh route; test discovery terblokir oleh bootstrap PHPUnit.

## Urutan pengerjaan yang disarankan

1. Slice 2: auth storefront dan dashboard akun dasar.
2. Slice 3: cart, wishlist, promo, dan pricing server-side.
3. Slice 4: checkout, orders, tracking, dan actions OrderResource.
4. Slice 5: loyalty, poin, tier, voucher benefit, dan redemption.
5. Slice 6: review storefront dan moderasi yang sudah ada di Filament.
6. Slice 7: RFQ/penawaran, Content Banner, Bantuan, dan 404.
7. Slice 8: PWA, hardening, test HTTP, dan visual regression.

Temuan yang mengubah urutan: Loyalty ditempatkan sebelum Review karena Dashboard membutuhkan saldo dan tier server-side. `OrderReturned` dimiliki `Modules\Orders\Events`; Loyalty mengimpornya langsung karena Loyalty membutuhkan Orders.

## Audit Slice 2 (per 2026-10-11)

### Perubahan yang diterapkan

- Routes storefront Accounts sekarang menyediakan GET/POST `/login`, `/register`, `/lupa-password`, `/reset-password/{token}`, POST `/logout`, serta `/akun`, `/checkout`, dan CRUD alamat.
- `HandleInertiaRequests` membagikan `auth.user` dengan `id`, `name`, `email`, `phone`, `company`, dan `company_role`.
- Register membuat company perorangan (`npwp = NULL`) dan user `company_role = admin` dalam transaksi, lalu mengirim `CompanyRegistered` setelah commit.
- Login menolak user tanpa company dengan pesan umum dan memakai rate limiter; `/akun` dan `/checkout` memakai middleware `auth`.
- Dashboard menerima data akun/alamat dari controller. Orders, vouchers, wishlist, reviews, dan loyalty masih props kosong/stub sesuai batas Slice 2.
- `resources/js/services/productService.js` dihapus dari runtime.

### Bukti perintah

```text
npm run build
✓ 588 modules transformed.
✓ built in 1.48s
exit code 0
```

```text
php -l Modules/Accounts/Http/Controllers/Storefront/AccountController.php
No syntax errors detected
php -l Modules/Accounts/Http/Controllers/Storefront/AuthController.php
No syntax errors detected
```

### Status berkas Slice 2

| Berkas | Status | Catatan |
|---|---|---|
| LoginPage.jsx | SEBAGIAN | Submit memakai Inertia; Google disembunyikan; source tidak memiliki elemen error khusus sehingga error ditampilkan melalui toast. |
| RegisterPage.jsx | SEBAGIAN | Submit memakai Inertia; Google disembunyikan; field tetap persis UI React. |
| ForgotPasswordPage.jsx | SEBAGIAN | Email dan reset memakai broker Laravel; UI OTP lama tetap ada tetapi tidak punya padanan backend OTP. |
| DashboardPage.jsx | SEBAGIAN | Data demo/localStorage dihapus dari runtime; order/voucher/wishlist/review/loyalty masih props kosong dan TODO slice berikutnya. |
| AccountController.php | SELESAI untuk cakupan Slice 2 | Show akun dan CRUD alamat milik sendiri, validasi city/postal_code, satu alamat utama. |
| AuthController.php | SELESAI untuk cakupan Slice 2 | Auth, register company perorangan, rate limit route, intended redirect, broker reset. |

### Dashboard demo yang sengaja tidak dibawa

- `user.orders` masih dibaca oleh markup pada tab orders dan ringkasan; nilainya sekarang `[]` dari `AccountController`.
- `user.vouchers` masih dibaca pada tab vouchers; nilainya sekarang `[]`.
- `walletBalance` tidak ditemukan lagi di `DashboardPage.jsx`; tidak ada saldo e-wallet internal yang ditambahkan.
- Nilai default tier sekarang `Classic` dan poin `0`, dengan TODO Slice 5.

### Tes Slice 2

Tes ditambahkan di `tests/Feature/StorefrontAuthTest.php` untuk registration company/user/event, intended login, penolakan staff tanpa company, rate limit, reset notification, ownership alamat, guest redirect, dan logout. Eksekusi suite tetap harus dilaporkan berdasarkan keluaran aktual runner; build frontend sudah lulus, sedangkan discovery PHPUnit sebelumnya terhenti pada bootstrap `vendor/autoload.php` yang tidak terbuka.

```text
php artisan test tests/Feature/StorefrontAuthTest.php --compact
PHPUnit 12.5.33 by Sebastian Bergmann and contributors.
Cannot open bootstrap script "C:\\development\\accommerce-laravel\\vendor/autoload.php"
```

```text
vendor/bin/pint --dirty --format agent
Cannot write to file "C:\\development\\accommerce-laravel\\app\\Providers\\AppServiceProvider.php" as it is not writable
```

### Temuan yang belum dapat dinyatakan selesai

- UI ForgotPassword mempertahankan langkah OTP sesuai aturan migrasi 1:1, tetapi Laravel Password Broker mengirim tautan reset bertoken. Ini penyimpangan fungsional yang harus diputuskan pada slice auth berikutnya; UI tidak dihapus.
- Tidak ada klaim HTTP sukses di dokumen ini tanpa menjalankan server dan permintaan aktual.

## Slice 3 progress

- WIP branch: `slice-3-wip`.
- Tahap A commit: `c16c1d4 slice3: markup migration + shared cart`.
- Tahap B commit: `434c5b8 slice3: guest merge + review form`.
- Cart/Wishlist/Pricing routes, server-side price, promo validation, shared cart props, and Login event merge are implemented.
- Rental dates/times are representable by the existing `cart_items` schema and are priced by rental day count. License configuration remains disabled because the ERD has no license configuration column.
- Final Slice 3 browser verification and full Pest execution remain required before claiming completion.

### Slice 3 closeout audit

- `resources/js/context/AuthContext.jsx`, `CartContext.jsx`, and `WishlistContext.jsx` had no imports in `resources/js`; they were removed. The only remaining application `localStorage` usage is `accommerce_search_history` in `SearchPage.jsx`.
- Cart promo, discount, shipping estimate, grand total, and points now come from `cart_summary`; CartPage no longer defines `PROMO_CODES`, `SHIPPING_BASE`, or client discount formulas.
- Dedicated Wishlist access is `/wishlist`; the dashboard tab remains available and reads the same server-backed products.

## Slice 5 progress

- `6fce307 slice5: loyalty core`: konfigurasi tier/bonus/maturity/expiry, `LoyaltyService`, listener pembuatan akun, scanner/job poin, expiry, benefit bulanan, evaluasi akhir tahun, reset tier, backfill command, dan scheduler.
- `8dbc735 slice5: vouchers + redeem`: voucher loyalty, validasi voucher per company, pricing free-shipping/fixed voucher, penggunaan voucher di checkout, pembatalan/retur, dan POST `/loyalty/redeem`.
- `8fb2956 slice5: dashboard`: shared props loyalty, dashboard memakai saldo/tier server, redemption memakai Inertia, dan data voucher company.
- Tahap D saat ini belum boleh disebut final sebelum pengguna menjalankan sinkronisasi permission dan full suite. Filament sudah memiliki adjustment berbasis `LoyaltyService`, relation manager ledger read-only, serta aksi redemption paid/failed.
- O-16 diberi plafon sementara Rp50.000 untuk Gold, Platinum, dan Diamond, ditandai `BELUM DIPUTUSKAN, O-16`; tes Diamond sudah aktif.
- Keputusan implementasi: `expires_at` adjustment pengembalian poin adalah `now()->addMonths(24)`, mengikuti masa kedaluwarsa poin standar; pengembalian tidak menambah `tier_points`.
- `walletBalance` tidak digunakan sebagai saldo internal. Dashboard memakai `balance_value_idr`, `redeemable_points`, dan `ewallet_paid_total_idr` dari server.
- Perubahan teks UI baru pada Slice 5: tidak ada. Perubahan handler hanya mengganti mutasi/demo klien dengan POST Inertia `/loyalty/redeem`; teks existing dipertahankan.

### Bukti Slice 5 sementara

```text
npm run build
✓ 592 modules transformed.
✓ built in 1.74s
```

```text
php artisan test tests\Feature\Slice5LoyaltyTest.php --compact
Tests: 12 passed (11 executed, 1 skipped) (19 assertions)
```

Tes Diamond `Diamond mendapat empat voucher gratis ongkir per bulan` sudah aktif dengan plafon sementara O-16.

### Koreksi Slice 5 terbaru

- `OrderReturned` berada di `Modules\Orders\Events\OrderReturned`; Loyalty hanya mengimpor event tersebut.
- Semua perubahan cache akun loyalti, konsumsi FIFO, expiry, adjustment, reversal, dan redemption dijalankan oleh `LoyaltyService` dalam transaksi dengan lock akun.
- Ledger FIFO dikunci dengan `lockForUpdate()` dan diurutkan `earned_at`, lalu `id` sebagai tie-breaker.
- Bonus dihitung memakai `current_tier` sebelum evaluasi upgrade; upgrade dapat melewati beberapa level dan tidak pernah menurunkan tier.
- Tes berbasis waktu memakai `travelTo` dan tes berat diberi group `slow`.

### Penutupan Slice 5

- Scheduler akhir tahun sekarang hanya memiliki satu jadwal `59 23 31 12 *` yang meng-dispatch `Bus::chain([EvaluateYearEndTierJob, ResetTierPointsJob])`; reset hanya berjalan setelah evaluasi sukses.
- Ditambahkan pengujian urutan chain, kegagalan evaluasi, retur lintas tahun tanpa `tier_points` negatif, kuota e-wallet bulanan versus voucher, poin kedaluwarsa, saldo/nominal redemption, serta penggunaan voucher saat checkout dan pengembaliannya saat batal/kedaluwarsa.
- `ProductDetailPage` menggunakan `points_estimate` dari server untuk dua label poin; tidak lagi menghitung estimasi dari harga di JSX.
- Dashboard menampilkan kartu saldo poin dari `user.balance_value_idr`, nilai yang dapat ditukar dari `user.redeemable_points`, dan riwayat klaim dari `user.ewalletClaims`; `walletBalance` bukan saldo internal.
- Tidak ada perubahan teks baru pada `DashboardPage`; teks existing dipertahankan.
- `laravel/boost` tetap berada di `require-dev` sebagai paket pengembangan.

## Slice 6 prerequisites

Branch `slice-6` dibuat dari `main` setelah Slice 5 close (`4644115`). Ringkasan sumber `Front/src` sebelum migrasi:

- `ReviewModal.jsx` (256 baris): rating 1–5, komentar wajib, maksimal 5 foto, 1 video maksimal 25 MB, checkbox anonim, preview media, dan pesan bonus `+50 Poin`. Upload memakai `FileReader`/data URL dan submit memakai `setTimeout(400ms)`.
- `ProductDetailPage.jsx` `ReviewSection`: membaca ulasan dari `localStorage` per produk; rating fallback `4.9`, jumlah fallback `33`, penjualan hard-coded `120`; pencocokan pesanan memakai nama produk pada `user.orders`; hanya pesanan yang belum ada di daftar ulasan yang dapat dipilih. Belum login mendapat tautan masuk, belum pernah membeli mendapat pesan pembelian, dan produk yang sudah diulas mendapat pesan bahwa seluruh pembelian sudah diulas. Ulasan terbaru berada di depan.
- `DashboardPage.jsx`: tab ulasan memiliki sub-tab `pending` dan `completed`. Pending mengharapkan `user.orders[].items[]` dengan `id`/`name` dan status pesanan; completed mengharapkan objek ulasan dengan `id`, `orderId`, `productName`, `userName`, `userEmail`, `isAnonymous`, `rating`, `comment`, `images`, `video`, dan `date`. Sumber lama menggunakan localStorage, `markOrderItemReviewed`, dan `updateUser` untuk poin.
- `ProductCard.jsx`: tidak menampilkan rating atau jumlah ulasan.

Prasyarat schema Slice 6 hanya additive: migration `2026_02_06_000002_add_anonymity_and_media_to_reviews_table.php` menambahkan `reviews.is_anonymous BOOLEAN DEFAULT FALSE` dan `reviews.media JSON NULL`; comparator dan ERD sudah diperbarui.

## Slice 6 closeout

- Commit tahap A: `a984094 slice6: review core`.
- Commit tahap B: `787d41e slice6: review pages`.
- Commit tahap C: dibuat setelah bukti dan test hijau.
- `ReviewStored` memakai kontrak `reviewId`, `companyId`, `userId`, `productId`, `orderId` dan `ShouldDispatchAfterCommit`. Listener Loyalty memberi 50 poin sekali melalui `LoyaltyService`; `tier_points` tidak berubah dan `expires_at` mengikuti 24 bulan.
- Endpoint review memvalidasi pesanan completed, company, unique company-product, rating, body, mime aktual foto/video, maksimal 5 foto, video maksimal 25 MB, serta menyimpan file ke disk `public` dengan nama acak. Foto memakai batas server 5 MB per file untuk perlindungan upload; modal sumber tidak menetapkan batas ukuran foto.
- Ulasan anonim tidak mengirim `user_id`, email, atau nama asli ke props publik. Produk hanya menerima agregat dan ulasan `is_approved`; ProductCard tidak menampilkan rating/jumlah ulasan.
- `updateUser`, `markOrderItemReviewed`, dan localStorage ulasan sudah dihapus dari runtime. Satu-satunya localStorage tersisa adalah `accommerce_search_history` di `SearchPage.jsx`.

### Bukti Slice 6

```text
php artisan schema:compare-erd
ERD_COMPARE_OK columns/types/nullability/FKs/unique verified

php artisan test tests/Feature/Slice6ReviewTest.php --compact
Tests: 10 passed (56 assertions)

php artisan test --compact
Tests: 105 passed (349 assertions)

npm run build
✓ 592 modules transformed.
✓ built successfully
```

Diff stat sumber vs hasil migrasi:

```text
ReviewModal.jsx       16 insertions(+), 26 deletions(-)
ProductDetailPage.jsx 47 insertions(+), 110 deletions(-)
DashboardPage.jsx    64 insertions(+), 140 deletions(-)
ProductCard.jsx       9 insertions(+), 8 deletions(-)
```

Grep akhir:

```text
localStorage: hanya resources/js/pages/SearchPage.jsx (accommerce_search_history)
updateUser: tidak ada
markOrderItemReviewed: tidak ada
```

Nama test Slice 6:

```text
ulasan_hanya_dapat_dibuat_dari_pesanan_completed (returned, cancelled)
ulasan_dari_item_company_lain_ditolak
sub_akun_boleh_mengulas_pertama_kali_tetapi_review_kedua_company_ditolak
review_memberi_lima_puluh_poin_sekali_dan_tier_points_tidak_berubah
retur_menarik_poin_ulasan
review_anonim_tidak_membocorkan_nama_email_atau_user_id_ke_publik
media_review_menolak_mime_palsu_dan_ukuran_video_berlebih
review_yang_disembunyikan_tidak_tampil_publik_tetapi_tetap_ada_di_admin
rata_rata_dan_jumlah_review_publik_benar
```

`php artisan test --list-tests` menemukan 107 entri termasuk dataset Pest; keluaran lengkap dijalankan setelah implementasi. Permission `approve_review` tersinkron melalui `module:permissions-sync`; ReviewResource memiliki filter rating/approved/product, lihat media melalui record, serta aksi setujui/sembunyikan.

## Slice 7 prerequisites

Branch `slice-7` dibuat dari `main` pada commit Slice 6 (`c93fa32`, merge Slice 6).

### Bukti sumber Front

Jumlah baris berkas yang diaudit:

```text
Front/src/App.jsx	80
Front/src/pages/RfqPage.jsx	103
Front/src/pages/BantuanPage.jsx	173
Front/src/pages/HomePage.jsx	568
Front/src/components/Layout.jsx	778
Front/src/components/Footer.jsx	124
```

`App.jsx` memiliki 16 deklarasi rute: `/cari`, `/kategori`, `/login`, `/register`, `/lupa-password`, `/`, `/katalog`, `/produk/:slug`, `/keranjang`, `/wishlist`, `/checkout` (ProtectedRoute), `/minta-penawaran`, `/lacak-pesanan`, `/bantuan`, `/akun` (ProtectedRoute), dan wildcard `*` (NotFound). Halaman login, register, dan lupa-password tanpa Layout; halaman kategori dan pencarian juga full-screen; halaman lain berada di Layout.

`RfqPage.jsx` memiliki empat field visual: Nama Perusahaan / Pembeli, Nama PIC / Pejabat belanja, Email Pelanggan, dan Nomor WhatsApp Aktif, serta textarea Daftar Kebutuhan Hardware & Spesifikasi Proyek. Submit asli hanya mengganti tampilan lokal; pesan suksesnya adalah `Permintaan Terkirim` dan `Terima kasih! Permintaan penawaran Anda (RFQ) telah kami terima. Tim sales kami akan merespons dalam waktu kurang dari 2 jam kerja.`

`BantuanPage.jsx` berisi empat kelompok FAQ statis (Pemesanan, Pengiriman, Garansi & Produk, Pembayaran), tiga kartu kontak (WhatsApp `https://wa.me/6287780116800`, telepon `tel:02150886800`, dan tautan RFQ), serta CTA WhatsApp yang sama. Tidak ada konstanta kontak yang dipakai; kontak tetap literal seperti sumber.

NotFound inline di `App.jsx` adalah blok ikon `error`, judul `Halaman tidak ditemukan`, dan tautan `href="#/"` berlabel `Kembali ke Beranda`.

`HERO_SLIDES` berisi empat slide `banner-only` berurutan: `diskon-yealink.png` ke `/katalog?brand=yealink`, `october-sale.png` ke `/katalog?sort=sale`, `zoom.png` ke `/katalog?q=zoom`, dan `zoom-educate.png` ke `/katalog?q=zoom`. Tiga slide sisanya adalah slide default dengan badge, teks, gambar latar, CTA utama, dan CTA alternatif. Urutan akhir sumber adalah empat banner lalu tiga default. Sumber tidak membedakan gambar mobile.

### Route list Tahap 0

Keluaran `php artisan route:list` pada branch awal menunjukkan rute storefront yang sudah ada, termasuk `/`, `/katalog`, `/produk/{slug}`, `/cari`, `/kategori`, `/keranjang`, `/checkout`, `/wishlist`, `/akun`, `/login`, `/register`, `/lupa-password`, `/reset-password/{token}`, `/lacak-pesanan`, `/pembayaran/{number}`, dan `/sukses/{number}`. Pada saat audit, `/minta-penawaran` dan `/bantuan` belum ada, dan belum ada fallback route untuk NotFound. Rute `/reset-password/{token}` dan `/sukses/{number}` berasal dari alur Laravel/Checkout dan bukan deklarasi eksplisit di `Front/src/App.jsx`.

### Internal href yang belum memiliki rute Laravel pada audit

`/minta-penawaran` (dipakai Layout, Footer, Bantuan, ProductDetail) dan `/bantuan` (dipakai Layout, Footer, Login, Register, ForgotPassword, LacakPesanan) belum terdaftar. Tautan `#/` pada persetujuan Register dan inline NotFound adalah hash-link legacy dari HashRouter, bukan rute Laravel. Tautan eksternal `wa.me`, `tel:`, `mailto:`, dan aset `/method/qris-accommerce.png` tidak dihitung sebagai rute aplikasi. Tautan internal tersebut dipertahankan dan akan diberi endpoint pada Slice 7; tidak ada tautan yang dihapus.

## Slice 7 closeout

### Tahap A: Content backend

- `ContentServiceProvider` hanya membagikan prop `banners` untuk route bernama `home`; query membatasi `is_active`, `starts_at`, dan `expires_at`, kemudian mengurutkan `sort_order` dan `id`. Catalog tidak mengimpor Content.
- `BannerSeeder` memakai empat aset `public/banner` dan `updateOrCreate` berdasarkan `image_url`: `diskon-yealink.png`, `october-sale.png`, `zoom.png`, dan `zoom-educate.png`. `mobile_image_url` tidak dipakai karena JSX sumber tidak membedakan gambar mobile.
- `HomePage` mempertahankan tiga slide default dan menyusun banner aktif dari props di posisi empat slide banner awal. Bila prop kosong, empat slide banner bawaan tetap dipakai.
- RFQ memiliki GET `/minta-penawaran` dan POST `/minta-penawaran` dengan `throttle:5,1`. Form sumber tidak mempunyai `name` attribute; handler mengambil lima elemen form berdasarkan urutannya dan mengirim `company_name`, `name`, `email`, `phone`, serta `message`. Tidak ada field tambahan yang perlu digabungkan ke `message` selain textarea tersebut. Status awal `new`; notifikasi dikirim ke `config('site.sales_email')` menggunakan `Mail::raw`.
- `/bantuan` merender halaman statis sumber. Fallback route merender `Catalog::NotFound` dengan status HTTP 404. Tidak dibuat halaman 500/503.

### Tahap B: Filament dan tes

BannerResource mendukung unggah `FileUpload` ke disk `public`, urutan, jadwal, dan aktif. RfqRequestResource hanya daftar, filter status, view modal, dan edit status; create/delete dinonaktifkan. Permission Content disinkronkan:

```text
Memulai sinkronisasi permissions dari modul aktif..
Sinkronisasi selesai: 1 dibuat, 3 dihapus, 63 total izin aktif.
```

Nama tes Slice 7:

```text
RFQ tersimpan dengan field benar dan email sales dikirim
RFQ validation fails for missing fields
RFQ sixth attempt in one minute returns 429
inactive and scheduled-out banners are not shared to the home page
home page renders with no banner records
help page renders successfully
random URL returns the Inertia NotFound page with status 404
internal storefront links return 200 or 302
```

Hasil tes fitur:

```text
php artisan test tests/Feature/Slice7ContentTest.php --compact
Tests: 8 passed (58 assertions)
```

### Bukti migrasi dan validasi

Diff stat sumber Front terhadap hasil migrasi:

```text
RfqPage.jsx      17 insertions(+), 2 deletions(-)
BantuanPage.jsx   3 insertions(+), 1 deletion(-)
HomePage.jsx     21 insertions(+), 47 deletions(-)
```

Perubahan teks tidak dilakukan pada markup sumber. Perubahan non-markup adalah import `react-router-dom` ke compat router pada Bantuan, handler submit RFQ dari state lokal ke `router.post`, empat slide banner-only dibaca dari prop server dengan fallback ke array sumber, dan penambahan slide prop ke `HeroCarousel`.

```text
php artisan schema:compare-erd
ERD_COMPARE_OK columns/types/nullability/FKs/unique verified

npm run build
✓ built in 9.04s
```

Tautan internal yang sebelumnya belum punya endpoint (`/bantuan` dan `/minta-penawaran`) sekarang terdaftar. Tautan `#/` pada persetujuan Register dan NotFound tetap legacy HashRouter dan tidak dihapus; usulan berikutnya adalah menggantinya dengan rute kebijakan ketika halaman kebijakan tersedia. Tautan eksternal WhatsApp, telepon, email, dan aset pembayaran tidak diuji sebagai rute Laravel.
