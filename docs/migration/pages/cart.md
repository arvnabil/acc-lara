# P-006 — Keranjang Belanja (CartPage)

**ID**: P-006 | **Path rute**: `#/keranjang` | **Nama rute**: Cart | **File sumber**: `src/pages/CartPage.jsx` (322 baris)
**Layout**: `<Layout>`
**Guard**: Tidak ada (bisa diakses tamu).

---

## 1. Parameter & Query String
Tidak ada parameter path atau query string.

## 2. Komponen Anak (Pohon)

```
CartPage (P-006)
├── [State Kosong] -> Ditampilkan jika `items.length === 0`
│   └── Link ke "/katalog"
│
├── Left Column: List Item (8 cols)
│   └── Looping `items` dari context
│       ├── Gambar, SKU, Nama
│       ├── Rental Badge (Jika ada `item.rentalInfo`)
│       ├── Harga & Subtotal
│       ├── Stepper (Kurang/Tambah Qty) -> panggil `updateQuantity`
│       └── Tombol Hapus -> panggil `removeItem`
│
└── Right Column: Ringkasan & Checkout (4 cols)
    ├── Input Kode Promo
    │   ├── Button Terapkan Promo
    │   ├── Pesan Validasi Promo (Berhasil/Gagal)
    │   └── Badge Promo Aktif -> tombol Hapus Promo
    ├── Ringkasan Belanja
    │   ├── Total Harga (`total`)
    │   ├── Estimasi Ongkir (`SHIPPING_BASE`)
    │   ├── Diskon (`discountAmount`)
    │   └── Total Tagihan (`grandTotal`)
    ├── Estimasi Poin Didapat (`earnedPoints`)
    └── Tombol Checkout -> Link ke `/checkout`
```

## 3. Kebutuhan Data
- Hanya bergantung pada state lokal browser via `CartContext` (`items`, `total`, `itemCount`, `appliedPromo`).
- Memiliki konstanta hardcoded `PROMO_CODES` untuk simulasi diskon, dan `SHIPPING_BASE = 350000` untuk estimasi ongkir.

## 4. State Lokal
| ID | Nama | Awal | Diubah oleh | Persisten |
|---|---|---|---|---|
| ST-001 | `promoInput` | `appliedPromo?.code \|\| ''` | Ketikan user di form | Tidak |
| ST-002 | `promoMessage` | `null` | validasi promo | Tidak |

## 5. Efek Samping (useEffect)
Tidak ada `useEffect` eksplisit di komponen ini (seluruh kalkulasi sinkron selama render cycle berdasar data context).

## 6. Interaksi
- **Apply Promo**: Cek kode yang diketik terhadap konstanta `PROMO_CODES`. Jika valid, update `CartContext`, jika tidak set `promoMessage` error merah.
- **Hapus Promo**: Hapus `appliedPromo` di context, hapus `promoInput`.
- **Ubah Qty**: Panggil `updateQuantity(key, newQty)`. Jika `newQty < 1` diabaikan, atau di handle context.
- **Hapus Item**: Panggil `removeItem(key)`.

## 7. Target Laravel + Inertia

| Fitur | Adaptasi Laravel |
|---|---|
| Route | `GET /keranjang` |
| Sumber Data | Keranjang harus dipindah ke DB (Session / DB Table `carts`). Dikirim ke frontend sebagai props. |
| Perhitungan | Sama seperti checkout, diskon & total WAJIB dari Backend. |
| Aksi Qty/Hapus | Karena keranjang pindah ke DB, tiap aksi +/- atau hapus item di keranjang harus memicu *Inertia visit* (`POST /cart/{id}/update` atau sejenisnya) dengan `preserveScroll` agar layar tidak melompat. |
| Promo | Terapkan via `POST /cart/promo`. Validasi di backend. |
| Estimasi poin | "Estimasi Poin Didapat" harus memakai `PricingService` yang sama dengan checkout: `floor((subtotal − diskon barang) / 10.000)`, tanpa PPN, ongkir, dan biaya admin (Revisi 4). Beri label "Estimasi" dan jelaskan poin masuk setelah pesanan selesai. |
| Ongkir | Cart memakai `SHIPPING_BASE = 350000` (estimasi), Checkout memakai `SHIPPING_OPTIONS`. Satukan di server agar total keranjang dan checkout tidak berbeda. |
| Data demo | `DEFAULT_DEMO_ITEMS` (2 item) di `localStorage` hanya untuk demo. Jangan dipindah ke produksi (keranjang baru harus kosong). |
| Keranjang tamu | Halaman bisa diakses tanpa login. Simpan keranjang tamu di session dan **gabungkan** ke keranjang database saat login. |

## 8. Kriteria Penerimaan Paritas
- **Given** keranjang kosong, **When** mengakses halaman, **Then** tampil desain empty state "Keranjang Belanja Kosong" tanpa ringkasan bayar.
- **Given** ada item di keranjang, **When** menekan tombol `-`, **Then** qty berkurang, subtotal & grand total ter-update instan.
- **Given** memasukkan kode promo `"ACCOMM25"`, **When** klik Terapkan, **Then** subtotal dipotong 25% dan badge hijau diskon muncul.
- **Given** promo sudah diterapkan, **When** mengklik tombol silang hapus promo, **Then** total kembali normal.
