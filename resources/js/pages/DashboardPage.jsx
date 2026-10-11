import { useState, useEffect } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useWishlist } from '../context/WishlistContext';
import { getProducts } from '../services/productService';
import productsIndex from '../data/products-index.json';
import InvoiceModal from '../components/InvoiceModal';
import ReviewModal from '../components/ReviewModal';

export default function DashboardPage() {
  const { 
    user, 
    logout, 
    redeemPointsForWallet, 
    redeemPointsForVoucher, 
    claimEwalletPoints,
    markOrderItemReviewed,
    saveAddress, 
    deleteAddress, 
    setDefaultAddress 
  } = useAuth();
  const { wishlist } = useWishlist();
  const navigate = useNavigate();
  const location = useLocation();

  // Active Tab state: 'dashboard', 'orders', 'addresses', 'vouchers', 'wishlist', 'points', 'reviews'
  const [activeTab, setActiveTab] = useState('dashboard');
  
  // Redeem state feedback
  const [feedback, setFeedback] = useState(null);
  const [wishlistProducts, setWishlistProducts] = useState([]);

  // Invoice Modal State
  const [selectedInvoiceOrder, setSelectedInvoiceOrder] = useState(null);
  const [showInvoiceModal, setShowInvoiceModal] = useState(false);

  // Review Modal & Sub-tab State
  const [reviewModalData, setReviewModalData] = useState(null);
  const [showReviewModal, setShowReviewModal] = useState(false);
  const [reviewSubTab, setReviewSubTab] = useState('pending'); // 'pending' | 'completed'

  // E-Wallet Claim Form State
  const [showEwalletForm, setShowEwalletForm] = useState(false);
  const [ewalletPlatform, setEwalletPlatform] = useState('GoPay');
  const [ewalletPhone, setEwalletPhone] = useState(user?.phone || '0812-3456-7890');
  const [ewalletAccountName, setEwalletAccountName] = useState(user?.name || 'John Doe');
  const [selectedEwalletPackage, setSelectedEwalletPackage] = useState({ points: 500, rupiah: 50000 });
  const [ewalletFeedback, setEwalletFeedback] = useState(null);

  // Loyalty Banner State
  const [showLoyaltyInfo, setShowLoyaltyInfo] = useState(false);
  const LOYALTY_LEVELS = [
    { 
      id: 'classic',   
      label: 'Classic',   
      minPoints: 0,     
      maxPoints: 499,  
      color: 'from-slate-600 via-slate-700 to-zinc-800',    
      badgeBg: 'bg-slate-600', 
      textColor: 'text-slate-200', 
      icon: '🔰',
      spendMin: 0,
      spendMax: 4990000,
      perks: [
        'Akun member terverifikasi & katalog lengkap',
        'Bonus 50 Poin per ulasan pembelian terverifikasi',
        'Faktur Pajak PKP (bagi member ber-NPWP/SPPKP)',
        'Akses promo reguler Accommerce'
      ] 
    },
    { 
      id: 'bronze',     
      label: 'Bronze',     
      minPoints: 500,  
      maxPoints: 1499, 
      color: 'from-amber-800 via-amber-900 to-stone-900',   
      badgeBg: 'bg-amber-800', 
      textColor: 'text-amber-200', 
      icon: '🥉', 
      spendMin: 5000000,
      spendMax: 14990000,
      perks: [
        'Semua benefit Classic',
        'Voucher potongan Rp 25.000 / diskon khusus aksesori',
        'Gratis ongkir 1x/bulan (s/d Rp 30.000)',
        'Pengiriman pesanan prioritas reguler'
      ] 
    },
    { 
      id: 'silver',   
      label: 'Silver',   
      minPoints: 1500,     
      maxPoints: 7499,  
      color: 'from-slate-400 via-slate-500 to-gray-600',    
      badgeBg: 'bg-slate-500', 
      textColor: 'text-slate-100', 
      icon: '🥈',  
      spendMin: 15000000,
      spendMax: 74990000,
      perks: [
        'Semua benefit Bronze',
        'Bonus 5% Poin belanja per order',
        'Gratis ongkir 2x/bulan (s/d Rp 50.000)',
        'Layanan konsultasi teknis audio visual'
      ] 
    },
    { 
      id: 'gold',     
      label: 'Gold',     
      minPoints: 7500,  
      maxPoints: 14999, 
      color: 'from-amber-400 via-amber-500 to-yellow-600',   
      badgeBg: 'bg-amber-500', 
      textColor: 'text-amber-100', 
      icon: '🥇', 
      spendMin: 75000000,
      spendMax: 149990000,
      perks: [
        'Semua benefit Silver',
        'Harga khusus proyek B2B (Project Pricing)',
        'Prioritas alokasi stok hardware IT & AV',
        'Gratis ongkir 4x/bulan & Flash Sale VIP',
        'Bonus 10% Poin belanja per order'
      ] 
    },
    { 
      id: 'platinum', 
      label: 'Platinum', 
      minPoints: 15000, 
      maxPoints: 39999,  
      color: 'from-violet-700 via-purple-800 to-indigo-900',  
      badgeBg: 'bg-violet-700', 
      textColor: 'text-violet-200', 
      icon: '💎', 
      spendMin: 150000000,
      spendMax: 399990000,
      perks: [
        'Semua benefit Gold',
        'Dedicated Account Manager B2B',
        'Termin pembayaran B2B (NET 14 Hari)',
        'Dukungan garansi & RMA express prioritas',
        'Bonus 20% Poin belanja per order'
      ] 
    },
    { 
      id: 'diamond', 
      label: 'Diamond', 
      minPoints: 40000, 
      maxPoints: null,  
      color: 'from-cyan-500 via-blue-600 to-indigo-950',  
      badgeBg: 'bg-cyan-600', 
      textColor: 'text-cyan-100', 
      icon: '👑', 
      spendMin: 400000000,
      spendMax: null,
      perks: [
        'Semua benefit Platinum (Top Executive Tier)',
        'Termin pembayaran B2B (NET 30 Hari)',
        'Prioritas pengiriman instan same-day',
        'Hotline teknis 24/7 & asistensi instalasi on-site',
        'Bonus 30% Poin belanja per order',
        'Hadiah corporate anniversary eksklusif'
      ] 
    },
  ];
  // Revisi 3: Pemisahan data poin & sumber level aktif
  const pointsBalance = user?.points_balance ?? user?.points ?? 0; // Saldo reward aktif (spendable)
  const tierPoints = user?.tier_points ?? user?.tierPoints ?? user?.points ?? 0; // Poin kualifikasi tier tahun berjalan
  const currentTierName = user?.current_tier || user?.memberTier || 'Platinum';
  const tierLower = currentTierName.toLowerCase();

  // Status badge & perks aktif dibaca dari current_tier (Revisi 3), bukan dihitung ulang dari tier_points
  let currentLevelIndex = 0;
  if (tierLower.includes('diamond')) {
    currentLevelIndex = 5;
  } else if (tierLower.includes('platinum')) {
    currentLevelIndex = 4;
  } else if (tierLower.includes('gold')) {
    currentLevelIndex = 3;
  } else if (tierLower.includes('silver')) {
    currentLevelIndex = 2;
  } else if (tierLower.includes('bronze')) {
    currentLevelIndex = 1;
  } else {
    currentLevelIndex = 0;
  }

  // Revisi 3: Pemisahan "Nilai Saldo" vs "Dapat Ditukar Sekarang"
  const nilaiSaldoRupiah = pointsBalance * 100;
  const monthlyQuotaLeft = user?.monthlyQuotaLeft ?? 5000;
  const maturePoints = user?.maturePoints ?? pointsBalance; // Poin berusia >= 30 hari
  const canRedeemNowPoints = maturePoints >= 500 ? Math.min(maturePoints, monthlyQuotaLeft) : 0;
  const canRedeemNowRupiah = canRedeemNowPoints * 100;

  const [activeLoyaltyIdx, setActiveLoyaltyIdx] = useState(currentLevelIndex);

  useEffect(() => {
    setActiveLoyaltyIdx(currentLevelIndex);
  }, [currentLevelIndex]);

  // Address management state
  const [showAddressModal, setShowAddressModal] = useState(false);
  const [editingAddress, setEditingAddress] = useState(null);
  const [addrForm, setAddrForm] = useState({
    label: 'Rumah',
    recipientName: '',
    phone: '',
    province: 'DKI Jakarta',
    city: 'Jakarta Selatan',
    address: '',
    postalCode: '',
    isDefault: false
  });

  // All reviews from localStorage across all products
  const [myReviews, setMyReviews] = useState([]);

  // Product name -> slug lookup map (built from products-index at mount)
  const [productSlugs, setProductSlugs] = useState({});

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const tabParam = params.get('tab');
    if (tabParam) {
      setActiveTab(tabParam);
    }
  }, [location.search]);

  useEffect(() => {
    // Build name -> slug lookup from products-index (fast, synchronous)
    const slugMap = {};
    productsIndex.forEach(p => {
      slugMap[p.name.toLowerCase()] = p.slug;
    });
    setProductSlugs(slugMap);
  }, []);

  useEffect(() => {
    if (!user) {
      navigate('/login');
    } else {
      getProducts({ per_page: 50 }).then(res => {
        const filtered = (res.data || []).filter(p => 
          wishlist.includes(`prod-${p.id}`) || 
          wishlist.includes(p.id) ||
          wishlist.includes(p.sku)
        );
        setWishlistProducts(filtered);
      });
      // Load all reviews from localStorage
      const allReviews = [];
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith('accommerce_reviews_')) {
          try {
            const items = JSON.parse(localStorage.getItem(key) || '[]');
            const userItems = items
              .filter(r => r.userEmail === user.email)
              .map(r => ({ ...r, productKey: key.replace('accommerce_reviews_', '') }));
            allReviews.push(...userItems);
          } catch {}
        }
      }
      setMyReviews(allReviews);
    }
  }, [user, navigate, wishlist]);

  if (!user) return null;

  function handleLogout() {
    logout();
    navigate('/');
  }

  // Point redemption actions
  function handleRedeemWallet(points, rupiah) {
    if ((user.points || 0) < points) {
      setFeedback({ type: 'error', message: 'Poin Anda tidak mencukupi untuk penukaran ini.' });
      return;
    }
    const success = redeemPointsForWallet(points, rupiah);
    if (success) {
      setFeedback({ type: 'success', message: `Berhasil menukar ${points} Poin menjadi saldo E-Wallet Rp ${rupiah.toLocaleString('id-ID')}!` });
    }
  }

  function handleRedeemVoucher(points, voucher) {
    if ((user.points || 0) < points) {
      setFeedback({ type: 'error', message: 'Poin Anda tidak mencukupi untuk voucher ini.' });
      return;
    }
    const success = redeemPointsForVoucher(points, voucher);
    if (success) {
      setFeedback({ type: 'success', message: `Berhasil menukar ${points} Poin dengan ${voucher.title}!` });
    }
  }

  // Address Handlers
  function handleOpenAddAddress() {
    setEditingAddress(null);
    setAddrForm({
      label: 'Rumah',
      recipientName: user?.name || '',
      phone: user?.phone || '',
      province: 'DKI Jakarta',
      city: 'Jakarta Selatan',
      address: '',
      postalCode: '',
      isDefault: (user?.addresses || []).length === 0
    });
    setShowAddressModal(true);
  }

  function handleOpenEditAddress(addr) {
    setEditingAddress(addr);
    setAddrForm({
      label: addr.label || 'Rumah',
      recipientName: addr.recipientName || '',
      phone: addr.phone || '',
      province: addr.province || 'DKI Jakarta',
      city: addr.city || 'Jakarta Selatan',
      address: addr.address || '',
      postalCode: addr.postalCode || '',
      isDefault: !!addr.isDefault
    });
    setShowAddressModal(true);
  }

  function handleSaveAddress(e) {
    e.preventDefault();
    if (!addrForm.recipientName.trim() || !addrForm.phone.trim() || !addrForm.address.trim()) {
      setFeedback({ type: 'error', message: 'Mohon lengkapi nama penerima, nomor telepon, dan alamat lengkap.' });
      return;
    }
    saveAddress({
      ...(editingAddress ? { id: editingAddress.id } : {}),
      ...addrForm
    });
    setShowAddressModal(false);
    setFeedback({
      type: 'success',
      message: editingAddress ? 'Alamat berhasil diperbarui!' : 'Alamat baru berhasil ditambahkan!'
    });
  }

  function handleDeleteAddress(addrId) {
    if (window.confirm('Apakah Anda yakin ingin menghapus alamat ini?')) {
      deleteAddress(addrId);
      setFeedback({ type: 'success', message: 'Alamat berhasil dihapus.' });
    }
  }

  function handleSetDefaultAddress(addrId) {
    setDefaultAddress(addrId);
    setFeedback({ type: 'success', message: 'Alamat utama berhasil diperbarui.' });
  }

  // Invoice Handler
  function handleOpenInvoice(order) {
    setSelectedInvoiceOrder(order);
    setShowInvoiceModal(true);
  }

  // Review Handlers
  function handleOpenReview(product, order) {
    setReviewModalData({ product, order });
    setShowReviewModal(true);
  }

  function handleReviewSubmitted(newReview) {
    const key = `accommerce_reviews_${newReview.productId}`;
    try {
      const existing = JSON.parse(localStorage.getItem(key) || '[]');
      localStorage.setItem(key, JSON.stringify([newReview, ...existing]));
    } catch {}

    const itemKey = `${newReview.orderId}_${newReview.productName}`;
    if (markOrderItemReviewed) {
      markOrderItemReviewed(itemKey);
    }

    setMyReviews(prev => [newReview, ...prev]);
    setFeedback({ type: 'success', message: 'Ulasan Anda berhasil dikirim! Bonus +50 Poin ditambahkan ke akun Anda.' });
  }

  // E-Wallet Claim Handler
  function handleClaimEwalletSubmit(e) {
    e.preventDefault();
    if (!ewalletPhone.trim() || !ewalletAccountName.trim()) {
      setEwalletFeedback({ type: 'error', message: 'Mohon lengkapi nomor telepon dan nama pemilik akun e-wallet.' });
      return;
    }
    if ((user?.points || 0) < selectedEwalletPackage.points) {
      setEwalletFeedback({ type: 'error', message: `Poin Anda tidak mencukupi untuk menukar ${selectedEwalletPackage.points} Poin.` });
      return;
    }
    const result = claimEwalletPoints({
      platform: ewalletPlatform,
      phone: ewalletPhone,
      accountName: ewalletAccountName,
      points: selectedEwalletPackage.points,
      rupiah: selectedEwalletPackage.rupiah
    });
    if (result.success) {
      setShowEwalletForm(true);
      setEwalletFeedback({
        type: 'success',
        message: `Klaim saldo ${ewalletPlatform} senilai Rp ${selectedEwalletPackage.rupiah.toLocaleString('id-ID')} berhasil diproses ke nomor ${ewalletPhone} atas nama ${ewalletAccountName}!`
      });
    }
  }

  const activeVouchersCount = user.vouchers ? user.vouchers.length : 3;
  const pendingOrdersCount = (user.orders || []).filter(o => o.status === 'Dalam Pengiriman' || o.status === 'Diproses').length;
  const pendingReviewsCount = user.reviewsPending ?? 12;

  return (
    <main className="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8 min-h-[75vh]">
      {/* Top Banner Card matching user design */}
      <div className="w-full bg-[#0a3875] text-white rounded-2xl p-6 sm:p-8 mb-8 relative overflow-hidden shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-3 z-10">
          <div className="inline-block bg-[#164e9a] text-white text-[11px] font-semibold tracking-wider uppercase px-3 py-1 rounded">
            {user.current_tier ? `MEMBER ${user.current_tier.toUpperCase()}` : (user.memberTier || 'MEMBER PLATINUM')}
          </div>
          <h1 className="text-3xl sm:text-4xl font-bold tracking-tight">{user.name || 'John Doe'}</h1>
          <p className="text-blue-100 text-[13px] sm:text-[14px]">
            Member sejak {user.memberSince || '2024'} <span className="mx-1">|</span> {user.email || 'johndoe@email.com'}
          </p>
        </div>

        {/* Balance & Points Info: Revisi 3 (Nilai Saldo & Dapat Ditukar Sekarang) */}
        <div className="z-10 flex flex-wrap items-center gap-4 sm:gap-5 md:text-right">
          {/* Card 1: Saldo Poin */}
          <div className="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/20">
            <span className="text-[11px] font-medium text-blue-200 block">Saldo Poin</span>
            <div className="flex items-center gap-1.5 md:justify-end">
              <span className="material-symbols-outlined text-amber-300 text-[18px]">stars</span>
              <span className="text-xl sm:text-2xl font-bold text-amber-300">{pointsBalance.toLocaleString('id-ID')}</span>
              <span className="text-xs text-blue-200">pts</span>
            </div>
            <span className="text-[10px] text-blue-200/80 block mt-0.5">
              Nilai Saldo: Rp {nilaiSaldoRupiah.toLocaleString('id-ID')}
            </span>
          </div>

          {/* Card 2: Dapat Ditukar Sekarang */}
          <div className="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-emerald-400/30">
            <span className="text-[11px] font-medium text-emerald-200 block">Dapat Ditukar Sekarang</span>
            <div className="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
              Rp {canRedeemNowRupiah.toLocaleString('id-ID')}
            </div>
            <span className="text-[10px] text-emerald-100/80 mt-0.5 block">
              {canRedeemNowPoints > 0 ? `${canRedeemNowPoints.toLocaleString('id-ID')} Pts • Usia ≥30 hr` : 'Min. 500 Pts & usia 30 hr'}
            </span>
          </div>
        </div>

        {/* Decorative background glow */}
        <div className="absolute -right-20 -bottom-20 w-80 h-80 bg-blue-600/30 rounded-full blur-3xl pointer-events-none" />
      </div>

      {/* Notification Feedback */}
      {feedback && (
        <div className={`mb-6 p-4 rounded-xl flex items-center justify-between transition-all ${feedback.type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800'}`}>
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[20px]">{feedback.type === 'success' ? 'check_circle' : 'error'}</span>
            <span className="text-sm font-medium">{feedback.message}</span>
          </div>
          <button onClick={() => setFeedback(null)} className="text-xs font-semibold underline ml-4">Tutup</button>
        </div>
      )}

      {/* Main Grid: Sidebar Menu + Tab Content */}
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
        {/* Left Sidebar Menu */}
        <aside className="lg:col-span-1 bg-white rounded-xl border border-border-subtle p-3 shadow-sm">
          <nav className="flex flex-col gap-1">
            <button
              onClick={() => { setActiveTab('dashboard'); setFeedback(null); }}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-colors text-left ${activeTab === 'dashboard' ? 'bg-blue-50 text-primary border-l-4 border-primary font-bold' : 'text-text-primary hover:bg-surface'}`}
            >
              <span className="material-symbols-outlined text-[20px]">dashboard</span>
              Dashboard Akun
            </button>

            <button
              onClick={() => { setActiveTab('orders'); setFeedback(null); }}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-colors text-left ${activeTab === 'orders' ? 'bg-blue-50 text-primary border-l-4 border-primary font-bold' : 'text-text-secondary hover:bg-surface hover:text-text-primary'}`}
            >
              <span className="material-symbols-outlined text-[20px]">local_mall</span>
              Riwayat Pesanan
            </button>

            <button
              onClick={() => { setActiveTab('addresses'); setFeedback(null); }}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-colors text-left ${activeTab === 'addresses' ? 'bg-blue-50 text-primary border-l-4 border-primary font-bold' : 'text-text-secondary hover:bg-surface hover:text-text-primary'}`}
            >
              <span className="material-symbols-outlined text-[20px]">location_on</span>
              Alamat Saya
              {(user.addresses || []).length > 0 && (
                <span className="ml-auto bg-blue-100 text-primary text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                  {(user.addresses || []).length}
                </span>
              )}
            </button>

            <button
              onClick={() => { setActiveTab('points'); setFeedback(null); }}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-colors text-left ${activeTab === 'points' ? 'bg-blue-50 text-primary border-l-4 border-primary font-bold' : 'text-text-secondary hover:bg-surface hover:text-text-primary'}`}
            >
              <span className="material-symbols-outlined text-[20px] text-amber-500">stars</span>
              Tukar Poin & Reward
            </button>

            <button
              onClick={() => { setActiveTab('vouchers'); setFeedback(null); }}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-colors text-left ${activeTab === 'vouchers' ? 'bg-blue-50 text-primary border-l-4 border-primary font-bold' : 'text-text-secondary hover:bg-surface hover:text-text-primary'}`}
            >
              <span className="material-symbols-outlined text-[20px]">confirmation_number</span>
              Voucher & Promo
            </button>

            <button
              onClick={() => { setActiveTab('wishlist'); setFeedback(null); }}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-colors text-left ${activeTab === 'wishlist' ? 'bg-blue-50 text-primary border-l-4 border-primary font-bold' : 'text-text-secondary hover:bg-surface hover:text-text-primary'}`}
            >
              <span className="material-symbols-outlined text-[20px]">favorite</span>
              Wishlist Produk
            </button>

            <button
              onClick={() => { setActiveTab('reviews'); setFeedback(null); }}
              className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-colors text-left ${activeTab === 'reviews' ? 'bg-blue-50 text-primary border-l-4 border-primary font-bold' : 'text-text-secondary hover:bg-surface hover:text-text-primary'}`}
            >
              <span className="material-symbols-outlined text-[20px]">rate_review</span>
              Ulasan Saya
              {(user.reviewsPending || 0) > 0 && (
                <span className="ml-auto bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                  {user.reviewsPending}
                </span>
              )}
            </button>

            <div className="my-2 border-t border-border-subtle" />

            <button
              onClick={handleLogout}
              className="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-error hover:bg-red-50 transition-colors text-left"
            >
              <span className="material-symbols-outlined text-[20px]">logout</span>
              Keluar Akun
            </button>
          </nav>
        </aside>

        {/* Right Content Area */}
        <section className="lg:col-span-3 space-y-6">
          {/* TAB 1: DASHBOARD OVERVIEW */}
          {activeTab === 'dashboard' && (
            <>
              {/* 3 Metric Cards */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div 
                  onClick={() => setActiveTab('orders')}
                  className="bg-white rounded-xl border border-border-subtle p-5 shadow-sm hover:border-primary/50 transition-all cursor-pointer"
                >
                  <span className="text-[13px] text-text-secondary block font-medium">Pesanan Diproses</span>
                  <div className="text-2xl font-bold text-text-primary mt-1">
                    {pendingOrdersCount} Paket
                  </div>
                </div>

                <div 
                  onClick={() => setActiveTab('vouchers')}
                  className="bg-white rounded-xl border border-border-subtle p-5 shadow-sm hover:border-primary/50 transition-all cursor-pointer"
                >
                  <span className="text-[13px] text-text-secondary block font-medium">Voucher Aktif</span>
                  <div className="text-2xl font-bold text-text-primary mt-1">
                    {activeVouchersCount} Kupon
                  </div>
                </div>

                <div 
                  onClick={() => setActiveTab('orders')}
                  className="bg-white rounded-xl border border-border-subtle p-5 shadow-sm hover:border-primary/50 transition-all cursor-pointer"
                >
                  <span className="text-[13px] text-text-secondary block font-medium">Ulasan Belum Ditulis</span>
                  <div className="text-2xl font-bold text-text-primary mt-1">
                    {pendingReviewsCount} Ulasan
                  </div>
                </div>
              </div>

              {/* Point Banner in Dashboard */}
              <div className="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-amber-500 text-white rounded-full flex items-center justify-center shrink-0 shadow-sm">
                    <span className="material-symbols-outlined text-[28px]">stars</span>
                  </div>
                  <div>
                    <h4 className="font-bold text-text-primary text-[15px]">Tukar Poin Anda Menjadi Saldo & Diskon</h4>
                    <p className="text-[13px] text-text-secondary">Anda memiliki <strong className="text-amber-700">{(user.points || 0).toLocaleString('id-ID')} Poin</strong> yang siap ditukarkan!</p>
                  </div>
                </div>
                <button
                  onClick={() => setActiveTab('points')}
                  className="bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors whitespace-nowrap shadow-sm"
                >
                  Tukar Poin Sekarang
                </button>
              </div>

              {/* Pesanan Terakhir section matching screenshot */}
              <div className="bg-white rounded-xl border border-border-subtle p-6 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-[16px] font-bold text-text-primary">Pesanan Terakhir</h3>
                  <button 
                    onClick={() => setActiveTab('orders')}
                    className="text-xs font-semibold text-primary hover:underline"
                  >
                    Lihat Semua
                  </button>
                </div>

                {(user.orders || []).length > 0 ? (
                  <div className="space-y-4">
                    {(user.orders || []).slice(0, 2).map((order) => (
                      <div key={order.id} className="bg-blue-50/60 rounded-xl p-5 border border-blue-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                          <div className="text-xs font-bold text-primary mb-1 uppercase tracking-wider">{order.id}</div>
                          <h4 className="font-bold text-[15px] text-text-primary">{order.title}</h4>
                          <p className="text-xs text-text-secondary mt-1">
                            {order.date} • <span className="font-semibold text-text-primary">Rp {order.total.toLocaleString('id-ID')}</span>
                          </p>
                        </div>
                        <div className="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2">
                          <span className={`text-xs font-semibold px-3 py-1.5 rounded-md ${
                            order.status === 'Dalam Pengiriman' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'
                          }`}>
                            {order.status}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="py-8 text-center text-text-secondary border-2 border-dashed border-border-subtle rounded-lg">
                    <span className="material-symbols-outlined text-[48px] text-outline mb-2">inbox</span>
                    <p>Belum ada riwayat pesanan.</p>
                  </div>
                )}
              </div>
            </>
          )}

          {/* TAB 2: RIWAYAT PESANAN */}
          {activeTab === 'orders' && (
            <div className="bg-white rounded-xl border border-border-subtle p-6 shadow-sm">
              <h3 className="text-lg font-bold text-text-primary mb-6 flex items-center gap-2">
                <span className="material-symbols-outlined text-primary">local_mall</span>
                Riwayat Pesanan & Transaksi
              </h3>

              {(user.orders || []).length > 0 ? (
                <div className="space-y-4">
                  {(user.orders || []).map((order) => (
                    <div key={order.id} className="border border-border-subtle rounded-xl p-5 hover:border-primary/40 transition-colors">
                      <div className="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-border-subtle text-xs text-text-secondary">
                        <div className="flex items-center gap-3">
                          <span className="font-bold text-primary text-sm">{order.id}</span>
                          <span>{order.date}</span>
                        </div>
                        <span className={`font-semibold px-2.5 py-1 rounded-md ${
                          order.status === 'Dalam Pengiriman' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'
                        }`}>
                          {order.status}
                        </span>
                      </div>

                      <div className="py-3">
                        <h4 className="font-bold text-[15px] text-text-primary mb-2">{order.title}</h4>
                        <ul className="text-xs space-y-2">
                          {order.items.map((item, idx) => {
                            const itemKey = `${order.id}_${item.name}`;
                            const isReviewed = (user.reviewedOrderItems || []).includes(itemKey) ||
                              myReviews.some(r => r.orderId === order.id && r.productName === item.name);
                            return (
                              <li key={idx} className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2 rounded-lg bg-surface/50 border border-border-subtle">
                                <span className="flex-1 min-w-0 font-medium text-text-primary">
                                •{' '}
                                {productSlugs[item.name?.toLowerCase()] ? (
                                  <Link
                                    to={`/produk/${productSlugs[item.name.toLowerCase()]}`}
                                    className="text-primary hover:underline font-semibold"
                                  >
                                    {item.name}
                                  </Link>
                                ) : (
                                  item.name
                                )}{' '}({item.qty}x)
                              </span>
                                <div className="flex items-center gap-3 shrink-0">
                                  <span className="font-semibold text-text-primary">Rp {(item.price * item.qty).toLocaleString('id-ID')}</span>
                                  <button
                                    type="button"
                                    onClick={() => handleOpenReview({ name: item.name, id: `prod-${idx + 1}` }, order)}
                                    className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border transition-colors flex items-center gap-1 cursor-pointer ${
                                      isReviewed
                                        ? 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100'
                                        : 'border-primary text-primary hover:bg-primary hover:text-white shadow-2xs'
                                    }`}
                                  >
                                    <span className="material-symbols-outlined text-[13px]">{isReviewed ? 'check_circle' : 'rate_review'}</span>
                                    {isReviewed ? 'Sudah Diulas' : 'Beri Ulasan (+50 Poin)'}
                                  </button>
                                </div>
                              </li>
                            );
                          })}
                        </ul>
                      </div>

                      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-border-subtle">
                        <div>
                          <span className="text-xs text-text-secondary block">Total Belanja:</span>
                          <span className="text-base font-bold text-primary">Rp {order.total.toLocaleString('id-ID')}</span>
                        </div>
                        <div className="flex gap-2 self-end sm:self-auto">
                          <button
                            type="button"
                            onClick={() => handleOpenInvoice(order)}
                            className="text-xs font-bold px-3.5 py-2 border border-gray-300 rounded-lg hover:border-primary hover:text-primary text-text-primary transition-colors whitespace-nowrap flex items-center gap-1.5 cursor-pointer shadow-2xs bg-white"
                          >
                            <span className="material-symbols-outlined text-[16px] text-primary">receipt_long</span>
                            Download Invoice
                          </button>
                          <Link to="/katalog" className="text-xs font-semibold px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors whitespace-nowrap">
                            Beli Lagi
                          </Link>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="py-12 text-center text-text-secondary">
                  <span className="material-symbols-outlined text-[48px] text-outline mb-2">inbox</span>
                  <p>Belum ada riwayat pesanan yang ditemukan.</p>
                </div>
              )}
            </div>
          )}

          {/* TAB: ALAMAT SAYA (SHOPEE STYLE) */}
          {activeTab === 'addresses' && (
            <div className="bg-white rounded-xl border border-border-subtle p-6 shadow-sm">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-border-subtle">
                <div>
                  <h3 className="text-lg font-bold text-text-primary flex items-center gap-2">
                    <span className="material-symbols-outlined text-primary">location_on</span>
                    Alamat Saya
                  </h3>
                  <p className="text-xs text-text-secondary mt-1">
                    Kelola alamat pengiriman Anda untuk kemudahan dan kecepatan saat proses checkout.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={handleOpenAddAddress}
                  className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-hover shadow-sm transition-colors whitespace-nowrap self-start sm:self-auto"
                >
                  <span className="material-symbols-outlined text-[18px]">add</span>
                  Tambah Alamat Baru
                </button>
              </div>

              {(user.addresses || []).length > 0 ? (
                <div className="space-y-4">
                  {(user.addresses || []).map((addr) => (
                    <div
                      key={addr.id}
                      className={`border rounded-xl p-5 transition-colors ${
                        addr.isDefault
                          ? 'border-primary/50 bg-blue-50/20 shadow-xs'
                          : 'border-border-subtle hover:border-gray-300'
                      }`}
                    >
                      <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div className="space-y-1.5 flex-1">
                          <div className="flex flex-wrap items-center gap-2">
                            <span className="font-bold text-[15px] text-text-primary">
                              {addr.recipientName}
                            </span>
                            <span className="text-xs text-text-secondary font-medium">
                              | {addr.phone}
                            </span>
                            {addr.label && (
                              <span className="bg-gray-100 text-gray-700 text-[11px] font-semibold px-2 py-0.5 rounded border border-gray-200">
                                {addr.label}
                              </span>
                            )}
                            {addr.isDefault && (
                              <span className="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold px-2 py-0.5 rounded flex items-center gap-1">
                                <span className="material-symbols-outlined text-[12px]">check_circle</span>
                                Utama
                              </span>
                            )}
                          </div>
                          <p className="text-xs text-text-primary leading-relaxed">
                            {addr.address}
                          </p>
                          <p className="text-xs text-text-secondary">
                            {[addr.city, addr.province, addr.postalCode].filter(Boolean).join(', ')}
                          </p>
                        </div>

                        {/* Action buttons */}
                        <div className="flex flex-col sm:items-end gap-2 flex-shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-border-subtle">
                          <div className="flex items-center gap-3 text-xs">
                            <button
                              type="button"
                              onClick={() => handleOpenEditAddress(addr)}
                              className="font-semibold text-primary hover:underline flex items-center gap-1"
                            >
                              <span className="material-symbols-outlined text-[15px]">edit</span>
                              Ubah
                            </button>
                            {!addr.isDefault && (
                              <button
                                type="button"
                                onClick={() => handleDeleteAddress(addr.id)}
                                className="font-semibold text-error hover:underline flex items-center gap-1"
                              >
                                <span className="material-symbols-outlined text-[15px]">delete</span>
                                Hapus
                              </button>
                            )}
                          </div>
                          {!addr.isDefault && (
                            <button
                              type="button"
                              onClick={() => handleSetDefaultAddress(addr.id)}
                              className="text-xs font-semibold px-3 py-1.5 border border-border-subtle rounded-lg hover:border-primary hover:text-primary transition-colors text-text-secondary"
                            >
                              Atur sebagai Utama
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="py-12 text-center text-text-secondary border-2 border-dashed border-border-subtle rounded-xl">
                  <span className="material-symbols-outlined text-[48px] text-outline mb-2">location_off</span>
                  <p className="font-medium text-sm">Belum ada alamat yang tersimpan.</p>
                  <p className="text-xs text-text-secondary mt-1 mb-4">Tambahkan alamat untuk mempermudah transaksi pengiriman.</p>
                  <button
                    type="button"
                    onClick={handleOpenAddAddress}
                    className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-hover transition-colors"
                  >
                    <span className="material-symbols-outlined text-[16px]">add</span>
                    Tambah Alamat Sekarang
                  </button>
                </div>
              )}

              {/* Modal Tambah / Edit Alamat */}
              {showAddressModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                  <div className="bg-white rounded-2xl w-full max-w-lg shadow-2xl border border-border-subtle overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div className="flex items-center justify-between px-6 py-4 border-b border-border-subtle bg-surface">
                      <h4 className="font-bold text-base text-text-primary flex items-center gap-2">
                        <span className="material-symbols-outlined text-primary text-[20px]">
                          {editingAddress ? 'edit_location' : 'add_location'}
                        </span>
                        {editingAddress ? 'Ubah Alamat Pengiriman' : 'Tambah Alamat Baru'}
                      </h4>
                      <button
                        type="button"
                        onClick={() => setShowAddressModal(false)}
                        className="text-text-secondary hover:text-text-primary p-1 rounded-lg hover:bg-gray-200 transition-colors"
                      >
                        <span className="material-symbols-outlined text-[20px]">close</span>
                      </button>
                    </div>

                    <form onSubmit={handleSaveAddress} className="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                      {/* Label Alamat */}
                      <div>
                        <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1.5">
                          Tandai Sebagai
                        </label>
                        <div className="flex flex-wrap gap-2">
                          {['Rumah', 'Kantor', 'Toko', 'Apartemen'].map(tag => (
                            <button
                              key={tag}
                              type="button"
                              onClick={() => setAddrForm(f => ({ ...f, label: tag }))}
                              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                addrForm.label === tag
                                  ? 'bg-primary text-white shadow-xs'
                                  : 'bg-surface border border-border-subtle text-text-secondary hover:border-primary/50'
                              }`}
                            >
                              {tag}
                            </button>
                          ))}
                        </div>
                      </div>

                      {/* Recipient & Phone */}
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                          <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                            Nama Penerima *
                          </label>
                          <input
                            type="text"
                            required
                            value={addrForm.recipientName}
                            onChange={e => setAddrForm(f => ({ ...f, recipientName: e.target.value }))}
                            placeholder="Cth: Budi Santoso"
                            className="w-full bg-surface border border-border-subtle rounded-lg px-3.5 py-2 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                          />
                        </div>
                        <div>
                          <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                            Nomor Telepon *
                          </label>
                          <input
                            type="text"
                            required
                            value={addrForm.phone}
                            onChange={e => setAddrForm(f => ({ ...f, phone: e.target.value }))}
                            placeholder="Cth: 081234567890"
                            className="w-full bg-surface border border-border-subtle rounded-lg px-3.5 py-2 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                          />
                        </div>
                      </div>

                      {/* Province & City */}
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                          <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                            Provinsi *
                          </label>
                          <select
                            value={addrForm.province}
                            onChange={e => setAddrForm(f => ({ ...f, province: e.target.value }))}
                            className="w-full bg-surface border border-border-subtle rounded-lg px-3.5 py-2 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary cursor-pointer"
                          >
                            <option>DKI Jakarta</option>
                            <option>Banten</option>
                            <option>Jawa Barat</option>
                            <option>Jawa Tengah</option>
                            <option>Jawa Timur</option>
                            <option>DI Yogyakarta</option>
                            <option>Bali</option>
                            <option>Sumatera Utara</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                            Kota / Kabupaten *
                          </label>
                          <input
                            type="text"
                            required
                            value={addrForm.city}
                            onChange={e => setAddrForm(f => ({ ...f, city: e.target.value }))}
                            placeholder="Cth: Jakarta Selatan"
                            className="w-full bg-surface border border-border-subtle rounded-lg px-3.5 py-2 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                          />
                        </div>
                      </div>

                      {/* Full Address */}
                      <div>
                        <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                          Alamat Lengkap *
                        </label>
                        <textarea
                          rows="3"
                          required
                          value={addrForm.address}
                          onChange={e => setAddrForm(f => ({ ...f, address: e.target.value }))}
                          placeholder="Nama jalan, gedung, nomor rumah, RT/RW, kelurahan, kecamatan"
                          className="w-full bg-surface border border-border-subtle rounded-lg p-3 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                        />
                      </div>

                      {/* Postal Code */}
                      <div>
                        <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                          Kode Pos
                        </label>
                        <input
                          type="text"
                          value={addrForm.postalCode}
                          onChange={e => setAddrForm(f => ({ ...f, postalCode: e.target.value }))}
                          placeholder="Cth: 12345"
                          className="w-full bg-surface border border-border-subtle rounded-lg px-3.5 py-2 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                        />
                      </div>

                      {/* Checkbox Default */}
                      <label className="flex items-center gap-2 cursor-pointer pt-1">
                        <input
                          type="checkbox"
                          checked={addrForm.isDefault}
                          onChange={e => setAddrForm(f => ({ ...f, isDefault: e.target.checked }))}
                          className="w-4 h-4 rounded text-primary focus:ring-primary accent-primary"
                        />
                        <span className="text-xs font-medium text-text-primary">
                          Atur sebagai alamat utama
                        </span>
                      </label>

                      {/* Actions */}
                      <div className="flex items-center justify-end gap-3 pt-4 border-t border-border-subtle">
                        <button
                          type="button"
                          onClick={() => setShowAddressModal(false)}
                          className="px-4 py-2 text-xs font-semibold text-text-secondary hover:text-text-primary hover:bg-surface rounded-lg transition-colors"
                        >
                          Batal
                        </button>
                        <button
                          type="submit"
                          className="px-5 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-hover shadow-sm transition-colors"
                        >
                          Simpan Alamat
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* TAB 3: TUKAR POIN & REWARD (FEATURE BARU) */}
          {activeTab === 'points' && (
            <div className="space-y-6">
              {/* Point Status Banner — Tier-Colored with Level Carousel */}
              {(() => {
                const activeLevel = LOYALTY_LEVELS[activeLoyaltyIdx];
                const isCurrentLevel = activeLoyaltyIdx === currentLevelIndex;
                const nextLevel = currentLevelIndex < LOYALTY_LEVELS.length - 1 ? LOYALTY_LEVELS[currentLevelIndex + 1] : null;

                // Progress calculation towards next tier
                let pointsNeeded = 0;
                let rupiahNeeded = 0;
                let progressPct = 100;
                if (nextLevel) {
                  pointsNeeded = Math.max(0, nextLevel.minPoints - tierPoints);
                  rupiahNeeded = pointsNeeded * 10000; // 1 Poin = Rp 10.000 belanja
                  const rangeTotal = nextLevel.minPoints - LOYALTY_LEVELS[currentLevelIndex].minPoints;
                  const currentInRange = Math.max(0, tierPoints - LOYALTY_LEVELS[currentLevelIndex].minPoints);
                  progressPct = Math.min(100, Math.max(0, Math.round((currentInRange / rangeTotal) * 100)));
                }

                // If viewing a locked future level
                const targetPointsNeeded = Math.max(0, activeLevel.minPoints - tierPoints);
                const targetRupiahNeeded = targetPointsNeeded * 10000;

                return (
                  <div className={`bg-gradient-to-br ${activeLevel.color} text-white rounded-2xl shadow-md overflow-hidden transition-all duration-300`}>
                    {/* Level Carousel Header & Tabs with Slide Controls */}
                    <div className="flex items-center border-b border-white/20 bg-black/15">
                      {/* Prev Button */}
                      <button
                        type="button"
                        onClick={() => setActiveLoyaltyIdx(prev => Math.max(0, prev - 1))}
                        disabled={activeLoyaltyIdx === 0}
                        className="px-2.5 sm:px-3 py-2.5 text-white/70 hover:text-white disabled:opacity-20 disabled:cursor-not-allowed transition-colors cursor-pointer flex items-center justify-center shrink-0"
                        title="Level Sebelumnya"
                      >
                        <span className="material-symbols-outlined text-[20px]">chevron_left</span>
                      </button>

                      {/* Level Tabs (6 Tiers) */}
                      <div className="flex-1 flex overflow-x-auto scrollbar-none py-1">
                        {LOYALTY_LEVELS.map((lv, idx) => {
                          const isCurrent = idx === currentLevelIndex;
                          const isAchieved = idx < currentLevelIndex;
                          const isLocked = idx > currentLevelIndex;
                          const isSelected = activeLoyaltyIdx === idx;
                          return (
                            <button
                              key={lv.id}
                              type="button"
                              onClick={() => setActiveLoyaltyIdx(idx)}
                              className={`flex-1 min-w-[95px] sm:min-w-0 py-2 px-1.5 text-[11px] font-bold tracking-wider uppercase transition-all cursor-pointer flex items-center justify-center gap-1 whitespace-nowrap rounded-lg mx-0.5 ${
                                isSelected
                                  ? 'bg-white/25 text-white shadow-inner font-extrabold'
                                  : 'text-white/60 hover:text-white/95 hover:bg-white/10'
                              }`}
                            >
                              <span className="text-[13px]">{lv.icon}</span>
                              <span>{lv.label}</span>
                              {isCurrent && (
                                <span className="text-[9px] bg-emerald-500 text-white font-bold px-1.5 py-0.2 rounded-full ml-0.5">
                                  Anda
                                </span>
                              )}
                              {isAchieved && (
                                <span className="text-[9px] bg-white/20 text-white/90 px-1 py-0.2 rounded ml-0.5 hidden md:inline">
                                  ✓
                                </span>
                              )}
                              {isLocked && (
                                <span className="text-[9px] bg-black/25 text-white/50 px-1 py-0.2 rounded ml-0.5 hidden md:inline">
                                  🔒
                                </span>
                              )}
                            </button>
                          );
                        })}
                      </div>

                      {/* Next Button */}
                      <button
                        type="button"
                        onClick={() => setActiveLoyaltyIdx(prev => Math.min(LOYALTY_LEVELS.length - 1, prev + 1))}
                        disabled={activeLoyaltyIdx === LOYALTY_LEVELS.length - 1}
                        className="px-2.5 sm:px-3 py-2.5 text-white/70 hover:text-white disabled:opacity-20 disabled:cursor-not-allowed transition-colors cursor-pointer flex items-center justify-center shrink-0"
                        title="Level Berikutnya"
                      >
                        <span className="material-symbols-outlined text-[20px]">chevron_right</span>
                      </button>
                    </div>

                    {/* Banner Body */}
                    <div className="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2 flex-wrap">
                          <span className={`text-[11px] uppercase tracking-wider font-semibold ${activeLevel.textColor}`}>
                            Accommerce Loyalty Points — {activeLevel.label} Tier
                          </span>
                          {/* Info Button (? / !) */}
                          <button
                            type="button"
                            onClick={() => setShowLoyaltyInfo(v => !v)}
                            className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white/20 hover:bg-white/35 text-white text-[11px] font-semibold transition-colors cursor-pointer shrink-0 border border-white/30 shadow-xs"
                            title="Klik untuk melihat aturan lengkap program loyalitas"
                          >
                            <span className="material-symbols-outlined text-[14px]">help</span>
                            <span>Aturan & Info Program</span>
                          </button>
                        </div>

                        {/* Point Stats — Revisi 3 */}
                        {isCurrentLevel ? (
                          <div className="space-y-1.5">
                            {/* Row 1: Saldo Poin + Nilai Saldo */}
                            <div className="flex flex-wrap items-baseline gap-3">
                              <h3 className="text-3xl sm:text-4xl font-extrabold tracking-tight">
                                {pointsBalance.toLocaleString('id-ID')} Pts
                              </h3>
                              <span className="text-xs bg-white/20 px-2 py-0.5 rounded-full font-medium text-white/90">
                                Saldo Poin
                              </span>
                            </div>
                            <p className={`text-xs ${activeLevel.textColor}`}>
                              Nilai Saldo: <strong className="text-white font-bold">Rp {nilaiSaldoRupiah.toLocaleString('id-ID')}</strong> (1 Pts = Rp 100) • Berlaku 24 bulan, FIFO
                            </p>
                            {/* Row 2: Dapat Ditukar Sekarang */}
                            <p className="text-[11px] text-emerald-200 pt-0.5 flex items-center gap-1.5">
                              <span className="material-symbols-outlined text-[14px] text-emerald-300">swap_horiz</span>
                              Dapat Ditukar Sekarang: <strong className="text-white font-bold">Rp {canRedeemNowRupiah.toLocaleString('id-ID')}</strong>
                              <span className="text-white/50">{canRedeemNowPoints > 0 ? `(${canRedeemNowPoints.toLocaleString('id-ID')} Pts ≥30 hr)` : '— Min. 500 Pts & usia 30 hr'}</span>
                            </p>
                            {/* Row 3: Poin Tier */}
                            <p className="text-[11px] text-white/60 pt-0.5">
                              Poin Tier Tahun Ini: <strong className="text-white/80 font-bold">{tierPoints.toLocaleString('id-ID')} Pts</strong> (1 Jan – 31 Des 2026) • Tidak berkurang saat redeem
                            </p>
                          </div>
                        ) : (
                          <div>
                            <h3 className="text-2xl font-extrabold mt-1 flex items-center gap-2">
                              <span>{activeLevel.icon} {activeLevel.label} Tier</span>
                              {activeLoyaltyIdx < currentLevelIndex ? (
                                <span className="text-xs bg-emerald-500/90 text-white px-2 py-0.5 rounded-full font-medium">✓ Level Sudah Dilewati</span>
                              ) : (
                                <span className="text-xs bg-black/40 text-white/90 px-2 py-0.5 rounded-full font-medium">🔒 Level Belum Tercapai</span>
                              )}
                            </h3>
                            <p className={`text-xs mt-1 ${activeLevel.textColor}`}>
                              {activeLoyaltyIdx > currentLevelIndex
                                ? `Kualifikasi: Min. ${activeLevel.minPoints.toLocaleString('id-ID')} Poin tier (Akumulasi belanja ≥ Rp ${(activeLevel.spendMin / 1000000).toFixed(0)} Juta)`
                                : `Tingkat awal membership dengan keuntungan yang telah Anda miliki`}
                            </p>
                          </div>
                        )}

                        {/* Progress Bar: Kurang Rp X lagi untuk naik tier */}
                        {isCurrentLevel && nextLevel && (
                          <div className="mt-4 max-w-lg bg-black/25 backdrop-blur-sm p-3.5 rounded-xl border border-white/20 shadow-xs">
                            <div className="flex flex-wrap justify-between items-center text-xs mb-1.5 font-medium gap-1">
                              <span className="flex items-center gap-1.5 text-white/90">
                                <span>Menuju {nextLevel.icon} <strong>{nextLevel.label}</strong></span>
                              </span>
                              <span className="font-extrabold text-amber-200 bg-amber-950/40 px-2 py-0.5 rounded border border-amber-300/30">
                                Kurang Rp {rupiahNeeded.toLocaleString('id-ID')} lagi ({pointsNeeded.toLocaleString('id-ID')} Pts)
                              </span>
                            </div>
                            <div className="h-2.5 bg-white/20 rounded-full overflow-hidden p-0.5">
                              <div
                                className="h-full bg-gradient-to-r from-amber-300 via-emerald-300 to-emerald-400 rounded-full transition-all duration-500 shadow-xs"
                                style={{ width: `${progressPct}%` }}
                              />
                            </div>
                            <div className="flex justify-between text-[10px] text-white/70 mt-1">
                              <span>{tierPoints.toLocaleString('id-ID')} Poin tercapai ({progressPct}%)</span>
                              <span>Target: {nextLevel.minPoints.toLocaleString('id-ID')} Poin</span>
                            </div>
                          </div>
                        )}

                        {/* Progress display when viewing unachieved level */}
                        {!isCurrentLevel && activeLoyaltyIdx > currentLevelIndex && (
                          <div className="mt-4 max-w-lg bg-black/25 backdrop-blur-sm p-3.5 rounded-xl border border-white/20">
                            <div className="flex items-center justify-between text-xs font-semibold mb-1">
                              <span>Target ke {activeLevel.label}</span>
                              <span className="text-amber-200">
                                Butuh Belanja Tambahan Rp {targetRupiahNeeded.toLocaleString('id-ID')}
                              </span>
                            </div>
                            <p className="text-[11px] text-white/70">
                              Kumpulkan {targetPointsNeeded.toLocaleString('id-ID')} Poin tier lagi untuk membuka seluruh keuntungan eksklusif level {activeLevel.label}.
                            </p>
                          </div>
                        )}

                        {/* Diamond Highest Level notice */}
                        {isCurrentLevel && !nextLevel && (
                          <div className="mt-3 inline-flex items-center gap-1.5 bg-white/20 px-3 py-1 rounded-full text-xs font-bold text-white border border-white/30">
                            <span>👑 Level Tertinggi Tercapai (Diamond Member)</span>
                          </div>
                        )}
                      </div>

                      {/* Right: Perks list */}
                      <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 text-xs text-white/95 max-w-sm w-full lg:w-80 shrink-0">
                        <div className="flex items-center justify-between border-b border-white/15 pb-2 mb-2.5">
                          <p className="font-bold text-white text-[12px] uppercase tracking-wide flex items-center gap-1.5">
                            <span>{activeLevel.icon}</span>
                            <span>Keuntungan {activeLevel.label}</span>
                          </p>
                          <span className="text-[10px] font-semibold text-white/80 bg-white/15 px-2 py-0.5 rounded">
                            {activeLevel.maxPoints ? `≥ ${activeLevel.minPoints.toLocaleString('id-ID')} pts` : `≥ 40.000 pts`}
                          </span>
                        </div>
                        <ul className="space-y-2">
                          {activeLevel.perks.map((p, i) => (
                            <li key={i} className="flex items-start gap-2 leading-relaxed">
                              <span className="material-symbols-outlined text-[14px] text-emerald-300 mt-0.5 shrink-0">check_circle</span>
                              <span>{p}</span>
                            </li>
                          ))}
                        </ul>
                      </div>
                    </div>

                    {/* Loyalty Info Popup */}
                    {showLoyaltyInfo && (
                      <div className="mx-5 mb-5 p-5 bg-black/40 backdrop-blur-md rounded-2xl text-xs text-white/95 border border-white/20 space-y-4 shadow-xl">
                        <div className="flex items-center justify-between border-b border-white/20 pb-2">
                          <span className="font-bold text-white text-sm sm:text-base flex items-center gap-2">
                            <span className="material-symbols-outlined text-[20px] text-amber-300">verified</span>
                            Ketentuan & Panduan Program Loyalitas Accommerce
                          </span>
                          <button onClick={() => setShowLoyaltyInfo(false)} className="text-white/60 hover:text-white p-1 cursor-pointer">
                            <span className="material-symbols-outlined text-[20px]">close</span>
                          </button>
                        </div>

                        {/* 6 Tiers Grid Table */}
                        <div>
                          <p className="text-[11px] font-bold uppercase tracking-wider text-white/80 mb-2">Tingkatan Level & Threshold Belanja</p>
                          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                            {LOYALTY_LEVELS.map(lv => (
                              <div key={lv.id} className="bg-white/10 rounded-xl p-2.5 text-center border border-white/15">
                                <div className="text-xl mb-1">{lv.icon}</div>
                                <div className="font-extrabold text-white text-[12px]">{lv.label}</div>
                                <div className="text-white/90 font-bold text-[11px] mt-0.5">
                                  {lv.maxPoints ? `${lv.minPoints.toLocaleString('id-ID')}–${lv.maxPoints.toLocaleString('id-ID')} Pts` : `≥ ${lv.minPoints.toLocaleString('id-ID')} Pts`}
                                </div>
                                <div className="text-white/60 text-[10px] mt-0.5">
                                  {lv.spendMin ? `Belanja ≥ Rp ${(lv.spendMin / 1000000).toFixed(0)} Juta` : 'Belanja awal'}
                                </div>
                              </div>
                            ))}
                          </div>
                        </div>

                        {/* 6 Rules List — Revisi 3 */}
                        <div className="bg-white/10 rounded-xl p-4 space-y-2 border border-white/15">
                          <p className="font-bold text-white text-xs uppercase tracking-wide">6 Aturan Utama Sistem Loyalitas (Revisi 3):</p>
                          <ol className="list-decimal list-inside space-y-1.5 text-[11px] text-white/90 leading-relaxed">
                            <li><strong>Status Level dari <code>current_tier</code></strong>: Badge dan perks aktif dibaca dari status level yang tersimpan, bukan dihitung ulang dari tier_points. Upgrade aktif seketika saat threshold tier_points tercapai. Evaluasi akhir tahun (31 Des): jika tidak memenuhi syarat, turun maks. 1 level.</li>
                            <li><strong>3 Kolom Data Poin</strong>: <em>tier_points</em> = akumulasi belanja murni tahun ini (direset 1 Jan, tidak berkurang saat redeem). <em>points_balance</em> = saldo reward aktif (berlaku 24 bulan FIFO, berkurang saat redeem). <em>current_tier</em> = sumber tunggal badge & fasilitas.</li>
                            <li><strong>Faktur Pajak PKP Terbuka untuk Semua</strong>: Faktur Pajak resmi diberikan kepada seluruh member terverifikasi yang melampirkan NPWP & SPPKP resmi perusahaan, tidak terbatas pada level tertentu.</li>
                            <li><strong>Perk Tier Bronze</strong>: Mendapatkan voucher nominal potongan Rp 25.000 / diskon khusus aksesori audio visual dan gratis ongkir 1x/bulan.</li>
                            <li><strong>Keuntungan B2B Enterprise (Gold, Platinum, Diamond)</strong>: Akses Project Pricing, prioritas alokasi stok, termin pembayaran B2B (NET 14 / NET 30 hari setelah verifikasi kredit), serta garansi & RMA Express.</li>
                            <li><strong>Retur & Poin</strong>: Jika pesanan diretur: tier_points ditarik poin dasar (level tidak langsung turun). points_balance ditarik poin dasar + bonus. Jika saldo tidak cukup, saldo boleh minus (utang poin, dipotong dari perolehan berikutnya).</li>
                          </ol>
                        </div>

                        <div className="text-white/70 text-[10px] pt-1 flex items-center gap-1.5">
                          <span className="material-symbols-outlined text-[15px] text-amber-300">lightbulb</span>
                          <span>Formula: Belanja Rp 10.000 = 1 Poin dasar. Bonus tier (5–30%) hanya masuk Saldo Poin. Nilai penukaran: 1 Pts = Rp 100. Limit e-wallet: Maks 5.000 Pts/bulan, poin usia ≥30 hr.</span>
                        </div>
                      </div>
                    )}
                  </div>
                );
              })()}

              {/* Sub-section 1: Klaim Saldo E-Wallet — Collapsible Accordion */}
              <div className="bg-white rounded-xl border border-border-subtle shadow-sm overflow-hidden">
                {/* Accordion Header / Toggle Button — Mobile-Optimized */}
                <button
                  type="button"
                  onClick={() => setShowEwalletForm(v => !v)}
                  className="w-full p-4 sm:px-6 sm:py-4 hover:bg-surface/50 transition-colors cursor-pointer text-left"
                >
                  <div className="flex items-center justify-between gap-3">
                    {/* Left: Icon + Title */}
                    <div className="flex items-center gap-3 min-w-0">
                      <div className="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span className="material-symbols-outlined text-[22px]">account_balance_wallet</span>
                      </div>
                      <div className="min-w-0">
                        <h4 className="text-sm sm:text-base font-bold text-text-primary leading-tight">
                          Klaim Saldo E-Wallet dari Poin
                        </h4>
                        <p className="text-[11px] sm:text-xs text-text-secondary mt-0.5 truncate">
                          Tukar poin loyalty Anda menjadi saldo e-wallet
                        </p>
                      </div>
                    </div>

                    {/* Right: Badge (Desktop) & Chevron Button */}
                    <div className="flex items-center gap-2 shrink-0">
                      <span className="hidden sm:inline-flex text-[11px] font-bold text-primary bg-blue-50 border border-blue-100 px-2.5 py-1 rounded-lg whitespace-nowrap">
                        {(user.points || 0).toLocaleString('id-ID')} Poin tersedia
                      </span>
                      <div className={`w-8 h-8 rounded-full bg-surface sm:bg-transparent flex items-center justify-center transition-transform duration-200 ${showEwalletForm ? 'rotate-180' : 'rotate-0'}`}>
                        <span className="material-symbols-outlined text-text-secondary text-[22px]">
                          expand_more
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Mobile-only secondary info bar to give title full breathing room */}
                  <div className="flex sm:hidden items-center justify-between mt-2.5 pt-2 border-t border-border-subtle/70 text-[11px]">
                    <span className="text-text-secondary flex items-center gap-1.5">
                      <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                      Poin Tersedia:
                    </span>
                    <span className="font-bold text-primary bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-md">
                      {(user.points || 0).toLocaleString('id-ID')} Poin
                    </span>
                  </div>
                </button>

                {/* Collapsible Form Body — Mobile-Optimized */}
                {showEwalletForm && (
                  <div className="border-t border-border-subtle px-3.5 sm:px-6 pb-5 pt-4 space-y-4">
                    {/* Notifikasi Hasil Klaim Saldo E-Wallet Langsung di Atas Form */}
                    {ewalletFeedback && (
                      <div className={`p-4 rounded-xl flex items-start justify-between gap-3 transition-all ${
                        ewalletFeedback.type === 'success'
                          ? 'bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-xs'
                          : 'bg-red-50 border border-red-200 text-red-900 shadow-xs'
                      }`}>
                        <div className="flex items-start gap-2.5">
                          <span className={`material-symbols-outlined text-[22px] mt-0.5 shrink-0 ${
                            ewalletFeedback.type === 'success' ? 'text-emerald-600' : 'text-red-600'
                          }`}>
                            {ewalletFeedback.type === 'success' ? 'verified' : 'error'}
                          </span>
                          <div>
                            <h5 className="text-xs sm:text-sm font-bold">
                              {ewalletFeedback.type === 'success' ? `Klaim Saldo ${ewalletPlatform} Berhasil!` : 'Klaim Saldo Gagal'}
                            </h5>
                            <p className="text-[11px] sm:text-xs mt-0.5 leading-relaxed opacity-90">
                              {ewalletFeedback.message}
                            </p>
                          </div>
                        </div>
                        <button
                          type="button"
                          onClick={() => setEwalletFeedback(null)}
                          className={`text-xs font-bold underline shrink-0 cursor-pointer ${
                            ewalletFeedback.type === 'success' ? 'text-emerald-700 hover:text-emerald-900' : 'text-red-700 hover:text-red-900'
                          }`}
                        >
                          Tutup
                        </button>
                      </div>
                    )}

                    <form onSubmit={handleClaimEwalletSubmit} className="p-3.5 sm:p-5 bg-surface/60 rounded-xl border border-border-subtle space-y-4">
                      {/* Step 1: Pilih Platform */}
                      <div>
                        <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-2">
                          1. Pilih Platform E-Wallet Tujuan
                        </label>
                        <div className="grid grid-cols-5 gap-1 sm:gap-2.5">
                          {[
                            { name: 'GoPay', shortName: 'GoPay', color: 'bg-emerald-600 text-white', icon: 'payments' },
                            { name: 'OVO', shortName: 'OVO', color: 'bg-purple-700 text-white', icon: 'credit_card' },
                            { name: 'DANA', shortName: 'DANA', color: 'bg-sky-500 text-white', icon: 'account_balance_wallet' },
                            { name: 'ShopeePay', shortName: 'Shopee', color: 'bg-orange-500 text-white', icon: 'shopping_bag' },
                            { name: 'LinkAja', shortName: 'LinkAja', color: 'bg-red-600 text-white', icon: 'send_to_mobile' },
                          ].map((pl) => (
                            <button
                              key={pl.name}
                              type="button"
                              onClick={() => setEwalletPlatform(pl.name)}
                              className={`py-2 px-1 sm:p-3 rounded-xl font-bold text-[10px] sm:text-xs flex flex-col items-center justify-center gap-1 transition-all cursor-pointer border ${
                                ewalletPlatform === pl.name
                                  ? `${pl.color} ring-2 ring-primary ring-offset-1 shadow-sm`
                                  : 'bg-white border-border-subtle text-text-primary hover:border-primary/50'
                              }`}
                            >
                              <span className="material-symbols-outlined text-[18px] sm:text-[20px]">{pl.icon}</span>
                              <span className="truncate max-w-full">{pl.shortName}</span>
                            </button>
                          ))}
                        </div>
                      </div>

                      {/* Step 2: Nomor HP & Nama Pemilik */}
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                          <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                            2. Nomor HP Akun {ewalletPlatform} *
                          </label>
                          <input
                            type="text"
                            required
                            value={ewalletPhone}
                            onChange={(e) => setEwalletPhone(e.target.value)}
                            placeholder="Cth: 081234567890"
                            className="w-full bg-white border border-border-subtle rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary font-mono"
                          />
                        </div>
                        <div>
                          <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                            3. Nama Pemilik Akun *
                          </label>
                          <input
                            type="text"
                            required
                            value={ewalletAccountName}
                            onChange={(e) => setEwalletAccountName(e.target.value)}
                            placeholder="Cth: Budi Santoso"
                            className="w-full bg-white border border-border-subtle rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                          />
                        </div>
                      </div>

                      {/* Step 3: Pilih Paket Nominal */}
                      <div>
                        <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-2">
                          4. Pilih Nominal Penukaran Poin
                        </label>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                          {[
                            { points: 500, rupiah: 50000, label: '500 Poin' },
                            { points: 1000, rupiah: 100000, label: '1.000 Poin' },
                            { points: 2500, rupiah: 250000, label: '2.500 Poin' },
                            { points: 5000, rupiah: 500000, label: '5.000 Poin' },
                          ].map((pkg) => {
                            const isSelected = selectedEwalletPackage.points === pkg.points;
                            const isAffordable = (user.points || 0) >= pkg.points;
                            return (
                              <div
                                key={pkg.points}
                                onClick={() => setSelectedEwalletPackage(pkg)}
                                className={`p-2.5 sm:p-3.5 rounded-xl border cursor-pointer transition-all ${
                                  isSelected
                                    ? 'bg-blue-50/60 border-primary ring-1 ring-primary shadow-xs'
                                    : 'bg-white border-border-subtle hover:border-gray-300'
                                }`}
                              >
                                <div className="flex items-center justify-between mb-1">
                                  <span className="text-[10px] sm:text-xs font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">
                                    {pkg.label}
                                  </span>
                                  <input
                                    type="radio"
                                    name="ewallet_package"
                                    checked={isSelected}
                                    onChange={() => setSelectedEwalletPackage(pkg)}
                                    className="accent-primary cursor-pointer w-3.5 h-3.5"
                                  />
                                </div>
                                <div className="text-sm sm:text-lg font-extrabold text-text-primary">
                                  Rp {pkg.rupiah.toLocaleString('id-ID')}
                                </div>
                                <p className={`text-[10px] sm:text-[11px] mt-0.5 font-medium ${isAffordable ? 'text-emerald-600' : 'text-red-400'}`}>
                                  {isAffordable ? '✓ Poin cukup' : 'Poin kurang'}
                                </p>
                              </div>
                            );
                          })}
                        </div>
                      </div>

                      {/* Submit Button */}
                      <div className="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div className="text-xs text-text-secondary">
                          Poin saat ini: <strong className="text-primary font-bold">{(user.points || 0).toLocaleString('id-ID')} Pts</strong> • Biaya penukaran: <strong className="text-amber-700 font-bold">{selectedEwalletPackage.points} Pts</strong>
                          <p className="text-[10px] text-text-secondary/80 mt-1 flex items-center gap-1">
                            <span>🛡️ Batas cash-out: Maks. 1x per 30 hari (plafon s/d Rp 500.000) untuk akun terverifikasi.</span>
                          </p>
                        </div>
                        <button
                          type="submit"
                          disabled={(user.points || 0) < selectedEwalletPackage.points}
                          className="w-full sm:w-auto px-6 py-2.5 bg-primary hover:bg-primary-hover disabled:bg-gray-200 disabled:text-gray-400 text-white text-xs sm:text-sm font-bold rounded-xl shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-1.5"
                        >
                          <span className="material-symbols-outlined text-[18px]">verified</span>
                          Klaim Saldo {ewalletPlatform} Sekarang
                        </button>
                      </div>
                    </form>

                    {/* Status & Riwayat Klaim Saldo E-Wallet */}
                    <div className="border-t border-border-subtle pt-4">
                      <div className="flex items-center justify-between mb-3">
                        <h5 className="font-bold text-xs sm:text-sm text-text-primary flex items-center gap-1.5 sm:gap-2">
                          <span className="material-symbols-outlined text-emerald-600 text-[18px] sm:text-[20px]">history</span>
                          Riwayat Klaim Saldo E-Wallet
                        </h5>
                        <span className="text-[11px] sm:text-xs text-text-secondary">
                          {(user.ewalletClaims || []).length} transaksi
                        </span>
                      </div>

                      {(user.ewalletClaims || []).length > 0 ? (
                        <div className="space-y-2.5">
                          {(user.ewalletClaims || []).map((cl) => (
                            <div
                              key={cl.id}
                              className="p-3 sm:p-4 rounded-xl border border-border-subtle bg-white hover:border-gray-300 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3 shadow-2xs"
                            >
                              <div className="space-y-1">
                                <div className="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                  <span className="font-bold text-[10px] sm:text-xs font-mono text-primary bg-blue-50 px-1.5 py-0.5 rounded">
                                    {cl.id}
                                  </span>
                                  <span className="font-bold text-xs sm:text-sm text-text-primary">
                                    {cl.platform} • {cl.accountName}
                                  </span>
                                  <span className="text-[11px] font-mono text-text-secondary">
                                    ({cl.phone})
                                  </span>
                                  <span className="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] sm:text-[11px] font-bold px-2 py-0.2 rounded-full flex items-center gap-0.5">
                                    <span className="material-symbols-outlined text-[12px]">check_circle</span>
                                    {cl.status || 'Berhasil'}
                                  </span>
                                </div>
                                <p className="text-[10px] sm:text-[11px] text-text-secondary">
                                  {cl.date} • Ref: <span className="font-mono">{cl.refNumber || 'TRX-8927163'}</span>
                                </p>
                              </div>
                              <div className="sm:text-right shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 flex sm:flex-col justify-between items-baseline sm:items-end">
                                <div className="text-sm sm:text-base font-extrabold text-emerald-600">
                                  +Rp {(cl.amount || 0).toLocaleString('id-ID')}
                                </div>
                                <div className="text-[10px] sm:text-[11px] font-bold text-amber-700">
                                  -{cl.points} Poin
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <div className="py-6 text-center text-text-secondary border-2 border-dashed border-border-subtle rounded-xl">
                          <span className="material-symbols-outlined text-[32px] text-outline mb-1">receipt_long</span>
                          <p className="text-xs font-medium">Belum ada riwayat penukaran e-wallet.</p>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>

              {/* Sub-section 2: Tukar Poin ke Voucher Potongan Belanja (Rasio 1:1) */}
              <div className="bg-white rounded-xl border border-border-subtle p-6 shadow-sm">
                <div className="mb-4">
                  <h4 className="text-base font-bold text-text-primary flex items-center gap-2">
                    <span className="material-symbols-outlined text-amber-600">confirmation_number</span>
                    Tukar Poin Jadi Voucher Potongan Belanja (Rasio 1:1)
                  </h4>
                  <p className="text-xs text-text-secondary mt-0.5">
                    Nilai potongan 1:1 setara dengan nilai poin (1 Poin = Rp 100). Tanpa batasan kuota penarikan bulanan & langsung masuk ke tab Voucher & Promo.
                  </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  {/* Voucher 1: 750 Poin -> Rp 75.000 */}
                  <div className="border border-border-subtle rounded-xl p-4 bg-amber-50/40 flex flex-col justify-between">
                    <div>
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded">VOUCHER 75K</span>
                        <span className="text-xs font-bold text-amber-700">750 Pts</span>
                      </div>
                      <h5 className="font-bold text-text-primary text-sm">Potongan Rp 75.000 Aksesori & Hardware</h5>
                      <p className="text-xs text-text-secondary mt-1">Min. belanja Rp 750.000 • Berlaku 30 Hari</p>
                    </div>
                    <button
                      onClick={() => handleRedeemVoucher(750, {
                        id: `VCH-${Date.now()}`,
                        code: `ACC75K-${Math.floor(1000 + Math.random() * 9000)}`,
                        title: 'Voucher Potongan Rp 75.000 Aksesori & Hardware',
                        minOrder: 750000,
                        discount: 75000,
                        expiry: '30 Hari ke depan'
                      })}
                      disabled={(user.points || 0) < 750}
                      className="mt-4 w-full bg-amber-600 hover:bg-amber-700 disabled:bg-gray-200 disabled:text-gray-400 text-white text-xs font-semibold py-2 rounded-lg transition-colors cursor-pointer"
                    >
                      {(user.points || 0) >= 750 ? 'Tukar 750 Poin' : 'Poin Kurang'}
                    </button>
                  </div>

                  {/* Voucher 2: 1.500 Poin -> Rp 150.000 */}
                  <div className="border border-border-subtle rounded-xl p-4 bg-blue-50/40 flex flex-col justify-between">
                    <div>
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-bold text-primary bg-blue-100 px-2 py-0.5 rounded">VOUCHER 150K</span>
                        <span className="text-xs font-bold text-amber-700">1.500 Pts</span>
                      </div>
                      <h5 className="font-bold text-text-primary text-sm">Potongan Rp 150.000 Audio Visual & IT</h5>
                      <p className="text-xs text-text-secondary mt-1">Min. belanja Rp 1.500.000 • Berlaku 30 Hari</p>
                    </div>
                    <button
                      onClick={() => handleRedeemVoucher(1500, {
                        id: `VCH-${Date.now()}`,
                        code: `AV150K-${Math.floor(1000 + Math.random() * 9000)}`,
                        title: 'Voucher Potongan Rp 150.000 Audio Visual & IT',
                        minOrder: 1500000,
                        discount: 150000,
                        expiry: '30 Hari ke depan'
                      })}
                      disabled={(user.points || 0) < 1500}
                      className="mt-4 w-full bg-primary hover:bg-primary-hover disabled:bg-gray-200 disabled:text-gray-400 text-white text-xs font-semibold py-2 rounded-lg transition-colors cursor-pointer"
                    >
                      {(user.points || 0) >= 1500 ? 'Tukar 1.500 Poin' : 'Poin Kurang'}
                    </button>
                  </div>

                  {/* Voucher 3: 3.000 Poin -> Rp 300.000 */}
                  <div className="border border-border-subtle rounded-xl p-4 bg-purple-50/40 flex flex-col justify-between">
                    <div>
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded">VOUCHER 300K</span>
                        <span className="text-xs font-bold text-amber-700">3.000 Pts</span>
                      </div>
                      <h5 className="font-bold text-text-primary text-sm">Potongan Rp 300.000 Solusi Enterprise</h5>
                      <p className="text-xs text-text-secondary mt-1">Min. belanja Rp 3.000.000 • Berlaku 30 Hari</p>
                    </div>
                    <button
                      onClick={() => handleRedeemVoucher(3000, {
                        id: `VCH-${Date.now()}`,
                        code: `ENT300K-${Math.floor(1000 + Math.random() * 9000)}`,
                        title: 'Voucher Potongan Rp 300.000 Solusi Enterprise',
                        minOrder: 3000000,
                        discount: 300000,
                        expiry: '30 Hari ke depan'
                      })}
                      disabled={(user.points || 0) < 3000}
                      className="mt-4 w-full bg-purple-700 hover:bg-purple-800 disabled:bg-gray-200 disabled:text-gray-400 text-white text-xs font-semibold py-2 rounded-lg transition-colors cursor-pointer"
                    >
                      {(user.points || 0) >= 3000 ? 'Tukar 3.000 Poin' : 'Poin Kurang'}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* TAB 4: VOUCHER & PROMO */}
          {activeTab === 'vouchers' && (
            <div className="bg-white rounded-xl border border-border-subtle p-6 shadow-sm">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h3 className="text-lg font-bold text-text-primary flex items-center gap-2">
                    <span className="material-symbols-outlined text-primary">confirmation_number</span>
                    Voucher & Kupon Saya
                  </h3>
                  <p className="text-xs text-text-secondary mt-0.5">Gunakan kupon saat checkout untuk mendapatkan potongan harga.</p>
                </div>
                <button
                  onClick={() => setActiveTab('points')}
                  className="text-xs font-bold text-primary bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors"
                >
                  + Dapatkan Kupon via Poin
                </button>
              </div>

              {(user.vouchers || []).length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {(user.vouchers || []).map((v) => (
                    <div key={v.id} className="border-2 border-dashed border-primary/30 bg-blue-50/30 rounded-xl p-4 flex flex-col justify-between">
                      <div>
                        <div className="flex items-center justify-between mb-2">
                          <span className="font-mono font-bold text-xs bg-primary text-white px-2 py-0.5 rounded tracking-wide">{v.code}</span>
                          <span className="text-[11px] text-text-secondary">Hingga: {v.expiry}</span>
                        </div>
                        <h4 className="font-bold text-sm text-text-primary">{v.title}</h4>
                        <p className="text-xs text-text-secondary mt-1">
                          Min. Transaksi Rp {v.minOrder.toLocaleString('id-ID')}
                        </p>
                      </div>
                      <div className="mt-4 pt-3 border-t border-border-subtle flex items-center justify-between">
                        <span className="text-xs font-bold text-emerald-600">Hemat Rp {v.discount.toLocaleString('id-ID')}</span>
                        <Link to="/katalog" className="text-xs font-semibold text-primary hover:underline">
                          Pakai Sekarang
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="py-12 text-center text-text-secondary">
                  <span className="material-symbols-outlined text-[48px] text-outline mb-2">confirmation_number</span>
                  <p>Belum ada voucher aktif saat ini.</p>
                </div>
              )}
            </div>
          )}

          {/* TAB 5: WISHLIST PRODUK */}
          {activeTab === 'wishlist' && (
            <div className="bg-white rounded-xl border border-border-subtle p-6 shadow-sm">
              <h3 className="text-lg font-bold text-text-primary mb-6 flex items-center gap-2">
                <span className="material-symbols-outlined text-primary">favorite</span>
                Wishlist Produk Disimpan
              </h3>

              {wishlistProducts.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                  {wishlistProducts.map((product) => {
                    const price = product.sale_price || product.regular_price;
                    const img = (product.images?.[0] || '').split(',')[0].trim();
                    return (
                      <div key={product.id} className="border border-border-subtle rounded-xl p-3 flex flex-col justify-between hover:shadow-md transition-shadow">
                        <div>
                          <div className="w-full h-36 bg-surface rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                            {img ? (
                              <img src={img} alt={product.name} className="w-full h-full object-contain p-2" />
                            ) : (
                              <span className="material-symbols-outlined text-outline text-[40px]">image</span>
                            )}
                          </div>
                          <span className="text-[10px] uppercase font-bold text-primary tracking-wider">{product.sku || 'IT SOLUTION'}</span>
                          <h4 className="font-semibold text-xs text-text-primary line-clamp-2 mt-1 leading-snug">{product.name}</h4>
                          <div className="mt-2 text-sm font-bold text-text-primary">
                            Rp {price ? price.toLocaleString('id-ID') : 'Hubungi Sales'}
                          </div>
                        </div>
                        <div className="mt-4 pt-2 border-t border-border-subtle flex gap-2">
                          <Link 
                            to={`/produk/${product.slug}`}
                            className="flex-1 text-center bg-primary hover:bg-primary-hover text-white text-xs font-semibold py-2 rounded-lg transition-colors"
                          >
                            Lihat Produk
                          </Link>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <div className="py-12 text-center text-text-secondary">
                  <span className="material-symbols-outlined text-[48px] text-outline mb-2">favorite_border</span>
                  <p>Belum ada produk favorit di wishlist Anda.</p>
                  <Link to="/katalog" className="mt-4 inline-block text-xs font-semibold text-primary underline">
                    Eksplor Katalog Produk
                  </Link>
                </div>
              )}
            </div>
          )}

          {/* TAB 6: ULASAN SAYA */}
          {activeTab === 'reviews' && (() => {
            const pendingReviewItems = [];
            (user.orders || []).forEach(order => {
              (order.items || []).forEach((item, itemIdx) => {
                const itemKey = `${order.id}_${item.name}`;
                const isReviewed = (user.reviewedOrderItems || []).includes(itemKey) ||
                  myReviews.some(r => r.orderId === order.id && r.productName === item.name);
                if (!isReviewed) {
                  pendingReviewItems.push({ order, item, itemKey, itemIdx });
                }
              });
            });

            return (
              <div className="bg-white rounded-xl border border-border-subtle p-6 shadow-sm">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-border-subtle">
                  <div>
                    <h3 className="text-lg font-bold text-text-primary flex items-center gap-2">
                      <span className="material-symbols-outlined text-primary">rate_review</span>
                      Ulasan Produk Saya
                    </h3>
                    <p className="text-xs text-text-secondary mt-0.5">
                      Berikan ulasan dan penilaian untuk setiap produk yang Anda beli untuk membantu pembeli lain dan raih <strong>+50 Poin</strong> per ulasan!
                    </p>
                  </div>

                  {/* Sub-tabs: Menunggu Ulasan vs Riwayat Ulasan */}
                  <div className="flex bg-surface p-1 rounded-xl border border-border-subtle self-start sm:self-auto shrink-0">
                    <button
                      type="button"
                      onClick={() => setReviewSubTab('pending')}
                      className={`px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer flex items-center gap-1.5 ${
                        reviewSubTab === 'pending'
                          ? 'bg-primary text-white shadow-2xs'
                          : 'text-text-secondary hover:text-text-primary'
                      }`}
                    >
                      <span>Menunggu Diulas</span>
                      {pendingReviewItems.length > 0 && (
                        <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-extrabold ${
                          reviewSubTab === 'pending' ? 'bg-white text-primary' : 'bg-amber-500 text-white'
                        }`}>
                          {pendingReviewItems.length}
                        </span>
                      )}
                    </button>
                    <button
                      type="button"
                      onClick={() => setReviewSubTab('completed')}
                      className={`px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer flex items-center gap-1.5 ${
                        reviewSubTab === 'completed'
                          ? 'bg-primary text-white shadow-2xs'
                          : 'text-text-secondary hover:text-text-primary'
                      }`}
                    >
                      <span>Riwayat Ulasan</span>
                      <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-extrabold ${
                        reviewSubTab === 'completed' ? 'bg-white text-primary' : 'bg-gray-200 text-gray-700'
                      }`}>
                        {myReviews.length}
                      </span>
                    </button>
                  </div>
                </div>

                {/* Sub-tab 1: Menunggu Diulas */}
                {reviewSubTab === 'pending' && (
                  <div>
                    {pendingReviewItems.length > 0 ? (
                      <div className="space-y-3.5">
                        {pendingReviewItems.map(({ order, item, itemKey, itemIdx }) => (
                          <div
                            key={itemKey}
                            className="p-4 rounded-xl border border-border-subtle bg-white hover:border-primary/50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs"
                          >
                            <div className="space-y-1">
                              <div className="flex items-center gap-2">
                                <span className="font-bold text-xs font-mono text-primary bg-blue-50 px-2 py-0.5 rounded">
                                  #{order.id}
                                </span>
                                <span className="text-xs text-text-secondary">{order.date}</span>
                                <span className="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-200">
                                  {order.status}
                                </span>
                              </div>
                              <h4 className="font-bold text-sm text-text-primary">
                                {productSlugs[item.name?.toLowerCase()] ? (
                                  <Link
                                    to={`/produk/${productSlugs[item.name.toLowerCase()]}`}
                                    className="text-primary hover:underline"
                                  >
                                    {item.name}
                                  </Link>
                                ) : (
                                  item.name
                                )}
                              </h4>
                              <p className="text-xs text-text-secondary">
                                Kuantitas: {item.qty}x • Rp {(item.price || 0).toLocaleString('id-ID')}
                              </p>
                            </div>

                            <button
                              type="button"
                              onClick={() => handleOpenReview({ name: item.name, id: `prod-${itemIdx + 1}` }, order)}
                              className="px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5 shrink-0 cursor-pointer self-start sm:self-auto"
                            >
                              <span className="material-symbols-outlined text-[16px]">rate_review</span>
                              Tulis Ulasan (+50 Poin)
                            </button>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="py-12 text-center text-text-secondary border-2 border-dashed border-border-subtle rounded-xl">
                        <span className="material-symbols-outlined text-[48px] text-emerald-500 mb-2">task_alt</span>
                        <p className="font-bold text-sm text-text-primary">Semua pesanan Anda sudah diulas!</p>
                        <p className="text-xs text-text-secondary mt-1 mb-4">
                          Terima kasih atas ulasan dan masukan Anda. Belanja lagi untuk mengumpulkan lebih banyak poin.
                        </p>
                        <Link
                          to="/katalog"
                          className="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-hover transition-colors"
                        >
                          <span className="material-symbols-outlined text-[16px]">shopping_bag</span>
                          Belanja Produk Baru
                        </Link>
                      </div>
                    )}
                  </div>
                )}

                {/* Sub-tab 2: Riwayat Ulasan */}
                {reviewSubTab === 'completed' && (
                  <div>
                    {myReviews.length > 0 ? (
                      <div className="space-y-4">
                        {myReviews.map((r) => (
                          <div key={r.id} className="border border-border-subtle rounded-xl p-5 bg-white space-y-3 shadow-2xs">
                            <div className="flex items-start justify-between gap-3">
                              <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm shrink-0">
                                  {r.isAnonymous ? '?' : (r.userName?.charAt(0)?.toUpperCase() || 'U')}
                                </div>
                                <div>
                                  <div className="flex items-center gap-2">
                                    <span className="font-bold text-sm text-text-primary">
                                      {r.isAnonymous ? 'Pengguna Anonim' : r.userName}
                                    </span>
                                    {r.isAnonymous && (
                                      <span className="text-[10px] font-semibold text-gray-500 bg-gray-100 px-1.5 py-0.2 rounded">
                                        Anonim
                                      </span>
                                    )}
                                    <span className="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.2 rounded border border-emerald-200">
                                      ✓ Terverifikasi Pembeli
                                    </span>
                                  </div>
                                  <div className="text-xs text-text-secondary flex items-center gap-2 mt-0.5">
                                    <span>{r.date}</span>
                                    {r.orderId && <span>• #{r.orderId}</span>}
                                    {r.productName && (
                                      <span>•{' '}
                                        {productSlugs[r.productName?.toLowerCase()] ? (
                                          <Link
                                            to={`/produk/${productSlugs[r.productName.toLowerCase()]}`}
                                            className="font-semibold text-primary hover:underline"
                                          >
                                            {r.productName}
                                          </Link>
                                        ) : (
                                          <strong>{r.productName}</strong>
                                        )}
                                      </span>
                                    )}
                                  </div>
                                </div>
                              </div>

                              <div className="flex">
                                {[1, 2, 3, 4, 5].map((s) => (
                                  <span
                                    key={s}
                                    className="material-symbols-outlined text-[16px]"
                                    style={{
                                      color: s <= r.rating ? '#f59e0b' : '#d1d5db',
                                      fontVariationSettings: s <= r.rating ? "'FILL' 1" : "'FILL' 0",
                                    }}
                                  >
                                    star
                                  </span>
                                ))}
                              </div>
                            </div>

                            <p className="text-xs sm:text-sm text-text-primary leading-relaxed pl-12">
                              {r.comment}
                            </p>

                            {/* Images if attached */}
                            {r.images && r.images.length > 0 && (
                              <div className="flex flex-wrap gap-2 pl-12">
                                {r.images.map((img, idx) => (
                                  <img
                                    key={idx}
                                    src={img}
                                    alt={`Foto ${idx}`}
                                    className="w-14 h-14 object-cover rounded-lg border border-border-subtle"
                                  />
                                ))}
                              </div>
                            )}

                            {/* Video if attached */}
                            {r.video && (
                              <div className="pl-12 max-w-xs">
                                <video src={r.video} controls className="w-full max-h-32 rounded-lg bg-black object-contain" />
                              </div>
                            )}
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="py-12 text-center text-text-secondary border-2 border-dashed border-border-subtle rounded-xl">
                        <span className="material-symbols-outlined text-[48px] text-outline mb-2">rate_review</span>
                        <p className="font-bold text-sm text-text-primary">Belum ada riwayat ulasan.</p>
                        <p className="text-xs text-text-secondary mt-1">
                          Pilih tab "Menunggu Diulas" di atas untuk mulai memberikan ulasan pesanan Anda.
                        </p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            );
          })()}
        </section>
      </div>

      {/* INVOICE MODAL */}
      <InvoiceModal
        isOpen={showInvoiceModal}
        onClose={() => setShowInvoiceModal(false)}
        order={selectedInvoiceOrder}
        user={user}
      />

      {/* REVIEW MODAL (FORM ULASAN) */}
      <ReviewModal
        isOpen={showReviewModal}
        onClose={() => setShowReviewModal(false)}
        product={reviewModalData?.product}
        order={reviewModalData?.order}
        user={user}
        onSubmitReview={handleReviewSubmitted}
      />
    </main>
  );
}
