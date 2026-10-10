# Fase 4: Panduan Migrasi Sistem Loyalitas (Spesifikasi Revisi 4)

Sumber kebenaran aturan bisnis: `accommerce-loyalty-spec-v4.md`. Dokumen ini hanya menerjemahkan aturan tersebut ke arsitektur Laravel + Inertia. Jika ada selisih antara dokumen ini dan spesifikasi Revisi 4, **spesifikasi Revisi 4 yang berlaku**.

> **Prinsip utama:** semua perhitungan poin, saldo, tier, dan penukaran dilakukan **di server**. React SPA saat ini menghitung dan menyimpannya di `localStorage` (`addPoints()`, `redeemPointsForWallet()` di `AuthContext`). Fungsi-fungsi itu tidak boleh dipindahkan ke frontend Inertia. Frontend hanya menampilkan data dari props.

---

## 1. Ringkasan Aturan (Revisi 4)

| Aturan | Nilai |
|---|---|
| Poin dasar | `floor(nilai_bersih / 10.000)`; nilai bersih = nilai barang setelah diskon dan voucher, tanpa PPN dan ongkir |
| Bonus poin | `floor(poin_dasar × bonus_tier)`: Classic/Bronze 0%, Silver 5%, Gold 10%, Platinum 20%, Diamond 30% |
| Bonus dan poin ulasan | Hanya ke `points_balance`, tidak ke `tier_points` |
| Poin ulasan | +50 per produk, pembelian terverifikasi (pesanan Selesai), maks. 1 ulasan per produk |
| Nilai tukar | 1 poin = Rp 100 |
| Kedaluwarsa | 24 bulan sejak poin masuk, FIFO |
| Penukaran e-wallet | Min. 500 poin; poin berusia ≥ 30 hari; maks. 5.000 poin per bulan per akun induk |
| Voucher | 750 → Rp 75.000 (min. order Rp 750.000); 1.500 → Rp 150.000 (min. Rp 1.500.000); 3.000 → Rp 300.000 (min. Rp 3.000.000) |
| Tier | Classic 0–499, Bronze 500–1.499, Silver 1.500–7.499, Gold 7.500–14.999, Platinum 15.000–39.999, Diamond ≥ 40.000 (dari `tier_points`) |
| Akun | Poin dan tier per perusahaan (NPWP) = akun induk; staf = sub-akun |

---

## 2. Skema Database

```php
// users: tambahkan
$table->foreignId('company_id')->nullable();
$table->enum('company_role', ['admin', 'member'])->default('member'); // admin = akun induk

// companies (satu NPWP = satu akun induk)
id, name, npwp (unique, nullable untuk perorangan), timestamps

// loyalty_accounts (1 per company; perorangan = company sendiri)
id, company_id (unique),
tier_points INT default 0,        // reset 1 Januari
points_balance INT default 0,     // boleh minus (utang poin akibat retur)
current_tier ENUM(classic,bronze,silver,gold,platinum,diamond) default classic,
tier_valid_until DATE nullable,

// point_ledger (sumber kebenaran; kolom di loyalty_accounts hanya cache)
id, loyalty_account_id, order_id nullable, review_id nullable,
type ENUM(earn_base, earn_bonus, earn_review, redeem_ewallet, redeem_voucher, expire, reversal, adjustment),
points INT,                       // positif = masuk, negatif = keluar
remaining_points INT nullable,    // hanya baris earn: sisa poin untuk FIFO
counts_for_tier BOOLEAN,          // true hanya untuk earn_base (dan reversal poin dasar)
earned_at TIMESTAMP, expires_at TIMESTAMP nullable,
created_at

// redemptions
id, loyalty_account_id, type (ewallet|voucher), points, amount_idr,
ewallet_provider nullable, ewallet_account nullable, voucher_code nullable,
status ENUM(pending, paid, failed, cancelled), requested_by (user_id), timestamps

// vouchers
id, code (unique), loyalty_account_id nullable, source (tier_bronze|redeem),
amount_idr, min_order_idr, expires_at, used_at nullable
```

Catatan:
- `tier_points` dan `points_balance` adalah **hasil agregasi ledger**. Ubah hanya lewat `LoyaltyService` di dalam satu DB transaction.
- Semua nilai uang dan poin disimpan sebagai **integer** (rupiah, tanpa desimal).

---

## 3. `LoyaltyService` (satu-satunya pintu mutasi)

| Method | Tugas |
|---|---|
| `calculateBasePoints(int $netValueIdr): int` | `intdiv($netValueIdr, 10000)` |
| `awardForOrder(Order $order)` | Tulis `earn_base` (counts_for_tier = true) dan `earn_bonus`; update cache; cek upgrade tier |
| `awardForReview(Review $review)` | Tulis `earn_review` +50 (tidak ke tier) |
| `reverseForOrder(Order $order)` | Retur/batal: tarik poin dasar dari `tier_points`, tarik poin dasar + bonus dari `points_balance`, tarik poin ulasan terkait. Saldo boleh minus. `tier_points` minimal 0 |
| `redeemEwallet(LoyaltyAccount $a, int $points, ...)` | Validasi (lihat bagian 5), kurangi poin FIFO, buat `redemptions` |
| `redeemVoucher(LoyaltyAccount $a, int $points)` | Sama, hasilnya baris `vouchers` |
| `redeemablePoints(LoyaltyAccount $a): int` | Poin yang berusia ≥ 30 hari, dibatasi sisa kuota bulan berjalan |
| `expirePoints()` | Dipanggil scheduler harian: hanguskan poin berusia 24 bulan |
| `evaluateYearEnd()` | Dipanggil 31 Desember (bagian 4) |

**Kapan poin masuk (`awardForOrder` dipanggil):**
- Pembayaran langsung: setelah pesanan **Selesai** + masa retur 7 hari.
- Pembayaran bertermin (NET 14/30): setelah invoice **Lunas** + masa retur 7 hari.
- **Jangan** memanggilnya saat checkout atau saat pesanan dibuat.

**Upgrade tier:** setelah `awardForOrder`, jika `tier_points` melewati ambang tier lebih tinggi → set `current_tier` dan `tier_valid_until = 31 Desember tahun berikutnya` seketika. Penurunan tidak pernah terjadi di sini.

---

## 4. Scheduler (`routes/console.php`)

| Jadwal | Job | Aturan |
|---|---|---|
| Harian | `ExpirePointsJob` | Hanguskan sisa poin earn yang `expires_at` ≤ sekarang (FIFO) |
| Harian | `AwardEligiblePointsJob` | Cari pesanan yang memenuhi syarat (Selesai/Lunas + 7 hari, belum pernah diberi poin), panggil `awardForOrder` secara idempoten |
| 31 Des 23:59 | `EvaluateYearEndTierJob` | Hanya untuk akun dengan `tier_valid_until` = 31 Desember tahun ini. Jika `tier_points` memenuhi tier saat ini atau lebih tinggi → perpanjang 1 tahun. Jika hanya memenuhi tier lebih rendah → turun **maks. 1 level**, `tier_valid_until` = 31 Desember tahun depan |
| 1 Jan 00:00 | `ResetTierPointsJob` | `tier_points = 0` untuk semua akun. **Wajib berjalan setelah** evaluasi akhir tahun |

Retur tidak menurunkan `current_tier` langsung. Dampaknya hanya terlihat saat evaluasi akhir tahun.

---

## 5. Validasi Penukaran (server-side, wajib)

Semua dalam satu transaction dengan `lockForUpdate()` pada `loyalty_accounts`:

1. User adalah `company_role = admin` pada akun induk tersebut.
2. Jumlah poin sesuai pilihan nominal yang diizinkan (e-wallet: 500/1.000/2.500; voucher: 750/1.500/3.000).
3. Jumlah ≤ `redeemablePoints()` (usia ≥ 30 hari, kuota bulan berjalan belum melewati 5.000 poin).
4. E-wallet atas nama akun terverifikasi dan belum terhubung ke akun induk lain.
5. Kurangi poin dari baris earn tertua dulu (FIFO) lewat `remaining_points`.
6. Gunakan **idempotency key** agar klik ganda tidak memproses dua kali.

Nilai rupiah dihitung server (`points × 100`). Jangan menerima nominal rupiah dari klien.

---

## 6. Inertia Shared Data & Props

Di `HandleInertiaRequests::share()`:

```php
'loyalty' => fn () => $request->user() ? [
    'current_tier'     => $account->current_tier,       // badge dan perks
    'tier_valid_until' => $account->tier_valid_until,
    'tier_points'      => $account->tier_points,        // progress bar
    'points_balance'   => $account->points_balance,
    'balance_value_idr'=> $account->points_balance * 100,       // kartu "Nilai Saldo"
    'redeemable_points'=> $loyalty->redeemablePoints($account), // kartu "Dapat Ditukar Sekarang"
    'next_tier'        => ..., 'remaining_spend_idr' => ...,    // (target - tier_points) * 10000
    'can_redeem'       => $user->company_role === 'admin',
] : null,
```

Aturan tampilan di dashboard:
- Badge level dan perks: dari `current_tier`.
- Progress bar "kurang Rp X lagi": dari `tier_points`.
- Kartu "Nilai Saldo": dari `points_balance × 100`.
- Kartu "Dapat Ditukar Sekarang": dari `redeemable_points × 100`; bisa lebih kecil dari Nilai Saldo.
- Tombol tukar poin disembunyikan untuk sub-akun.

---

## 7. Acceptance Criteria (uji wajib)

1. Belanja Rp 1.900.000 (nilai bersih) → 190 poin dasar; Gold → +19 bonus ke saldo; `tier_points` bertambah 190 saja.
2. Belanja Rp 999.000 → 99 poin (bukan 0).
3. Tukar 500 poin dari saldo 600 → `points_balance` 100, `tier_points` tetap 600, `current_tier` tidak berubah.
4. Poin yang berusia 29 hari tidak bisa ditukar; usia 30 hari bisa.
5. Penukaran ke-11 yang membuat total melewati 5.000 poin dalam satu bulan ditolak.
6. Retur pesanan saat poin sudah ditukar → saldo boleh minus; poin berikutnya memotong utang tersebut.
7. Retur pesanan tahun lalu menarik dari `tier_points` tahun berjalan (minimal 0).
8. Pesanan NET 30 yang belum Lunas tidak menghasilkan poin.
9. Sub-akun tidak bisa menukar poin (HTTP 403).
10. Setelah 31 Des evaluasi: Gold yang hanya memenuhi Silver turun ke Silver (bukan Classic); `tier_points` direset 1 Jan.
11. Klik ganda tombol tukar hanya memproses satu penukaran.
12. Tidak ada `localStorage` yang menyimpan poin, saldo, atau tier.

---

## 8. Pertanyaan Terbuka (putuskan sebelum implementasi)

- **Harga dummy:** produk dengan `is_dummy_price = true` adalah harga estimasi, bukan harga resmi. Apakah pesanan berisi produk seperti itu boleh menghasilkan poin?
- **Pesanan ber-RFQ / harga proyek:** apakah poin dihitung dari nilai final di invoice (disarankan) atau dari harga katalog?
- **Data demo:** user demo di `AuthContext` punya saldo poin awal. Jangan ikut di-seed ke produksi.
