# 💎 Dokumen Spesifikasi Resmi: Sistem Loyalitas, Poin & Tier Member Accommerce (Revisi 4)

Dokumen ini merupakan panduan arsitektur dan spesifikasi bisnis lengkap untuk sistem loyalitas **Accommerce Points & Member Tier System** pada platform **Accommerce.id** (B2B & Enterprise IT / Audio Visual Solution).

**Catatan revisi 4:** ditetapkan dasar perhitungan poin (nilai bersih tanpa PPN dan ongkir), poin untuk pembeli bertermin NET baru masuk setelah invoice lunas, voucher Bronze dibatasi 1x per kuartal, aturan retur lintas tahun, dan poin/tier digabung per perusahaan (akun induk). Lihat bagian 6 untuk rincian.

**Catatan revisi 3:** ditambahkan kolom `current_tier` untuk status level (badge tidak lagi dibaca dari `tier_points`), contoh penukaran diperbaiki agar sesuai aturan minimum, aturan retur dirinci, kedaluwarsa poin ditetapkan 24 bulan dengan metode FIFO, dan dashboard memisahkan "Nilai Saldo" dari "Dapat Ditukar Sekarang".

---

## 1. 🎯 Ikhtisar & Tujuan Program

Program Loyalitas Accommerce dirancang khusus untuk memotivasi pelanggan retail dan enterprise (B2B):

1. **Mendorong Transaksi Berulang (_Repeat Orders_)**: Melalui poin reward yang dapat dikonversi menjadi saldo e-wallet dan voucher diskon.
2. **Memberikan Insentif Pengadaan Skala Besar**: Menghargai klien B2B bernilai tinggi (proyek video conference, signage, networking) dengan hak istimewa seperti harga proyek (_Project Pricing_), alokasi stok prioritas, dan termin pembayaran (_Payment Terms_).
3. **Struktur yang Realistis & Memotivasi**: Tingkatan level dirancang dengan batas yang proporsional sehingga pelanggan dapat mencapai tier atas secara wajar dan termotivasi.

---

## 2. 🏆 6 Tingkatan Tier Membership

| Level        | Rentang Poin Tier     | Nilai Akumulasi Belanja         | Tema & Warna UI                                                          | Ikon |
| :----------- | :-------------------- | :------------------------------ | :----------------------------------------------------------------------- | :--: |
| **Classic**  | `0 – 499 Pts`         | Rp 0 – Rp 4.990.000             | Neutral Slate & Zinc (`from-slate-600 via-slate-700 to-zinc-800`)        |  🔰  |
| **Bronze**   | `500 – 1.499 Pts`     | Rp 5.000.000 – Rp 14.990.000    | Warm Bronze & Copper (`from-amber-800 via-amber-900 to-stone-900`)       |  🥉  |
| **Silver**   | `1.500 – 7.499 Pts`   | Rp 15.000.000 – Rp 74.990.000   | Metallic Silver (`from-slate-400 via-slate-500 to-gray-600`)             |  🥈  |
| **Gold**     | `7.500 – 14.999 Pts`  | Rp 75.000.000 – Rp 149.990.000  | Luxury Gold (`from-amber-400 via-amber-500 to-yellow-600`)               |  🥇  |
| **Platinum** | `15.000 – 39.999 Pts` | Rp 150.000.000 – Rp 399.990.000 | Royal Violet & Indigo (`from-violet-700 via-purple-800 to-indigo-900`)   |  💎  |
| **Diamond**  | `≥ 40.000 Pts`        | ≥ Rp 400.000.000                | Brilliant Cyan & Deep Ocean (`from-cyan-500 via-blue-600 to-indigo-950`) |  👑  |

---

## 3. 🎁 Hak Istimewa & Keuntungan Per Level (Tier Perks)

### 🔰 1. Classic Member (0 – 499 Poin)

- Akun enterprise terverifikasi dengan akses katalog produk lengkap.
- Hak menerbitkan **Faktur Pajak PKP** (tersedia bagi seluruh entitas bisnis ber-NPWP).
- Akses promo katalog publik dan penawaran reguler.
- Bonus **+50 Poin** per ulasan produk untuk pembelian terverifikasi.

### 🥉 2. Bronze Member (500 – 1.499 Poin)

- Semua keuntungan level **Classic**.
- **Voucher Potongan Tetap Rp 25.000** atau kupon diskon khusus kategori aksesori/kabel/mounting (menyesuaikan tipisnya margin hardware). Diberikan **maksimal 1x per kuartal** per akun perusahaan, masa berlaku 90 hari, tidak dapat digabung dengan kupon lain.
- **Gratis Ongkir 1x per bulan** (subsidi s/d Rp 30.000).
- Prioritas penanganan pengiriman pesanan standar.

### 🥈 3. Silver Member (1.500 – 7.499 Poin)

- Semua keuntungan level **Bronze**.
- **Bonus Poin Belanja 5%** untuk setiap pesanan yang selesai.
- **Gratis Ongkir 2x per bulan** (subsidi s/d Rp 50.000).
- Layanan asistensi konsultasi teknis & spesifikasi hardware audio visual.

### 🥇 4. Gold Member (7.500 – 14.999 Poin)

- Semua keuntungan level **Silver**.
- **Harga Khusus Proyek B2B (_Project Pricing_)** untuk pengadaan volume / tender kantor.
- **Prioritas Alokasi Stok** untuk perangkat berpermintaan tinggi (seperti Logitech Rally Bar, kamera PTZ, switch UniFi).
- **Bonus Poin Belanja 10%** per pesanan.
- **Gratis Ongkir 4x per bulan** dan akses eksklusif ke _Flash Sale VIP_.

### 💎 5. Platinum Member (15.000 – 39.999 Poin)

- Semua keuntungan level **Gold**.
- **Dedicated Account Manager B2B** sebagai PIC pengadaan dan penawaran satu pintu.
- **Termin Pembayaran B2B (NET 14 Hari)** setelah pesanan diterima dan invoice terbit.
- **Dukungan Garansi & RMA Express**: Prioritas penanganan klaim dan unit pengganti sementara selama masa servis.
- **Bonus Poin Belanja 20%** per transaksi.

### 👑 6. Diamond Member (≥ 40.000 Poin) — _Top Executive Tier_

- Semua keuntungan level **Platinum**.
- **Termin Pembayaran Fleksibel (NET 30 Hari)** dengan plafon kredit enterprise khusus.
- **Pengiriman Instan Same-Day Prioritas** untuk area Jabodetabek.
- **Dukungan Hotline Teknis 24/7 & Teknisi On-Site** untuk setup dan troubleshooting ruang rapat.
- **Bonus Poin Belanja 30%** per transaksi.
- **Hadiah Anniversary Korporat & Hampers Eksklusif Akhir Tahun**.

> **Catatan termin pembayaran:** termin NET hanya diberikan setelah verifikasi kelayakan kredit perusahaan, bukan otomatis saat tier tercapai.

---

## 4. 📜 Kebijakan & Aturan Operasional Sistem Loyalitas

### 1. Periode Kualifikasi & Masa Berlaku Tier

- **Periode Kualifikasi**: Dihitung berdasarkan **Tahun Kalender (1 Januari – 31 Desember)**.
- **Masa Berlaku Status**: Status tier yang diraih berlaku selama tahun berjalan hingga **akhir tahun kalender berikutnya (31 Desember tahun depan)**.
- **Kenaikan Tier (_Upgrade_)**: Langsung aktif seketika saat threshold `tier_points` tercapai. Masa berlaku status dihitung ulang menjadi 31 Desember tahun berikutnya.
- **Evaluasi Akhir Tahun**: Dijalankan setiap **31 Desember** hanya untuk member yang masa berlaku statusnya berakhir pada tanggal tersebut:
  - Jika `tier_points` tahun itu memenuhi level saat ini atau lebih tinggi → status dipertahankan dan masa berlaku diperpanjang 1 tahun.
  - Jika `tier_points` hanya memenuhi level lebih rendah → status **turun maksimal 1 level** (grace period ramah pelanggan), masa berlaku baru sampai 31 Desember tahun berikutnya.
- **Retur & Tier**: Pengurangan `tier_points` akibat retur tidak menurunkan `current_tier` secara langsung. Dampaknya baru diperhitungkan pada evaluasi akhir tahun.

### 2. Pemisahan "Poin Tier", "Saldo Poin", dan "Status Level"

Sistem memakai **tiga data terpisah** pada tabel member:

| Kolom                               | Fungsi                                                                    | Berubah Ketika                                                |
| :---------------------------------- | :------------------------------------------------------------------------ | :------------------------------------------------------------ |
| `tier_points` (INTEGER)             | Akumulasi belanja tahun berjalan untuk kualifikasi level dan progress bar | Belanja selesai, retur, reset 1 Januari                       |
| `points_balance` (INTEGER)          | Saldo reward aktif yang dapat ditukarkan                                  | Belanja selesai, bonus, ulasan, penukaran, kedaluwarsa, retur |
| `current_tier` + `tier_valid_until` | Status level aktif dan masa berlakunya (sumber badge dan perks)           | Upgrade, evaluasi akhir tahun                                 |

- **Poin Tier (_Tier Qualification Points_)**:
  - Dihitung murni dari total nilai belanja tahun berjalan (1 Poin = Rp 10.000 belanja).
  - **Bonus poin tier (5% – 30%) dan poin ulasan tidak dihitung ke Poin Tier**, hanya masuk ke Saldo Poin, agar member tidak naik level lebih cepat karena bonus.
  - Tidak berkurang saat member melakukan penukaran e-wallet atau voucher.
  - Direset setiap awal tahun kalender (1 Januari) untuk siklus kualifikasi baru.
- **Saldo Poin (_Spendable Balance_)**:
  - Poin reward riil yang dapat ditukarkan ke uang saku (e-wallet) atau voucher diskon.
  - Terdiri dari poin dasar belanja ditambah bonus poin tier dan poin ulasan.
  - Berkurang saat member mengklaim saldo e-wallet atau menukar kupon.
  - **Masa berlaku 24 bulan** sejak tanggal perolehan. Pemakaian dan kedaluwarsa memakai metode **FIFO** (poin tertua dipakai dan hangus lebih dulu).
- **Status Level (`current_tier`)**:
  - Satu-satunya sumber untuk badge level dan perks yang aktif.
  - **Tidak dihitung ulang dari `tier_points` setiap saat**, karena `tier_points` direset tiap 1 Januari sementara status tier tetap berlaku sampai akhir tahun berikutnya.

> ⚠️ **Catatan Penting: Mengapa Poin Tier & Saldo Poin Terlihat Sama di Awal, Lalu Divergen?**
>
> Pada transaksi awal (misal belanja Rp 4.500.000), Poin Tier dan Saldo Poin sama-sama bernilai **450 Poin**. Hal ini murni kebetulan angka awal. Keduanya **wajib diperlakukan sebagai entitas berbeda** karena nilainya akan divergen pada kondisi berikut:
>
> 1. **Member Menukar Poin**: Saldo Poin berkurang, sedangkan Poin Tier tetap. _Contoh_: member memiliki 600 poin (belanja Rp 6.000.000, sudah lewat 30 hari), menukar **500 poin** ke e-wallet Rp 50.000 → `points_balance` menjadi 100, `tier_points` tetap 600.
> 2. **Bonus Tier & Ulasan Masuk**: Saldo Poin bertambah (bonus 5%–30% atau +50 poin per ulasan), sedangkan Poin Tier **tidak ikut naik** (mencegah lonjakan tier buatan).
> 3. **Kedaluwarsa Poin**: Saldo Poin hangus setelah 24 bulan sejak perolehan, sedangkan Poin Tier hanya direset serentak tiap 1 Januari.
> 4. **Pesanan Diretur / Dibatalkan**: Lihat aturan rinci di bagian 4.6.
>
> 🛠️ **Aturan Tampilan di Dashboard:**
>
> - **Badge level dan perks aktif** dibaca dari `current_tier`.
> - **Progress Bar** (_"Kurang Rp X lagi untuk naik tier"_) memakai `tier_points`.
> - **Kartu "Nilai Saldo"** (misal Rp 45.000) dihitung dari `points_balance × Rp 100`, BUKAN dari `tier_points`.
> - **Kartu "Dapat Ditukar Sekarang"** dihitung hanya dari poin yang memenuhi syarat penukaran: berusia minimal 30 hari, minimal 500 poin per penukaran, dan dibatasi sisa kuota bulan berjalan (maks. 5.000 poin). Angka ini bisa lebih kecil dari Nilai Saldo.
> - Berikan label dan penjelasan UI yang tegas agar pelanggan tidak bingung antara poin untuk naik level vs poin uang saku.

### 3. Faktur Pajak PKP Terbuka untuk Seluruh Member

- Faktur Pajak resmi bukan merupakan perk eksklusif tier atas, melainkan hak seluruh pelanggan berbadan hukum/pribadi yang melampirkan NPWP & SPPKP resmi perusahaan.

### 4. Reward Ulasan Pembelian Terverifikasi

- Member berhak mendapatkan reward **+50 Poin** (masuk ke Saldo Poin) untuk setiap ulasan produk.
- Hanya berlaku untuk produk dari pesanan dengan status **Selesai**.
- Dibatasi **maksimal 1 ulasan per produk** untuk mencegah spam dan kecurangan.

### 5. Kalkulasi Sisa Belanja di Dashboard

- Dashboard menampilkan progress bar interaktif dengan konversi nominal:

  `Sisa Belanja (Rp) = (Target Poin Tier Berikutnya − tier_points Saat Ini) × Rp 10.000`

  _Contoh_: Menuju Gold (7.500 Pts), member memiliki `tier_points` 5.000 → kurang 2.500 poin atau **kurang Rp 25.000.000 belanja lagi**.
  _(Perhitungan ini selalu merujuk ke `tier_points`, tidak terpengaruh penukaran saldo poin.)_

### 6. Kapan Poin Diberikan & Ditarik Kembali

- **Pembayaran langsung (non-termin):** poin dasar dan bonus baru masuk setelah pesanan berstatus **Selesai** dan melewati masa retur/RMA (disarankan 7 hari).
- **Pembayaran bertermin (NET 14/NET 30):** poin baru masuk setelah **invoice berstatus Lunas** dan melewati masa retur/RMA. Pesanan yang belum dibayar tidak menghasilkan poin. Tanggal perolehan poin (untuk kedaluwarsa 24 bulan dan syarat tunggu 30 hari penukaran) dihitung sejak poin masuk, bukan sejak tanggal pesanan.
- **Jika pesanan diretur atau dibatalkan setelah poin diberikan:**
  - Dari `tier_points` ditarik **poin dasar** saja (nilai minimal 0).
  - Dari `points_balance` ditarik **poin dasar + bonus tier** dari pesanan tersebut.
  - Poin ulasan untuk produk yang diretur ikut ditarik.
- **Jika poin sudah ditukar sebelum retur** sehingga saldo tidak cukup: saldo boleh bernilai **minus** (utang poin) dan akan dipotong otomatis dari poin yang diperoleh berikutnya. Voucher atau e-wallet yang sudah dicairkan tidak ditarik kembali.
- **Retur pesanan tahun sebelumnya** (poin tier tahun itu sudah direset): penarikan poin dasar tetap diambil dari `tier_points` **tahun berjalan** (nilai minimal 0), dan dari `points_balance` seperti biasa. Poin tier tahun lalu tidak dihitung ulang.
- `current_tier` tidak berubah akibat retur (lihat bagian 4.1).

### 7. Akun Perusahaan (Akun Induk) dan Sub-Akun Pembeli

- Poin, tier, dan perks **dihitung per perusahaan**, bukan per individu. Satu NPWP perusahaan = satu **akun induk** (pemilik `tier_points`, `points_balance`, dan `current_tier`).
- Staf yang berbelanja atas nama perusahaan terdaftar sebagai **sub-akun** di bawah akun induk. Seluruh belanja sub-akun masuk ke akun induk.
- Penukaran poin (e-wallet maupun voucher) hanya dapat dilakukan oleh **admin akun induk**. Batas penukaran 5.000 poin per bulan berlaku per akun induk, bukan per sub-akun.
- Satu NPWP hanya boleh memiliki satu akun induk. Pendaftaran NPWP ganda ditolak atau digabung melalui verifikasi oleh tim Accommerce.
- Pembeli perorangan tanpa NPWP perusahaan diperlakukan sebagai akun induk tersendiri.

---

## 5. 💰 Skema Konversi Poin & Penukaran

### A. Rasio Perolehan Poin

**Dasar perhitungan "Nilai Belanja"** adalah nilai barang **setelah diskon dan voucher, tanpa PPN dan tanpa ongkir**. Pesanan dengan **Harga Khusus Proyek** tetap mendapat poin dari nilai transaksinya (dasar perhitungan yang sama). Pembayaran memakai saldo poin atau kupon hasil penukaran tidak menghasilkan poin pada bagian yang dibayar dengan kupon tersebut.

`Poin Dasar = floor(Nilai Belanja / Rp 10.000)`

`Bonus Poin = floor(Poin Dasar × Persentase Bonus Tier)`

_Contoh_: belanja Rp 1.900.000 → 190 poin dasar. Member Gold (bonus 10%) → +19 poin ke Saldo Poin, dan `tier_points` bertambah 190.

### B. Rasio Penukaran ke Saldo E-Wallet

- Nilai tukar tetap: **1 Poin = Rp 100 Saldo Uang Nyata**.
- Saldo dapat ditransfer ke: **GoPay, OVO, DANA, ShopeePay, dan LinkAja**.
- Pilihan nominal penukaran:
  - **500 Poin** → Rp 50.000 Saldo E-Wallet
  - **1.000 Poin** → Rp 100.000 Saldo E-Wallet
  - **2.500 Poin** → Rp 250.000 Saldo E-Wallet
- **Batas pengamanan penukaran e-wallet:**
  - Maksimal **5.000 Poin (Rp 500.000) per bulan** per akun.
  - Poin hanya dapat ditukar setelah **30 hari** sejak diperoleh.
  - E-wallet tujuan harus atas nama yang sama dengan akun terverifikasi (atau perusahaan yang terdaftar).
  - Satu e-wallet hanya boleh terhubung ke satu akun member.

### C. Penukaran ke Voucher Diskon

Semua voucher bernilai sama dengan nilai poin yang ditukar (1 Poin = Rp 100), dengan minimum order untuk melindungi margin:

- **750 Poin**: Kupon Potongan Rp 75.000 (Min. order Rp 750.000).
- **1.500 Poin**: Kupon Potongan Rp 150.000 (Min. order Rp 1.500.000).
- **3.000 Poin**: Kupon Potongan Rp 300.000 (Min. order Rp 3.000.000).

_Kupon diskon persen (15%) dihapus karena nilainya bisa melebihi nilai poin yang ditukar._

---

## 6. 📋 Ringkasan Perubahan

### Revisi 3 → Revisi 4

| Bagian                   | Revisi 3                | Revisi 4                                                                |
| :----------------------- | :---------------------- | :---------------------------------------------------------------------- |
| Dasar hitung poin        | Tidak ditentukan        | Nilai bersih setelah diskon/voucher, tanpa PPN dan ongkir               |
| Poin harga proyek        | Tidak ditentukan        | Tetap dapat poin dari nilai transaksi                                   |
| Poin pembeli termin NET  | Setelah pesanan selesai | Setelah invoice Lunas dan lewat masa retur                              |
| Voucher Bronze Rp 25.000 | Tanpa frekuensi         | Maks. 1x per kuartal, berlaku 90 hari                                   |
| Retur pesanan tahun lalu | Tidak dijelaskan        | Ditarik dari `tier_points` tahun berjalan (min. 0) dan `points_balance` |
| Akun perusahaan          | Tidak dijelaskan        | Poin dan tier per NPWP (akun induk), penukaran oleh admin induk         |

### Revisi 2 → Revisi 3

| Bagian             | Revisi 2                  | Revisi 3                                                       |
| :----------------- | :------------------------ | :------------------------------------------------------------- |
| Sumber badge level | `tier_points`             | `current_tier` (+ `tier_valid_until`)                          |
| Evaluasi tier      | Tidak dirinci             | Tiap 31 Des untuk status yang berakhir, turun maks. 1 level    |
| Contoh penukaran   | Tukar 450 poin            | Tukar 500 poin dari saldo 600 (sesuai minimum)                 |
| Retur              | "Ditarik proporsional"    | Tier: poin dasar. Saldo: poin dasar + bonus. Saldo boleh minus |
| Retur dan level    | Tidak dijelaskan          | Level tidak turun langsung, menunggu evaluasi akhir tahun      |
| Kedaluwarsa poin   | 12–24 bulan               | 24 bulan, FIFO                                                 |
| Dashboard          | Satu kartu nilai konversi | "Nilai Saldo" dan "Dapat Ditukar Sekarang"                     |
| Poin ulasan        | Masuk saldo (implisit)    | Ditegaskan hanya ke Saldo Poin                                 |
