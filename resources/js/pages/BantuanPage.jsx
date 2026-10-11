/**
 * src/pages/BantuanPage.jsx
 * Halaman bantuan / FAQ
 */
import { useState } from 'react';
import { Link } from 'react-router-dom';

const FAQS = [
  {
    category: 'Pemesanan',
    icon: 'shopping_bag',
    items: [
      {
        q: 'Bagaimana cara memesan produk di Accommerce.id?',
        a: 'Anda dapat memilih produk dari katalog, tambahkan ke keranjang, lalu lanjutkan ke checkout. Kami mendukung pemesanan B2B dengan invoice resmi.'
      },
      {
        q: 'Apakah saya bisa memesan dalam jumlah besar?',
        a: 'Ya! Untuk pembelian korporat atau enterprise, gunakan fitur "Minta Penawaran" atau hubungi tim sales kami. Kami menyediakan harga khusus untuk volume besar.'
      },
      {
        q: 'Apakah tersedia pembelian dengan PO (Purchase Order)?',
        a: 'Ya, kami mendukung transaksi dengan Purchase Order untuk perusahaan, instansi pemerintah, dan institusi pendidikan. Hubungi CS kami untuk informasi lebih lanjut.'
      }
    ]
  },
  {
    category: 'Pengiriman',
    icon: 'local_shipping',
    items: [
      {
        q: 'Berapa lama proses pengiriman?',
        a: 'Untuk produk stok ready, biasanya 1–3 hari kerja ke seluruh Indonesia. Produk pre-order atau indent dapat memakan waktu lebih lama sesuai ketersediaan.'
      },
      {
        q: 'Apakah tersedia pengiriman ke luar Jawa?',
        a: 'Ya, kami mengirimkan ke seluruh Indonesia menggunakan JNE, J&T, dan mitra ekspedisi terpercaya lainnya.'
      },
      {
        q: 'Bagaimana cara melacak pesanan saya?',
        a: 'Gunakan halaman "Lacak Pesanan" di menu atas, masukkan nomor order Anda. Atau login ke Akun Saya > Riwayat Pesanan.'
      }
    ]
  },
  {
    category: 'Garansi & Produk',
    icon: 'verified',
    items: [
      {
        q: 'Apakah produk yang dijual bergaransi resmi?',
        a: 'Semua produk di Accommerce.id adalah 100% original dengan garansi resmi dari masing-masing brand/distributor resmi di Indonesia.'
      },
      {
        q: 'Bagaimana proses klaim garansi?',
        a: 'Klaim garansi dilakukan langsung melalui service center resmi brand. Accommerce.id akan membantu proses administrasi dan koordinasi dengan distributor.'
      }
    ]
  },
  {
    category: 'Pembayaran',
    icon: 'payment',
    items: [
      {
        q: 'Metode pembayaran apa saja yang tersedia?',
        a: 'Kami menerima transfer bank (BCA, BNI, BRI, Mandiri), virtual account, kartu kredit, dan pembayaran via invoice resmi untuk transaksi korporat.'
      },
      {
        q: 'Apakah harga sudah termasuk PPN 11%?',
        a: 'Harga yang ditampilkan di katalog belum termasuk PPN 11%. Nilai PPN akan ditampilkan secara transparan di halaman checkout dan invoice.'
      }
    ]
  }
];

function FaqItem({ q, a }) {
  const [open, setOpen] = useState(false);
  return (
    <div className="border-b border-border-subtle last:border-0">
      <button
        onClick={() => setOpen(o => !o)}
        className="w-full text-left flex justify-between items-start py-4 gap-4 hover:text-primary transition-colors"
      >
        <span className="font-semibold text-sm text-text-primary">{q}</span>
        <span className={`material-symbols-outlined text-[20px] flex-shrink-0 text-text-secondary transition-transform ${open ? 'rotate-180' : ''}`}>
          expand_more
        </span>
      </button>
      {open && (
        <p className="text-sm text-text-secondary pb-4 leading-relaxed pr-8">{a}</p>
      )}
    </div>
  );
}

export default function BantuanPage() {
  return (
    <main className="max-w-[1000px] mx-auto px-4 sm:px-6 py-8 min-h-[70vh]">
      <h1 className="text-2xl font-bold text-text-primary mb-2">Pusat Bantuan</h1>
      <p className="text-text-secondary text-sm mb-8">Temukan jawaban atas pertanyaan Anda, atau hubungi tim support kami.</p>

      {/* Contact cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
        <a
          href="https://wa.me/6287780116800"
          target="_blank"
          rel="noopener noreferrer"
          className="bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl p-5 flex items-center gap-4 transition-colors shadow-sm"
        >
          <span className="material-symbols-outlined text-[32px]">chat</span>
          <div>
            <div className="font-bold text-base">WhatsApp CS</div>
            <div className="text-xs text-emerald-100">+62 877-8011-6800 · 24 Jam</div>
          </div>
        </a>

        <a
          href="tel:02150886800"
          className="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl p-5 flex items-center gap-4 transition-colors shadow-sm"
        >
          <span className="material-symbols-outlined text-[32px]">call</span>
          <div>
            <div className="font-bold text-base">Telepon CS</div>
            <div className="text-xs text-blue-100">(021) 5088-6800</div>
          </div>
        </a>

        <Link
          to="/minta-penawaran"
          className="bg-[#0a3875] hover:bg-[#0c4494] text-white rounded-2xl p-5 flex items-center gap-4 transition-colors shadow-sm"
        >
          <span className="material-symbols-outlined text-[32px]">request_quote</span>
          <div>
            <div className="font-bold text-base">Minta Penawaran</div>
            <div className="text-xs text-blue-200">Harga Khusus Korporat</div>
          </div>
        </Link>
      </div>

      {/* FAQ sections */}
      <h2 className="text-lg font-bold text-text-primary mb-5">Pertanyaan Umum (FAQ)</h2>
      <div className="space-y-4">
        {FAQS.map(section => (
          <div key={section.category} className="bg-white border border-border-subtle rounded-2xl overflow-hidden shadow-sm">
            <div className="flex items-center gap-3 px-5 py-4 bg-gray-50 border-b border-border-subtle">
              <span className="material-symbols-outlined text-primary text-[22px]">{section.icon}</span>
              <h3 className="font-bold text-text-primary">{section.category}</h3>
            </div>
            <div className="px-5">
              {section.items.map((item, i) => (
                <FaqItem key={i} q={item.q} a={item.a} />
              ))}
            </div>
          </div>
        ))}
      </div>

      {/* Bottom CTA */}
      <div className="mt-10 bg-gradient-to-r from-[#0a3875] to-[#1a5cb5] text-white rounded-2xl p-6 text-center">
        <h3 className="font-bold text-lg mb-1">Masih Ada Pertanyaan?</h3>
        <p className="text-blue-100 text-sm mb-4">Tim CS kami siap membantu Anda 24 jam sehari, 7 hari seminggu.</p>
        <a
          href="https://wa.me/6287780116800"
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-6 py-2.5 rounded-xl transition-colors"
        >
          <span className="material-symbols-outlined text-[20px]">chat</span>
          Chat WhatsApp Sekarang
        </a>
      </div>
    </main>
  );
}
