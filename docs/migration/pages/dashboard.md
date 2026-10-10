# P-004 — Dasbor Akun (DashboardPage)

**ID**: P-004 | **Path rute**: `#/akun` | **Nama rute**: Dashboard | **File sumber**: `src/pages/DashboardPage.jsx` (2057 baris)
**Layout**: `<Layout>`
**Guard**: `<ProtectedRoute>` (Wajib login, redirect ke `/login` jika tidak ada `user` di AuthContext).

---

## 1. Parameter & Query String
- Query String `tab`: mengatur tab aktif (misal `?tab=orders`, `?tab=addresses`). Dipantau saat mount untuk mengubah `activeTab`.

## 2. Komponen Anak (Pohon)

```
DashboardPage (P-004)
├── Hero Header (Level Member, Saldo Poin, Nilai Rupiah, "Dapat Ditukar")
├── Feedback Toast (inline banner alert success/error)
├── Sidebar Menu (desktop kiri, navigasi pindah state activeTab)
│
├── Konten Tab (switch case by activeTab):
│   ├── 'dashboard'
│   │   ├── Metric Cards (3 kotak ringkasan)
│   │   ├── Banner Tukar Poin
│   │   └── Widget Pesanan Terakhir
│   ├── 'orders'
│   │   └── List riwayat pesanan
│   │       ├── Link Beli Lagi
│   │       ├── Tombol Ulasan / Lihat Ulasan
│   │       └── Tombol Download Invoice -> memicu showInvoiceModal
│   ├── 'addresses'
│   │   ├── Tombol Tambah Alamat
│   │   └── List Alamat (Pilih Utama, Ubah, Hapus) -> memicu showAddressModal
│   ├── 'points' (Sistem Loyalitas Revisi 4)
│   │   ├── Progress Bar Tier
│   │   ├── Daftar Benefit Level (Loyalty Info)
│   │   ├── Form Klaim Saldo E-Wallet (mengurangi poin)
│   │   └── Katalog Voucher Potongan Belanja
│   ├── 'vouchers'
│   │   └── List Kupon Saya
│   ├── 'wishlist'
│   │   └── List ProductCard hasil filter useWishlist
│   └── 'reviews'
│       ├── Sub-tab 'pending' (Belum Diulas)
│       └── Sub-tab 'completed' (Riwayat Ulasan)
│
├── InvoiceModal (komponen terpisah, lazy/conditional render)
├── ReviewModal (komponen terpisah, popup tulis ulasan)
└── Modal Tambah/Ubah Alamat (inline JSX form absolute)
```

## 3. Kebutuhan Data

Halaman ini sangat terpusat pada data `user` dari Context.
1. `user`: Object profil, alamat, order, voucher. **Data loyalitas** (`tier_points`, `points_balance`, `current_tier`, `tier_valid_until`, nilai saldo, poin dapat ditukar, `can_redeem`) datang dari props `loyalty` yang dihitung server (lihat `04-loyalty-system-migration.md` bagian 6), bukan dari objek `user` di klien.
2. `getProducts({per_page: 50})`: Fetch produk untuk difilter berdasarkan ID yang ada di `wishlist`.
3. `products-index.json` (synchronous import): Dipakai untuk map `product.name` ke `slug` agar produk di riwayat pesanan bisa di-klik ke URL `/produk/:slug`.
4. `localStorage`: Ulasan user dibaca dari iterasi semua key `accommerce_reviews_*`.

## 4. State Lokal

- `activeTab`: (string) tab saat ini
- `feedback`: (object|null) notifikasi hijau/merah
- `wishlistProducts`: (array)
- `selectedInvoiceOrder`, `showInvoiceModal`: control invoice
- `reviewModalData`, `showReviewModal`, `reviewSubTab`: control form ulasan
- `showEwalletForm`, `ewalletPlatform`, `ewalletPhone`, `ewalletAccountName`, `selectedEwalletPackage`, `ewalletFeedback`: Form klaim e-wallet
- `showAddressModal`, `editingAddress`, `addrForm`: Form alamat
- `myReviews`: Daftar ulasan user
- `activeLoyaltyIdx`, `showLoyaltyInfo`: Interaksi progress bar loyalty

## 5. Efek Samping (useEffect)

- Sinkronisasi URL parameter `?tab=` ke `activeTab`.
- Mengamankan rute (redirect `navigate('/login')` jika tidak ada user).
- Fetch data `wishlistProducts` berdasarkan `wishlist` dari context.
- Load seluruh `myReviews` dari LocalStorage (looping `.length`).

## 6. Interaksi & Operasi Spesifik

### Sistem Poin & Tier (Spesifikasi Revisi 4)
Sumber aturan: `accommerce-loyalty-spec-v4.md`. Implementasi server: `04-loyalty-system-migration.md`. Halaman ini hanya **menampilkan** data dari props `loyalty`; semua angka dihitung dan divalidasi di server.
- `points_balance`: saldo poin yang dapat ditukar (boleh minus akibat retur). Tampil di hero.
- `tier_points`: akumulasi poin dasar belanja tahun berjalan, direset tiap 1 Januari, **bisa berkurang** akibat retur (catatan Revisi 3 "hanya naik" tidak berlaku lagi). Dipakai **hanya** untuk progress bar.
- `current_tier` (+ `tier_valid_until`): sumber badge level dan daftar benefit aktif: 'Classic', 'Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond'. Jangan dihitung ulang dari `tier_points` di klien.
- "Nilai Saldo": `balance_value_idr` (= `points_balance × 100`).
- "Dapat Ditukar Sekarang": `redeemable_points × 100`. Syarat: poin berusia **≥ 30 hari**, minimal **500 poin** per penukaran, dan sisa kuota bulan berjalan (maks. **5.000 poin per bulan per akun induk**). Bisa lebih kecil dari Nilai Saldo.
- Progress bar: `remaining_spend_idr = (target_tier_points − tier_points) × 10.000`.
- Poin bonus tier dan poin ulasan masuk ke saldo, **tidak** menambah `tier_points`.
- Form klaim e-wallet mengirim `POST /loyalty/redeem` (`type`, `points`, `provider`, `account`, `idempotency_key`). Server memvalidasi dan menghitung rupiah (`points × 100`); klien tidak mengirim nominal rupiah. Nominal pilihan: e-wallet 500/1.000/2.500 poin, voucher 750/1.500/3.000 poin.
- Tombol/form tukar poin hanya tampil jika `can_redeem = true` (admin akun induk). Sub-akun hanya melihat saldo.
- Tab `vouchers` menampilkan voucher penukaran poin dan voucher Bronze Rp 25.000 (maks. 1x per kuartal, berlaku 90 hari).

### Alamat
- Add/Edit/Delete/SetDefault dimediasi oleh context `useAuth` (`saveAddress`, `deleteAddress`, `setDefaultAddress`). Data `addrForm` disimpan di state lokal halaman saat pengetikan.

### Ulasan (Review)
- Aturan Revisi 4: hanya produk dari pesanan berstatus **Selesai**, dan **maksimal 1 ulasan per produk** (bukan per item pesanan). Di React saat ini pelacakan per item pesanan (`user.reviewedOrderItems`), jadi produk yang dibeli dua kali bisa diulas dua kali. Server harus menegakkan batas 1 ulasan per produk.
- Ulasan disimpan di database (`reviews`), bukan `localStorage`. Poin +50 diberikan server ke **saldo poin** (bukan `tier_points`), tanpa pemanggilan `markOrderItemReviewed()` dari klien.

### Invoice
- Membuka `InvoiceModal` yang men-generate PDF/Print preview berbasis HTML.

## 7. Target Laravel + Inertia

Halaman ini dalam bentuk SPA React sangat berat karena menampung 7 "halaman" fungsional dalam satu komponen yang diswitch menggunakan tab.

| Item | Saran Implementasi Laravel + Inertia |
|---|---|
| Route & Page | **PECAH HALAMAN INI**. Inertia bukan SPA React murni. Tab sebaiknya diarahkan ke URL fisik, bukan React State. |
| URL | `/akun/dashboard`, `/akun/pesanan`, `/akun/alamat`, `/akun/poin`, `/akun/ulasan`, `/akun/wishlist` |
| Komponen | `resources/js/Pages/Account/Dashboard.jsx`, `Account/Orders.jsx`, dst. Gunakan `<AccountLayout>` sebagai pembungkus sidebar kiri. |
| Data | Hindari me-load semua transaksi, poin, dan alamat dalam 1 controller. Gunakan controller terpisah untuk tiap tab. |

**Tantangan Migrasi**: Logika localStorage untuk Review dan integrasi fungsi mutasi dummy seperti `claimEwalletPoints` harus diganti total menggunakan HTTP POST ke endpoint backend (`POST /loyalty/redeem`, `POST /user/addresses`, `POST /reviews`). Poin tidak boleh diubah dari klien.

## 8. Kriteria Penerimaan Paritas (Frontend View)

- **Given** URL berisi `?tab=points`, **When** Dashboard mount, **Then** langsung aktif di tab Tukar Poin.
- **Given** tab Tukar Poin terbuka dan akun admin dengan 500+ poin berusia ≥ 30 hari, **When** mengklaim GoPay Rp 50.000 seharga 500 Poin, **Then** `points_balance` berkurang 500, `tier_points` dan `current_tier` tidak berubah, form sukses tampil.
- **Given** poin berusia 29 hari atau kuota 5.000 poin bulan ini sudah habis, **When** mencoba menukar, **Then** server menolak dengan pesan validasi dan saldo tidak berubah.
- **Given** sub-akun (bukan admin akun induk), **When** membuka tab Tukar Poin, **Then** form penukaran tidak tampil, dan `POST /loyalty/redeem` mengembalikan 403.
- **Given** klik ganda pada tombol Klaim, **Then** hanya satu penukaran yang diproses.
- **Given** masuk ke tab pesanan, **When** menekan Download Invoice pada sebuah pesanan, **Then** modal `InvoiceModal` muncul menampilkan data order spesifik tersebut.
- **Given** berada di tab alamat, **When** form edit alamat disubmit kosong, **Then** muncul alert merah validasi form di atas.
- **Given** header dashboard, **Then** "Nilai Saldo" menunjukkan `points_balance × 100`, "Dapat Ditukar Sekarang" menunjukkan `redeemable_points × 100` (≤ Nilai Saldo), badge level dari `current_tier`, dan progress bar dari `tier_points`, sesuai Revisi 4.
- **Given** pesanan diretur sehingga `tier_points` turun di bawah ambang tier saat ini, **Then** badge level **tidak** langsung turun (menunggu evaluasi 31 Desember).
