/**
 * src/pages/HomePage.jsx
 * Beranda: hero, kategori unggulan, produk unggulan, produk diskon
 */
import { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import {
  getCategories, getBrands,
  getFeaturedProducts, getSaleProducts, getFlashSaleProducts,
  formatPrice
} from '../services/productService';
import ProductCard from '../components/ProductCard';
import { ProductGridSkeleton } from '../components/Skeleton';

// ─── Hero Carousel ───────────────────────────────────────────────────────────
const HERO_SLIDES = [
  // Asset banner lokal murni (tampil full tanpa teks tumpang tindih & tanpa transparan)
  {
    id: 'b-yealink',
    type: 'banner-only',
    img: '/banner/diskon-yealink.png',
    link: '/katalog?brand=yealink',
    title: 'Diskon Yealink',
  },
  {
    id: 'b-october',
    type: 'banner-only',
    img: '/banner/october-sale.png',
    link: '/katalog?sort=sale',
    title: 'October Sale',
  },
  {
    id: 'b-zoom',
    type: 'banner-only',
    img: '/banner/zoom.png',
    link: '/katalog?q=zoom',
    title: 'Zoom Video Collaboration',
  },
  {
    id: 'b-zoom-edu',
    type: 'banner-only',
    img: '/banner/zoom-educate.png',
    link: '/katalog?q=zoom',
    title: 'Zoom Education Solution',
  },
  // Slide bawaan sebelumnya (data tetap ada)
  {
    id: 1,
    type: 'default',
    badge: 'Belanja RESMI & E-KATALOG LKPP',
    title: 'Solusi Enterprise IT & Audio Visual Terintegrasi',
    desc: 'Mitra belanja resmi pemerintah & korporasi. Hardware original bergaransi distributor tunggal dengan dukungan teknis SLA 4 jam.',
    cta: { label: 'Eksplor Katalog AV', href: '/katalog?kategori=video-conference' },
    ctaAlt: { label: 'Minta Penawaran (RFQ)', href: '/checkout' },
    bg: 'from-primary to-primary-container',
    img: 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1400&q=80',
  },
  {
    id: 2,
    type: 'default',
    badge: 'NEW ARRIVAL 2026',
    title: 'Kamera PTZ & Video Bar Terbaru untuk Ruang Rapat',
    desc: 'Rasakan pengalaman meeting profesional dengan kualitas 4K, AI Auto-framing, dan noise-cancelling berlapis.',
    cta: { label: 'Lihat Video Conference', href: '/katalog?kategori=video-conference' },
    ctaAlt: { label: 'Bandingkan Produk', href: '/katalog' },
    bg: 'from-[#00205b] to-[#003599]',
    img: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=1400&q=80',
  },
  {
    id: 3,
    type: 'default',
    badge: 'PROMO AKHIR TAHUN',
    title: 'Diskon hingga 30% untuk Perangkat Personal Workspace',
    desc: 'Lengkapi meja kerja Anda: Headset, Webcam, Docking Station, dan Monitor Profesional.',
    cta: { label: 'Lihat Promo', href: '/katalog?sort=sale' },
    ctaAlt: { label: 'Semua Produk', href: '/katalog' },
    bg: 'from-[#003599] to-[#0049cc]',
    img: 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=1400&q=80',
  },
];

function HeroCarousel() {
  const [active, setActive] = useState(0);
  const timerRef = useRef(null);

  const startTimer = () => {
    timerRef.current = setInterval(() => {
      setActive(prev => (prev + 1) % HERO_SLIDES.length);
    }, 5000);
  };

  useEffect(() => {
    startTimer();
    return () => clearInterval(timerRef.current);
  }, []);

  function goTo(idx) {
    setActive(idx);
    clearInterval(timerRef.current);
    startTimer();
  }

  const slide = HERO_SLIDES[active];
  const isBannerOnly = slide.type === 'banner-only';

  return (
    <div className="relative rounded-xl overflow-hidden min-h-[300px] sm:min-h-[360px] lg:min-h-[460px] shadow-md transition-all duration-500 bg-surface">
      {isBannerOnly ? (
        /* Opsi Banner Murni: Tanpa transparansi, tanpa teks bertumpuk, full solid & tajam */
        <Link to={slide.link || '/katalog'} className="block w-full h-full min-h-[300px] sm:min-h-[360px] lg:min-h-[460px] relative group">
          <img
            src={slide.img}
            alt={slide.title || 'Promo Banner'}
            className="w-full h-full min-h-[300px] sm:min-h-[360px] lg:min-h-[460px] object-cover object-center block"
          />
        </Link>
      ) : (
        /* Slide Standar dengan Teks & Gradient */
        <div className={`relative bg-gradient-to-br ${slide.bg} w-full h-full min-h-[360px] lg:min-h-[460px] text-on-primary p-space-2xl flex flex-col justify-between`}>
          {/* Background image */}
          <div
            className="absolute inset-0 bg-cover bg-center opacity-15 transition-opacity duration-500"
            style={{ backgroundImage: `url('${slide.img}')` }}
            aria-hidden
          />
          
          {/* Slide content */}
          <div className="relative z-10 flex flex-col justify-center h-full max-w-[620px]">
            <div className="inline-flex items-center gap-2 bg-white/10 px-3 py-1 rounded font-sku text-[11px] font-semibold text-primary-fixed mb-space-md w-max">
              <span className="material-symbols-outlined text-[14px]">verified</span>
              <span>{slide.badge}</span>
            </div>
            <h1 className="font-headline-hero text-[26px] lg:text-[40px] tracking-tight leading-tight mb-space-md">
              {slide.title}
            </h1>
            <p className="font-body-md text-[14px] lg:text-[15px] text-primary-fixed mb-space-xl leading-relaxed max-w-[480px]">
              {slide.desc}
            </p>
            <div className="flex flex-wrap items-center gap-space-md">
              <Link to={slide.cta.href} className="bg-card-bg text-text-primary hover:bg-surface font-label-sm text-label-sm px-6 py-3 rounded-lg transition-colors shadow-sm flex items-center gap-2">
                <span>{slide.cta.label}</span>
                <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
              </Link>
              <Link to={slide.ctaAlt.href} className="bg-transparent border border-white/40 hover:border-white text-on-primary font-label-sm text-label-sm px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
                <span className="material-symbols-outlined text-[16px]">request_quote</span>
                <span>{slide.ctaAlt.label}</span>
              </Link>
            </div>
          </div>
        </div>
      )}

      {/* Controls Overlay (Navigasi slide & pagination) */}
      <div className={`absolute bottom-0 left-0 right-0 z-20 flex items-center justify-between p-4 ${isBannerOnly ? 'bg-gradient-to-t from-black/40 via-black/10 to-transparent' : 'border-t border-white/20'}`}>
        <div className="flex items-center gap-2">
          {HERO_SLIDES.map((_, i) => (
            <button
              key={i}
              onClick={() => goTo(i)}
              aria-label={`Slide ${i + 1}`}
              className={`rounded-full transition-all duration-300 ${i === active ? 'w-8 h-2 bg-white shadow-sm' : 'w-2 h-2 bg-white/60 hover:bg-white'}`}
            />
          ))}
        </div>
        <div className="flex items-center gap-2">
          <button onClick={() => goTo((active - 1 + HERO_SLIDES.length) % HERO_SLIDES.length)} aria-label="Slide sebelumnya" className="w-8 h-8 rounded-lg bg-black/30 hover:bg-black/50 text-white flex items-center justify-center transition-colors backdrop-blur-sm">
            <span className="material-symbols-outlined text-[18px]">chevron_left</span>
          </button>
          <button onClick={() => goTo((active + 1) % HERO_SLIDES.length)} aria-label="Slide berikutnya" className="w-8 h-8 rounded-lg bg-black/30 hover:bg-black/50 text-white flex items-center justify-center transition-colors backdrop-blur-sm">
            <span className="material-symbols-outlined text-[18px]">chevron_right</span>
          </button>
        </div>
      </div>
    </div>
  );
}

// ─── Category Icons Map ───────────────────────────────────────────────────────
const CAT_ICONS = {
  'video-conference': 'video_call',
  'interactive-display': 'tv',
  'server': 'dns',
  'storage': 'storage',
  'network': 'router',
  'cctv': 'videocam',
  'ip-phone': 'call',
  'headset': 'headset_mic',
  'webcam': 'webcam',
  'camera': 'camera_alt',
  'aksesoris': 'cable',
  'brand': 'domain',
  'logitech': 'domain',
  'yealink': 'domain',
  'jabra': 'domain',
  'poly': 'domain',
  'default': 'category',
};

function getCatIcon(slug = '') {
  for (const [key, icon] of Object.entries(CAT_ICONS)) {
    if (slug.includes(key)) return icon;
  }
  return CAT_ICONS.default;
}

// ─── Flash Sale Countdown ───────────────────────────────────────────────────
function useCountdown(targetDate) {
  const calc = () => {
    const diff = Math.max(0, targetDate - Date.now());
    return {
      h: Math.floor(diff / 3600000),
      m: Math.floor((diff % 3600000) / 60000),
      s: Math.floor((diff % 60000) / 1000),
    };
  };
  const [time, setTime] = useState(calc);
  useEffect(() => {
    const t = setInterval(() => setTime(calc()), 1000);
    return () => clearInterval(t);
  }, []);
  return time;
}

function FlashSaleCard({ product }) {
  const img = Array.isArray(product.images) ? product.images[0] : product.images;
  const imgUrl = img ? img.split(',')[0].trim() : '';
  const disc = product.sale_price
    ? Math.round(((product.regular_price - product.sale_price) / product.regular_price) * 100)
    : 0;
  return (
    <Link
      to={`/produk/${product.slug}`}
      className="flex-shrink-0 w-[170px] sm:w-[190px] bg-card-bg border border-border-subtle rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all group hover:border-red-400/50"
    >
      <div className="relative w-full bg-white overflow-hidden" style={{ aspectRatio: '1/1' }}>
        {imgUrl ? (
          <img src={imgUrl} alt={product.name} className="w-full h-full object-contain p-3 group-hover:scale-105 transition-transform duration-300" />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <span className="material-symbols-outlined text-[40px] text-border-subtle">image</span>
          </div>
        )}
        <div className="absolute top-2 left-2 bg-red-500 text-white font-bold text-[11px] px-2 py-0.5 rounded">
          -{disc}%
        </div>
      </div>
      <div className="p-3">
        <p className="font-label-sm text-[11px] text-text-secondary line-clamp-2 mb-1 leading-tight">{product.name}</p>
        <div className="font-price text-[14px] font-bold text-red-500">{formatPrice(product.sale_price)}</div>
        <div className="font-sku text-[10px] text-text-secondary line-through">{formatPrice(product.regular_price)}</div>
      </div>
    </Link>
  );
}

// ─── Benefits Bar ────────────────────────────────────────────────────────────
const BENEFITS = [
  { icon: 'verified', title: '100% Original & Resmi', desc: 'Garansi distributor tunggal Indonesia' },
  { icon: 'receipt_long', title: 'Invoice & Faktur Pajak', desc: 'Legalitas perusahaan PKP lengkap' },
  { icon: 'local_shipping', title: 'Pengiriman Aman', desc: 'Asuransi pengiriman & kurir khusus' },
  { icon: 'support_agent', title: 'SLA Dukungan 4 Jam', desc: 'Engineer bersertifikat siap bantu' },
];

export default function HomePage({ featuredProducts: propFeatured, saleProducts: propSale, categories: propCats }) {
  const [featured, setFeatured] = useState(propFeatured || []);
  const [sale, setSale] = useState(propSale || []);
  const [flashSale, setFlashSale] = useState([]);
  const [cats, setCats] = useState(propCats || []);
  const [brands, setBrands] = useState([]);
  const [loading, setLoading] = useState(!propFeatured);

  // Flash sale ends at next midnight
  const flashSaleEnd = useRef((() => {
    const d = new Date(); d.setHours(23, 59, 59, 999); return d.getTime();
  })());
  const countdown = useCountdown(flashSaleEnd.current);

  useEffect(() => {
    if (propFeatured) return;
    setLoading(true);
    Promise.all([getFeaturedProducts(8), getSaleProducts(8), getFlashSaleProducts(10), getCategories(), getBrands()])
      .then(([feat, sal, flash, cats, brands]) => {
        setFeatured(feat);
        setSale(sal);
        setFlashSale(flash);
        // Top-level non-addon, non-brand categories
        setCats(cats.filter(c => !c.parent_id && !c.is_addon && !c.is_brand_group).slice(0, 8));
        setBrands(brands.slice(0, 9));
      })
      .finally(() => setLoading(false));
  }, []);

  // Derive top-level cats when propCats provided
  useEffect(() => {
    if (propCats) {
      setCats(propCats.filter(c => !c.parent_id && !c.is_addon && !c.is_brand_group).slice(0, 8));
    }
  }, [propCats]);

  useEffect(() => {
    if (!propFeatured) return;
    getBrands().then(b => setBrands(b.slice(0, 9)));
  }, [propFeatured]);

  return (
    <div className="flex flex-col w-full">
      {/* Hero */}
      <section className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl w-full">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-stretch">
          <div className="lg:col-span-8">
            <HeroCarousel />
          </div>
          {/* Promo Side Card */}
          <div className="lg:col-span-4 bg-card-bg rounded-xl border border-border-subtle p-space-xl flex flex-col justify-between shadow-sm relative overflow-hidden">
            <div className="absolute top-0 right-0 bg-tertiary-container text-on-tertiary font-sku text-[10px] font-bold px-3 py-1 rounded-bl-lg uppercase tracking-wider">
              Spesial Bundle
            </div>
            <div>
              <span className="font-sku text-[11px] text-text-secondary uppercase font-semibold">Small to Medium Rooms</span>
              <h3 className="font-title-card text-[18px] text-text-primary font-bold mt-1 mb-space-md">
                Yealink MeetingBar A40 Video Conference Bar
              </h3>
              <div className="w-full h-[160px] rounded-lg bg-surface mb-space-md overflow-hidden border border-border-subtle">
                <img
                  src="/products/Meeting-bar-yealink.webp"
                  alt="Yealink MeetingBar A40"
                  className="w-full h-full object-cover object-center"
                  onError={e => { e.target.src = 'https://picsum.photos/seed/yealink-a40/600/300'; }}
                />
              </div>
              <ul className="flex flex-col gap-2 font-label-sm text-[13px] text-text-secondary mb-space-lg">
                {[
                  'Powerful Meetings, Simple Connect',
                  'Dual 48MP AI Camera & 4K Ultra HD',
                  'Zoom Rooms & Teams Native Appliance',
                  'AI Noise Cancellation & 8 MEMS Array'
                ].map(t => (
                  <li key={t} className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-[16px] text-primary">check_circle</span>
                    <span>{t}</span>
                  </li>
                ))}
              </ul>
            </div>
            <div>
              <div className="flex items-baseline justify-between mb-space-md">
                <div>
                  <span className="font-sku text-[11px] text-text-secondary line-through">Rp 53.138.817</span>
                  <div className="font-price text-[20px] text-primary font-bold">Rp 40.957.517</div>
                </div>
                <span className="bg-amber-bg text-amber-text font-sku text-[10px] font-bold px-2 py-0.5 rounded">Hemat 23%</span>
              </div>
              <Link to="/katalog?brand=yealink" className="w-full bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm py-3 rounded-lg flex items-center justify-center gap-2 transition-colors">
                <span className="material-symbols-outlined text-[18px]">shopping_cart</span>
                <span>Ambil Paket Bundling</span>
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Benefits */}
      <section className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-md lg:py-space-lg w-full">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 bg-card-bg p-3.5 sm:p-space-lg rounded-xl border border-border-subtle shadow-sm divide-y sm:divide-y-0 divide-border-subtle/60">
          {BENEFITS.map(b => (
            <div key={b.icon} className="flex items-center sm:items-start gap-3 p-2 sm:p-space-sm first:pt-1 sm:first:pt-space-sm">
              <div className="w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12 rounded-lg bg-surface flex items-center justify-center text-primary shrink-0">
                <span className="material-symbols-outlined text-[20px] md:text-[24px]">{b.icon}</span>
              </div>
              <div className="min-w-0 flex-1">
                <h4 className="font-title-card text-[13px] md:text-[14px] font-bold text-text-primary leading-snug">{b.title}</h4>
                <p className="font-sku text-[11px] md:text-[12px] text-text-secondary mt-0.5 leading-snug">{b.desc}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* Flash Sale Section */}
      {(loading || flashSale.length > 0) && (
        <section className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl w-full">
          {/* Header */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-space-lg">
            <div className="flex items-center justify-between sm:justify-start gap-3">
              <div className="flex items-center gap-2 bg-gradient-to-r from-red-600 via-rose-600 to-red-500 text-white px-3 py-1.5 rounded-lg shadow-sm">
                <span className="material-symbols-outlined text-[18px] animate-pulse">local_fire_department</span>
                <span className="font-bold text-[14px] tracking-wide uppercase">Flash Sale</span>
              </div>
              {/* Countdown */}
              <div className="flex items-center gap-1.5">
                <span className="font-sku text-[10px] text-text-secondary hidden sm:inline">Berakhir dalam:</span>
                {[countdown.h, countdown.m, countdown.s].map((v, i) => (
                  <span key={i} className="bg-surface border border-border-subtle rounded-md px-2 py-0.5 font-mono font-bold text-[13px] text-text-primary min-w-[28px] text-center">
                    {String(v).padStart(2, '0')}
                  </span>
                ))}
              </div>
              {/* Mobile: Lihat Semua inline */}
              <Link to="/katalog?sort=sale" className="sm:hidden font-label-sm text-label-sm text-red-500 hover:text-red-600 flex items-center gap-0.5 font-semibold ml-auto shrink-0 whitespace-nowrap">
                <span>Semua</span>
                <span className="material-symbols-outlined text-[14px]">chevron_right</span>
              </Link>
            </div>
            {/* Desktop: Lihat Semua */}
            <Link to="/katalog?sort=sale" className="hidden sm:flex font-label-sm text-label-sm text-red-500 hover:text-red-600 items-center gap-1 font-semibold shrink-0 whitespace-nowrap">
              <span>Lihat Semua</span>
              <span className="material-symbols-outlined text-[16px]">chevron_right</span>
            </Link>
          </div>

          {/* Horizontal Scroll Rail */}
          {loading ? (
            <div className="flex gap-3 overflow-x-auto pb-2">
              {Array.from({ length: 6 }).map((_, i) => (
                <div key={i} className="flex-shrink-0 w-[170px] h-[220px] bg-surface rounded-xl animate-pulse" />
              ))}
            </div>
          ) : (
            <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-border-subtle scrollbar-track-transparent">
              {flashSale.map(p => <FlashSaleCard key={p.id} product={p} />)}
            </div>
          )}
        </section>
      )}

      {/* Featured Products */}
      <section className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl w-full">
        <div className="flex items-center justify-between mb-space-lg">
          <div>
            <h2 className="font-headline-section text-[18px] lg:text-[26px] text-text-primary font-bold">Produk Unggulan</h2>
            <p className="font-body-md text-[12px] lg:text-[13px] text-text-secondary">Pilihan terbaik dari katalog kami</p>
          </div>
          <Link to="/katalog?sort=featured" className="font-label-sm text-label-sm text-primary hover:text-secondary flex items-center gap-0.5 font-semibold shrink-0 whitespace-nowrap">
            <span className="hidden sm:inline">Lihat Semua</span>
            <span className="sm:hidden">Semua</span>
            <span className="material-symbols-outlined text-[16px]">chevron_right</span>
          </Link>
        </div>
        {loading ? (
          <ProductGridSkeleton count={8} />
        ) : featured.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4">
            {featured.map(p => <ProductCard key={p.id} product={p} />)}
          </div>
        ) : (
          <p className="text-text-secondary text-center py-8">Belum ada produk unggulan.</p>
        )}
      </section>

      {/* Sale Products */}
      {(loading || sale.length > 0) && (
        <section className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl w-full">
          <div className="flex items-center justify-between mb-space-lg">
            <div>
              <h2 className="font-headline-section text-[18px] lg:text-[26px] text-text-primary font-bold">Promo & Diskon</h2>
              <p className="font-body-md text-[12px] lg:text-[13px] text-text-secondary">Produk dengan harga spesial terbatas</p>
            </div>
            <Link to="/katalog?sort=sale" className="font-label-sm text-label-sm text-primary hover:text-secondary flex items-center gap-0.5 font-semibold shrink-0 whitespace-nowrap">
              <span className="hidden sm:inline">Semua Promo</span>
              <span className="sm:hidden">Semua</span>
              <span className="material-symbols-outlined text-[16px]">chevron_right</span>
            </Link>
          </div>
          {loading ? (
            <ProductGridSkeleton count={8} />
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4">
              {sale.map(p => <ProductCard key={p.id} product={p} />)}
            </div>
          )}
        </section>
      )}

      {/* Brand Partners */}
      {brands.length > 0 && (
        <section className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl w-full">
          <div className="flex items-center justify-between gap-2 mb-space-lg">
            <div className="min-w-0">
              <h2 className="font-headline-section text-[18px] lg:text-[26px] text-text-primary font-bold">Brand Partner Resmi</h2>
              <p className="font-body-md text-[12px] lg:text-[13px] text-text-secondary">Authorized Distributor & System Integrator</p>
            </div>
            <Link to="/katalog" className="font-label-sm text-label-sm text-primary hover:text-secondary flex items-center gap-0.5 font-semibold shrink-0 whitespace-nowrap">
              <span className="hidden sm:inline">Lihat Semua Brand</span>
              <span className="sm:hidden">Semua</span>
              <span className="material-symbols-outlined text-[16px]">chevron_right</span>
            </Link>
          </div>
          <div className="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-9 gap-space-md">
            {brands.map(brand => (
              <Link
                key={brand.id}
                to={`/katalog?brand=${brand.slug}`}
                className="bg-card-bg hover:bg-surface border border-border-subtle rounded-xl p-3 flex flex-col items-center justify-center gap-2 transition-all group shadow-sm hover:shadow-md hover:border-primary/40 min-h-[90px]"
              >
                <div className="w-14 h-11 flex items-center justify-center relative">
                  {brand.logo ? (
                    <img
                      src={brand.logo}
                      alt={brand.name}
                      className="max-w-full max-h-full object-contain transition-all group-hover:scale-105"
                      onError={e => {
                        e.target.style.display = 'none';
                        const fallback = e.target.parentElement.querySelector('.brand-avatar-fallback');
                        if (fallback) fallback.style.display = 'flex';
                      }}
                    />
                  ) : null}
                  <div
                    className="brand-avatar-fallback w-10 h-10 rounded-full bg-surface flex items-center justify-center font-bold text-primary text-sm"
                    style={{ display: brand.logo ? 'none' : 'flex' }}
                  >
                    {brand.name.charAt(0)}
                  </div>
                </div>
                <span className="font-title-card text-[11px] font-bold text-text-primary text-center leading-tight line-clamp-1 group-hover:text-primary transition-colors">
                  {brand.name}
                </span>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Categories Grid */}
      {cats.length > 0 && (
        <section className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl w-full">
          <div className="flex items-center justify-between mb-space-lg">
            <div>
              <h2 className="font-headline-section text-[18px] lg:text-[26px] text-text-primary font-bold">Belanja Berdasarkan Kategori</h2>
              <p className="font-body-md text-[12px] lg:text-[13px] text-text-secondary">Pilih solusi perangkat keras enterprise sesuai kebutuhan proyek</p>
            </div>
            <Link to="/kategori" className="font-label-sm text-label-sm text-primary hover:text-secondary flex items-center gap-0.5 font-semibold shrink-0 whitespace-nowrap">
              <span className="hidden sm:inline">Semua Kategori</span>
              <span className="sm:hidden">Semua</span>
              <span className="material-symbols-outlined text-[16px]">chevron_right</span>
            </Link>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-space-lg">
            {cats.map(cat => (
              <Link
                key={cat.id}
                to={`/katalog?kategori=${cat.slug}`}
                className="bg-card-bg hover:bg-surface border border-border-subtle rounded-xl p-space-lg flex flex-col justify-between transition-all group shadow-sm"
              >
                <div className="flex items-center justify-between mb-space-md">
                  <div className="w-12 h-12 rounded-lg bg-surface flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                    <span className="material-symbols-outlined text-[24px]">{getCatIcon(cat.slug)}</span>
                  </div>
                  <span className="font-sku text-[11px] text-text-secondary">{cat.product_count} Produk</span>
                </div>
                <div>
                  <h3 className="font-title-card text-[15px] font-bold text-text-primary mb-1 group-hover:text-primary transition-colors">{cat.name}</h3>
                  <span className="font-label-sm text-[12px] text-primary flex items-center gap-1 font-semibold">
                    <span>Lihat Katalog</span>
                    <span className="material-symbols-outlined text-[14px]">arrow_forward</span>
                  </span>
                </div>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Bottom spacer for mobile bottom nav */}
      <div className="h-4" />
    </div>
  );
}
