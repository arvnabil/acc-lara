# P-009 — Otentikasi (LoginPage & RegisterPage)

**Path rute**: `#/login`, `#/register`, `#/forgot-password` (belum diimplementasi secara UI, tapi konsepnya sama)

> **Perhatian:** `00-inventory.md` mencatat rute `#/lupa-password` ke `ForgotPasswordPage` (407 baris, R-15), sedangkan dokumen ini menyebut forgot-password belum diimplementasi. Salah satunya keliru; verifikasi ke kode sebelum migrasi. Halaman tersebut belum punya blueprint sendiri.
**Nama rute**: Login, Register
**File sumber**: `src/pages/LoginPage.jsx` (249 baris), `src/pages/RegisterPage.jsx` (278 baris)
**Layout**: Berdiri sendiri (Tidak menggunakan `<Layout>`), menggunakan layar penuh tanpa Header/Footer global.

---

## 1. Komponen Anak (Pohon)

```
Auth Page (Login/Register)
├── Top Header (Logo & Butuh Bantuan link)
└── Main Content (Latar biru `bg-[#1a56db]`)
    ├── Left Column: Dekorasi & Feature Card (hidden di mobile)
    └── Right Column: Form Card
        ├── Input (Email, Password, Name) dengan icon prepend
        ├── Checkbox "Ingat Saya" / "Syarat Ketentuan"
        ├── Tombol Submit (Primary)
        ├── Opsi Lupa Password (khusus Login)
        └── Tombol Login dengan Google
```

## 2. Kebutuhan Data & State

- Menggunakan `login` dari `AuthContext`.
- Membutuhkan parameter `location.state.from` (React Router) untuk redirect kembali ke halaman semula setelah login berhasil.
- **State Form**: `email`, `password`, `name` (register), `showPassword` (toggle mata).
- **State Loading**: `loading` (tombol utama), `googleLoading` (tombol google).

## 3. Efek Samping & Interaksi

1. **Submit Manual**: Memanggil `login({ name: 'Pengguna Demo', email })` dengan set timeout 1.2 detik (efek loading).
2. **Submit Google**: Memanggil `login({ name: 'Demo Google User', email: 'demo.google@gmail.com', memberTier: 'MEMBER GOLD' })` dengan set timeout.
3. Setelah login berhasil, memanggil `showToast` (sukses) lalu navigasi ke `from` (biasanya `/akun` atau kembali ke checkout).

## 4. Target Laravel + Inertia

Ini adalah area yang WAJIB terhubung dengan sistem otentikasi nyata (Laravel Breeze / Fortify / UI).

| Fitur | Adaptasi Laravel |
|---|---|
| Route | `GET /login`, `POST /login`, `GET /register`, `POST /register`, `POST /logout` |
| Komponen | `resources/js/Pages/Auth/Login.jsx`, `resources/js/Pages/Auth/Register.jsx` |
| Autentikasi | Gunakan `useForm` dari `@inertiajs/react`. Form tidak lagi di-*mock* set timeout, melainkan disubmit POST ke backend `route('login')`. |
| Login Google | Implementasi Laravel Socialite. Arahkan href tombol google ke `/auth/google/redirect`. |
| Desain | Salin *plek-ketiplek* seluruh class Tailwind dan SVG dari React ke komponen Inertia. |
| Tier dari klien | Login demo Google React mengirim `memberTier: 'MEMBER GOLD'`. Di Laravel tier **tidak pernah** diterima dari klien; semua akun baru mulai di `Classic` (`current_tier`) dengan `tier_points = 0` dan `points_balance = 0`. |
| Registrasi (Revisi 4) | Poin dan tier dimiliki **akun perusahaan** (satu NPWP = satu akun induk). Form register saat ini hanya nama/email/password. Tambahkan pilihan: daftar sebagai perorangan, atau perusahaan (nama perusahaan, NPWP opsional) dan alur bergabung sebagai sub-akun. Saat register, buat `companies` + `loyalty_accounts` (lihat `04-loyalty-system-migration.md`). Ini penambahan, bukan paritas 1:1. |

## 5. Kriteria Penerimaan Paritas

- **Given** URL `/login`, **When** user mengakses halaman, **Then** Header utama tertutup dan hanya header spesifik login yang muncul.
- **Given** form login, **When** mengklik ikon mata di field password, **Then** input type berubah dari `password` menjadi `text`.
- **Given** mencoba masuk halaman terproteksi (misal `/akun`), **When** dialihkan ke `/login`, **Then** setelah berhasil login, user dikembalikan ke halaman `/akun` (menggunakan logika redirect intended url Laravel).
- **Given** mengisi form dan submit, **When** loading berlangsung, **Then** tombol berubah disable dengan ikon spinner *loading*.
