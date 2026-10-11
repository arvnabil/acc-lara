import { useState } from 'react';
import { Link } from 'react-router-dom';

const STEPS = {
  EMAIL: 'email',
  OTP: 'otp',
  NEW_PASSWORD: 'new_password',
  SUCCESS: 'success',
};

export default function ForgotPasswordPage() {
  const [step, setStep] = useState(STEPS.EMAIL);
  const [email, setEmail] = useState('');
  const [otp, setOtp] = useState(['', '', '', '', '', '']);
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [resendCooldown, setResendCooldown] = useState(0);

  // ── OTP input logic ──────────────────────────────────────────
  function handleOtpChange(idx, val) {
    if (!/^\d?$/.test(val)) return;
    const next = [...otp];
    next[idx] = val;
    setOtp(next);
    if (val && idx < 5) {
      document.getElementById(`otp-${idx + 1}`)?.focus();
    }
  }

  function handleOtpKeyDown(idx, e) {
    if (e.key === 'Backspace' && !otp[idx] && idx > 0) {
      document.getElementById(`otp-${idx - 1}`)?.focus();
    }
  }

  function handleOtpPaste(e) {
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    if (pasted.length === 6) {
      setOtp(pasted.split(''));
      e.preventDefault();
    }
  }

  // ── Step handlers ────────────────────────────────────────────
  function handleSendOtp(e) {
    e.preventDefault();
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      setStep(STEPS.OTP);
      startResendCooldown();
    }, 1500);
  }

  function handleVerifyOtp(e) {
    e.preventDefault();
    const code = otp.join('');
    if (code.length < 6) return;
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      setStep(STEPS.NEW_PASSWORD);
    }, 1200);
  }

  function handleResetPassword(e) {
    e.preventDefault();
    if (newPassword !== confirmPassword) return;
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      setStep(STEPS.SUCCESS);
    }, 1400);
  }

  function startResendCooldown() {
    setResendCooldown(60);
    const timer = setInterval(() => {
      setResendCooldown(prev => {
        if (prev <= 1) { clearInterval(timer); return 0; }
        return prev - 1;
      });
    }, 1000);
  }

  function handleResend() {
    if (resendCooldown > 0) return;
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      startResendCooldown();
    }, 800);
  }

  // ── Step metadata ────────────────────────────────────────────
  const STEP_META = {
    [STEPS.EMAIL]: { num: 1, label: 'Email' },
    [STEPS.OTP]: { num: 2, label: 'Verifikasi' },
    [STEPS.NEW_PASSWORD]: { num: 3, label: 'Password Baru' },
    [STEPS.SUCCESS]: { num: 4, label: 'Selesai' },
  };

  const passwordMatch = newPassword === confirmPassword;
  const passwordStrong = newPassword.length >= 8;

  return (
    <div className="min-h-screen flex flex-col bg-white">
      {/* ════ TOP HEADER ════ */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div className="max-w-[1200px] mx-auto px-4 sm:px-8 h-[72px] flex items-center justify-between">
          <div className="flex items-center gap-3 sm:gap-5">
            <Link to="/" className="flex items-center">
              <img src="/accommerce-blue.png" alt="Accommerce" className="h-8 sm:h-9 w-auto object-contain" />
            </Link>
            <div className="h-6 w-px bg-gray-300 hidden sm:block"></div>
            <span className="text-[20px] sm:text-[22px] font-bold text-gray-800">
              Lupa Password
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

      {/* ════ MAIN CONTENT ════ */}
      <main className="bg-[#1a56db] py-10 sm:py-16 px-4 sm:px-8 flex-1 flex items-center relative overflow-hidden">
        {/* Decorative shapes */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-white opacity-5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
        <div className="absolute bottom-0 left-0 w-80 h-80 bg-white opacity-5 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3 pointer-events-none"></div>

        <div className="max-w-[500px] mx-auto w-full relative z-10">
          {/* Step Progress Indicator */}
          {step !== STEPS.SUCCESS && (
            <div className="flex items-center justify-center gap-2 mb-8">
              {Object.values(STEPS).filter(s => s !== STEPS.SUCCESS).map((s, idx, arr) => {
                const meta = STEP_META[s];
                const isDone = STEP_META[step].num > meta.num;
                const isActive = step === s;
                return (
                  <div key={s} className="flex items-center gap-2">
                    <div className="flex flex-col items-center gap-1">
                      <div className={`w-8 h-8 rounded-full flex items-center justify-center text-[13px] font-bold transition-all ${
                        isDone ? 'bg-white text-[#1a56db]' :
                        isActive ? 'bg-white text-[#1a56db] shadow-lg' :
                        'bg-white/20 text-white/70'
                      }`}>
                        {isDone ? '✓' : meta.num}
                      </div>
                      <span className={`text-[10px] font-medium ${isActive ? 'text-white' : 'text-white/60'}`}>{meta.label}</span>
                    </div>
                    {idx < arr.length - 1 && (
                      <div className={`w-12 sm:w-16 h-0.5 mb-4 ${isDone ? 'bg-white' : 'bg-white/25'}`}></div>
                    )}
                  </div>
                );
              })}
            </div>
          )}

          {/* ═══ CARD ═══ */}
          <div className="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">

            {/* STEP 1: Email */}
            {step === STEPS.EMAIL && (
              <>
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <span className="material-symbols-outlined text-[28px] text-[#1a56db]">lock_reset</span>
                  </div>
                  <div>
                    <h1 className="text-[20px] font-bold text-gray-900">Reset Password</h1>
                    <p className="text-[12px] text-gray-500">Masukkan email akun Anda</p>
                  </div>
                </div>

                <form onSubmit={handleSendOtp} className="flex flex-col gap-4">
                  <div className="bg-blue-50 border border-blue-100 rounded-xl p-3.5 flex items-start gap-2.5">
                    <span className="material-symbols-outlined text-[18px] text-[#1a56db] flex-shrink-0 mt-0.5">info</span>
                    <p className="text-[12px] text-blue-800 leading-relaxed">
                      Kami akan mengirimkan kode OTP 6 digit ke email Anda. Kode berlaku selama <strong>10 menit</strong>.
                    </p>
                  </div>

                  <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-bold text-gray-700 uppercase tracking-wider">Email Terdaftar</label>
                    <div className="relative">
                      <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">mail</span>
                      <input
                        type="email"
                        required
                        value={email}
                        onChange={e => setEmail(e.target.value)}
                        placeholder="Masukkan email Anda"
                        className="w-full bg-white border border-gray-300 rounded-xl pl-11 pr-4 py-3 text-[14px] text-gray-800 focus:outline-none focus:border-[#1a56db] focus:ring-1 focus:ring-[#1a56db] transition-colors placeholder:text-gray-400"
                      />
                    </div>
                  </div>

                  <button
                    type="submit"
                    disabled={loading || !email}
                    className="w-full bg-[#1a56db] hover:bg-[#1e40af] text-white font-bold text-[15px] py-3.5 rounded-xl mt-2 transition-colors flex items-center justify-center gap-2 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    {loading ? (
                      <><span className="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" /> Mengirim OTP...</>
                    ) : (
                      <>Kirim Kode OTP <span className="material-symbols-outlined text-[18px]">send</span></>
                    )}
                  </button>
                </form>

                <p className="text-center text-[13px] text-gray-500 mt-5">
                  Ingat password? <Link to="/login" className="font-bold text-[#1a56db] hover:underline">Masuk sekarang</Link>
                </p>
              </>
            )}

            {/* STEP 2: OTP Verification */}
            {step === STEPS.OTP && (
              <>
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <span className="material-symbols-outlined text-[28px] text-[#1a56db]">sms</span>
                  </div>
                  <div>
                    <h1 className="text-[20px] font-bold text-gray-900">Kode Verifikasi</h1>
                    <p className="text-[12px] text-gray-500">Masukkan 6 digit OTP</p>
                  </div>
                </div>

                <div className="bg-blue-50 border border-blue-100 rounded-xl p-3.5 mb-6">
                  <p className="text-[12px] text-blue-800">
                    Kode OTP telah dikirim ke <strong className="text-[#1a56db]">{email}</strong>. Periksa inbox atau folder spam Anda.
                  </p>
                </div>

                <form onSubmit={handleVerifyOtp} className="flex flex-col gap-4">
                  {/* OTP Boxes */}
                  <div>
                    <label className="text-[12px] font-bold text-gray-700 uppercase tracking-wider block mb-3">Kode OTP (6 Digit)</label>
                    <div className="flex gap-2 justify-between" onPaste={handleOtpPaste}>
                      {otp.map((digit, idx) => (
                        <input
                          key={idx}
                          id={`otp-${idx}`}
                          type="text"
                          inputMode="numeric"
                          maxLength={1}
                          value={digit}
                          onChange={e => handleOtpChange(idx, e.target.value)}
                          onKeyDown={e => handleOtpKeyDown(idx, e)}
                          className="w-12 h-14 text-center text-[22px] font-bold border-2 rounded-xl focus:outline-none focus:border-[#1a56db] transition-colors text-gray-800 bg-gray-50 border-gray-200"
                          style={{ caretColor: 'transparent' }}
                        />
                      ))}
                    </div>
                  </div>

                  <button
                    type="submit"
                    disabled={loading || otp.join('').length < 6}
                    className="w-full bg-[#1a56db] hover:bg-[#1e40af] text-white font-bold text-[15px] py-3.5 rounded-xl transition-colors flex items-center justify-center gap-2 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    {loading ? (
                      <><span className="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" /> Memverifikasi...</>
                    ) : (
                      'Verifikasi Kode'
                    )}
                  </button>
                </form>

                <div className="flex items-center justify-between mt-4">
                  <button
                    onClick={() => setStep(STEPS.EMAIL)}
                    className="text-[12px] text-gray-500 hover:text-gray-700 flex items-center gap-1"
                  >
                    <span className="material-symbols-outlined text-[16px]">arrow_back</span>
                    Ganti Email
                  </button>
                  <button
                    onClick={handleResend}
                    disabled={resendCooldown > 0}
                    className="text-[12px] font-semibold text-[#1a56db] hover:underline disabled:text-gray-400 disabled:no-underline disabled:cursor-not-allowed"
                  >
                    {resendCooldown > 0 ? `Kirim ulang (${resendCooldown}s)` : 'Kirim Ulang Kode'}
                  </button>
                </div>
              </>
            )}

            {/* STEP 3: New Password */}
            {step === STEPS.NEW_PASSWORD && (
              <>
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <span className="material-symbols-outlined text-[28px] text-[#1a56db]">key</span>
                  </div>
                  <div>
                    <h1 className="text-[20px] font-bold text-gray-900">Password Baru</h1>
                    <p className="text-[12px] text-gray-500">Buat password yang kuat</p>
                  </div>
                </div>

                <form onSubmit={handleResetPassword} className="flex flex-col gap-4">
                  {/* New Password */}
                  <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-bold text-gray-700 uppercase tracking-wider">Password Baru</label>
                    <div className="relative">
                      <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">lock</span>
                      <input
                        type={showNewPassword ? 'text' : 'password'}
                        required
                        value={newPassword}
                        onChange={e => setNewPassword(e.target.value)}
                        placeholder="Minimal 8 karakter"
                        className="w-full bg-white border border-gray-300 rounded-xl pl-11 pr-11 py-3 text-[14px] text-gray-800 focus:outline-none focus:border-[#1a56db] focus:ring-1 focus:ring-[#1a56db] transition-colors placeholder:text-gray-400"
                      />
                      <button type="button" onClick={() => setShowNewPassword(!showNewPassword)}
                        className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                        <span className="material-symbols-outlined text-[20px]">{showNewPassword ? 'visibility_off' : 'visibility'}</span>
                      </button>
                    </div>

                    {/* Strength indicator */}
                    {newPassword && (
                      <div className="flex gap-1 mt-1">
                        {[8, 10, 12].map(len => (
                          <div key={len} className={`flex-1 h-1 rounded-full transition-colors ${
                            newPassword.length >= len ? 'bg-green-500' :
                            newPassword.length >= len - 3 ? 'bg-amber-400' : 'bg-gray-200'
                          }`} />
                        ))}
                        <span className={`text-[10px] font-medium ml-1 ${passwordStrong ? 'text-green-600' : 'text-gray-400'}`}>
                          {newPassword.length < 8 ? 'Terlalu pendek' : newPassword.length < 10 ? 'Lemah' : newPassword.length < 12 ? 'Sedang' : 'Kuat'}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Confirm Password */}
                  <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-bold text-gray-700 uppercase tracking-wider">Konfirmasi Password</label>
                    <div className="relative">
                      <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">lock_clock</span>
                      <input
                        type={showConfirmPassword ? 'text' : 'password'}
                        required
                        value={confirmPassword}
                        onChange={e => setConfirmPassword(e.target.value)}
                        placeholder="Ulangi password baru"
                        className={`w-full bg-white border rounded-xl pl-11 pr-11 py-3 text-[14px] text-gray-800 focus:outline-none focus:ring-1 transition-colors placeholder:text-gray-400 ${
                          confirmPassword && !passwordMatch
                            ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
                            : 'border-gray-300 focus:border-[#1a56db] focus:ring-[#1a56db]'
                        }`}
                      />
                      <button type="button" onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                        className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                        <span className="material-symbols-outlined text-[20px]">{showConfirmPassword ? 'visibility_off' : 'visibility'}</span>
                      </button>
                    </div>
                    {confirmPassword && !passwordMatch && (
                      <p className="text-[11px] text-red-500 flex items-center gap-1">
                        <span className="material-symbols-outlined text-[13px]">error</span>
                        Password tidak cocok
                      </p>
                    )}
                    {confirmPassword && passwordMatch && (
                      <p className="text-[11px] text-green-600 flex items-center gap-1">
                        <span className="material-symbols-outlined text-[13px]">check_circle</span>
                        Password cocok
                      </p>
                    )}
                  </div>

                  <button
                    type="submit"
                    disabled={loading || !passwordStrong || !passwordMatch || !confirmPassword}
                    className="w-full bg-[#1a56db] hover:bg-[#1e40af] text-white font-bold text-[15px] py-3.5 rounded-xl mt-2 transition-colors flex items-center justify-center gap-2 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    {loading ? (
                      <><span className="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" /> Menyimpan...</>
                    ) : (
                      'Simpan Password Baru'
                    )}
                  </button>
                </form>
              </>
            )}

            {/* STEP 4: Success */}
            {step === STEPS.SUCCESS && (
              <div className="text-center py-4">
                <div className="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
                  <span className="material-symbols-outlined text-[48px] text-green-600">check_circle</span>
                </div>
                <h1 className="text-[22px] font-bold text-gray-900 mb-2">Password Berhasil Diubah!</h1>
                <p className="text-[13px] text-gray-500 mb-6">
                  Password akun <strong className="text-gray-800">{email}</strong> telah berhasil diperbarui. Silakan masuk dengan password baru Anda.
                </p>
                <Link
                  to="/login"
                  className="w-full inline-flex items-center justify-center gap-2 bg-[#1a56db] hover:bg-[#1e40af] text-white font-bold text-[15px] py-3.5 rounded-xl transition-colors shadow-sm"
                >
                  <span className="material-symbols-outlined text-[18px]">login</span>
                  Masuk Sekarang
                </Link>
              </div>
            )}
          </div>
        </div>
      </main>

      {/* ════ FOOTER ════ */}
      <footer className="bg-white border-t border-gray-200 py-6 sm:py-8">
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
