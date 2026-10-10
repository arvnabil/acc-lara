# P-005 — Checkout Pembayaran (CheckoutPage)

**ID**: P-005 | **Path rute**: `#/checkout` | **Nama rute**: Checkout | **File sumber**: `src/pages/CheckoutPage.jsx` (1466 baris)
**Layout**: `<Layout>`
**Guard**: `<ProtectedRoute>` (Wajib login).

---

## 1. Parameter & Query String
Tidak ada parameter atau query string.

## 2. Komponen Anak (Pohon)

```
CheckoutPage (P-005)
├── [Step Indicator] (Progress bar statis)
│
├── Step 1: `checkout` (Render default)
│   ├── Left Column
│   │   ├── Alamat Pengiriman Section
│   │   │   └── Modal Pilih Alamat (`showAddressPickerModal`)
│   │   │   └── Modal Alamat Baru (`showNewAddressModal`)
│   │   ├── Opsi Pengiriman (Radio list dari `SHIPPING_OPTIONS`)
│   │   ├── Catatan Tambahan (Textarea)
│   │   └── Opsi Pembayaran (Radio list dari `PAYMENT_METHODS`)
│   │
│   └── Right Column (Ringkasan)
│       ├── Item List (dari `items` context cart)
│       ├── Input Kode Promo
│       ├── Rincian Harga (Subtotal, PPN, Ongkir, Admin, Diskon)
│       └── Tombol "Pilih Pembayaran" -> panggil `handleProcessPayment`
│
├── Step 2: `payment` (Menunggu Pembayaran)
│   ├── Tab Switcher (QRIS / Virtual Account)
│   ├── [Jika QRIS]
│   │   └── Gambar QR Code statis `/method/qris-accommerce.png`
│   ├── [Jika VA]
│   │   ├── Pilihan Bank (`BANKS`)
│   │   └── Nomor VA & Salin Total
│   └── Tombol "Saya Sudah Bayar" -> panggil `handleFinishPayment`
│
└── Step 3: `success` (Order Selesai)
    ├── Informasi Pesanan (Nomor Pesanan ACC-XXXXX)
    ├── Kartu Estimasi Poin (label "Estimasi", poin masuk setelah pesanan selesai)
    ├── Status "Apa yang terjadi selanjutnya?"
    └── Tombol Lacak Pesanan / Kembali ke Beranda
```

## 3. Kebutuhan Data

- **Cart State**: `items`, `total`, `itemCount`, `appliedPromo` (dari `CartContext`).
- **User State**: `user.addresses`, `addPoints`, `saveAddress` (dari `AuthContext`).
- Konstanta di-hardcode dalam file: `PROMO_CODES`, `SHIPPING_OPTIONS`, `PAYMENT_METHODS`, `BANKS`.

## 4. State Lokal Halaman

| ID | Nama | Awal | Diubah oleh | Persisten |
|---|---|---|---|---|
| ST-001 | `currentStep` | `'checkout'` | `'payment'` -> `'success'` | Tidak |
| ST-002 | `orderNumber` | `'ACC-98214'` | `Math.random()` saat ke step payment | Tidak |
| ST-003 | `selectedAddressId` | ID alamat utama / user | Pilih / tambah alamat | Tidak (tapi form checkout tersinkron) |
| ST-004 | `formData` | (Nama, Telp, Alamat) diisi dari profile user | Input form / Ganti alamat | Tidak |
| ST-005 | `selectedShipping` | `SHIPPING_OPTIONS[0]` | Klik radio | Tidak |
| ST-006 | `selectedPayment` | `PAYMENT_METHODS[0]` | Klik radio | Tidak |
| ST-007 | `promoInput` | `appliedPromo.code` atau `''` | Input form promo | Tidak |
| ST-008 | `promoMessage` | `null` | validasi promo | Tidak |
| ST-009 | `selectedBank` | `BANKS[0]` | Klik bank di modal VA | Tidak |

## 5. Perhitungan Harga & Poin

```javascript
// Algoritma
const subtotal = total > 0 ? total : 52400000; // dummy fallback
const ppn = Math.round(subtotal * 0.11);
const shippingCost = selectedShipping.price;
const adminFee = 15000;

// Diskon
let discountAmount = 0;
if (appliedPromo.type === 'percent') discountAmount = Math.round((subtotal * appliedPromo.value) / 100);
if (appliedPromo.type === 'fixed') discountAmount = min(appliedPromo.value, subtotal+ppn+shippingCost+adminFee);

// Total
const grandTotal = Math.max(0, subtotal + ppn + shippingCost + adminFee - discountAmount);

// Poin (kode React saat ini; TIDAK berlaku lagi di Revisi 4)
const earnedPoints = Math.floor(grandTotal / 10000);
```

### Aturan poin Revisi 4 (menggantikan rumus di atas)

Rumus React menghitung poin dari `grandTotal`, yang sudah termasuk PPN, ongkir, dan biaya admin. Spesifikasi Revisi 4 menghitung dari **nilai barang setelah diskon/voucher, tanpa PPN, ongkir, dan biaya admin**:

```javascript
// Revisi 4, dihitung SERVER (nilai di UI hanya estimasi)
const netValue = Math.max(0, subtotal - discountOnGoods); // tanpa ppn, shippingCost, adminFee
const basePoints = Math.floor(netValue / 10000);
const bonusPoints = Math.floor(basePoints * tierBonusRate); // 0 / 5% / 10% / 20% / 30%, masuk ke saldo saja
```

Contoh: subtotal Rp 10.000.000, diskon Rp 500.000 → nilai bersih Rp 9.500.000 → 950 poin dasar (rumus lama memberi poin dari grandTotal ± Rp 10,5 juta lebih).

Perbedaan penting dari React:
- **Poin tidak diberikan saat checkout atau saat menekan "Saya Sudah Bayar".** Pembayaran langsung: poin masuk setelah pesanan **Selesai** + masa retur 7 hari. Pembeli bertermin NET: setelah invoice **Lunas** + masa retur.
- Layar sukses hanya menampilkan **estimasi** poin dengan keterangan kapan poin masuk.
- Poin produk dengan `is_dummy_price = true`: lihat pertanyaan terbuka di `04-loyalty-system-migration.md` bagian 8.

## 6. Efek Samping (useEffect)

- Mengamati perubahan `user.addresses` dan mensinkronisasikan `formData` (Alamat, Provinsi, Telp) jika array address berubah (misalnya setelah simpan alamat baru).

## 7. Interaksi Khusus

- **Apply Promo**: Jika tidak valid akan set `promoMessage` merah. Jika valid, akan update `CartContext` (`setAppliedPromo`).
- **Payment Button**: Beralih view langsung di React tanpa refresh.
- **Save Address Modal**: Generate string unik ID untuk alamat baru, panggil `saveAddress()` context, lalu auto-pilih alamat tersebut.
- **Tombol "Saya Sudah Bayar"** (perilaku React saat ini): memanggil `clearCart()` untuk mengosongkan keranjang, lalu `addPoints(earnedPoints)`. Ini hanya simulasi: klien menyatakan sudah membayar dan langsung mendapat poin. **Jangan direplikasi.** Di Laravel tombol ini hanya mengubah status pesanan menjadi "menunggu verifikasi pembayaran". Status "Dibayar" ditetapkan oleh webhook gateway atau verifikasi admin, dan **tidak ada pemberian poin** dari aksi ini.

## 8. Target Laravel + Inertia

Ini halaman yang paling perlu dipecah / disesuaikan dengan alur backend e-commerce yang sebetulnya.

| Fitur | Adaptasi Laravel |
|---|---|
| Route | Bisa dipecah 3: `GET /checkout`, `GET /pembayaran/{order_id}`, `GET /sukses/{order_id}` |
| State Keranjang | Diambil dari database (Server-side validation) atau session, jangan mengandalkan local storage sepenuhnya pada saat kalkulasi. |
| Kalkulasi Harga | `grandTotal`, `ppn`, `shipping` WAJIB dihitung di Server Side, dikirim lewat props. Jangan lakukan kalkulasi billing di Frontend JSX. |
| Submit Order | `POST /checkout` -> buat Order di database -> return Redirect ke halaman `pembayaran`. |
| Promo | Validasi promo harus lewat Axios API atau re-visit dengan partial props, jangan hardcode di konstanta. |
| Nomor pesanan | `orderNumber` React diisi `'ACC-98214'` lalu `Math.random()`. Di server gunakan nomor berurutan unik (tidak acak di klien). |
| Subtotal dummy | `subtotal = total > 0 ? total : 52400000` adalah fallback demo. **Hapus** di produksi; checkout dengan keranjang kosong harus diarahkan ke `/keranjang`. |
| Pricing service | Satu `PricingService` di server dipakai Cart dan Checkout (subtotal, PPN, ongkir, biaya admin, diskon, grand total, estimasi poin). Di React Cart memakai `SHIPPING_BASE = 350000` sedangkan Checkout memakai `SHIPPING_OPTIONS`, dan Cart tidak menampilkan PPN. Samakan lewat satu sumber. |
| Termin NET (baru) | `PAYMENT_METHODS` saat ini hanya QRIS/VA. Perk Platinum (NET 14) dan Diamond (NET 30) butuh metode pembayaran **"Termin"** yang hanya tampil untuk akun dengan tier sesuai **dan** kelayakan kredit terverifikasi. Ini fitur baru, bukan paritas 1:1. Order bertermin berstatus "menunggu pembayaran invoice", dan poin baru masuk setelah invoice Lunas. |
| PPN | Kode React menghitung PPN dari `subtotal` sebelum diskon. Konfirmasi ke akuntan apakah PPN seharusnya dihitung dari nilai setelah diskon sebelum mereplikasi 1:1. |

## 9. Kriteria Penerimaan Paritas

- **Given** berada di halaman Checkout, **When** memuat halaman, **Then** Rincian alamat otomatis terisi dengan Alamat Utama user.
- **Given** kode promo dimasukkan `"SAVE10"`, **When** Apply, **Then** Diskon dihitung 10% dari Subtotal.
- **Given** di halaman checkout, **When** mengklik "Pilih Pembayaran", **Then** tampilan beralih ke Step QRIS/VA dan form payment muncul.
- **Given** di halaman Pembayaran, **When** mengklik "Saya Sudah Bayar", **Then** pesanan berstatus "menunggu verifikasi pembayaran", keranjang dikosongkan, dan **tidak ada poin** yang ditambahkan pada saat itu.
- **Given** pesanan pembayaran langsung berstatus Selesai dan masa retur 7 hari lewat, **Then** poin dasar `floor(nilai bersih / 10.000)` dan bonus tier masuk (tier_points hanya poin dasar).
- **Given** pesanan bertermin NET dan invoice belum Lunas, **Then** poin belum masuk; setelah Lunas + masa retur, poin masuk.
- **Given** subtotal Rp 10.000.000 dan diskon Rp 500.000, **Then** estimasi poin dasar di layar sukses = 950 (bukan dari grand total).
