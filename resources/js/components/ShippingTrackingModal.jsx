export default function ShippingTrackingModal({ isOpen, onClose, order }) {
  if (!isOpen || !order) return null;

  const history = order.biteshipTrackingHistory || [];
  const trackingNumber = order.trackingNumber || order.biteshipWaybillId || 'Resi belum tersedia';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
      <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div className="flex items-center justify-between border-b border-border-subtle px-6 py-4">
          <div>
            <p className="text-xs font-bold uppercase tracking-wider text-primary">Info Pengiriman</p>
            <h2 className="mt-1 text-lg font-bold text-text-primary">{order.id}</h2>
          </div>
          <button type="button" onClick={onClose} className="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Tutup">
            <span className="material-symbols-outlined">close</span>
          </button>
        </div>

        <div className="p-6">
          <div className="rounded-xl bg-blue-50 p-4">
            <div className="flex items-start gap-3">
              <span className="material-symbols-outlined text-primary">local_shipping</span>
              <div>
                <div className="font-bold text-text-primary">{order.shippingLabel || 'Kurir belum ditentukan'}</div>
                <div className="mt-1 text-sm text-text-secondary">{trackingNumber}</div>
                <div className="mt-2 text-xs text-primary">Estimasi tiba: {order.shippingEstimate || 'Menunggu estimasi'}</div>
              </div>
            </div>
          </div>

          <div className="mt-6">
            <h3 className="mb-4 text-sm font-bold text-text-primary">Riwayat Pengiriman</h3>
            {history.length > 0 ? (
              <div className="space-y-0">
                {history.map((event, index) => (
                  <div key={`${event.updated_at || event.status}-${index}`} className="relative flex gap-3 pb-5 last:pb-0">
                    {index < history.length - 1 && <span className="absolute left-[5px] top-3 bottom-0 w-px bg-border-subtle" />}
                    <span className={`relative z-10 mt-1 h-3 w-3 rounded-full border-2 ${index === 0 ? 'border-primary bg-primary' : 'border-outline bg-white'}`} />
                    <div>
                      <p className={`text-sm ${index === 0 ? 'font-bold text-primary' : 'text-text-secondary'}`}>
                        {event.note || event.status || 'Status pengiriman diperbarui'}
                      </p>
                      {event.updated_at && <p className="mt-1 text-xs text-text-secondary">{new Date(event.updated_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</p>}
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p className="rounded-lg border border-dashed border-border-subtle p-4 text-sm text-text-secondary">Riwayat pengiriman belum tersedia.</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
