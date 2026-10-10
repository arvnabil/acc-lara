# Fase 2: Panduan Migrasi State & Context (React ke Inertia)

Proyek ini saat ini adalah Single Page Application (SPA) murni dengan `HashRouter` yang memelihara state secara lokal menggunakan React Context API dan `localStorage`. Saat migrasi ke Laravel + Inertia, peran state global ini akan digantikan secara dramatis oleh Server-side session, Database, dan Inertia Shared Data.

Dokumen ini memetakan setiap Context yang ada dan bagaimana mereka harus diimplementasikan di Laravel Inertia agar **berperilaku persis sama**.

---

## 1. AuthContext (`src/context/AuthContext.jsx`)

**Tugas Saat Ini:**
- Menyimpan profil `user`, saldo poin loyalitas, kupon, pesanan *dummy*, dan daftar alamat.
- Menyediakan fungsi mutasi seperti `login()`, `logout()`, `addPoints()`, `redeemPointsForWallet()`, `saveAddress()`, `deleteAddress()`.
- Menyimpan string/JSON ke `localStorage` untuk mensimulasikan persistensi database.

**Target Migrasi (Laravel):**
1. **Model & Database**:
   - `User` (id, name, email, password, phone)
   - `UserAddress` (user_id, label, recipient, phone, province, address, is_default)
   - `Order` (user_id, total, status, dll)
   - **Loyalitas: JANGAN** memakai satu tabel `LoyaltyPoint (balance, tier_points, current_tier)`. Gunakan `companies`, `loyalty_accounts`, `point_ledger`, `redemptions`, dan `vouchers` sesuai **`04-loyalty-system-migration.md`** (Spesifikasi Revisi 4). Poin dan tier dimiliki **akun perusahaan (induk)**, bukan user individual.
2. **Inertia Shared Data**:
   Di `HandleInertiaRequests.php`, bagian `share()`, pass object `auth.user` secara global agar semua halaman bisa membaca nama dan status login tanpa perlu memanggil API.
   ```php
   'auth' => [
       'user' => $request->user() ? $request->user()->load(['addresses']) : null,
   ],
   'loyalty' => /* lihat 04-loyalty-system-migration.md, bagian 6 */,
   ```
   Jangan mengirim relasi ledger loyalitas ke klien. Kirim hanya angka ringkasan yang dibutuhkan UI.
3. **Pengganti Mutasi**:
   Semua fungsi seperti `saveAddress()` dihapus di React, digantikan dengan `router.post('/user/addresses', data)`.
   **Pengecualian loyalitas:** `addPoints()` dan `redeemPointsForWallet()` **tidak** diganti dengan padanan klien. `addPoints()` dihapus total (poin diberikan otomatis oleh server lewat job, bukan lewat aksi klien). `redeemPointsForWallet()` diganti `router.post('/loyalty/redeem', { type, points, idempotency_key })` dengan validasi dan perhitungan rupiah sepenuhnya di server.
4. **Auth guard:** `ProtectedRoute` (rute `#/checkout` dan `#/akun`) diganti middleware `auth` Laravel. Tambahkan pengecekan `company_role` untuk aksi penukaran poin.

---

## 2. CartContext (`src/context/CartContext.jsx`)

**Tugas Saat Ini:**
- Menyimpan array `items` yang berisi produk yang akan dibeli.
- Menghitung `total` dan `itemCount`.
- Menyimpan status promo code (`appliedPromo`).
- Disimpan persisten dalam `localStorage` (key: `accommerce_cart`).

**Target Migrasi (Laravel):**
1. **Disimpan di Database atau Session**:
   Karena Accommerce B2B, keranjang belanja lebih ideal disimpan di tabel database `carts` dan `cart_items` agar tersinkron lintas perangkat saat user login. Jika belum login, gunakan session ID atau cookies.
2. **Global Prop**:
   Sama seperti Auth, bagikan via middleware:
   ```php
   'cart' => [
       'items' => CartService::getItems($request),
       'total' => CartService::calculateTotal($request),
       'count' => CartService::count($request)
   ]
   ```
3. **Pengganti Mutasi**:
   Tombol "Add to Cart" di `ProductDetailPage.jsx` harus memanggil `router.post('/cart/add', { product_id: X, qty: 1 })`.

---

## 3. WishlistContext (`src/context/WishlistContext.jsx`)

**Tugas Saat Ini:**
- Menyimpan array string berupa `product.id` / `sku` di `localStorage` (key: `accommerce_wishlist`).
- Menyediakan `toggleWishlist(id)`.

**Target Migrasi (Laravel):**
1. **Tabel Database**: Pivot table `wishlists` (user_id, product_id).
2. **Mutasi**: Tombol *heart* memanggil `router.post('/wishlist/toggle', { product_id: X })` dengan atribut `preserveScroll: true` agar layar tidak melompat.
3. **Status di Frontend**: Array daftar wishlist bisa disematkan ke dalam profil user (`$user->wishlists->pluck('product_id')`) di Shared Data untuk memunculkan ikon merah secara instan di setiap card produk.

---

## 4. ToastContext (`src/context/ToastContext.jsx`)

**Tugas Saat Ini:**
- Memiliki queue notifikasi kecil yang muncul di kanan bawah / bawah layar.
- Mengontrol komponen global `<ToastContainer>`.

**Target Migrasi (Laravel):**
Ini adalah satu-satunya context yang **DIPERTAHANKAN 100% di Frontend**. Karena toast adalah interaksi UI murni.
Namun, harus diperkuat dengan kapabilitas membaca **Flash Messages** dari sesi Laravel.

**Pola Migrasi:**
Di `HandleInertiaRequests.php`:
```php
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error' => fn () => $request->session()->get('error'),
]
```
Di komponen Layout utama React:
```jsx
const { flash } = usePage().props;
useEffect(() => {
  if (flash.success) showToast({ title: flash.success, type: 'success' });
  if (flash.error) showToast({ title: flash.error, type: 'error' });
}, [flash]);
```

## Kesimpulan Eksekusi
1. Hapus total interaksi `localStorage` di Context.
2. Ganti semua `dispatch` atau setter lokal dengan panggilan `router.post()`, `router.put()`, atau `router.delete()` milik Inertia.
3. Alirkan *state* ke bawah menggunakan `usePage().props` dari balasan server.
