import { Link } from 'react-router-dom';

export default function Footer() {
  return (
    <footer className="w-full bg-card-bg border-t border-border-subtle pt-10 pb-8 text-text-primary mt-auto">
      <div className="max-w-[1440px] mx-auto px-gutter lg:px-margin">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-10 mb-10">
          
          {/* Kolom 1: Profil Perusahaan */}
          <div className="flex flex-col">
            <Link to="/" className="inline-block mb-4">
              <img 
                src="/accommerce-blue.png" 
                alt="Accommerce.id" 
                className="h-8 lg:h-9 w-auto object-contain object-left" 
              />
            </Link>
            <p className="font-body-md text-[13px] text-text-secondary mb-4 leading-relaxed">
              Accommerce by ACTiV adalah platform penyedia solusi perangkat IT & Audio Visual enterprise terpercaya, original, dan bergaransi resmi untuk kebutuhan korporasi & institusi di Indonesia.
            </p>
            <div className="flex flex-col gap-2 font-label-sm text-[12px] text-text-secondary">
              <div className="flex items-start gap-2">
                <span className="material-symbols-outlined text-[16px] text-primary mt-0.5 shrink-0">location_on</span>
                <span>Bellezza BSA 1st Floor SA1-06, Jl. Letjen Soepeno, Permata Hijau, Grogol Utara, Kebayoran Lama, Jakarta Selatan 12210</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-[16px] text-primary shrink-0">mail</span>
                <a href="mailto:cs@accommerce.id" className="hover:text-primary transition-colors">cs@accommerce.id</a>
              </div>
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-[16px] text-primary shrink-0">call</span>
                <a href="tel:+6287780116800" className="hover:text-primary transition-colors">+62 877-8011-6800</a>
              </div>
            </div>
          </div>

          {/* Kolom 2: Tentang Kami & Kebijakan */}
          <div>
            <h4 className="font-title-card text-[15px] font-bold text-text-primary mb-3 border-b border-border-subtle pb-2">
              Tentang Kami
            </h4>
            <ul className="flex flex-col gap-2 text-[13px] text-text-secondary mb-6">
              <li><Link to="/katalog" className="hover:text-primary transition-colors">Semua Produk</Link></li>
              <li><Link to="/bantuan" className="hover:text-primary transition-colors">Tentang Kami</Link></li>
              <li>
                <a href="https://wa.me/6287780116800" target="_blank" rel="noopener noreferrer" className="hover:text-primary transition-colors">
                  Layanan Pelanggan (WhatsApp)
                </a>
              </li>
            </ul>

            <h4 className="font-title-card text-[15px] font-bold text-text-primary mb-3 border-b border-border-subtle pb-2">
              Kebijakan Layanan
            </h4>
            <ul className="flex flex-col gap-2 text-[13px] text-text-secondary">
              <li><Link to="/bantuan" className="hover:text-primary transition-colors">Kebijakan Pengiriman & Ekspedisi</Link></li>
              <li><Link to="/bantuan" className="hover:text-primary transition-colors">Kebijakan Klaim Garansi</Link></li>
              <li><Link to="/bantuan" className="hover:text-primary transition-colors">Syarat & Ketentuan Pengadaan</Link></li>
            </ul>
          </div>

          {/* Kolom 3: Layanan & Marketplace */}
          <div>
            <h4 className="font-title-card text-[15px] font-bold text-text-primary mb-3 border-b border-border-subtle pb-2">
              Akses Cepat
            </h4>
            <ul className="flex flex-col gap-2 text-[13px] text-text-secondary mb-6">
              <li><Link to="/akun" className="hover:text-primary transition-colors">Dasbor Akun</Link></li>
              <li><Link to="/keranjang" className="hover:text-primary transition-colors">Keranjang Belanja</Link></li>
              <li><Link to="/lacak-pesanan" className="hover:text-primary transition-colors">Lacak Status Pesanan</Link></li>
              <li><Link to="/minta-penawaran" className="hover:text-primary transition-colors">Draft Permintaan RFQ</Link></li>
            </ul>

            <h4 className="font-title-card text-[15px] font-bold text-text-primary mb-3 border-b border-border-subtle pb-2">
              Tersedia di Marketplace
            </h4>
            <div className="flex flex-wrap gap-2">
              {['Tokopedia', 'Shopee', 'Blibli', 'TikTok Shop'].map(mp => (
                <span key={mp} className="bg-surface px-2.5 py-1 rounded text-[11px] font-semibold text-text-secondary border border-border-subtle">
                  {mp}
                </span>
              ))}
            </div>
          </div>

          {/* Kolom 4: Pembayaran & Keamanan */}
          <div>
            <h4 className="font-title-card text-[15px] font-bold text-text-primary mb-3 border-b border-border-subtle pb-2">
              Metode Pembayaran
            </h4>
            <p className="text-[12px] text-text-secondary leading-relaxed mb-6">
              Mendukung Virtual Account BCA, Mandiri, BNI, BRI, Kartu Kredit Korporat, QRIS, serta Invoice / Term of Payment (B2B).
            </p>

            <h4 className="font-title-card text-[15px] font-bold text-text-primary mb-3 border-b border-border-subtle pb-2">
              Keamanan & Legalitas
            </h4>
            <div className="bg-surface p-3 rounded-lg border border-border-subtle flex flex-col gap-1.5">
              <div className="flex items-center gap-1.5 font-bold text-[12px] text-text-primary">
                <span className="material-symbols-outlined text-[18px] text-primary">verified</span>
                Partner Resmi Principal
              </div>
              <p className="text-[11px] text-text-secondary leading-normal">
                Accommerce by ACTiV (PT Alfa Cipta Teknologi Virtual) berstatus Pengusaha Kena Pajak (PKP) resmi dan melayani penerbitan Faktur Pajak PPN 11%.
              </p>
            </div>
          </div>

        </div>

        {/* Bottom bar */}
        <div className="border-t border-border-subtle pt-6 flex flex-col md:flex-row items-center justify-between gap-3 text-text-secondary text-[12px]">
          <div>
            © {new Date().getFullYear()} Accommerce by ACTiV (PT Alfa Cipta Teknologi Virtual). Hak Cipta Dilindungi.
          </div>
          <div className="flex items-center gap-6">
            <span>Enterprise IT & AV Solutions</span>
            <span>CS: (021) 5088-6800</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
