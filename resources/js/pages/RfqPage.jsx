import { useState } from 'react';

export default function RfqPage() {
  const [submitted, setSubmitted] = useState(false);

  function handleSubmit(e) {
    e.preventDefault();
    setSubmitted(true);
    window.scrollTo(0, 0);
  }

  if (submitted) {
    return (
      <main className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl">
        <div className="max-w-[800px] mx-auto bg-card-bg border border-border-subtle rounded-xl p-8 lg:p-12 text-center shadow-sm">
          <span className="material-symbols-outlined text-[64px] text-primary mb-4">check_circle</span>
          <h1 className="font-headline-section text-[24px] font-bold text-text-primary mb-2">Permintaan Terkirim</h1>
          <p className="font-body-md text-[15px] text-text-secondary mb-8">
            Terima kasih! Permintaan penawaran Anda (RFQ) telah kami terima. Tim sales kami akan merespons dalam waktu kurang dari 2 jam kerja.
          </p>
          <button onClick={() => setSubmitted(false)} className="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-[14px] px-8 py-3 rounded-lg transition-colors inline-flex items-center gap-2">
            <span className="material-symbols-outlined text-[18px]">arrow_back</span>
            Kembali
          </button>
        </div>
      </main>
    );
  }

  return (
    <main className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl">
      <div className="max-w-[1000px] mx-auto bg-card-bg border border-border-subtle rounded-xl p-6 lg:p-10 shadow-sm">
        <div className="mb-8">
          <span className="inline-flex items-center gap-1 bg-surface text-primary border border-primary-fixed text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider mb-4">
            Formulir Belanja Resmi
          </span>
          <h1 className="font-headline-section text-[24px] lg:text-[28px] font-bold text-text-primary mb-2">
            Minta Penawaran Prioritas (RFQ / Kupon)
          </h1>
          <p className="font-body-md text-[15px] text-text-secondary">
            Ajukan daftar kebutuhan perangkat IT & AV untuk penerbitan Surat Penawaran Harga resmi Pembeli Anda.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="flex flex-col gap-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="flex flex-col gap-2">
              <label className="font-label-sm text-[13px] font-bold text-text-primary">Nama Perusahaan / Pembeli</label>
              <input 
                required 
                type="text" 
                placeholder="John Doe" 
                className="w-full bg-page-background border border-border-subtle rounded-lg px-4 py-3 text-[14px] text-text-primary placeholder:text-outline focus:outline-none focus:border-primary transition-colors" 
              />
            </div>
            <div className="flex flex-col gap-2">
              <label className="font-label-sm text-[13px] font-bold text-text-primary">Nama PIC / Pejabat belanja</label>
              <input 
                required 
                type="text" 
                placeholder="John Doe" 
                className="w-full bg-page-background border border-border-subtle rounded-lg px-4 py-3 text-[14px] text-text-primary placeholder:text-outline focus:outline-none focus:border-primary transition-colors" 
              />
            </div>
            <div className="flex flex-col gap-2">
              <label className="font-label-sm text-[13px] font-bold text-text-primary">Email Pelanggan</label>
              <input 
                required 
                type="email" 
                placeholder="budi@pelanggan.co.id" 
                className="w-full bg-page-background border border-border-subtle rounded-lg px-4 py-3 text-[14px] text-text-primary placeholder:text-outline focus:outline-none focus:border-primary transition-colors" 
              />
            </div>
            <div className="flex flex-col gap-2">
              <label className="font-label-sm text-[13px] font-bold text-text-primary">Nomor WhatsApp Aktif</label>
              <input 
                required 
                type="tel" 
                placeholder="+62 812-3456-7890" 
                className="w-full bg-page-background border border-border-subtle rounded-lg px-4 py-3 text-[14px] text-text-primary placeholder:text-outline focus:outline-none focus:border-primary transition-colors" 
              />
            </div>
          </div>

          <div className="flex flex-col gap-2">
            <label className="font-label-sm text-[13px] font-bold text-text-primary">Daftar Kebutuhan Hardware & Spesifikasi Proyek</label>
            <textarea 
              required 
              rows={5} 
              placeholder="Sebutkan part number, jumlah unit, dan tenggat waktu belanja..." 
              className="w-full bg-page-background border border-border-subtle rounded-lg px-4 py-3 text-[14px] text-text-primary placeholder:text-outline focus:outline-none focus:border-primary transition-colors resize-y" 
            />
          </div>

          <button type="submit" className="w-full bg-primary hover:bg-primary-container text-on-primary font-label-sm text-[15px] font-bold py-4 rounded-lg flex items-center justify-center gap-2 transition-colors mt-2">
            <span className="material-symbols-outlined text-[20px]">send</span>
            Kirim Permintaan RFQ (Respon &lt; 2 Jam Kerja)
          </button>
        </form>
      </div>
    </main>
  );
}
