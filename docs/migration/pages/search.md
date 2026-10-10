# P-008 — Halaman Pencarian Mobile (SearchPage)

**ID**: P-008 | **Path rute**: `#/cari` | **Nama rute**: Search | **File sumber**: `src/pages/SearchPage.jsx` (129 baris)
**Layout**: Tidak menggunakan layout. View fullscreen dengan sticky header pencarian.

---

## 1. Parameter & Query String
Query string yang dikonsumsi: Tidak ada. (Query string `?q=` dihasilkan dan dipush ke rute tujuan).

## 2. Komponen Anak (Pohon)

```
SearchPage (P-008)
├── Top Bar
│   ├── Tombol Kembali (`navigate(-1)`)
│   ├── Input Teks Pencarian (dengan tombol X clear)
│   └── Tombol Cari
├── Area Riwayat (jika ada history)
│   ├── Tombol "Hapus semua"
│   └── List Item History
│       ├── Klik teks -> Set input & Cari
│       └── Klik silang -> Hapus dari history
└── Kategori Populer (Quick access button statis)
```

## 3. Kebutuhan Data & State
- **State Lokal**: `query` (input text).
- **State Riwayat**: `history` (array string) -> dibaca dan disinkronisasikan dengan `localStorage` (`accommerce_search_history`). Maksimal 10 entri unik.
- Tidak mengambil data produk langsung.

## 4. Efek Samping & Interaksi
- `useEffect` saat mount mengatur fokus ke elemen `<input type="search">` agar keyboard mobile langsung terbuka.
- **Pencarian**:
  - Di-trigger via menekan tombol Enter pada keyboard, mengklik ikon lup/cari, mengetap riwayat, atau mengetap tag kategori populer.
  - Memanggil `saveHistory()` (geser string ke index 0 array history, slice maks 10).
  - Melakukan navigasi ke `navigate(/katalog?q=${encodeURIComponent(query)})`.

## 5. Target Laravel + Inertia
- Route: `GET /cari` -> `SearchController@mobileIndex`
- Komponen Inertia tetap independen (atau bahkan bisa menjadi modal saja di base layout, tapi karena sudah ada pagenya, buat sebagai `Pages/Search.jsx`).
- Penyimpanan `history` bisa tetap di `localStorage` untuk menghindari beban DB, karena riwayat pencarian biasanya spesifik ke device.
