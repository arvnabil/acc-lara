# Dokumentasi Modul & Hak Akses (Role & Permissions)
**PT Alfa Cipta Teknologi Virtual (ACTiV) - ActivonCMS**

Dokumen ini menjelaskan seluruh modul fitur yang ada di sistem ActivonCMS, struktur hak akses (Permissions Matrix), logika filter sidebar otomatis, serta mekanisme pembatasan data (*Author Scope*).

---

## 1. Arsitektur Hak Akses & Keamanan

Sistem keamanan dan otorisasi ActivonCMS dibangun di atas **Spatie Laravel Permission** yang diintegrasikan langsung dengan **Filament v4**.

### Prinsip Utama Sistem:
1. **Dynamic Sidebar Filtering (Filter Menu Otomatis)**:
   - Sidebar hanya menampilkan menu modul jika pengguna memiliki **minimal 1 izin (permission)** pada modul tersebut (misal `view`, `create`, atau `update`).
   - Jika semua hak akses pada modul tersebut dimatikan, menu modul akan **hilang total dari sidebar**.
   - Akses langsung via URL address bar pada modul yang tidak diizinkan otomatis diblokir (**403 Forbidden**).

2. **Granular Action Protection (Proteksi Tombol Aksi)**:
   - Tidak memiliki izin `create_*` -> Tombol **"New / Tambah"** disembunyikan.
   - Tidak memiliki izin `update_*` -> Tombol aksi **"Edit"** disembunyikan.
   - Tidak memiliki izin `delete_*` -> Tombol aksi **"Delete / Hapus"** disembunyikan.

3. **Super Administrator Bypass**:
   - Pengguna dengan role `administrator` secara otomatis memiliki akses penuh tanpa batas ke semua modul melalui `Gate::before`.

4. **Author Scope (Pembatasan Data Co-Admin)**:
   - **Administrator**: Dapat melihat, mengedit, dan menghapus **seluruh data** dari semua author.
   - **Co-Admin**: Hanya dapat melihat dan mengelola **data buatannya sendiri** (`user_id = auth()->id()`) pada modul konten utama (**News** dan **Projects**).

---

## 2. Daftar 15 Modul & Konfigurasi Hak Akses

Berikut adalah 15 modul lengkap beserta menu navigasi dan permission yang mengendalikannya:

---

### [Campaign] 1. Campaign Management
Modul untuk membuat landing page promosi dan marketing campaigns dengan integrasi Google Tag Manager / GA4 otomatis.

- **Grup Navigasi**: `Marketing`
- **Menu Navigasi**: `Campaigns`
- **Resource Terkait**: `Modules\Campaign\Filament\Resources\CampaignResource`
- **Daftar Permissions**:
  - `view_any_campaign` : Melihat daftar seluruh campaign
  - `view_campaign` : Melihat detail spesifik campaign
  - `create_campaign` : Membuat campaign baru
  - `update_campaign` : Mengubah data campaign
  - `delete_campaign` : Menghapus campaign

---

### [CMS] 2. CMS / Pages Management
Modul manajemen halaman statis, layout builder, serta branding identitas website.

- **Grup Navigasi**: `Site Management`
- **Menu Navigasi**: `Pages`, `Site Branding`
- **Resource & Page**:
  - `Modules\CMS\Filament\Resources\PageResource`
  - `Modules\CMS\Filament\Pages\ManageSiteBranding`
- **Daftar Permissions**:
  - `view_any_page` : Melihat daftar halaman
  - `view_page` : Melihat detail halaman
  - `create_page` : Membuat halaman baru
  - `update_page` : Mengedit konten halaman
  - `delete_page` : Menghapus halaman
  - `delete_any_page` : Menghapus massal halaman
  - `view_settings` / `update_settings` : Mengakses halaman `Site Branding`

---

### [Services] 3. Services Management
Modul katalog layanan, solusi integrasi sistem, dan formulir kalkulator/konfigurator kebutuhan layanan.

- **Grup Navigasi**: `Service Management`
- **Menu Navigasi**: `Services`, `Configurators`
- **Resource Terkait**:
  - `Modules\Services\Filament\Resources\ServiceResource`
  - `Modules\Services\Filament\Resources\ConfiguratorResource`
- **Daftar Permissions**:
  - `view_any_service` : Melihat daftar layanan & konfigurator
  - `view_service` : Melihat detail layanan
  - `create_service` : Menambah layanan baru
  - `update_service` : Mengubah data layanan
  - `delete_service` : Menghapus layanan
  - `delete_any_service` : Menghapus massal layanan

---

### [Products] 4. Product Catalog
Modul katalog produk hardware/software enterprise, brand prinsipal resmi, dan kategori perangkat.

- **Grup Navigasi**: `Product Catalog`
- **Menu Navigasi**: `Products`, `Brands`, `Device Categories`
- **Resource Terkait**:
  - `Modules\ProductCatalog\Filament\Resources\ProductResource`
  - `Modules\ProductCatalog\Filament\Resources\BrandResource`
  - `Modules\ProductCatalog\Filament\Resources\ProductCategoryResource`
- **Daftar Permissions**:
  - `view_any_product` : Melihat daftar produk & kategori perangkat
  - `view_product` : Melihat detail spesifikasi produk
  - `create_product` : Menambah produk baru
  - `update_product` : Mengubah data produk
  - `delete_product` : Menghapus produk
  - `delete_any_product` : Menghapus massal produk
  - `view_any_brand` : Melihat daftar brand partner
  - `view_brand` : Melihat detail brand
  - `create_brand` : Menambah brand partner
  - `update_brand` : Mengubah data brand
  - `delete_brand` : Menghapus brand partner

---

### [Clients] 5. Clients Management
Modul portofolio klien dan testimoni korporasi yang bekerjasama dengan ACTiV.

- **Grup Navigasi**: `Client Management`
- **Menu Navigasi**: `Clients`
- **Resource Terkait**: `Modules\Clients\Filament\Resources\ClientResource`
- **Daftar Permissions**:
  - `view_any_client` : Melihat daftar klien
  - `view_client` : Melihat detail data klien
  - `create_client` : Menambah logo/klien baru
  - `update_client` : Mengubah data klien
  - `delete_client` : Menghapus data klien
  - `delete_any_client` : Menghapus massal data klien

---

### [Events] 6. Events Management
Modul manajemen event/seminar/webinar lengkap beserta pendaftaran peserta, sertifikat, organizer, dan dokumentasi.

- **Grup Navigasi**: `Event Management`, `Event Manage Data`
- **Menu Navigasi**: `Event Dashboard`, `Events`, `Event Categories`, `Organizers`, `Event Documentations`, `Registrations`, `Certificates`
- **Resource & Page**:
  - `Modules\Events\Filament\Pages\EventDashboard`
  - `Modules\Events\Filament\Resources\EventResource`
  - `Modules\Events\Filament\Resources\EventCategoryResource`
  - `Modules\Events\Filament\Resources\OrganizerResource`
  - `Modules\Events\Filament\Resources\EventDocumentationResource`
  - `Modules\Events\Filament\Resources\EventRegistrationResource`
  - `Modules\Events\Filament\Resources\EventCertificateResource`
  - `Modules\Events\Filament\Resources\EventUserResource`
- **Daftar Permissions**:
  - `view_any_event` : Melihat dashboard & daftar event
  - `view_event` : Melihat detail event
  - `create_event` : Membuat event baru
  - `update_event` : Mengubah data event
  - `delete_event` : Menghapus event
  - `delete_any_event` : Menghapus massal event

---

### [News] 7. News Management (Dengan Author Scope)
Modul artikel berita, press release, wawasan teknologi, kategori artikel, dan tag berita.

- **Grup Navigasi**: `News Management`
- **Menu Navigasi**: `News`, `News Categories`, `News Tags`
- **Resource Terkait**:
  - `Modules\News\Filament\Resources\NewsResource`
  - `Modules\News\Filament\Resources\NewsCategoryResource`
  - `Modules\News\Filament\Resources\NewsTagResource`
- **Fitur Khusus**: **`HasAuthorScope`** -> Co-Admin hanya melihat berita miliknya sendiri.
- **Daftar Permissions**:
  - `view_any_news` : Melihat daftar artikel berita
  - `view_news` : Membaca artikel
  - `create_news` : Menulis artikel baru
  - `update_news` : Mengedit artikel
  - `delete_news` : Menghapus artikel
  - `delete_any_news` : Menghapus massal artikel (Admin Only)
  - `view_any_news_category` / `create_news_category` / `update_news_category` / `delete_news_category`
  - `view_any_news_tag` / `create_news_tag` / `update_news_tag` / `delete_news_tag`

---

### [Projects] 8. Projects Management (Dengan Author Scope)
Modul portofolio studi kasus proyek integrasi sistem (System Integrator Case Studies).

- **Grup Navigasi**: `Project Management`
- **Menu Navigasi**: `Projects`
- **Resource Terkait**: `Modules\Projects\Filament\Resources\ProjectResource`
- **Fitur Khusus**: **`HasAuthorScope`** -> Co-Admin hanya melihat proyek buatannya sendiri.
- **Daftar Permissions**:
  - `view_any_project` : Melihat daftar proyek portofolio
  - `view_project` : Melihat detail studi kasus proyek
  - `create_project` : Menambah proyek baru
  - `update_project` : Mengedit data proyek
  - `delete_project` : Menghapus data proyek
  - `delete_any_project` : Menghapus massal proyek (Admin Only)

---

### [Analytics] 9. Analytics Management
Modul tracking konversi dan audit log klik tombol CTA / interaksi WhatsApp di website publik.

- **Grup Navigasi**: `Analytics`
- **Menu Navigasi**: `Click Events`, `WhatsApp Logs`
- **Resource Terkait**:
  - `Modules\Analytics\Filament\Resources\ClickEventResource`
  - `Modules\Analytics\Filament\Resources\WhatsAppAnalyticsResource`
- **Daftar Permissions**:
  - `view_analytics` : Melihat log klik tombol & konversi CTA
  - `view_whatsapp` : Melihat riwayat percakapan/klik WhatsApp

---

### [SEO] 10. SEO Management
Modul optimalisasi mesin pencari (SEO Meta, SERP Preview, Domain Whitelist, dan Google Index Coverage).

- **Grup Navigasi**: `Seo Management`
- **Menu Navigasi**: `Seo Domains`, `Seo Meta`, `Index Coverage`, `SEO Settings`
- **Resource & Page**:
  - `Modules\SEO\Filament\Resources\SeoWhitelistDomainResource`
  - `Modules\SEO\Filament\Resources\SeoMetaResource`
  - `Modules\SEO\Filament\Pages\IndexCoverage`
  - `Modules\SEO\Filament\Pages\ManageSeoSettings`
- **Daftar Permissions**:
  - `view_seo` : Melihat data meta SEO, domain, dan Google Indexing
  - `update_seo` : Memperbarui konfigurasi SEO, sitemap, dan verifikasi domain

---

### [Settings] 11. Settings & API Keys
Modul konfigurasi global website dan kredensial API Key untuk integrasi aplikasi pihak ketiga (REST API / Mobile App).

- **Grup Navigasi**: `Settings`
- **Menu Navigasi**: `Settings`, `Api Keys`
- **Resource Terkait**:
  - `Modules\Settings\Filament\Resources\SettingResource`
  - `Modules\Settings\Filament\Resources\ApiKeyResource`
- **Daftar Permissions**:
  - `view_settings` : Melihat pengaturan sistem dan daftar API Key
  - `update_settings` : Mengubah setting sistem & me-regenerate API Key

---

### [Menu & Forms] 12. Menu & Form Builder
Modul navigasi header/footer dinamis serta perekam data formulir kustom (*Contact Us*, Penawaran, dll).

- **Grup Navigasi**: `Menu Management`, `Form Management`
- **Menu Navigasi**: `Menus`, `Form Submissions`
- **Resource Terkait**:
  - `Modules\Menu\Filament\Resources\MenuResource`
  - `Modules\FormBuilder\Filament\Resources\FormSubmissionResource`
- **Daftar Permissions**:
  - `view_any_menu` / `create_menu` / `update_menu` / `delete_menu`
  - `view_any_form` / `view_form` / `delete_form` : Melihat & mengelola data pesan masuk

---

### [WhatsApp] 13. WhatsApp Management
Modul kustomisasi widget floating chat WhatsApp di website publik.

- **Grup Navigasi**: `Settings`
- **Menu Navigasi**: `WhatsApp Bubble`
- **Page Terkait**: `Modules\WhatsApp\Filament\Pages\ManageWhatsAppBubble`
- **Daftar Permissions**:
  - `view_whatsapp` : Melihat setting widget WhatsApp
  - `update_whatsapp` : Mengubah nomor CS, teks template, dan posisi bubble

---

### [AI] 14. AI Management
Modul integrasi Google Gemini AI untuk pembuatan konten otomatis dan riwayat chat interaktif.

- **Grup Navigasi**: `AI Management`
- **Menu Navigasi**: `AI Settings`, `Chat Sessions`
- **Resource & Page**:
  - `Modules\AI\Filament\Resources\ChatSessionResource`
  - `Modules\AI\Filament\Pages\ManageAiSettings`
- **Daftar Permissions**:
  - `view_settings` / `update_settings` : Mengelola API Key AI dan sesi chat

---

### [Users & Roles] 15. User & Role Management (Fitur Utama Sistem)
Pusat kendali hak akses pengguna dan pembuatan peran (Role) kustom.

- **Grup Navigasi**: `User Management`
- **Menu Navigasi**: `Users`, `Roles & Permissions`
- **Resource Terkait**:
  - `App\Filament\Activioncms\Resources\UserResource`
  - `App\Filament\Activioncms\Resources\RoleResource`
- **Daftar Permissions**:
  - `view_any_user`, `view_user`, `create_user`, `update_user`, `delete_user`, `delete_any_user`
  - `view_any_role`, `view_role`, `create_role`, `update_role`, `delete_role`

---

## 3. Fitur UI Permissions Matrix (Switch Toggle)

Formulir pembuatan dan pengeditan Role (`RoleResource`) dilengkapi dengan fitur modern:
1. **Master Switch (Semua Modul Sekaligus)**:
   - Tombol **"Select All Modules"** (Hijau): Mencetang seluruh hak akses di semua 15 modul dalam 1 kali klik.
   - Tombol **"Clear All Modules"** (Abu-abu): Mengosongkan seluruh hak akses sistem.
2. **Per-Module Reactive Switch (Saklar di Tiap Kartu Modul)**:
   - Setiap kartu modul dilengkapi saklar **Switch "Select All"**.
   - Menggeser switch ke posisi **ON** otomatis mencentang seluruh checkbox modul tersebut.
   - Menggeser switch ke posisi **OFF** otomatis mengosongkan seluruh checkbox modul tersebut.
   - Jika pengguna mencentang satu-persatu checkbox hingga lengkap, saklar otomatis menyala **ON**.

---

## 4. Panduan Perawatan & Troubleshooting

### Membersihkan Cache Setelah Mengubah Role / Permission:
Ketika memperbarui role pengguna di production, bersihkan cache aplikasi agar perubahan langsung aktif:
```bash
php artisan optimize:clear
```

### Konfigurasi Cache di Lingkungan cPanel:
Untuk performa optimal dan menghindari error karakter biner Google Analytics pada MySQL, pastikan file `.env` menggunakan driver file:
```env
CACHE_STORE=file
```
