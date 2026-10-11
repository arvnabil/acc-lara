/**
 * src/components/RentalBookingModal.jsx
 * Popup / Bottom Sheet untuk memilih Tanggal & Jam Sewa (Tinggal klik-klik di mobile & desktop).
 */
import { useState, useId } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { formatPrice } from '../services/productService';
import { useCart } from '../context/CartContext';
import { useToast } from '../context/ToastContext';

// Helper date utilities without timezone shift issues
function getISODate(d = new Date()) {
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function addDaysToDate(baseDateStr, days) {
  const [y, m, d] = (baseDateStr || getISODate()).split('-').map(Number);
  const date = new Date(y, m - 1, d);
  date.setDate(date.getDate() + days);
  return getISODate(date);
}

function formatDateDisplay(dateStr) {
  if (!dateStr) return '';
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  return date.toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
}

function getRentalDays(startDateStr, endDateStr) {
  if (!startDateStr || !endDateStr) return 1;
  const [y1, m1, d1] = startDateStr.split('-').map(Number);
  const [y2, m2, d2] = endDateStr.split('-').map(Number);
  const date1 = new Date(y1, m1 - 1, d1);
  const date2 = new Date(y2, m2 - 1, d2);
  const diffTime = date2.getTime() - date1.getTime();
  const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
  if (diffDays <= 0) return 1; // Same-day return counts as 1 day
  return diffDays;
}

const QUICK_START_OPTIONS = [
  { label: 'Hari Ini', offsetDays: 0 },
  { label: 'Besok', offsetDays: 1 },
  { label: 'Lusa', offsetDays: 2 },
];

const QUICK_TIMES = ['08:00', '09:00', '10:00', '13:00', '14:00', '16:00', '18:00'];

const DURATION_PRESETS = [
  { days: 1, label: '1 Hari' },
  { days: 2, label: '2 Hari' },
  { days: 3, label: '3 Hari', isPopular: true },
  { days: 5, label: '5 Hari' },
  { days: 7, label: '1 Minggu' },
  { days: 14, label: '2 Minggu' },
  { days: 30, label: '1 Bulan' },
];

export default function RentalBookingModal({ product, isOpen, onClose, onSuccess }) {
  const todayStr = getISODate();
  const tomorrowStr = addDaysToDate(todayStr, 1);

  const [startDate, setStartDate] = useState(todayStr);
  const [endDate, setEndDate] = useState(tomorrowStr);
  const [startTime, setStartTime] = useState('09:00');
  const [endTime, setEndTime] = useState('17:00');
  const [activeDuration, setActiveDuration] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { addItem } = useCart();
  const { showToast } = useToast();
  const navigate = useNavigate();

  if (!isOpen || !product) return null;

  const pricePerDay = product.sale_price || product.regular_price || 0;
  const rentalDays = getRentalDays(startDate, endDate);
  const rentalTotal = pricePerDay * rentalDays;
  const image = (product.images?.[0] || '').split(',')[0].trim() ||
    `https://picsum.photos/seed/${product.sku || product.id}/200/200`;

  // Quick preset duration click
  function handleSelectDuration(days) {
    setActiveDuration(days);
    const newEnd = addDaysToDate(startDate, days);
    setEndDate(newEnd);
  }

  // Quick start date click
  function handleSelectQuickStart(offset) {
    const newStart = addDaysToDate(todayStr, offset);
    setStartDate(newStart);
    // keep current duration
    const newEnd = addDaysToDate(newStart, activeDuration || 1);
    setEndDate(newEnd);
  }

  // Manual start date change
  function handleManualStartDate(val) {
    setStartDate(val);
    if (val >= endDate) {
      const newEnd = addDaysToDate(val, activeDuration || 1);
      setEndDate(newEnd);
    } else {
      setActiveDuration(getRentalDays(val, endDate));
    }
  }

  // Manual end date change
  function handleManualEndDate(val) {
    setEndDate(val);
    setActiveDuration(getRentalDays(startDate, val));
  }

  function createCartPayload() {
    const formattedStart = `${formatDateDisplay(startDate)} ${startTime}`;
    const formattedEnd = `${formatDateDisplay(endDate)} ${endTime}`;
    const rentalLabel = `(Sewa ${rentalDays} Hari: ${startDate} s/d ${endDate})`;

    return {
      ...product,
      cartKey: `${product.id}-rental-${startDate}-${endDate}-${startTime}-${endTime}`,
      name: `${product.name} ${rentalLabel}`,
      regular_price: rentalTotal,
      sale_price: null,
      rentalInfo: {
        isRental: true,
        days: rentalDays,
        startDate,
        endDate,
        startTime,
        endTime,
        formattedStart,
        formattedEnd,
        pricePerDay,
        rentalTotal,
      }
    };
  }

  function handleAddToCart() {
    setIsSubmitting(true);
    const rentalItem = createCartPayload();
    addItem(rentalItem, 1);

    showToast({
      type: 'cart',
      title: `Booking Sewa ${rentalDays} Hari Ditambahkan!`,
      message: `${product.name} (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`,
      link: '/keranjang',
      linkText: 'Lihat Keranjang',
    });

    onSuccess?.();
    setIsSubmitting(false);
    onClose();
  }

  function handleDirectCheckout() {
    const rentalItem = createCartPayload();
    addItem(rentalItem, 1);
    onClose();
    navigate('/checkout');
  }

  return (
    <div
      className="fixed inset-0 z-[70] flex items-end sm:items-center justify-center sm:p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
      aria-labelledby="rental-modal-title"
    >
      <div
        className="w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col max-h-[90dvh] sm:max-h-[88vh] overflow-hidden animate-in fade-in slide-in-from-bottom-6 duration-200 pb-[env(safe-area-inset-bottom,0px)] sm:pb-0"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Mobile drag handle pill */}
        <div className="w-12 h-1.5 bg-gray-300 rounded-full mx-auto mt-2.5 mb-1 sm:hidden" />

        {/* Modal Header */}
        <div className="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between bg-orange-50/50">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0">
              <span className="material-symbols-outlined text-[20px]">event_available</span>
            </div>
            <div>
              <h2 id="rental-modal-title" className="text-[15px] sm:text-[16px] font-bold text-gray-900 leading-tight">
                Atur Jadwal Sewa
              </h2>
              <p className="text-[11px] sm:text-[12px] text-gray-500">
                Pilih tanggal & jam sewa (tinggal klik)
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
              <span className="text-[10px] font-mono text-gray-400 uppercase block">{product.sku || 'SKU SEWA'}</span>
              <h4 className="text-[12px] sm:text-[13px] font-semibold text-gray-800 line-clamp-1">{product.name}</h4>
              <div className="flex items-center gap-2 mt-0.5">
                <span className="text-[13px] font-bold text-orange-600">{formatPrice(pricePerDay)}</span>
                <span className="text-[10px] text-gray-500 font-medium bg-orange-100/80 px-1.5 py-0.2 rounded">/ hari</span>
                {product.stock > 0 ? (
                  <span className="text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded font-medium ml-auto">
                    Ready {product.stock} Unit
                  </span>
                ) : (
                  <span className="text-[10px] text-red-600 bg-red-50 px-1.5 py-0.2 rounded font-medium ml-auto">
                    Stok Habis
                  </span>
                )}
              </div>
            </div>
          </div>

          {/* Preset Durasi Sewa (Tinggal Klik-Klik!) */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <label className="text-[12px] font-bold text-gray-700 flex items-center gap-1.5">
                <span className="material-symbols-outlined text-[16px] text-orange-500">timer</span>
                Pilih Durasi Sewa
              </label>
              <span className="text-[11px] font-semibold text-orange-600">
                {rentalDays} Hari Terpilih
              </span>
            </div>
            <div className="grid grid-cols-4 sm:grid-cols-7 gap-1.5">
              {DURATION_PRESETS.map((p) => {
                const isSelected = activeDuration === p.days;
                return (
                  <button
                    key={p.days}
                    type="button"
                    onClick={() => handleSelectDuration(p.days)}
                    className={`py-2 px-1 rounded-xl text-center text-[11px] font-semibold transition-all relative flex flex-col items-center justify-center border
                      ${isSelected
                        ? 'bg-orange-500 text-white border-orange-500 shadow-sm shadow-orange-500/30 scale-[1.02]'
                        : 'bg-white hover:bg-orange-50/50 text-gray-700 border-gray-200'
                      }`}
                  >
                    {p.isPopular && !isSelected && (
                      <span className="absolute -top-1.5 text-[8px] bg-amber-400 text-amber-950 px-1 rounded-full font-bold">
                        Populer
                      </span>
                    )}
                    <span>{p.label}</span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Section: Tanggal & Jam Mulai */}
          <div className="bg-slate-50/70 p-3 rounded-2xl border border-gray-200/80 space-y-2.5">
            <div className="flex items-center justify-between">
              <span className="text-[12px] font-bold text-gray-800 flex items-center gap-1.5">
                <span className="material-symbols-outlined text-[16px] text-emerald-600">calendar_today</span>
                1. Mulai Sewa (Ambil / Kirim)
              </span>
              <span className="text-[11px] text-emerald-700 font-semibold">
                {formatDateDisplay(startDate)}
              </span>
            </div>

            {/* Quick chips start date */}
            <div className="flex items-center gap-1.5">
              <span className="text-[10px] text-gray-400 font-medium uppercase mr-1">Cepat:</span>
              {QUICK_START_OPTIONS.map((opt) => {
                const targetDate = addDaysToDate(todayStr, opt.offsetDays);
                const isSelected = startDate === targetDate;
                return (
                  <button
                    key={opt.label}
                    type="button"
                    onClick={() => handleSelectQuickStart(opt.offsetDays)}
                    className={`px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors border
                      ${isSelected
                        ? 'bg-emerald-600 text-white border-emerald-600'
                        : 'bg-white text-gray-700 border-gray-200 hover:border-emerald-300'
                      }`}
                  >
                    {opt.label}
                  </button>
                );
              })}
            </div>

            {/* Manual input date & time */}
            <div className="grid grid-cols-2 gap-2 pt-1">
              <div>
                <label className="text-[10px] text-gray-500 font-semibold block mb-1">Tanggal Mulai</label>
                <input
                  type="date"
                  min={todayStr}
                  value={startDate}
                  onChange={(e) => handleManualStartDate(e.target.value)}
                  className="w-full bg-white border border-gray-300 rounded-xl px-2.5 py-1.5 text-[12px] text-gray-800 focus:outline-none focus:border-orange-500 font-medium"
                />
              </div>
              <div>
                <label className="text-[10px] text-gray-500 font-semibold block mb-1">Jam Ambil</label>
                <div className="flex items-center gap-1">
                  <input
                    type="time"
                    value={startTime}
                    onChange={(e) => setStartTime(e.target.value)}
                    className="w-full bg-white border border-gray-300 rounded-xl px-2.5 py-1.5 text-[12px] text-gray-800 focus:outline-none focus:border-orange-500 font-medium"
                  />
                </div>
              </div>
            </div>

            {/* Quick chips Jam Mulai */}
            <div>
              <span className="text-[10px] text-gray-400 block mb-1">Pilihan Jam Cepat:</span>
              <div className="flex flex-wrap gap-1">
                {QUICK_TIMES.map((t) => (
                  <button
                    key={t}
                    type="button"
                    onClick={() => setStartTime(t)}
                    className={`px-2 py-0.5 rounded text-[10px] font-semibold transition-colors
                      ${startTime === t
                        ? 'bg-emerald-600 text-white'
                        : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-400'
                      }`}
                  >
                    {t}
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Section: Tanggal & Jam Selesai */}
          <div className="bg-slate-50/70 p-3 rounded-2xl border border-gray-200/80 space-y-2.5">
            <div className="flex items-center justify-between">
              <span className="text-[12px] font-bold text-gray-800 flex items-center gap-1.5">
                <span className="material-symbols-outlined text-[16px] text-rose-500">event_repeat</span>
                2. Selesai Sewa (Pengembalian)
              </span>
              <span className="text-[11px] text-rose-700 font-semibold">
                {formatDateDisplay(endDate)}
              </span>
            </div>

            {/* Manual input date & time */}
            <div className="grid grid-cols-2 gap-2">
              <div>
                <label className="text-[10px] text-gray-500 font-semibold block mb-1">Tanggal Pengembalian</label>
                <input
                  type="date"
                  min={startDate}
                  value={endDate}
                  onChange={(e) => handleManualEndDate(e.target.value)}
                  className="w-full bg-white border border-gray-300 rounded-xl px-2.5 py-1.5 text-[12px] text-gray-800 focus:outline-none focus:border-orange-500 font-medium"
                />
              </div>
              <div>
                <label className="text-[10px] text-gray-500 font-semibold block mb-1">Jam Kembali</label>
                <input
                  type="time"
                  value={endTime}
                  onChange={(e) => setEndTime(e.target.value)}
                  className="w-full bg-white border border-gray-300 rounded-xl px-2.5 py-1.5 text-[12px] text-gray-800 focus:outline-none focus:border-orange-500 font-medium"
                />
              </div>
            </div>

            {/* Quick chips Jam Selesai */}
            <div>
              <span className="text-[10px] text-gray-400 block mb-1">Pilihan Jam Cepat:</span>
              <div className="flex flex-wrap gap-1">
                {QUICK_TIMES.map((t) => (
                  <button
                    key={t}
                    type="button"
                    onClick={() => setEndTime(t)}
                    className={`px-2 py-0.5 rounded text-[10px] font-semibold transition-colors
                      ${endTime === t
                        ? 'bg-rose-600 text-white'
                        : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-400'
                      }`}
                  >
                    {t}
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Ringkasan Biaya Sewa */}
          <div className="p-3.5 rounded-2xl bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-200">
            <div className="flex items-center justify-between text-[11px] text-orange-950/80 mb-1">
              <span>Rincian Biaya Sewa ({rentalDays} Hari):</span>
              <span className="font-semibold">{rentalDays} hari × {formatPrice(pricePerDay)}</span>
            </div>
            <div className="flex items-baseline justify-between pt-1 border-t border-orange-200/60">
              <span className="text-[12px] font-bold text-gray-800">Total Biaya Sewa</span>
              <div className="text-right">
                <span className="text-[17px] font-black text-orange-600 leading-none">
                  {formatPrice(rentalTotal)}
                </span>
                <span className="text-[10px] text-gray-500 block">+PPN 11%</span>
              </div>
            </div>
            <div className="mt-2 flex items-center gap-1.5 text-[10px] text-orange-800 bg-white/70 px-2 py-1 rounded-lg">
              <span className="material-symbols-outlined text-[13px] text-orange-600">verified</span>
              <span>Unit siap dikirim/diambil sesuai jadwal. CS akan konfirmasi via WA.</span>
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
            disabled={isSubmitting || product.stock === 0}
            className="flex-1 h-10 rounded-xl bg-orange-600 hover:bg-orange-700 active:scale-98 text-white text-[12px] font-bold flex items-center justify-center gap-1.5 transition-all shadow-md shadow-orange-600/20 disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            <span className="material-symbols-outlined text-[17px]">add_shopping_cart</span>
            <span>+ Masukkan Keranjang</span>
          </button>

          <button
            type="button"
            onClick={handleDirectCheckout}
            disabled={product.stock === 0}
            className="h-10 px-3 sm:px-4 rounded-xl bg-primary hover:bg-primary/90 text-white text-[12px] font-bold flex items-center justify-center gap-1 transition-colors disabled:bg-gray-300"
          >
            <span>Checkout</span>
            <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>
  );
}
