# P-007 — Wishlist (WishlistPage)

**ID**: P-007 | **Path rute**: `#/wishlist` | **Nama rute**: Wishlist | **File sumber**: `src/pages/WishlistPage.jsx` (58 baris)
**Layout**: `<Layout>`
**Guard**: Tidak ada (bisa diakses tanpa login).

---

## 1. Parameter & Query String
Tidak ada.

## 2. Komponen Anak (Pohon)

```
WishlistPage (P-007)
├── Header & Icon
├── List Produk (jika ada)
│   └── ProductCard (berulang untuk setiap produk di wishlist)
└── Empty State (jika kosong)
    └── Link ke "/katalog"
```

## 3. Kebutuhan Data & State

- Menggunakan `useWishlist()` dari `WishlistContext`.
- Memuat produk via `getProducts({ per_page: 50 })`, lalu di-*filter* ID atau SKU-nya jika ada di array `wishlist`.
- **State**: `wishlistProducts` (array produk), `loading` (boolean).
- Array wishlist disimpan di `localStorage` melalui context.

## 4. Efek Samping
- Memastikan `window.scrollTo` ke atas (instan) saat di-mount.
- Mengambil daftar produk jika state array `wishlist` dari context berubah.

## 5. Target Laravel + Inertia
- Route: `GET /wishlist`
- Di sisi server, simpan tabel wishlist di database (terikat ke `user_id`). Jika user tidak login, fallback ke session atau biarkan saja kosong bergantung kebijakan bisnis.
- Props ke page: `products` (daftar detail product yang di-wishlist).
- Filter array tidak perlu dilakukan di client side lagi, tapi difilter menggunakan query SQL/Eloquent: `Product::whereIn('id', $wishlistIds)->get()`.
- **Perbedaan dari React (disengaja):** React memuat hanya `getProducts({ per_page: 50 })` lalu memfilter, sehingga produk wishlist di luar 50 produk pertama dari 539 tidak akan tampil (bug yang sudah ada). Query `whereIn` memperbaikinya, jadi hasilnya bisa lebih banyak daripada versi React.
- Wishlist awal demo di React (`['prod-1','prod-3']`) memakai ID string yang tidak cocok dengan `id` produk numerik. Jangan dipindah ke produksi.
- Tamu (tanpa login) bisa membuka halaman ini. Tentukan: simpan wishlist tamu di session lalu gabungkan saat login, atau wajibkan login untuk menambah wishlist.

---
