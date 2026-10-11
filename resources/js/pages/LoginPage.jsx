import { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useToast } from '../context/ToastContext';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [googleLoading, setGoogleLoading] = useState(false);
  const { login } = useAuth();
  const { showToast } = useToast();
  const navigate = useNavigate();
  const location = useLocation();

  const from = location.state?.from || '/akun';

  function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);
    setTimeout(() => {
      login({ name: 'Pengguna Demo', email });
      showToast({ title: 'Login berhasil! Selamat datang 👋', type: 'success' });
      navigate(from, { replace: true });
      setLoading(false);
    }, 1200);
  }

  function handleGoogleLogin() {
    setGoogleLoading(true);
    setTimeout(() => {
      login({ name: 'Demo Google User', email: 'demo.google@gmail.com', memberTier: 'MEMBER GOLD' });
      showToast({ title: 'Login dengan Google berhasil! 🎉', type: 'success' });
      navigate(from, { replace: true });
      setGoogleLoading(false);
    }, 1400);
  }

  return (
    <div className="min-h-screen flex flex-col bg-white">
      {/* ════════════════════════════════════════
          SHOPEE-STYLE TOP HEADER
      ════════════════════════════════════════ */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div className="max-w-[1200px] mx-auto px-4 sm:px-8 h-[72px] flex items-center justify-between">
          <div className="flex items-center gap-3 sm:gap-5">
            <Link to="/" className="flex items-center">
              <img src="/accommerce-blue.png" alt="Accommerce" className="h-8 sm:h-9 w-auto object-contain" />
            </Link>
            <div className="h-6 w-px bg-gray-300 hidden sm:block"></div>
            <span className="text-[20px] sm:text-[22px] font-bold text-gray-800">
              Masuk
            </span>
          </div>
          <a
            href="https://wa.me/6287780116800"
            target="_blank"
            rel="noopener noreferrer"
            className="text-[13px] sm:text-[14px] font-semibold text-[#1a56db] hover:underline flex items-center gap-1.5"
          >
            <span className="material-symbols-outlined text-[18px]">help</span>
            <span>Butuh bantuan?</span>
          </a>
        </div>
      </header>

      {/* ════════════════════════════════════════
          MAIN CONTENT — Accommerce Primary Blue
      ════════════════════════════════════════ */}
      <main className="bg-[#1a56db] py-8 sm:py-14 px-4 sm:px-8 flex-1 flex items-center relative overflow-hidden">
        {/* Decorative background shapes */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-white opacity-5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
        <div className="absolute bottom-0 left-0 w-80 h-80 bg-white opacity-5 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3 pointer-events-none"></div>

        <div className="max-w-[1200px] mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10">
          {/* Left Column: Accommerce Feature Card & Description */}
          <div className="hidden lg:flex flex-col items-center justify-center lg:col-span-7">
            <div className="w-full max-w-[440px]">
              <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 shadow-2xl">
                <div className="flex items-center justify-between mb-6">
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-white text-[24px]">shopping_bag</span>
                    <span className="text-white text-[12px] font-bold tracking-widest uppercase">Platform Belanja Online</span>
                  </div>
                  <span className="bg-white text-[#1a56db] text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">Trusted</span>
                </div>

                <div className="flex flex-col gap-3">
                  {/* Feature 1 */}
                  <div className="bg-white/10 hover:bg-white/20 transition-colors rounded-xl p-4 flex items-center justify-between border border-white/10">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                        <span className="material-symbols-outlined text-white text-[20px]">local_shipping</span>
                      </div>
                      <span className="text-white text-[15px] font-bold">Gratis Ongkir</span>
                    </div>
                    <span className="bg-green-400 text-green-900 text-[10px] font-bold px-2 py-1 rounded-full">Klaim Tiap Hari</span>
                  </div>
                  {/* Feature 2 */}
                  <div className="bg-white/10 hover:bg-white/20 transition-colors rounded-xl p-4 flex items-center justify-between border border-white/10">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                        <span className="material-symbols-outlined text-white text-[20px]">verified</span>
                      </div>
                      <span className="text-white text-[15px] font-bold">100% Original</span>
                    </div>
                    <span className="bg-white/20 text-white text-[10px] font-bold px-2 py-1 rounded-full">Garansi Resmi</span>
                  </div>
                  {/* Feature 3 */}
                  <div className="bg-white/10 hover:bg-white/20 transition-colors rounded-xl p-4 flex items-center justify-between border border-white/10">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                        <span className="material-symbols-outlined text-white text-[20px]">bolt</span>
                      </div>
                      <span className="text-white text-[15px] font-bold">Flash Sale</span>
                    </div>
                    <span className="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-full">Diskon s/d 90%</span>
                  </div>
                </div>
              </div>
              
              <div className="text-center mt-8">
                <h2 className="text-white text-2xl font-bold mb-2">Belanja IT Lebih Mudah</h2>
                <p className="text-blue-100 text-[14px] leading-relaxed font-medium">
                  Temukan jutaan produk gadget, elektronik, dan aksesoris komputer original dengan penawaran harga terbaik setiap harinya.
                </p>
              </div>
            </div>
          </div>

          {/* Right Column: Login Card Form */}
          <div className="lg:col-span-5 w-full flex justify-center lg:justify-end">
            <div className="w-full max-w-[420px] bg-white rounded-2xl shadow-2xl p-6 sm:p-8">
              <h1 className="text-[24px] sm:text-[26px] font-bold text-gray-900 mb-1">Masuk</h1>
              <p className="text-[13px] sm:text-[14px] text-gray-500 mb-6">Silakan masuk ke akun Accommerce Anda untuk mulai belanja.</p>

              <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                {/* Email Field */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-bold text-gray-700 uppercase tracking-wider">Email / No. HP</label>
                  <div className="relative">
                    <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">person</span>
                    <input
                      type="text"
                      required
                      value={email}
                      onChange={e => setEmail(e.target.value)}
                      placeholder="Masukkan email / no. HP"
                      className="w-full bg-white border border-gray-300 rounded-xl pl-11 pr-4 py-3 text-[14px] text-gray-800 focus:outline-none focus:border-[#1a56db] focus:ring-1 focus:ring-[#1a56db] transition-colors placeholder:text-gray-400"
                    />
                  </div>
                </div>

                {/* Password Field */}
                <div className="flex flex-col gap-1.5">
                  <div className="flex items-center justify-between">
                    <label className="text-[12px] font-bold text-gray-700 uppercase tracking-wider">Kata Sandi</label>
                    <Link to="/lupa-password" className="text-[12px] font-bold text-[#1a56db] hover:underline">Lupa Password?</Link>
                  </div>
                  <div className="relative">
                    <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">lock</span>
                    <input
                      type={showPassword ? 'text' : 'password'}
                      required
                      value={password}
                      onChange={e => setPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full bg-white border border-gray-300 rounded-xl pl-11 pr-11 py-3 text-[14px] text-gray-800 focus:outline-none focus:border-[#1a56db] focus:ring-1 focus:ring-[#1a56db] transition-colors placeholder:text-gray-400"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 transition-colors"
                    >
                      <span className="material-symbols-outlined text-[20px]">{showPassword ? 'visibility_off' : 'visibility'}</span>
                    </button>
                  </div>
                </div>

                {/* Submit Button */}
                <button
                  type="submit"
                  disabled={loading || googleLoading}
                  className="w-full bg-[#1a56db] hover:bg-[#1e40af] text-white font-bold text-[15px] py-3.5 rounded-xl mt-2 transition-colors flex items-center justify-center gap-2 shadow-sm active:scale-98"
                >
                  {loading ? (
                    <><span className="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" /> Memproses...</>
                  ) : (
                    'Masuk'
                  )}
                </button>
              </form>

              {/* Divider */}
              <div className="flex items-center gap-4 my-5">
                <div className="flex-1 h-px bg-gray-200"></div>
                <span className="text-[12px] font-medium text-gray-400">Atau masuk dengan</span>
                <div className="flex-1 h-px bg-gray-200"></div>
              </div>

              {/* Google Button */}
              <button
                type="button"
                onClick={handleGoogleLogin}
                disabled={loading || googleLoading}
                className="w-full bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold text-[14px] py-3 rounded-xl transition-all flex items-center justify-center gap-3 shadow-sm active:scale-98"
              >
                {googleLoading ? (
                  <span className="w-5 h-5 border-2 border-gray-300 border-t-[#1a56db] rounded-full animate-spin" />
                ) : (
                  <svg className="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                  </svg>
                )}
                Google
              </button>

              <p className="text-center text-[13px] sm:text-[14px] text-gray-500 mt-6">
                Baru di Accommerce? <Link to="/register" className="font-bold text-[#1a56db] hover:underline">Daftar sekarang</Link>
              </p>
            </div>
          </div>
        </div>
      </main>

      {/* ════════════════════════════════════════
          SHOPEE-STYLE BOTTOM FOOTER
      ════════════════════════════════════════ */}
      <footer className="bg-white border-t border-gray-200 py-6 sm:py-8 text-center text-gray-500 text-[12px] sm:text-[13px]">
        <div className="max-w-[1200px] mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-gray-600 font-medium text-[12px]">
            <Link to="/" className="hover:text-[#1a56db] transition-colors">Beranda</Link>
            <Link to="/katalog" className="hover:text-[#1a56db] transition-colors">Katalog</Link>
            <Link to="/bantuan" className="hover:text-[#1a56db] transition-colors">Pusat Bantuan</Link>
            <a href="https://wa.me/6287780116800" target="_blank" rel="noopener noreferrer" className="hover:text-[#1a56db] transition-colors">Hubungi CS</a>
          </div>
          <div className="text-gray-400 text-[12px]">
            &copy; 2026 Accommerce by ACTiV. All Rights Reserved.
          </div>
        </div>
      </footer>
    </div>
  );
}
