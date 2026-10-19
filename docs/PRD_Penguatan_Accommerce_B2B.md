# PRD — Penguatan Accommerce B2B

## 1. Tujuan

Mengembangkan Accommerce dari e-commerce biasa menjadi platform procurement B2B untuk produk IT dan Audio Visual, dengan fokus pada:

- Keamanan transaksi bernilai tinggi.
- Proses quotation dan pembelian perusahaan.
- Garansi dan layanan purna jual.
- Retensi client dan repeat order.

## 2. Fitur yang Sudah Tersedia

Fitur berikut tidak perlu dibuat ulang:

- Katalog, produk, kategori, brand, varian, stok, dan gambar.
- Keranjang dan checkout.
- Pembayaran manual, QRIS, Midtrans, dan virtual account.
- Status pesanan.
- Invoice.
- RFQ atau permintaan penawaran dasar.
- Voucher, promo, poin, dan referral.
- Notifikasi email.
- Tracking pengiriman.
- Client, NPWP, dan banyak alamat.
- Review produk.
- Banner dan konten homepage melalui Filament.
- PWA.

## 3. Tahapan Pengembangan

### Tahap 1 — RMA, Retur, dan Garansi

**Prioritas: Paling utama / Must Have**

#### Tujuan

Memberikan rasa aman untuk pembelian perangkat IT bernilai tinggi.

#### Fitur

- Pengajuan retur dari detail pesanan.
- Pengajuan klaim garansi.
- Alasan retur.
- Upload foto kerusakan.
- Input serial number.
- Nomor RMA otomatis.
- Pilihan retur, refund, replacement, atau repair.
- Pemeriksaan oleh admin atau teknisi.
- Catatan inspeksi.
- Status proses:

```text
Diajukan
→ Ditinjau
→ Disetujui/Ditolak
→ Barang Dikirim
→ Diperiksa
→ Refund/Replacement/Repair
→ Selesai
```

#### Admin Filament

- Daftar RMA.
- Detail pesanan dan client.
- Riwayat status.
- Upload bukti inspeksi.
- Penugasan teknisi.
- Catatan internal.
- Persetujuan refund atau penggantian.
- SLA penyelesaian.

#### Kriteria selesai

- User dapat membuat klaim dari pesanan selesai.
- Admin dapat menyetujui atau menolak klaim.
- Semua perubahan status tercatat.
- User menerima notifikasi setiap perubahan.
- Klaim tidak dapat dibuat setelah masa retur berakhir.
- Refund tidak dapat diproses dua kali.

### Tahap 2 — Quotation dan Procurement B2B

**Prioritas: Paling utama / Must Have**

#### Tujuan

Mengubah RFQ dasar menjadi proses penawaran resmi perusahaan.

#### Fitur

- User mengirim RFQ dari keranjang atau halaman produk.
- Admin membuat quotation.
- Harga khusus per produk.
- Diskon berdasarkan jumlah.
- Biaya instalasi dan konfigurasi.
- Masa berlaku quotation.
- Revisi quotation.
- User menyetujui atau menolak quotation.
- Quotation dikonversi menjadi order.
- Generate PDF quotation.

#### Tambahan B2B

- Purchase Order.
- NPWP client.
- Alamat penagihan dan pengiriman.
- Termin pembayaran.
- Catatan procurement.
- Approval internal perusahaan.

#### Kriteria selesai

- RFQ dapat berubah menjadi quotation.
- Harga quotation tidak mengikuti harga katalog secara otomatis setelah disetujui.
- Quotation memiliki nomor unik dan masa berlaku.
- Quotation yang disetujui dapat dibuat menjadi order.
- Semua aktivitas tercatat.

### Tahap 3 — Harga Khusus, Approval, dan Credit Limit

**Prioritas: Must Have untuk client korporasi**

#### Fitur

- Price list per client.
- Harga berdasarkan tier client.
- Diskon volume.
- Harga reseller atau integrator.
- Batas pembelian user.
- Approval berjenjang.
- Purchase Order wajib untuk client tertentu.
- Credit limit.
- Termin pembayaran.
- Status tagihan jatuh tempo.

#### Contoh alur

```text
User membuat order
→ Approval atasan
→ Verifikasi procurement
→ Verifikasi finance
→ Order diproses
```

#### Kriteria selesai

- Client dapat memiliki harga berbeda.
- User tidak dapat melewati batas approval.
- Order dengan termin memiliki status piutang.
- Admin dapat melihat limit dan saldo kewajiban client.

### Tahap 4 — Customer Relationship Management

**Prioritas: Should Have**

#### Fitur

- Membership Silver, Gold, dan Platinum.
- Benefit berbeda tiap tier.
- Account manager.
- Ticketing CS.
- Riwayat komunikasi client.
- SLA respons dan penyelesaian.
- Prioritas support.
- Prioritas klaim garansi.
- Reminder repeat order.

#### Contoh benefit

- Harga khusus.
- Prioritas quotation.
- Konsultasi teknis gratis.
- Gratis instalasi.
- SLA support lebih cepat.
- Prioritas stok.
- Poin tambahan.

#### Kriteria selesai

- Client memiliki tier.
- Benefit otomatis diterapkan.
- Admin dapat menetapkan account manager.
- Tiket memiliki SLA dan status.
- Riwayat komunikasi dapat dilihat dari profil client.

### Tahap 5 — Campaign dan Promo Dinamis

**Prioritas: Should Have**

#### Fitur

- Campaign 9.9, 10.10, dan promo akhir tahun.
- Promo berdasarkan brand.
- Promo kategori.
- Promo volume.
- Bundling produk.
- Gratis ongkir.
- Voucher khusus client.
- Jadwal mulai dan berakhir.
- Banner campaign.
- Statistik penggunaan promo.

#### Admin Filament

- Campaign resource.
- Produk atau kategori yang ikut.
- Banner desktop dan mobile.
- Kuota.
- Periode campaign.
- Nilai diskon.
- Minimum transaksi.
- Maksimum pemakaian per user.

#### Kriteria selesai

- Admin tidak perlu mengubah kode untuk membuat promo.
- Promo hanya aktif pada periode yang ditentukan.
- Promo dapat dibatasi berdasarkan client, kategori, atau produk.
- Penggunaan promo dapat dilaporkan.

### Tahap 6 — Affiliate dan Reseller

**Prioritas: Could Have / Optional**

#### Fitur

- Akun partner atau reseller.
- Kode referral khusus partner.
- Link referral.
- Komisi transaksi.
- Status komisi: pending, approved, paid.
- Dashboard partner.
- Rekap transaksi.
- Pengajuan pencairan komisi.

#### Aturan komisi

Komisi diberikan setelah:

```text
Order selesai
+ Masa retur berakhir
+ Tidak ada refund
```

Referral user biasa tetap dipisahkan dari affiliate partner.

### Tahap 7 — Pengembangan Lanjutan

**Prioritas: Optional**

- Live chat internal.
- Integrasi ERP atau accounting.
- Integrasi e-faktur.
- Dashboard analitik penjualan.
- Forecast stok.
- Rekomendasi produk.
- Subscription atau kontrak maintenance.
- Paket instalasi dan service berkala.
- Portal vendor dan partner.
- Mobile app native.

## 4. Urutan Pengerjaan

```text
1. RMA, retur, refund, dan garansi
2. Serial number dan service ticket
3. RFQ menjadi quotation resmi
4. Harga khusus client dan PO
5. Approval dan credit limit
6. CRM, account manager, dan SLA
7. Campaign promo dinamis
8. Affiliate dan reseller
9. Integrasi ERP dan fitur lanjutan
```

## 5. Prioritas MVP Tahap 2

Jika ingin memulai dari versi paling penting, kerjakan:

1. Modul RMA.
2. Klaim garansi.
3. Serial number.
4. Quotation resmi.
5. Approval quotation.
6. Notifikasi status.
7. Admin Filament untuk seluruh proses.

Dengan menyelesaikan tujuh bagian tersebut, Accommerce sudah lebih siap digunakan sebagai platform B2B, bukan hanya toko online.

## 6. Non-Goals

Fitur berikut tidak menjadi prioritas karena tidak sesuai dengan fokus Accommerce:

- Shopee Mall.
- Shopee Live atau Video.
- Shopee Games.
- ShopeeFood.
- SPayLater konsumen.
- Biaya admin seller berdasarkan kategori.
- Koin sebagai mata uang utama.

