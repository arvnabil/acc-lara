/**
 * src/components/LicenseConfigModal.jsx
 * Popup / Bottom Sheet untuk memilih Durasi Lisensi & Jumlah Seat (Tinggal klik-klik di mobile & desktop).
 */
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { formatPrice } from '../services/productService';
import { useCart } from '../context/CartContext';
import { useToast } from '../context/ToastContext';

const LICENSE_DURATIONS = [
  { id: '1m', label: '1 Bulan', multiplier: 0.1, badge: 'Fleksibel' },
  { id: '1y', label: '1 Tahun', multiplier: 1, badge: 'Paling Populer', isPopular: true },
  { id: '2y', label: '2 Tahun', multiplier: 1.8, badge: 'Hemat 10%' },
  { id: '3y', label: '3 Tahun', multiplier: 2.5, badge: 'Hemat 17%' },
  { id: 'lifetime', label: 'Lifetime', multiplier: 4, badge: 'Permanen' },
];

const SEAT_PRESETS = [1, 5, 10, 25, 50, 100];

export default function LicenseConfigModal({ product, isOpen, onClose, onSuccess }) {
  const [duration, setDuration] = useState('1y');
  const [seats, setSeats] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { addItem } = useCart();
  const { showToast } = useToast();
  const navigate = useNavigate();

  if (!isOpen || !product) return null;

  const basePrice = product.sale_price || product.regular_price || 0;
  const selectedDuration = LICENSE_DURATIONS.find((d) => d.id === duration) || LICENSE_DURATIONS[1];
  const pricePerSeat = Math.round(basePrice * (selectedDuration?.multiplier || 1));
  const licenseTotal = pricePerSeat * seats;

  const image = (product.images?.[0] || '').split(',')[0].trim() ||
    `https://picsum.photos/seed/${product.sku || product.id}/200/200`;

  function createCartPayload() {
    return {
      ...product,
      cartKey: `${product.id}-license-${duration}-${seats}`,
      name: `${product.name} - Lisensi ${selectedDuration?.label} (${seats} User/Seat)`,
      regular_price: licenseTotal,
      sale_price: null,
      licenseInfo: {
        isLicense: true,
        duration: selectedDuration?.label,
        durationId: duration,
        seats,
        pricePerSeat,
        licenseTotal,
      },
    };
  }

  function handleAddToCart() {
    setIsSubmitting(true);
    const item = createCartPayload();
    addItem(item, 1);

    showToast({
      type: 'cart',
      title: `Lisensi ${seats} Seat Ditambahkan!`,
      message: `${product.name} (${selectedDuration?.label})`,
      link: '/keranjang',
      linkText: 'Lihat Keranjang',
    });

    onSuccess?.();
    setIsSubmitting(false);
    onClose();
  }

  function handleDirectCheckout() {
    const item = createCartPayload();
    addItem(item, 1);
    onClose();
    navigate('/checkout');
  }

  return (
    <div
      className="fixed inset-0 z-[70] flex items-end sm:items-center justify-center sm:p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
      aria-labelledby="license-modal-title"
    >
      <div
        className="w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col max-h-[90dvh] sm:max-h-[88vh] overflow-hidden animate-in fade-in slide-in-from-bottom-6 duration-200 pb-[env(safe-area-inset-bottom,0px)] sm:pb-0"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Mobile drag handle pill */}
        <div className="w-12 h-1.5 bg-gray-300 rounded-full mx-auto mt-2.5 mb-1 sm:hidden" />

        {/* Modal Header */}
        <div className="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between bg-purple-50/50">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0">
              <span className="material-symbols-outlined text-[20px]">key</span>
            </div>
            <div>
              <h2 id="license-modal-title" className="text-[15px] sm:text-[16px] font-bold text-gray-900 leading-tight">
                Pilih Paket & Lisensi
              </h2>
              <p className="text-[11px] sm:text-[12px] text-gray-500">
                Pilih durasi masa aktif dan jumlah user/seat (tinggal klik)
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
            aria-label="Tutup modal"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* Modal Body (Scrollable) */}
        <div className="p-4 sm:p-5 overflow-y-auto space-y-4 text-left">
          {/* Product Snapshot */}
          <div className="flex items-center gap-3 p-2.5 rounded-xl bg-gray-50 border border-gray-200/80">
            <img
              src={image}
              alt={product.name}
              className="w-14 h-14 object-contain rounded-lg bg-white p-1 border border-gray-200 shrink-0"
            />
            <div className="min-w-0 flex-1">
              <span className="text-[10px] font-mono text-gray-400 uppercase block">{product.sku || 'LISENSI DIGITAL'}</span>
              <h4 className="text-[12px] sm:text-[13px] font-semibold text-gray-800 line-clamp-1">{product.name}</h4>
              <div className="flex items-center gap-2 mt-0.5">
                <span className="text-[13px] font-bold text-purple-700">{formatPrice(basePrice)}</span>
                <span className="text-[10px] text-gray-500 font-medium bg-purple-100/80 px-1.5 py-0.2 rounded">/ seat / thn</span>
                <span className="text-[10px] text-purple-700 bg-purple-50 px-1.5 py-0.2 rounded font-medium ml-auto">
                  Aktivasi Instan
                </span>
              </div>
            </div>
          </div>

          {/* Durasi Lisensi (Tinggal Klik-Klik!) */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <label className="text-[12px] font-bold text-gray-700 flex items-center gap-1.5">
                <span className="material-symbols-outlined text-[16px] text-purple-600">schedule</span>
                Pilih Durasi Masa Aktif
              </label>
              <span className="text-[11px] font-semibold text-purple-700">
                {selectedDuration.label}
              </span>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-5 gap-1.5">
              {LICENSE_DURATIONS.map((d) => {
                const isSelected = duration === d.id;
                return (
                  <button
                    key={d.id}
                    type="button"
                    onClick={() => setDuration(d.id)}
                    className={`py-2 px-1.5 rounded-xl text-center transition-all relative border flex flex-col items-center justify-center
                      ${isSelected
                        ? 'bg-purple-600 text-white border-purple-600 shadow-sm shadow-purple-600/30 scale-[1.02]'
                        : 'bg-white hover:bg-purple-50/50 text-gray-700 border-gray-200'
                      }`}
                  >
                    {d.isPopular && !isSelected && (
                      <span className="absolute -top-1.5 text-[8px] bg-amber-400 text-amber-950 px-1 rounded-full font-bold">
                        Populer
                      </span>
                    )}
                    <span className="text-[11px] font-bold leading-tight">{d.label}</span>
                    <span className={`text-[9px] mt-0.5 block ${
                      isSelected ? 'text-purple-100' : 'text-purple-600 font-semibold'
                    }`}>
                      {d.badge}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Jumlah User / Seat */}
          <div className="bg-slate-50/70 p-3 rounded-2xl border border-gray-200/80 space-y-2.5">
            <div className="flex items-center justify-between">
              <span className="text-[12px] font-bold text-gray-800 flex items-center gap-1.5">
                <span className="material-symbols-outlined text-[16px] text-purple-600">groups</span>
                Jumlah User / Seat Lisensi
              </span>
              <span className="text-[11px] text-purple-700 font-semibold">
                {seats} Akun / Seat
              </span>
            </div>

            {/* Stepper + Input */}
            <div className="flex items-center gap-2">
              <div className="flex items-center border border-gray-300 rounded-xl overflow-hidden bg-white">
                <button
                  type="button"
                  onClick={() => setSeats((s) => Math.max(1, s - 1))}
                  className="w-10 h-10 flex items-center justify-center hover:bg-gray-50 transition-colors disabled:opacity-40"
                  disabled={seats <= 1}
                >
                  <span className="material-symbols-outlined text-[18px]">remove</span>
                </button>
                <input
                  type="number"
                  min={1}
                  max={999}
                  value={seats}
                  onChange={(e) => setSeats(Math.max(1, parseInt(e.target.value) || 1))}
                  className="w-16 text-center font-bold text-[15px] text-gray-800 border-x border-gray-200 h-10 focus:outline-none"
                />
                <button
                  type="button"
                  onClick={() => setSeats((s) => s + 1)}
                  className="w-10 h-10 flex items-center justify-center hover:bg-gray-50 transition-colors"
                >
                  <span className="material-symbols-outlined text-[18px]">add</span>
                </button>
              </div>
              <span className="text-[12px] text-gray-500">user / seat terdaftar</span>
            </div>

            {/* Quick chips seat preset */}
            <div>
              <span className="text-[10px] text-gray-400 block mb-1">Pilihan Cepat Seat:</span>
              <div className="flex flex-wrap gap-1.5">
                {SEAT_PRESETS.map((cnt) => {
                  const isSelected = seats === cnt;
                  return (
                    <button
                      key={cnt}
                      type="button"
                      onClick={() => setSeats(cnt)}
                      className={`px-3 py-1 rounded-lg text-[11px] font-semibold transition-colors border ${
                        isSelected
                          ? 'bg-purple-600 text-white border-purple-600'
                          : 'bg-white text-gray-700 border-gray-200 hover:border-purple-300'
                      }`}
                    >
                      {cnt} {cnt >= 100 ? 'Enterprise' : 'User'}
                    </button>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Benefit Lisensi ACTiV */}
          <div className="grid grid-cols-3 gap-2">
            <div className="flex items-center gap-1.5 bg-purple-50/70 p-2 rounded-xl border border-purple-100 text-[11px] text-gray-700">
              <span className="material-symbols-outlined text-[15px] text-emerald-600">bolt</span>
              <span className="leading-tight">Aktivasi Instan</span>
            </div>
            <div className="flex items-center gap-1.5 bg-purple-50/70 p-2 rounded-xl border border-purple-100 text-[11px] text-gray-700">
              <span className="material-symbols-outlined text-[15px] text-blue-600">verified</span>
              <span className="leading-tight">100% Resmi</span>
            </div>
            <div className="flex items-center gap-1.5 bg-purple-50/70 p-2 rounded-xl border border-purple-100 text-[11px] text-gray-700">
              <span className="material-symbols-outlined text-[15px] text-purple-600">support_agent</span>
              <span className="leading-tight">Bantuan Setup</span>
            </div>
          </div>

          {/* Ringkasan Biaya Lisensi */}
          <div className="p-3.5 rounded-2xl bg-gradient-to-br from-purple-50 to-indigo-50 border border-purple-200">
            <div className="flex items-center justify-between text-[11px] text-purple-950/80 mb-1">
              <span>Rincian Biaya Lisensi:</span>
              <span className="font-semibold">{seats} Seat × {selectedDuration.label}</span>
            </div>
            <div className="flex items-baseline justify-between pt-1 border-t border-purple-200/60">
              <span className="text-[12px] font-bold text-gray-800">Total Biaya Lisensi</span>
              <div className="text-right">
                <span className="text-[18px] font-black text-purple-700 leading-none">
                  {formatPrice(licenseTotal)}
                </span>
                <span className="text-[10px] text-gray-500 block">+PPN 11%</span>
              </div>
            </div>
            <div className="mt-2 flex items-center gap-1.5 text-[10px] text-purple-800 bg-white/70 px-2 py-1 rounded-lg">
              <span className="material-symbols-outlined text-[13px] text-purple-600">verified</span>
              <span>Kredensial aktivasi dikirimkan ke email akun setelah pembayaran.</span>
            </div>
          </div>
        </div>

        {/* Modal Footer / CTA */}
        <div className="p-3 sm:p-4 bg-white border-t border-gray-100 flex items-center gap-2">
          <button
            type="button"
            onClick={onClose}
            className="h-10 px-3.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 text-[12px] font-semibold transition-colors flex-shrink-0"
          >
            Batal
          </button>

          <button
            type="button"
            onClick={handleAddToCart}
            disabled={isSubmitting}
            className="flex-1 h-10 rounded-xl bg-purple-600 hover:bg-purple-700 active:scale-98 text-white text-[12px] font-bold flex items-center justify-center gap-1.5 transition-all shadow-md shadow-purple-600/20"
          >
            <span className="material-symbols-outlined text-[17px]">add_shopping_cart</span>
            <span>+ Masukkan Keranjang</span>
          </button>

          <button
            type="button"
            onClick={handleDirectCheckout}
            className="h-10 px-3 sm:px-4 rounded-xl bg-primary hover:bg-primary/90 text-white text-[12px] font-bold flex items-center justify-center gap-1 transition-colors"
          >
            <span>Checkout</span>
            <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>
  );
}
