# Dokumentasi Migrasi Accommerce (React SPA ke Laravel + Inertia)

Direktori `docs/migration/` ini berisi analisis mendalam dan panduan langkah-demi-langkah sebagai pedoman utama rekayasawan untuk mereplikasi keseluruhan frontend React SPA ini menjadi Laravel + Inertia secara **1:1 (Identik)**. 

Tujuan utama dari dokumen-dokumen ini adalah memastikan **tidak ada satupun halaman, komponen, perilaku state, maupun logika bisnis yang terlewat atau meleset** dari sumber aslinya.

## Struktur Dokumen

Silakan baca dokumen-dokumen berikut secara berurutan saat melakukan eksekusi migrasi:

### 1. Fondasi & Inventaris
- `00-inventory.md` — Daftar lengkap seluruh rute, komponen, state global, dan *dependency graph*. (Penting dibaca pertama kali).

### 2. Panduan Eksekusi Inti
- `02-state-context-migration.md` — Penjelasan perombakan total dari arsitektur React Context (Auth, Cart, Wishlist) menjadi arsitektur Session, Database, dan Inertia Shared Data.
- `03-data-services-migration.md` — Konversi dari _mock data service_ (`productService.js` yang membaca `.json`) menjadi Database Seeder dan Controller dengan Eloquent ORM.

### 2b. Sistem Loyalitas
- `04-loyalty-system-migration.md` — Skema database, `LoyaltyService`, scheduler, validasi penukaran, dan Acceptance Criteria sistem poin dan tier sesuai **Spesifikasi Revisi 4** (`accommerce-loyalty-spec-v4.md`). Baca sebelum mengerjakan `pages/dashboard.md` dan `pages/checkout.md`.

### 3. Blueprint Migrasi per Halaman (Pages)
Dokumen-dokumen ini memuat _breakdown_ setiap komponen anak, efek samping (`useEffect`), state lokal, dan prasyarat khusus (Acceptance Criteria) per halaman:
- `pages/home.md` — Halaman Beranda (P-001)
- `pages/catalog.md` — Halaman Katalog & Filter (P-002)
- `pages/product-detail.md` — Halaman Detail Produk (P-003)
- `pages/dashboard.md` — Dasbor Akun & Sistem Loyalitas (P-004)
- `pages/checkout.md` — Halaman Checkout & Pembayaran (P-005)
- `pages/cart.md` — Keranjang Belanja (P-006)
- `pages/wishlist.md` — Wishlist Produk (P-007)
- `pages/search.md` — Halaman Pencarian Mobile (P-008)
- `pages/auth.md` — Halaman Login & Registrasi (P-009)

> **Halaman yang belum punya blueprint** (ada di `00-inventory.md` bagian E): `RfqPage` (R-07), `LacakPesananPage` (R-08), `BantuanPage` (R-09), `CategoryMenuPage` (R-12), `ForgotPasswordPage` (R-15), dan `NotFound` (R-16). Buat dokumen untuk keenamnya sebelum mengklaim cakupan 1:1. Dokumen ini juga merujuk `DECISIONS.md` dan `OPEN-QUESTIONS.md` yang belum ada di folder, dan tidak ada `01-*.md` (nomor dilompati).

> **Selisih jumlah baris:** jumlah baris file sumber di blueprint halaman lebih besar daripada di `00-inventory.md` (contoh: ProductDetailPage 2.090 vs 1.988, CheckoutPage 1.466 vs 1.385, HomePage 569 vs 541, CatalogPage 426 vs 389). Kemungkinan inventaris sudah usang atau salah satu dokumen keliru. Jalankan ulang inventaris sebelum mengklaim cakupan 1:1.

> **Catatan loyalitas:** `pages/dashboard.md` dan `pages/checkout.md` harus mengikuti Spesifikasi **Revisi 4**, bukan Revisi 3.

---

**Panduan Utama Auditor:** _Selama proses development di sisi Laravel, setiap PR atau commit yang merubah UI atau logika harus diuji ulang (cross-check) menggunakan kriteria penerimaan (Acceptance Criteria) yang terdapat di masing-masing file markdown di atas._
