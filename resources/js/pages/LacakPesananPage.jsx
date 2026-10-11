/**
 * src/pages/LacakPesananPage.jsx
 * Halaman lacak pesanan — simulasi frontend.
 */
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const STATUS_STEPS = ['Pesanan Diterima', 'Diproses', 'Dikemas', 'Dalam Pengiriman', 'Selesai'];

function StatusBar({ status }) {
  const idx = STATUS_STEPS.findIndex(s => s === status);
  const activeIdx = idx < 0 ? 0 : idx;
  return (
    <div className="relative mt-6 mb-2">
      {/* Line */}
      <div className="absolute top-4 left-4 right-4 h-0.5 bg-gray-200 z-0" />
      <div
        className="absolute top-4 left-4 h-0.5 bg-primary z-0 transition-all"
        style={{ width: `${(activeIdx / (STATUS_STEPS.length - 1)) * (100 - 8)}%` }}
      />
      <div className="flex justify-between relative z-10">
        {STATUS_STEPS.map((step, i) => (
          <div key={step} className="flex flex-col items-center gap-1 flex-1 first:items-start last:items-end">
            <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors
              ${i <= activeIdx ? 'bg-primary border-primary text-white' : 'bg-white border-gray-300 text-gray-400'}`}>
              {i < activeIdx
                ? <span className="material-symbols-outlined text-[16px]">check</span>
                : i + 1
              }
            </div>
            <span className={`text-[10px] font-medium text-center leading-tight max-w-[60px] ${i <= activeIdx ? 'text-primary' : 'text-gray-400'}`}>
              {step}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

export default function LacakPesananPage() {
  const { user } = useAuth();
  const [orderInput, setOrderInput] = useState('');
  const [searched, setSearched] = useState(false);
  const [foundOrder, setFoundOrder] = useState(null);

  function handleSearch(e) {
    e.preventDefault();
    setSearched(true);
    // Check against user's orders if logged in
    if (user && user.orders) {
      const found = user.orders.find(o =>
        o.id.toLowerCase() === orderInput.trim().toLowerCase()
      );
      setFoundOrder(found || null);
    } else {
      setFoundOrder(null);
    }
  }

  const demoOrders = user?.orders || [];

  return (
    <main className="max-w-[900px] mx-auto px-4 sm:px-6 py-8 min-h-[70vh]">
      {/* Page header */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-text-primary">Lacak Pesanan</h1>
        <p className="text-text-secondary text-sm mt-1">Masukkan nomor pesanan Anda untuk melihat status pengiriman terkini.</p>
      </div>

      {/* Search box */}
      <div className="bg-white border border-border-subtle rounded-2xl p-6 shadow-sm mb-8">
        <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-3">
          <div className="flex-1">
            <label htmlFor="order-id" className="block text-xs font-semibold text-text-secondary mb-1.5 uppercase tracking-wider">
              Nomor Pesanan / Order ID
            </label>
            <input
              id="order-id"
              type="text"
              value={orderInput}
              onChange={e => setOrderInput(e.target.value)}
              placeholder="Contoh: ACC-98214"
              className="w-full border border-border-subtle rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary bg-gray-50"
            />
          </div>
          <div className="sm:self-end">
            <button
              type="submit"
              className="w-full sm:w-auto bg-primary hover:bg-primary/90 text-white font-semibold px-6 py-3 rounded-lg text-sm transition-colors flex items-center gap-2"
            >
              <span className="material-symbols-outlined text-[18px]">search</span>
              Lacak Sekarang
            </button>
          </div>
        </form>
      </div>

      {/* Search Result */}
      {searched && (
        foundOrder ? (
          <div className="bg-white border border-border-subtle rounded-2xl p-6 shadow-sm mb-6">
            <div className="flex flex-wrap items-center justify-between gap-3 mb-4">
              <div>
                <span className="text-xs font-bold text-primary uppercase tracking-wider">{foundOrder.id}</span>
                <h3 className="font-bold text-base text-text-primary mt-0.5">{foundOrder.title}</h3>
                <p className="text-xs text-text-secondary">{foundOrder.date} • Rp {foundOrder.total.toLocaleString('id-ID')}</p>
              </div>
              <span className={`text-sm font-semibold px-3 py-1.5 rounded-lg ${
                foundOrder.status === 'Dalam Pengiriman' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'
              }`}>
                {foundOrder.status}
              </span>
            </div>
            <StatusBar status={foundOrder.status} />

            {/* Rincian Item */}
            <div className="mt-6 border-t border-border-subtle pt-4">
              <h4 className="text-xs font-semibold text-text-secondary uppercase mb-3">Detail Item</h4>
              <ul className="space-y-2">
                {foundOrder.items.map((item, i) => (
                  <li key={i} className="flex justify-between text-sm">
                    <span className="text-text-primary">{item.name} <span className="text-text-secondary">({item.qty}x)</span></span>
                    <span className="font-semibold text-text-primary">Rp {(item.price * item.qty).toLocaleString('id-ID')}</span>
                  </li>
                ))}
              </ul>
            </div>

            {/* Info pengiriman mock */}
            <div className="mt-4 bg-blue-50 rounded-xl p-4 text-sm text-blue-800">
              <div className="flex items-start gap-2">
                <span className="material-symbols-outlined text-[18px] mt-0.5 text-blue-600">local_shipping</span>
                <div>
                  <div className="font-semibold">JNE Express — JNEXXX1234567890</div>
                  <div className="text-xs text-blue-700 mt-0.5">Estimasi tiba: 1–3 hari kerja. Hubungi CS untuk update real-time.</div>
                </div>
              </div>
            </div>
          </div>
        ) : (
          <div className="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center mb-6">
            <span className="material-symbols-outlined text-[40px] text-amber-500 mb-2">search_off</span>
            <h3 className="font-bold text-text-primary">Pesanan Tidak Ditemukan</h3>
            <p className="text-sm text-text-secondary mt-1">Nomor pesanan "<strong>{orderInput}</strong>" tidak ditemukan. Pastikan nomor pesanan Anda benar.</p>
            <p className="text-xs text-text-secondary mt-2">Butuh bantuan? <Link to="/bantuan" className="text-primary underline">Hubungi CS kami</Link></p>
          </div>
        )
      )}

      {/* My Orders (if logged in) */}
      {user && demoOrders.length > 0 && (
        <div className="bg-white border border-border-subtle rounded-2xl p-6 shadow-sm">
          <h3 className="font-bold text-base text-text-primary mb-4 flex items-center gap-2">
            <span className="material-symbols-outlined text-primary">receipt_long</span>
            Pesanan Saya
          </h3>
          <div className="space-y-3">
            {demoOrders.map(order => (
              <button
                key={order.id}
                onClick={() => { setOrderInput(order.id); setFoundOrder(order); setSearched(true); }}
                className="w-full text-left border border-border-subtle rounded-xl px-4 py-3 hover:border-primary/50 hover:bg-blue-50/50 transition-colors flex justify-between items-center"
              >
                <div>
                  <span className="text-xs font-bold text-primary">{order.id}</span>
                  <div className="text-sm font-semibold text-text-primary line-clamp-1">{order.title}</div>
                  <div className="text-xs text-text-secondary">{order.date}</div>
                </div>
                <span className={`text-xs font-semibold px-2.5 py-1 rounded-md whitespace-nowrap ${
                  order.status === 'Dalam Pengiriman' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'
                }`}>{order.status}</span>
              </button>
            ))}
          </div>
        </div>
      )}

      {!user && (
        <div className="mt-6 bg-surface rounded-xl p-4 text-center text-sm text-text-secondary">
          <Link to="/login" className="text-primary font-semibold underline">Masuk</Link> untuk melihat riwayat pesanan Anda secara otomatis.
        </div>
      )}
    </main>
  );
}
