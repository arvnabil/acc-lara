import React, { useRef } from 'react';

export default function InvoiceModal({ isOpen, onClose, order, user }) {
  const invoiceRef = useRef(null);

  if (!isOpen || !order) return null;

  // Extract or fallback invoice details
  const invoiceNumber = order.invoiceNo || order.id?.replace('ACC-', '') || '445';
  const orderRef = order.orderRef || `INV/ACM/20260928/${String(invoiceNumber).padStart(4, '0')}`;
  const orderDate = order.date || 'September 29, 2026';
  const paymentMethod = order.paymentMethod || 'Transfer Bank Langsung';

  // Address details from order or user default address
  const defaultAddr = (user?.addresses || []).find(a => a.isDefault) || (user?.addresses || [])[0] || {};
  const recipientName = order.recipientName || user?.name || 'Mohammad Fazri';
  const streetAddress = order.streetAddress || defaultAddr.address || 'Perumahan Residence one RS 3 No.17, Ciputat';
  const city = order.city || defaultAddr.city || 'Kota Tangerang Selatan';
  const province = order.province || defaultAddr.province || 'Banten';
  const postalCode = order.postalCode || defaultAddr.postalCode || '15310';
  const email = order.email || user?.email || 'mo.fazri@gmail.com';
  const phone = order.phone || defaultAddr.phone || '081311523538';

  // Items
  const items = order.items && order.items.length > 0 ? order.items : [
    {
      name: order.title || '8-256 OPS PC Module Intel I7 80 PIN for E Series',
      sku: 'OPT-IFP-005',
      weight: '1kg',
      qty: 1,
      price: order.total || 23107500
    }
  ];

  const subtotal = items.reduce((acc, it) => acc + (it.price * (it.qty || 1)), 0);
  const shippingCost = order.shippingCost || 11000;
  const insuranceCost = order.insuranceCost || Math.round(subtotal * 0.002) || 51215;
  const grandTotal = subtotal + shippingCost + insuranceCost;

  function handlePrint() {
    window.print();
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-xs print:p-0 print:bg-white print:static">
      {/* Print-specific style */}
      <style>{`
        @media print {
          body * {
            visibility: hidden;
          }
          #invoice-printable-area, #invoice-printable-area * {
            visibility: visible;
          }
          #invoice-printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 20px;
            box-shadow: none !important;
            border: none !important;
          }
          .no-print {
            display: none !important;
          }
        }
      `}</style>

      <div className="bg-white rounded-2xl w-full max-w-[850px] shadow-2xl border border-border-subtle overflow-hidden flex flex-col max-h-[95vh] print:max-h-none print:shadow-none print:border-none print:w-full">
        {/* Top Control Bar (Hidden on print) */}
        <div className="no-print flex items-center justify-between px-6 py-3.5 bg-gray-50 border-b border-gray-200">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-primary text-[22px]">receipt_long</span>
            <span className="font-bold text-sm text-gray-800">Invoice Pembelian #{order.id || invoiceNumber}</span>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={handlePrint}
              className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-lg transition-colors cursor-pointer shadow-xs"
            >
              <span className="material-symbols-outlined text-[16px]">download</span>
              Unduh / Cetak PDF
            </button>
            <button
              onClick={onClose}
              className="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer"
            >
              <span className="material-symbols-outlined text-[20px]">close</span>
            </button>
          </div>
        </div>

        {/* Printable Invoice Sheet */}
        <div className="overflow-y-auto p-6 sm:p-10 flex-1 bg-white" id="invoice-printable-area" ref={invoiceRef}>
          {/* Header Row: Logo left, Accommerce right */}
          <div className="flex items-start justify-between pb-6 border-b border-gray-200">
            <div>
              <img
                src="/accommerce-blue.png"
                alt="Accommerce"
                className="h-11 sm:h-12 w-auto object-contain"
              />
            </div>
            <div className="text-right">
              <span className="text-sm font-bold text-gray-900 tracking-wide">Accommerce</span>
            </div>
          </div>

          {/* Title: INVOICE */}
          <div className="mt-8 mb-6">
            <h1 className="text-2xl font-black text-black tracking-tight uppercase">INVOICE</h1>
          </div>

          {/* Two Columns: Customer Info & Invoice Meta */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-8 text-[13px] text-gray-800 leading-relaxed mb-8">
            {/* Left: Customer & Address */}
            <div className="space-y-0.5">
              <p className="font-bold text-gray-900 text-[14px]">{recipientName}</p>
              <p>{streetAddress}</p>
              <p>{city}</p>
              <p>{province} {postalCode}</p>
              <p className="text-gray-600 mt-1">{email}</p>
              <p className="text-gray-600">{phone}</p>
            </div>

            {/* Right: Invoice Metadata */}
            <div className="sm:text-right space-y-1">
              <div className="flex justify-between sm:justify-end gap-4">
                <span className="text-gray-600">Nomor invoice:</span>
                <span className="font-bold text-gray-900">{invoiceNumber}</span>
              </div>
              <div className="flex justify-between sm:justify-end gap-4">
                <span className="text-gray-600">Jumlah order:</span>
                <span className="font-medium text-gray-900 font-mono text-[12px]">{orderRef}</span>
              </div>
              <div className="flex justify-between sm:justify-end gap-4">
                <span className="text-gray-600">Tanggal pemesanan:</span>
                <span className="text-gray-900">{orderDate}</span>
              </div>
              <div className="flex justify-between sm:justify-end gap-4">
                <span className="text-gray-600">Metode pembayaran:</span>
                <span className="font-medium text-gray-900">{paymentMethod}</span>
              </div>
            </div>
          </div>

          {/* Table: Products */}
          <div className="w-full mb-6 overflow-hidden">
            <table className="w-full text-left text-[13px]">
              <thead>
                <tr className="bg-black text-white">
                  <th className="py-2.5 px-4 font-bold uppercase text-[12px] tracking-wider w-[60%]">Produk</th>
                  <th className="py-2.5 px-4 font-bold uppercase text-[12px] tracking-wider text-center w-[15%]">Kuantitas</th>
                  <th className="py-2.5 px-4 font-bold uppercase text-[12px] tracking-wider text-right w-[25%]">Harga</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 border-b border-gray-200">
                {items.map((it, idx) => (
                  <tr key={idx} className="align-top">
                    <td className="py-4 px-4 space-y-1">
                      <p className="font-bold text-gray-900 leading-snug">{it.name}</p>
                      <p className="text-[11px] text-gray-500 font-medium">SKU: {it.sku || 'OPT-IFP-005'}</p>
                      <p className="text-[11px] text-gray-500 font-medium">Berat: {it.weight || '1kg'}</p>
                    </td>
                    <td className="py-4 px-4 text-center text-gray-800 font-medium">{it.qty || 1}</td>
                    <td className="py-4 px-4 text-right font-semibold text-gray-900">
                      Rp {(it.price * (it.qty || 1)).toLocaleString('id-ID')}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Summary Box (Bottom Right) */}
          <div className="flex justify-end mt-4">
            <div className="w-full sm:w-[380px] space-y-2 text-[13px]">
              <div className="flex justify-between py-1 border-b border-gray-100">
                <span className="font-bold text-gray-800">Subtotal</span>
                <span className="font-semibold text-gray-900">Rp {subtotal.toLocaleString('id-ID')}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-gray-100">
                <span className="font-bold text-gray-800">Pengiriman</span>
                <span className="text-gray-800">
                  Rp {shippingCost.toLocaleString('id-ID')} via JNE REG (1-2 hari)
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-gray-100">
                <span className="font-bold text-gray-800">Asuransi</span>
                <span className="text-gray-800">Rp {insuranceCost.toLocaleString('id-ID')}</span>
              </div>
              <div className="flex justify-between py-2 border-t-2 border-b-2 border-black mt-2 font-black text-[15px]">
                <span className="uppercase text-black">Total</span>
                <span className="text-black">Rp {grandTotal.toLocaleString('id-ID')}</span>
              </div>
            </div>
          </div>

          {/* Stamp / Verification Note */}
          <div className="mt-12 pt-6 border-t border-dashed border-gray-200 text-center text-[11px] text-gray-400">
            Invoice ini sah dan diproses secara otomatis oleh sistem komputer PT Accommerce Indonesia Solusindo.
          </div>
        </div>

        {/* Footer actions on screen */}
        <div className="no-print px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
          <span className="text-xs text-gray-500">
            Dapat dicetak atau disimpan sebagai PDF dengan memilih "Save as PDF" di dialog cetak.
          </span>
          <div className="flex gap-2">
            <button
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
            >
              Tutup
            </button>
            <button
              onClick={handlePrint}
              className="px-5 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shadow-sm"
            >
              <span className="material-symbols-outlined text-[16px]">download</span>
              Unduh Invoice PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
