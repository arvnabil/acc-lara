export default function OrderDetailModal({ isOpen, onClose, order, onOpenShipping, onOpenInvoice }) {
  if (!isOpen || !order) return null;

  const trackingNumber = order.trackingNumber || order.biteshipWaybillId || 'Resi belum tersedia';
  const address = [order.streetAddress, order.city, order.province, order.postalCode]
    .filter(Boolean)
    .join(', ');
  const pointsLabel = order.pointsSkippedReason
    ? `Tidak diberikan (${order.pointsSkippedReason.replaceAll('_', ' ')})`
    : order.pointsAwardedAt
      ? `${order.pointsEarned >= 0 ? '+' : ''}${order.pointsEarned} poin diterima`
      : 'Sedang diproses';
  const pointTypeLabels = {
    earn_base: 'Poin belanja',
    earn_bonus: 'Bonus tier member',
    earn_review: 'Bonus ulasan',
    reversal: 'Penyesuaian retur',
    adjustment: 'Penyesuaian admin',
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
      <div className="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div className="flex items-center justify-between border-b border-border-subtle px-6 py-4">
          <div>
            <p className="text-xs font-bold uppercase tracking-wider text-primary">Rincian Pesanan</p>
            <h2 className="mt-1 text-lg font-bold text-text-primary">{order.number || order.id}</h2>
          </div>
          <button type="button" onClick={onClose} className="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Tutup">
            <span className="material-symbols-outlined">close</span>
          </button>
        </div>

        <div className="space-y-4 p-6">
          <div className="rounded-xl bg-emerald-50 p-4">
            <div className="flex items-center justify-between gap-3">
              <div>
                <p className="text-sm font-bold text-emerald-800">{order.status || 'Status pesanan'}</p>
                <p className="mt-1 text-xs text-emerald-700">{order.date || '-'}</p>
              </div>
              <span className="material-symbols-outlined text-emerald-600">check_circle</span>
            </div>
          </div>

          <button
            type="button"
            onClick={() => onOpenShipping(order)}
            className="flex w-full items-center justify-between rounded-xl border border-border-subtle p-4 text-left transition-colors hover:border-primary hover:bg-blue-50"
          >
            <span className="flex items-center gap-3">
              <span className="material-symbols-outlined text-primary">local_shipping</span>
              <span>
                <span className="block text-sm font-bold text-text-primary">Info Pengiriman</span>
                <span className="mt-1 block text-xs text-text-secondary">{order.shippingLabel || 'Kurir belum ditentukan'}: {trackingNumber}</span>
              </span>
            </span>
            <span className="material-symbols-outlined text-text-secondary">chevron_right</span>
          </button>

          <section className="rounded-xl border border-border-subtle p-4">
            <h3 className="mb-3 text-sm font-bold text-text-primary">Alamat Pengiriman</h3>
            <p className="font-semibold text-text-primary">{order.recipientName || '-'}</p>
            <p className="mt-1 text-sm text-text-secondary">{address || 'Alamat belum tersedia'}</p>
            {order.phone && <p className="mt-1 text-sm text-text-secondary">{order.phone}</p>}
          </section>

          <section className="rounded-xl border border-border-subtle p-4">
            <h3 className="mb-3 text-sm font-bold text-text-primary">Rincian Pembayaran</h3>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between gap-4"><span className="text-text-secondary">Subtotal produk</span><span>Rp {(order.goodsSubtotal || 0).toLocaleString('id-ID')}</span></div>
              {(order.discountOnGoods || 0) > 0 && <div className="flex justify-between gap-4 text-emerald-700"><span>Diskon produk</span><span>- Rp {order.discountOnGoods.toLocaleString('id-ID')}</span></div>}
              {(order.ppn || 0) > 0 && <div className="flex justify-between gap-4"><span className="text-text-secondary">Pajak</span><span>Rp {order.ppn.toLocaleString('id-ID')}</span></div>}
              {(order.shippingCost || 0) > 0 && <div className="flex justify-between gap-4"><span className="text-text-secondary">Ongkir</span><span>Rp {order.shippingCost.toLocaleString('id-ID')}</span></div>}
              {(order.adminFee || 0) > 0 && <div className="flex justify-between gap-4"><span className="text-text-secondary">Biaya layanan</span><span>Rp {order.adminFee.toLocaleString('id-ID')}</span></div>}
            </div>
          </section>

          <section className="rounded-xl border border-border-subtle p-4">
            <div className="flex items-center justify-between gap-3">
              <h3 className="text-sm font-bold text-text-primary">Riwayat Poin</h3>
              <span className="text-sm font-bold text-primary">{pointsLabel}</span>
            </div>
            {(order.pointsHistory || []).length > 0 ? (
              <div className="mt-3 space-y-2">
                {order.pointsHistory.map((entry, index) => (
                  <div key={`${entry.type}-${entry.earnedAt}-${index}`} className="flex items-center justify-between gap-3 text-xs">
                    <span className="text-text-secondary">{entry.notes || pointTypeLabels[entry.type] || entry.type.replaceAll('_', ' ')}</span>
                    <span className={`font-bold ${entry.points >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>{entry.points >= 0 ? '+' : ''}{entry.points} poin</span>
                  </div>
                ))}
              </div>
            ) : (
              <p className="mt-2 text-xs text-text-secondary">Poin akan tercatat setelah proses pemberian poin selesai.</p>
            )}
          </section>

          <section className="rounded-xl border border-border-subtle p-4">
            <h3 className="mb-3 text-sm font-bold text-text-primary">Produk</h3>
            <div className="divide-y divide-border-subtle">
              {(order.items || []).map((item, index) => (
                <div key={item.id || `${item.name}-${index}`} className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-text-primary">{item.name}</p>
                    <p className="mt-1 text-xs text-text-secondary">{item.qty || 1}x</p>
                  </div>
                  <p className="shrink-0 text-sm font-semibold text-text-primary">Rp {((item.price || 0) * (item.qty || 1)).toLocaleString('id-ID')}</p>
                </div>
              ))}
            </div>
          </section>

          <div className="flex items-center justify-between border-t border-border-subtle pt-4">
            <span className="text-sm font-semibold text-text-secondary">Total Pesanan</span>
            <span className="text-lg font-bold text-primary">Rp {(order.total || 0).toLocaleString('id-ID')}</span>
          </div>
        </div>

        <div className="flex justify-end gap-2 border-t border-border-subtle bg-surface/50 px-6 py-4">
          <button type="button" onClick={onClose} className="rounded-lg border border-border-subtle px-4 py-2 text-sm font-semibold text-text-primary hover:bg-white">Tutup</button>
          <button type="button" onClick={() => onOpenInvoice(order)} className="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-hover">
            <span className="material-symbols-outlined text-[16px]">receipt_long</span>
            Download Invoice
          </button>
        </div>
      </div>
    </div>
  );
}
