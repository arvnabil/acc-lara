/**
 * src/pages/CartPage.jsx
 * Halaman Keranjang Belanja — Persis sesuai Static Template & Desain UI
 */
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useCart } from '../context/CartContext';
import { formatPrice } from '../services/productService';

const PROMO_CODES = {
  'SAVE10': { code: 'SAVE10', label: 'Diskon 10%', type: 'percent', value: 10 },
  'ACCOMM25': { code: 'ACCOMM25', label: 'Diskon 25%', type: 'percent', value: 25 },
  'GRATIS50K': { code: 'GRATIS50K', label: 'Gratis Ongkir + Diskon 50rb', type: 'fixed', value: 400000 },
  'TECH100K': { code: 'TECH100K', label: 'Diskon Rp 100.000', type: 'fixed', value: 100000 },
  'NEWMEMBER': { code: 'NEWMEMBER', label: 'Member Baru 15% Off', type: 'percent', value: 15 },
};

const SHIPPING_BASE = 350000;

export default function CartPage() {
  const { items, removeItem, updateQuantity, total, itemCount, appliedPromo, setAppliedPromo } = useCart();
  const [promoInput, setPromoInput] = useState(appliedPromo?.code || '');
  const [promoMessage, setPromoMessage] = useState(null);

  // Apply promo calculation
  const applyPromoCode = (codeToApply) => {
    const raw = (codeToApply || promoInput).toUpperCase().trim();
    if (!raw) {
      setPromoMessage({ type: 'error', text: 'Masukkan kode promo terlebih dahulu.' });
      return;
    }

    const promo = PROMO_CODES[raw];
    if (!promo) {
      setPromoMessage({ type: 'error', text: '❌ Kode promo tidak valid atau sudah kedaluwarsa.' });
      return;
    }

    setAppliedPromo(promo);
    setPromoInput(promo.code);
    setPromoMessage({
      type: 'success',
      text: `✅ Kode ${promo.code} berhasil diterapkan! (${promo.label})`
    });
  };

  const removePromo = () => {
    setAppliedPromo(null);
    setPromoInput('');
    setPromoMessage(null);
  };

  // Discount calculation
  let discountAmount = 0;
  if (appliedPromo) {
    if (appliedPromo.type === 'percent') {
      discountAmount = Math.round((total * appliedPromo.value) / 100);
    } else {
      discountAmount = Math.min(appliedPromo.value, total + SHIPPING_BASE);
    }
  }

  const grandTotal = Math.max(0, total + SHIPPING_BASE - discountAmount);
  const earnedPoints = Math.floor(grandTotal / 10000);

  if (items.length === 0) {
    return (
      <div className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-2xl w-full">
        <div className="bg-card-bg border border-border-subtle rounded-xl p-12 lg:p-16 text-center max-w-[680px] mx-auto shadow-sm">
          <span className="material-symbols-outlined text-[64px] text-text-secondary mb-4 block">shopping_cart</span>
          <h2 className="font-title-card text-[22px] font-bold text-text-primary mb-2">Keranjang Belanja Kosong</h2>
          <p className="font-body-md text-[14px] text-text-secondary max-w-[420px] mx-auto mb-6">
            Yuk, temukan produk IT & Audio Visual terbaik untuk kebutuhan proyek dan kantor Anda!
          </p>
          <Link
            to="/katalog"
            className="bg-primary hover:bg-primary-container text-on-primary px-8 py-3.5 rounded-lg font-label-sm text-[14px] inline-flex items-center gap-2 transition-colors shadow-sm"
          >
            <span className="material-symbols-outlined text-[18px]">storefront</span>
            <span>Mulai Belanja Sekarang</span>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl w-full">
      {/* Header */}
      <div className="mb-space-xl">
        <h1 className="font-headline-hero-mobile lg:font-headline-hero text-text-primary font-bold">Keranjang Belanja</h1>
        <p className="font-body-md text-[14px] text-text-secondary mt-1">Periksa kembali item belanjaan Anda sebelum checkout.</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-space-xl">
        {/* Cart Items List (8 cols) */}
        <div className="lg:col-span-8 flex flex-col gap-space-md">
          {items.map((item, idx) => {
            const itemSubtotal = item.price * item.quantity;
            const imgSrc = item.image || `https://picsum.photos/seed/${item.sku || idx}/200/200`;

            return (
              <div
                key={item.key}
                className="bg-card-bg border border-border-subtle rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm hover:shadow-md transition-shadow"
              >
                {/* Left: Thumbnail & Info */}
                <div className="flex items-center gap-4 min-w-0">
                  <div className="w-20 h-20 rounded-lg overflow-hidden bg-surface border border-border-subtle shrink-0">
                    <img
                      src={imgSrc}
                      alt={item.name}
                      className="w-full h-full object-cover"
                      onError={e => {
                        e.target.src = `https://picsum.photos/seed/${idx}/200/200`;
                      }}
                    />
                  </div>
                  <div className="min-w-0">
                    <span className="font-sku text-[11px] text-text-secondary uppercase tracking-wider block">
                      {item.sku || 'LOG-960-001308'}
                    </span>
                    <Link
                      to={`/produk/${item.slug || item.product_id}`}
                      className="font-title-card text-[15px] font-bold text-text-primary mt-0.5 line-clamp-2 hover:text-primary transition-colors block"
                    >
                      {item.name}
                    </Link>
                    {item.rentalInfo && (
                      <div className="mt-1 inline-flex items-center gap-1.5 bg-orange-50 border border-orange-200 text-orange-800 text-[11px] font-semibold px-2 py-0.5 rounded-md">
                        <span className="material-symbols-outlined text-[13px] text-orange-600">event_available</span>
                        <span>{item.rentalInfo.days} Hari ({item.rentalInfo.startTime} - {item.rentalInfo.endTime})</span>
                      </div>
                    )}
                    <div className="font-price text-[15px] text-primary font-bold mt-1">
                      {formatPrice(item.price)}
                    </div>
                    <div className="text-[12px] text-text-secondary mt-1">
                      Subtotal: <span className="font-semibold text-text-primary">{formatPrice(itemSubtotal)}</span>
                    </div>
                  </div>
                </div>

                {/* Right: Quantity Controls & Delete */}
                <div className="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-border-subtle">
                  {/* Quantity Counter */}
                  <div className="flex items-center border border-border-subtle rounded-lg bg-surface">
                    <button
                      onClick={() => updateQuantity(item.key, item.quantity - 1)}
                      aria-label="Kurangi jumlah"
                      className="px-3 py-1.5 sm:py-2 text-text-primary font-bold hover:bg-surface-container rounded-l-lg transition-colors"
                    >
                      −
                    </button>
                    <span className="px-3 font-label-sm text-[14px] font-bold min-w-[36px] text-center">
                      {item.quantity}
                    </span>
                    <button
                      onClick={() => updateQuantity(item.key, item.quantity + 1)}
                      aria-label="Tambah jumlah"
                      className="px-3 py-1.5 sm:py-2 text-text-primary font-bold hover:bg-surface-container rounded-r-lg transition-colors"
                    >
                      +
                    </button>
                  </div>

                  {/* Delete Button */}
                  <button
                    onClick={() => removeItem(item.key)}
                    aria-label={`Hapus ${item.name}`}
                    className="flex items-center gap-1 text-[12px] text-text-secondary hover:text-red-500 transition-colors py-1 px-1.5 rounded"
                  >
                    <span className="material-symbols-outlined text-[16px]">delete</span>
                    <span>Hapus</span>
                  </button>
                </div>
              </div>
            );
          })}
        </div>

        {/* Sidebar Summary (4 cols) */}
        <div className="lg:col-span-4 flex flex-col gap-space-md">
          {/* Card 1: Promo Code */}
          <div className="bg-card-bg border border-border-subtle rounded-xl p-space-lg shadow-sm">
            <h3 className="font-title-card text-[14px] font-bold text-text-primary mb-3 flex items-center gap-2">
              <span className="material-symbols-outlined text-[18px] text-primary">local_offer</span>
              Kode Promo / Voucher
            </h3>
            <div className="flex gap-2">
              <input
                type="text"
                placeholder="Masukkan kode promo..."
                value={promoInput}
                onChange={e => setPromoInput(e.target.value)}
                onKeyDown={e => { if (e.key === 'Enter') applyPromoCode(); }}
                className="flex-1 bg-surface border border-border-subtle rounded-lg px-3 py-2.5 font-label-sm text-[13px] text-text-primary focus:outline-none focus:border-primary uppercase"
              />
              <button
                onClick={() => applyPromoCode()}
                className="bg-primary text-on-primary font-label-sm text-[13px] font-bold px-4 py-2.5 rounded-lg hover:bg-primary-container transition-colors shrink-0"
              >
                Pakai
              </button>
            </div>

            {/* Promo Result Message */}
            {promoMessage && (
              <div className={`mt-2 text-[12px] font-medium ${promoMessage.type === 'success' ? 'text-green-600' : 'text-red-500'}`}>
                {promoMessage.text}
              </div>
            )}

            {/* Quick Promo Chips */}
            <div className="mt-3 flex flex-wrap gap-2">
              {['SAVE10', 'ACCOMM25', 'GRATIS50K', 'TECH100K'].map(chip => (
                <button
                  key={chip}
                  onClick={() => {
                    setPromoInput(chip);
                    applyPromoCode(chip);
                  }}
                  className={`text-[11px] border px-2.5 py-1 rounded-full transition-colors ${
                    appliedPromo?.code === chip
                      ? 'border-primary bg-primary text-on-primary font-bold'
                      : 'border-primary text-primary hover:bg-primary hover:text-on-primary'
                  }`}
                >
                  {chip}
                </button>
              ))}
            </div>

            {appliedPromo && (
              <div className="mt-2.5 pt-2 border-t border-border-subtle flex items-center justify-between text-[11px] text-text-secondary">
                <span>Promo aktif: <strong className="text-primary">{appliedPromo.code}</strong></span>
                <button onClick={removePromo} className="text-red-500 hover:underline">Hapus</button>
              </div>
            )}
          </div>

          {/* Card 2: Ringkasan Belanja */}
          <div className="bg-card-bg border border-border-subtle rounded-xl p-space-xl shadow-sm">
            <h3 className="font-title-card text-[16px] font-bold text-text-primary mb-space-lg pb-2 border-b border-border-subtle">
              Ringkasan Belanja
            </h3>
            <div className="flex flex-col gap-3 font-body-md text-[14px] text-text-secondary mb-space-lg">
              <div className="flex justify-between">
                <span>{itemCount} Item</span>
                <span className="font-semibold text-text-primary">{formatPrice(total)}</span>
              </div>
              <div className="flex justify-between">
                <span>Ongkos Kirim</span>
                <span className="font-semibold text-text-primary">{formatPrice(SHIPPING_BASE)}</span>
              </div>

              {discountAmount > 0 && (
                <div className="flex justify-between text-green-600 font-bold">
                  <span>Diskon Promo ({appliedPromo?.code})</span>
                  <span>- {formatPrice(discountAmount)}</span>
                </div>
              )}

              <div className="border-t border-border-subtle pt-3 flex justify-between items-baseline">
                <span className="font-bold text-text-primary text-[15px]">Total Belanja</span>
                <span className="font-price text-[20px] text-primary font-bold">
                  {formatPrice(grandTotal)}
                </span>
              </div>

              {earnedPoints > 0 && (
                <div className="mt-1 bg-amber-50 border border-amber-200/80 rounded-lg p-2.5 flex items-center justify-between gap-2 text-[12px]">
                  <div className="flex items-center gap-1.5 text-amber-900 font-medium">
                    <span className="material-symbols-outlined text-[17px] text-amber-600 animate-pulse">stars</span>
                    <span>Potensi Poin Reward:</span>
                  </div>
                  <span className="font-bold text-amber-800 font-mono text-[13px]">+{earnedPoints.toLocaleString('id-ID')} Poin</span>
                </div>
              )}
            </div>

            <div className="flex flex-col gap-space-md">
              <Link
                to="/checkout"
                className="w-full bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm py-3.5 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-sm font-semibold"
              >
                <span>Lanjut ke Checkout</span>
                <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
              </Link>
              <Link
                to="/katalog"
                className="w-full bg-surface hover:bg-surface-container text-text-primary font-label-sm text-label-sm py-3 rounded-lg flex items-center justify-center gap-2 transition-colors font-medium border border-border-subtle"
              >
                <span className="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Lanjut Belanja</span>
              </Link>
            </div>
          </div>

          {/* Card 3: Trust Badges */}
          <div className="bg-card-bg border border-border-subtle rounded-xl p-space-lg shadow-sm">
            <div className="flex flex-col gap-2.5 text-[12px] text-text-secondary">
              <div className="flex items-center gap-2.5">
                <span className="material-symbols-outlined text-[18px] text-primary">verified_user</span>
                <span>Produk Original & Bergaransi Resmi</span>
              </div>
              <div className="flex items-center gap-2.5">
                <span className="material-symbols-outlined text-[18px] text-primary">local_shipping</span>
                <span>Pengiriman Aman ke Seluruh Indonesia</span>
              </div>
              <div className="flex items-center gap-2.5">
                <span className="material-symbols-outlined text-[18px] text-primary">support_agent</span>
                <span>CS Siap Bantu 08:30–17:30 WIB</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
