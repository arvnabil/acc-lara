/**
 * src/pages/CheckoutPage.jsx
 * Halaman Checkout Belanja — Persis sesuai Static Template & Desain UI
 */
import { useState, useEffect } from 'react';
import { usePage, router as inertiaRouter } from '@inertiajs/react';
import { Link, useNavigate } from '../compat/router';
import { useCart } from '../compat/context';
import { useAuth } from '../compat/context';
import { useToast } from '../compat/context';
import { formatPrice } from '../compat/productService';
import AddressLocationPicker from '../components/AddressLocationPicker';

export default function CheckoutPage() {
  const pageProps = usePage().props;
  const serverOrder = pageProps.order;
  const initialShippingOptions = pageProps.shippingOptions || [];
  const paymentMethods = pageProps.paymentMethods || [];
  const banks = pageProps.banks || [];
  const qris = pageProps.qris || {};
  const [shippingOptions, setShippingOptions] = useState(initialShippingOptions);
  const [quotes, setQuotes] = useState(pageProps.quotes || {});
  const promoCodes = pageProps.promoCodes || [];
  const availableVouchers = Array.isArray(pageProps.availableVouchers) ? pageProps.availableVouchers : [];
  const { items, itemCount, appliedPromo, setAppliedPromo, referralCode, applyReferral, removeReferral } = useCart();
  const { user, saveAddress } = useAuth();
  const { showToast } = useToast();
  const navigate = useNavigate();

  // Steps: 'checkout' (2) -> 'payment' (3) -> 'success' (4)
  const [currentStep, setCurrentStep] = useState(pageProps.initialStep || 'checkout');
  const [orderNumber, setOrderNumber] = useState(serverOrder?.number || '');
  const [referralInput, setReferralInput] = useState(pageProps.cart?.referralCode || referralCode || '');

  // Multi-address support (Shopee style)
  const userAddresses = user?.addresses || [];
  const defaultAddress = userAddresses.find(a => a.isDefault) || userAddresses[0];
  const [selectedAddressId, setSelectedAddressId] = useState(defaultAddress?.id || null);
  const [tempSelectedAddressId, setTempSelectedAddressId] = useState(defaultAddress?.id || null);
  const [showAddressPickerModal, setShowAddressPickerModal] = useState(false);
  const [showNewAddressModal, setShowNewAddressModal] = useState(false);
  const [showCheckoutConfirmModal, setShowCheckoutConfirmModal] = useState(false);
  const [newAddrForm, setNewAddrForm] = useState({
    label: 'Rumah',
    recipientName: '',
    phone: '',
    province: 'DKI Jakarta',
    provinceId: null,
    provinceName: '',
    city: 'Jakarta Selatan',
    cityId: null,
    cityName: '',
    districtId: null,
    address: '',
    subdistrictId: null,
    destinationId: null,
    postalCode: '',
    isDefault: false,
  });

  // Form State
  const [formData, setFormData] = useState({
    name: user?.name || 'Budi Santoso',
    email: user?.email || 'budi@perusahaan.co.id',
    recipientName: defaultAddress?.recipientName || user?.name || 'Budi Santoso',
    phone: defaultAddress?.phone || user?.phone || '+62 812-3456-7890',
    province: defaultAddress?.province || 'Banten',
    provinceId: defaultAddress?.provinceId || null,
    provinceName: defaultAddress?.provinceName || '',
    city: defaultAddress?.city || 'Tangerang Selatan',
    cityId: defaultAddress?.cityId || null,
    cityName: defaultAddress?.cityName || '',
    districtId: defaultAddress?.districtId || null,
    districtName: defaultAddress?.districtName || '',
    subdistrictId: defaultAddress?.subdistrictId || null,
    subdistrictName: defaultAddress?.subdistrictName || '',
    destinationId: defaultAddress?.destinationId || null,
    address: defaultAddress?.address || 'Green Office Park 9, BSD City, Pagedangan, Tangerang Selatan 15345',
    postalCode: defaultAddress?.postalCode || '15345',
    notes: '',
  });

  // Sync selected address if user profile addresses change
  useEffect(() => {
    if (userAddresses.length > 0) {
      const active = userAddresses.find(a => a.id === selectedAddressId) || defaultAddress;
      if (active) {
        setSelectedAddressId(active.id);
        setFormData(prev => ({
          ...prev,
          recipientName: active.recipientName || prev.recipientName,
          phone: active.phone || prev.phone,
          province: active.province || prev.province,
          provinceId: active.provinceId || prev.provinceId,
          provinceName: active.provinceName || prev.provinceName,
          city: active.city || prev.city,
          cityId: active.cityId || prev.cityId,
          cityName: active.cityName || prev.cityName,
          districtId: active.districtId || prev.districtId,
          districtName: active.districtName || prev.districtName,
          subdistrictId: active.subdistrictId || prev.subdistrictId,
          subdistrictName: active.subdistrictName || prev.subdistrictName,
          destinationId: active.destinationId || prev.destinationId,
          address: active.address || prev.address,
          postalCode: active.postalCode || prev.postalCode,
        }));
      }
    }
  }, [user?.addresses]);

  useEffect(() => {
    if (currentStep !== 'checkout' || !selectedAddressId) {
      return;
    }

    const controller = new AbortController();

    const params = new URLSearchParams({
      ...(selectedAddressId ? { address_id: selectedAddressId } : {}),
      ...(formData.biteshipAreaId ? { destination_area_id: formData.biteshipAreaId } : {}),
      ...(formData.postalCode ? { postal_code: formData.postalCode } : {}),
    });

    fetch(`/shipping/quote?${params.toString()}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      signal: controller.signal,
    })
      .then(response => response.ok ? response.json() : Promise.reject(new Error('Gagal memuat ongkir.')))
      .then(data => {
        if (!Array.isArray(data.options) || data.options.length === 0) {
          return;
        }

        setShippingOptions(data.options);
        setQuotes(data.quotes || {});
        setSelectedShipping(current => data.options.find(option => option.id === current?.id) || data.options[0]);
      })
      .catch(error => {
        if (error.name !== 'AbortError') {
          console.warn('Biteship quote unavailable, using current shipping options.', error);
        }
      });

    return () => controller.abort();
  }, [currentStep, selectedAddressId, formData.biteshipAreaId, formData.postalCode]);

  function handleOpenAddressPicker() {
    setTempSelectedAddressId(selectedAddressId);
    setShowAddressPickerModal(true);
  }

  function handleConfirmAddressPicker() {
    const selected = userAddresses.find(a => a.id === tempSelectedAddressId);
    if (selected) {
      setSelectedAddressId(selected.id);
      setFormData(prev => ({
        ...prev,
        recipientName: selected.recipientName || prev.recipientName,
        phone: selected.phone || prev.phone,
        province: selected.province || prev.province,
        provinceId: selected.provinceId || prev.provinceId,
        provinceName: selected.provinceName || prev.provinceName,
        city: selected.city || prev.city,
        cityId: selected.cityId || prev.cityId,
        cityName: selected.cityName || prev.cityName,
        districtId: selected.districtId || prev.districtId,
        districtName: selected.districtName || prev.districtName,
        subdistrictId: selected.subdistrictId || prev.subdistrictId,
        subdistrictName: selected.subdistrictName || prev.subdistrictName,
        destinationId: selected.destinationId || prev.destinationId,
        address: selected.address || prev.address,
        postalCode: selected.postalCode || prev.postalCode,
      }));
    }
    setShowAddressPickerModal(false);
  }

  function handleOpenNewAddressModal() {
    setNewAddrForm({
      label: 'Rumah',
      recipientName: user?.name || '',
      phone: user?.phone || '',
      province: 'DKI Jakarta',
      provinceId: null,
      provinceName: '',
      city: 'Jakarta Selatan',
      cityId: null,
      cityName: '',
      districtId: null,
      districtName: '',
      subdistrictId: null,
      subdistrictName: '',
      destinationId: null,
      address: '',
      postalCode: '',
      isDefault: userAddresses.length === 0,
    });
    setShowNewAddressModal(true);
  }

  function handleSaveNewAddressSubmit(e) {
    e.preventDefault();
    if (!newAddrForm.recipientName.trim() || !newAddrForm.phone.trim() || !newAddrForm.province.trim() || !newAddrForm.city.trim() || !newAddrForm.districtName.trim() || !newAddrForm.subdistrictName.trim() || !newAddrForm.address.trim() || !newAddrForm.postalCode.trim()) {
      alert('Mohon lengkapi penerima, telepon, provinsi, kabupaten/kota, kecamatan, kelurahan/desa, alamat, dan kode pos.');
      return;
    }
    const toSave = { ...newAddrForm };
    if (saveAddress) {
      saveAddress(toSave, {
        onSuccess: (page) => {
          const refreshedUser = {
            ...(page.props?.auth?.user || {}),
            ...(page.props?.account || {}),
          };
          const savedAddress = (refreshedUser.addresses || []).find((address) => (
            address.phone === toSave.phone && address.address === toSave.address && address.postalCode === toSave.postalCode
          ));

          if (savedAddress) {
            setSelectedAddressId(savedAddress.id);
            setTempSelectedAddressId(savedAddress.id);
          }

          setFormData(prev => ({
            ...prev,
            recipientName: toSave.recipientName,
            phone: toSave.phone,
            province: toSave.province,
            city: toSave.city,
            districtName: toSave.districtName,
            subdistrictName: toSave.subdistrictName,
            address: toSave.address,
            postalCode: toSave.postalCode,
          }));
          setShowNewAddressModal(false);
          setShowAddressPickerModal(false);
        },
      });
    }
  }

  // Selected Shipping & Payment
  const [selectedShipping, setSelectedShipping] = useState(() => {
    const savedShipping = shippingOptions.find(option => option.id === serverOrder?.shipping_method);

    if (savedShipping) {
      return savedShipping;
    }

    if (serverOrder?.shipping_label) {
      return {
        id: serverOrder.shipping_method || 'saved-shipping',
        title: serverOrder.shipping_label,
        desc: '',
        price: Number(serverOrder.shipping_cost || 0),
      };
    }

    return shippingOptions[0];
  });
  const [selectedPayment, setSelectedPayment] = useState(() => paymentMethods.find(method => method.id === serverOrder?.payment_method) || paymentMethods[0]);

  // Promo code
  const [promoInput, setPromoInput] = useState(appliedPromo?.code || '');
  const [promoMessage, setPromoMessage] = useState(null);

  // VA Payment State
  const [selectedBank, setSelectedBank] = useState(banks[0]);
  const [copiedVA, setCopiedVA] = useState(false);
  const [copiedAmount, setCopiedAmount] = useState(false);

  // Cost calculation
  const selectedQuote = quotes[selectedShipping?.id] || {
    goods_subtotal: serverOrder?.goods_subtotal,
    ppn: serverOrder?.ppn,
    shipping_estimate: serverOrder?.shipping_cost,
    admin_fee: serverOrder?.admin_fee,
    discount_on_goods: serverOrder?.discount_on_goods,
    grand_total: serverOrder?.grand_total,
    estimated_points: 0,
  };
  const subtotal = selectedQuote.goods_subtotal || 0;
  const ppn = selectedQuote.ppn || 0;
  const shippingCost = selectedQuote.shipping_estimate || 0;
  const adminFee = selectedQuote.admin_fee || 0;
  const discountAmount = selectedQuote.discount_on_goods || 0;
  const grandTotal = selectedQuote.grand_total || 0;
  const earnedPoints = selectedQuote.estimated_points || 0;

  // Apply promo
  const handleApplyPromo = (codeToApply) => {
    const raw = (codeToApply || promoInput).toUpperCase().trim();
    if (!raw) {
      setPromoMessage({ type: 'error', text: 'Masukkan kode promo terlebih dahulu.' });
      return;
    }
    inertiaRouter.post('/cart/promo', { code: raw }, {
      preserveScroll: true,
      onSuccess: () => setPromoMessage({ type: 'success', text: `✅ Kode ${raw} berhasil diterapkan!` }),
      onError: (errors) => setPromoMessage({ type: 'error', text: errors.code || '❌ Kode promo tidak valid atau sudah kedaluwarsa.' }),
    });
  };

  const handleProcessPayment = (e) => {
    e.preventDefault();
    inertiaRouter.post('/checkout', {
      address_id: selectedAddressId,
      shipping_address: {
        recipient: formData.recipientName,
        phone: formData.phone,
        province: formData.province,
        city: formData.city,
        district: formData.districtName,
        subdistrict: formData.subdistrictName,
        address: formData.address,
        postal_code: formData.postalCode,
        destination_area_id: formData.biteshipAreaId,
        label: 'Checkout',
      },
      customer_notes: formData.notes,
      payment_method: selectedPayment.id,
      shipping_method: selectedShipping.id,
      shipping_label: selectedShipping.title,
    }, {
      preserveScroll: true,
      onError: (errors) => {
        if (errors.stock) {
          showToast({ title: 'Stok tidak tersedia', message: errors.stock, type: 'error' });
          return;
        }

        if (errors.cart) {
          showToast({ title: 'Keranjang sudah kosong', message: errors.cart, type: 'error' });
          inertiaRouter.visit('/keranjang');
          return;
        }

        showToast({ title: 'Checkout gagal', message: errors.shipping_address || errors.payment_method || 'Periksa kembali data checkout.', type: 'error' });
      },
    });
  };

  const handleCheckoutClick = (e) => {
    e.preventDefault();
    setShowCheckoutConfirmModal(true);
  };

  const handleFinishPayment = () => {
    if (orderNumber) inertiaRouter.post(`/pembayaran/${orderNumber}/saya-sudah-bayar`, {}, { preserveScroll: true });
  };

  // ─────────────────────────────────────────────────────────────────────────────
  // STEP 4: ORDER SUCCESS VIEW
  // ─────────────────────────────────────────────────────────────────────────────
  if (currentStep === 'success') {
    return (
      <div className="max-w-[900px] mx-auto px-gutter lg:px-margin py-space-2xl w-full">
        {/* Step Indicator */}
        <div className="flex items-center gap-2 mb-space-xl justify-center max-w-[500px] mx-auto">
          <div className="flex items-center gap-1.5"><div className="w-7 h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[12px] font-bold text-text-secondary">✓</div><span className="text-[12px] text-text-secondary hidden sm:inline">Keranjang</span></div>
          <div className="flex-1 h-px bg-primary mx-1"></div>
          <div className="flex items-center gap-1.5"><div className="w-7 h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[12px] font-bold text-text-secondary">✓</div><span className="text-[12px] text-text-secondary hidden sm:inline">Checkout</span></div>
          <div className="flex-1 h-px bg-primary mx-1"></div>
          <div className="flex items-center gap-1.5"><div className="w-7 h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[12px] font-bold text-text-secondary">✓</div><span className="text-[12px] text-text-secondary hidden sm:inline">Pembayaran</span></div>
          <div className="flex-1 h-px bg-primary mx-1"></div>
          <div className="flex items-center gap-1.5"><div className="w-7 h-7 rounded-full bg-primary flex items-center justify-center text-[12px] font-bold text-on-primary">✓</div><span className="text-[12px] font-bold text-primary">Selesai</span></div>
        </div>

        <div className="bg-card-bg border border-border-subtle rounded-xl p-space-2xl shadow-sm text-center mb-space-xl">
          <div className="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-space-md">
            <span className="material-symbols-outlined text-[48px] text-green-600">check_circle</span>
          </div>
          <span className="inline-block bg-green-100 text-green-700 font-sku text-[11px] font-bold px-3 py-1.5 rounded-full mb-3">
            ✅ PEMBAYARAN BERHASIL DIVERIFIKASI
          </span>
          <h1 className="font-headline-hero-mobile lg:font-headline-section text-text-primary mt-2 mb-2 font-bold">
            Pesanan Sedang Disiapkan!
          </h1>
          <p className="font-body-md text-[14px] text-text-secondary max-w-[500px] mx-auto mb-space-xl">
            Nomor Pesanan: <strong className="text-text-primary font-mono">{orderNumber}</strong><br/>
            Konfirmasi dan detail invoice telah dikirim ke <strong className="text-text-primary">{formData.email}</strong>
          </p>

          {/* Order Details Card */}
          <div className="bg-surface border border-border-subtle rounded-xl p-space-xl max-w-[520px] mx-auto mb-space-xl text-left">
            <div className="flex items-center justify-between mb-4 pb-4 border-b border-border-subtle">
              <div>
                <span className="font-sku text-[11px] text-text-secondary">Nomor Pesanan</span>
                <div className="font-bold text-text-primary font-mono">{orderNumber}</div>
              </div>
              <span className="bg-blue-100 text-blue-700 font-sku text-[11px] font-bold px-3 py-1.5 rounded-full">
                Dikemas
              </span>
            </div>

            {/* Product summary */}
            <div className="flex flex-col gap-3 mb-4 pb-4 border-b border-border-subtle">
              {items.length > 0 ? (
                items.map(item => (
                  <div key={item.key} className="flex items-center gap-3">
                    <img src={item.image || '/products/placeholder.png'} className="w-12 h-12 rounded-lg object-cover" alt={item.name} onError={(e) => { e.currentTarget.src = '/products/placeholder.png'; }} />
                    <div className="flex-1">
                      <div className="text-[13px] font-bold text-text-primary line-clamp-1">{item.name}</div>
                      <div className="text-[12px] text-text-secondary">× {item.quantity} unit — {formatPrice(item.price * item.quantity)}</div>
                    </div>
                  </div>
                ))
              ) : (
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 rounded-lg bg-border-subtle flex items-center justify-center">
                    <span className="material-symbols-outlined text-primary">inventory_2</span>
                  </div>
                  <div className="flex-1">
                    <div className="text-[13px] font-bold text-text-primary">Logitech Rally Bar & IT Equipment</div>
                    <div className="text-[12px] text-text-secondary">Paket Perangkat Enterprise</div>
                  </div>
                </div>
              )}
            </div>

            <div className="flex flex-col gap-2 text-[13px]">
              <div className="flex justify-between"><span className="text-text-secondary">Total Dibayar</span><span className="font-bold text-text-primary">{formatPrice(grandTotal)}</span></div>
              <div className="flex justify-between"><span className="text-text-secondary">Metode Bayar</span><span className="font-semibold">{selectedPayment.title}</span></div>
              <div className="flex justify-between"><span className="text-text-secondary">Ekspedisi</span><span className="font-semibold">{selectedShipping.title}</span></div>
              <div className="flex justify-between"><span className="text-text-secondary">Estimasi Tiba</span><span className="font-semibold text-primary">{serverOrder?.shipping_estimate || serverOrder?.shippingEstimate || selectedShipping.desc || 'Menunggu estimasi'}</span></div>
              <div className="flex justify-between"><span className="text-text-secondary">Kirim ke</span><span className="font-semibold text-right max-w-[60%] line-clamp-1">{formData.address}</span></div>
            </div>
          </div>

          {/* Tokopedia-style Points Reward Earned Card */}
          {earnedPoints > 0 && (
            <div className="bg-gradient-to-r from-amber-50 via-amber-100/40 to-orange-50 border border-amber-300/80 rounded-xl p-4 mb-space-xl flex items-center justify-between gap-3 text-left max-w-[520px] mx-auto shadow-sm">
              <div className="flex items-center gap-3">
                <div className="w-11 h-11 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-md flex-shrink-0">
                  <span className="material-symbols-outlined text-[24px]">stars</span>
                </div>
                <div>
                  <div className="text-[13px] font-bold text-amber-950">🎉 Selamat! +{earnedPoints.toLocaleString('id-ID')} Poin Didapatkan</div>
                  <div className="text-[11px] text-amber-800/90">
                    Poin Reward telah ditambahkan ke akun Anda!
                  </div>
                </div>
              </div>
              <Link to="/akun" className="text-[11px] font-bold text-amber-950 bg-amber-200/90 hover:bg-amber-300 px-3 py-1.5 rounded-lg whitespace-nowrap transition-colors flex-shrink-0">
                Tukar Poin
              </Link>
            </div>
          )}

          {/* What's next */}
          <div className="max-w-[520px] mx-auto mb-space-xl text-left">
            <h3 className="font-title-card text-[14px] font-bold text-text-primary mb-3">Apa yang terjadi selanjutnya?</h3>
            <div className="flex flex-col gap-3">
              <div className="flex gap-3 items-start">
                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                  <span className="material-symbols-outlined text-[16px] text-primary">inventory_2</span>
                </div>
                <div>
                  <div className="text-[13px] font-bold text-text-primary">Pengemasan & QC Produk</div>
                  <div className="text-[12px] text-text-secondary">Tim gudang kami sedang mempersiapkan & mengecek kualitas produk Anda</div>
                </div>
              </div>
              <div className="flex gap-3 items-start">
                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                  <span className="material-symbols-outlined text-[16px] text-primary">local_shipping</span>
                </div>
                <div>
                  <div className="text-[13px] font-bold text-text-primary">Pengiriman ke Alamat Anda</div>
                  <div className="text-[12px] text-text-secondary">Produk dikirim dalam 1–2 hari kerja, Anda akan mendapat notifikasi resi</div>
                </div>
              </div>
              <div className="flex gap-3 items-start">
                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                  <span className="material-symbols-outlined text-[16px] text-primary">done_all</span>
                </div>
                <div>
                  <div className="text-[13px] font-bold text-text-primary">Diterima & Garansi Aktif</div>
                  <div className="text-[12px] text-text-secondary">Garansi resmi distributor langsung aktif dengan nomor seri produk</div>
                </div>
              </div>
            </div>
          </div>

          <div className="flex items-center justify-center gap-space-md flex-wrap">
            <Link
              to={`/lacak-pesanan?number=${encodeURIComponent(orderNumber)}`}
              className="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-8 py-3.5 rounded-lg transition-colors shadow-sm flex items-center gap-2"
            >
              <span className="material-symbols-outlined text-[18px]">location_on</span>
              <span>Lacak Pesanan Saya</span>
            </Link>
            <Link
              to="/"
              className="bg-surface hover:bg-surface-container text-text-primary font-label-sm text-label-sm px-6 py-3.5 rounded-lg transition-colors border border-border-subtle"
            >
              Kembali ke Beranda
            </Link>
          </div>
        </div>
      </div>
    );
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // STEP 3: PAYMENT (QRIS / VIRTUAL ACCOUNT VIEW)
  // ─────────────────────────────────────────────────────────────────────────────
  if (currentStep === 'payment') {
    const isMidtrans = selectedPayment.id === 'midtrans';
    const isQris = selectedPayment.id === 'qris';
    const isPaymentMethodLocked = Boolean(serverOrder?.payment_method);

    return (
      <div className="max-w-[900px] mx-auto px-gutter lg:px-margin py-space-2xl w-full">
        {/* Step Indicator */}
        <div className="flex items-center gap-2 mb-space-xl justify-center max-w-[500px] mx-auto">
          <div className="flex items-center gap-1.5"><div className="w-7 h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[12px] font-bold text-text-secondary">✓</div><span className="text-[12px] text-text-secondary hidden sm:inline">Keranjang</span></div>
          <div className="flex-1 h-px bg-primary mx-1"></div>
          <div className="flex items-center gap-1.5"><div className="w-7 h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[12px] font-bold text-text-secondary">✓</div><span className="text-[12px] text-text-secondary hidden sm:inline">Checkout</span></div>
          <div className="flex-1 h-px bg-primary mx-1"></div>
          <div className="flex items-center gap-1.5"><div className="w-7 h-7 rounded-full bg-primary flex items-center justify-center text-[12px] font-bold text-on-primary">3</div><span className="text-[12px] font-bold text-primary">Pembayaran</span></div>
          <div className="flex-1 h-px bg-border-subtle mx-1"></div>
          <div className="flex items-center gap-1.5"><div className="w-7 h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[12px] font-bold text-text-secondary">4</div><span className="text-[12px] text-text-secondary hidden sm:inline">Selesai</span></div>
        </div>

        {/* Payment Method Switcher Tabs on Step 3 */}
        {!isPaymentMethodLocked && <div className="flex max-w-[620px] mx-auto mb-6 bg-surface p-1 rounded-xl border border-border-subtle">
          <button
            onClick={() => setSelectedPayment(paymentMethods.find(p => p.id === 'midtrans') || paymentMethods[0])}
            className={`flex-1 py-2 px-3 rounded-lg text-[13px] font-bold flex items-center justify-center gap-1.5 transition-all ${
              isMidtrans ? 'bg-primary text-white shadow-sm' : 'text-text-secondary hover:text-text-primary'
            }`}
          >
            <span className="material-symbols-outlined text-[18px]">payments</span>
            <span>Midtrans</span>
          </button>
          <button
            onClick={() => setSelectedPayment(paymentMethods.find(p => p.id === 'qris') || paymentMethods[0])}
            className={`flex-1 py-2 px-3 rounded-lg text-[13px] font-bold flex items-center justify-center gap-1.5 transition-all ${
              isQris
                ? 'bg-primary text-white shadow-sm'
                : 'text-text-secondary hover:text-text-primary'
            }`}
          >
            <span className="material-symbols-outlined text-[18px]">qr_code_scanner</span>
            <span>QRIS</span>
          </button>
          <button
            onClick={() => setSelectedPayment(paymentMethods.find(p => p.id === 'bank_transfer') || paymentMethods[2])}
            className={`flex-1 py-2 px-3 rounded-lg text-[13px] font-bold flex items-center justify-center gap-1.5 transition-all ${
              !isMidtrans && !isQris
                ? 'bg-primary text-white shadow-sm'
                : 'text-text-secondary hover:text-text-primary'
            }`}
          >
            <span className="material-symbols-outlined text-[18px]">account_balance</span>
            <span>Transfer Bank</span>
          </button>
        </div>}

        <div className="bg-card-bg border border-border-subtle rounded-xl p-space-xl sm:p-space-2xl shadow-sm text-center">
          {isMidtrans ? (
            <>
              <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-space-md">
                <span className="material-symbols-outlined text-[36px] sm:text-[40px]">payments</span>
              </div>
              <span className="inline-block bg-blue-100 text-blue-800 font-sku text-[11px] font-bold px-3 py-1 rounded-full mb-2">Pembayaran Midtrans</span>
              <span className="block font-sku text-[11px] text-text-secondary">Nomor Invoice: {orderNumber}</span>
              <h1 className="font-headline-hero-mobile lg:font-headline-section text-text-primary mt-1 mb-2 font-bold">Lanjutkan Pembayaran</h1>
              <p className="font-body-md text-[13px] sm:text-[14px] text-text-secondary max-w-[520px] mx-auto mb-space-xl">Anda akan diarahkan ke halaman pembayaran Midtrans yang aman untuk memilih kartu, e-wallet, atau transfer bank.</p>
              <div className="bg-surface rounded-xl p-4 max-w-[420px] mx-auto mb-space-xl border border-border-subtle text-left">
                <div className="flex justify-between text-[13px]"><span className="text-text-secondary">Total pembayaran</span><strong>{formatPrice(grandTotal)}</strong></div>
                <div className="flex justify-between text-[13px] mt-2"><span className="text-text-secondary">Status</span><span className="text-amber-700">Menunggu pembayaran</span></div>
              </div>
              <div className="flex items-center justify-center gap-space-md flex-wrap">
                <button
                  onClick={() => inertiaRouter.post(`/pembayaran/${orderNumber}/midtrans`, {}, { preserveScroll: true })}
                  className="bg-primary hover:bg-primary-container text-on-primary font-label-sm px-8 py-3.5 rounded-lg transition-colors shadow-sm font-semibold flex items-center gap-2"
                >
                  <span>Bayar dengan Midtrans</span>
                  <span className="material-symbols-outlined text-[16px]">arrow_forward</span>
                </button>
                <button onClick={handleFinishPayment} className="bg-surface hover:bg-surface-container text-text-primary font-label-sm px-6 py-3.5 rounded-lg transition-colors border border-border-subtle">Cek status pembayaran</button>
              </div>
            </>
          ) : isQris ? (
            /* QRIS PAYMENT VIEW */
            <>
              <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-space-md">
                <span className="material-symbols-outlined text-[36px] sm:text-[40px]">qr_code_scanner</span>
              </div>
              <span className="inline-block bg-emerald-100 text-emerald-800 font-sku text-[11px] font-bold px-3 py-1 rounded-full mb-2">
                Menunggu Pembayaran QRIS
              </span>
              <span className="block font-sku text-[11px] text-text-secondary">Nomor Invoice: {orderNumber}</span>
              <h1 className="font-headline-hero-mobile lg:font-headline-section text-text-primary mt-1 mb-2 font-bold">
                Scan QRIS untuk Pembayaran
              </h1>
              <p className="font-body-md text-[13px] sm:text-[14px] text-text-secondary max-w-[520px] mx-auto mb-space-xl">
                Buka aplikasi Mobile Banking atau E-Wallet apa saja (BCA, Livin', BRImo, BNI, GoPay, OVO, DANA, ShopeePay), lalu scan kode QR di bawah ini:
              </p>

              {/* Countdown timer */}
              <div className="bg-amber-50 border border-amber-200 rounded-xl p-3.5 max-w-[420px] mx-auto mb-space-xl">
                <div className="text-[12px] sm:text-[13px] text-amber-800 font-semibold mb-1 flex items-center justify-center gap-1.5">
                  <span className="material-symbols-outlined text-[16px]">schedule</span>
                  Batas Waktu Scan QRIS
                </div>
                <div className="text-[26px] sm:text-[28px] font-bold font-mono text-amber-700">14:59</div>
                <div className="text-[11px] text-amber-600">Kode QR otomatis diperbarui setelah batas waktu habis</div>
              </div>

              {/* QRIS Card with Image */}
              <div className="bg-surface rounded-2xl p-4 sm:p-6 max-w-[420px] mx-auto mb-space-xl text-center border border-border-subtle shadow-sm">
                <div className="bg-white rounded-xl p-3 sm:p-4 border border-border-subtle shadow-sm inline-block mx-auto mb-3">
                  <img
                    src={qris.image || '/method/qris-accommerce.png'}
                    alt={qris.merchant_name || 'QRIS Accommerce by ACTiV'}
                    className="w-full max-w-[280px] sm:max-w-[320px] h-auto object-contain mx-auto rounded-lg"
                  />
                </div>
                <div className="font-bold text-[14px] sm:text-[15px] text-text-primary">{qris.merchant_name || 'Accommerce by ACTiV'}</div>
                <div className="font-mono text-[11px] text-text-secondary mt-0.5">NMID: {qris.nmid || 'BELUM DIISI'} · Standar Pembayaran Nasional</div>

                <div className="mt-4 pt-3.5 border-t border-border-subtle flex justify-between items-center text-left">
                  <span className="font-sku text-[12px] text-text-secondary">Total Tagihan</span>
                  <div className="flex items-center gap-2">
                    <span className="font-bold text-primary text-[16px] sm:text-[17px]">{formatPrice(grandTotal)}</span>
                    <button
                      onClick={() => {
                        navigator.clipboard?.writeText(String(grandTotal));
                        setCopiedAmount(true);
                        setTimeout(() => setCopiedAmount(false), 2000);
                      }}
                      className="text-[11px] text-primary hover:underline font-semibold"
                    >
                      {copiedAmount ? '✓ Tersalin' : 'Salin'}
                    </button>
                  </div>
                </div>
                <div className="mt-3 bg-emerald-50 border border-emerald-100 rounded-lg p-2.5 text-[11px] text-emerald-800 text-left flex items-center gap-1.5">
                  <span className="material-symbols-outlined text-[16px] flex-shrink-0 text-emerald-600">verified</span>
                  <span>Setelah membayar, klik <strong>Saya Sudah Bayar</strong>. Pembayaran akan diperiksa manual oleh admin.</span>
                </div>
              </div>

              {/* Cara Bayar QRIS */}
              <div className="max-w-[460px] mx-auto mb-space-xl text-left bg-card-bg border border-border-subtle rounded-xl p-4 sm:p-5">
                <h3 className="font-title-card text-[14px] font-bold text-text-primary mb-3 flex items-center gap-2">
                  <span className="material-symbols-outlined text-[18px] text-primary">help</span>
                  Panduan Pembayaran QRIS:
                </h3>
                <ol className="flex flex-col gap-2.5 text-[12px] sm:text-[13px] text-text-secondary">
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">1</span>
                    <span>Buka aplikasi m-Banking (BCA, Livin', BRImo, BNI, dll) atau E-Wallet (GoPay, OVO, DANA, ShopeePay).</span>
                  </li>
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">2</span>
                    <span>Pilih menu <strong>QRIS</strong> atau ikon <strong>Scan QR</strong>.</span>
                  </li>
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">3</span>
                    <span>Arahkan kamera ke gambar QRIS di atas (atau simpan gambar lalu import dari galeri).</span>
                  </li>
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">4</span>
                    <span>Pastikan penerima adalah <strong>Accommerce by ACTiV</strong> dan nominal <strong>{formatPrice(grandTotal)}</strong>.</span>
                  </li>
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">5</span>
                    <span>Konfirmasi dan masukkan PIN Anda, lalu klik <strong>Saya Sudah Bayar</strong>. Admin akan memeriksa pembayaran secara manual.</span>
                  </li>
                </ol>
              </div>

              <div className="flex items-center justify-center gap-space-md flex-wrap">
                <button
                  onClick={handleFinishPayment}
                  className="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-8 py-3.5 rounded-lg transition-colors shadow-sm font-semibold flex items-center gap-2 active:scale-95"
                >
                  <span>Saya Sudah Bayar</span>
                  <span className="material-symbols-outlined text-[16px]">check</span>
                </button>
                <a
                  href={qris.image || '/method/qris-accommerce.png'}
                  target="_blank"
                  download="qris-accommerce.png"
                  className="bg-surface hover:bg-surface-container text-text-primary font-label-sm text-label-sm px-6 py-3.5 rounded-lg transition-colors border border-border-subtle flex items-center gap-1.5"
                >
                  <span className="material-symbols-outlined text-[16px]">download</span>
                  <span>Unduh QRIS</span>
                </a>
                <button
                  onClick={() => setCurrentStep('checkout')}
                  className="bg-transparent hover:bg-surface text-text-secondary hover:text-text-primary font-label-sm text-label-sm px-5 py-3.5 rounded-lg transition-colors"
                >
                  Kembali ke Checkout
                </button>
              </div>
            </>
          ) : (
            /* MANUAL BANK TRANSFER PAYMENT VIEW */
            <>
              <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-space-md">
                <span className="material-symbols-outlined text-[36px] sm:text-[40px]">account_balance</span>
              </div>
              <span className="inline-block bg-amber-100 text-amber-700 font-sku text-[11px] font-bold px-3 py-1 rounded-full mb-2">
                Menunggu Verifikasi Transfer Bank
              </span>
              <span className="block font-sku text-[11px] text-text-secondary">Nomor Invoice: {orderNumber}</span>
              <h1 className="font-headline-hero-mobile lg:font-headline-section text-text-primary mt-1 mb-2 font-bold">
                Selesaikan Pembayaran Anda
              </h1>
              <p className="font-body-md text-[13px] sm:text-[14px] text-text-secondary max-w-[500px] mx-auto mb-space-xl">
                Transfer tepat sesuai nominal ke rekening perusahaan. Pembayaran akan diverifikasi oleh admin.
              </p>

              {/* Countdown timer */}
              <div className="bg-amber-50 border border-amber-200 rounded-xl p-3.5 max-w-[420px] mx-auto mb-space-xl">
                <div className="text-[12px] sm:text-[13px] text-amber-700 font-semibold mb-1 flex items-center justify-center gap-1.5">
                  <span className="material-symbols-outlined text-[16px]">schedule</span>
                  Batas Waktu Pembayaran
                </div>
                <div className="text-[26px] sm:text-[28px] font-bold font-mono text-amber-700">23:59:00</div>
                <div className="text-[11px] text-amber-600">Pesanan otomatis dibatalkan setelah habis waktu</div>
              </div>

              {/* VA Detail Card */}
              <div className="bg-surface rounded-xl p-4 sm:p-space-xl max-w-[460px] mx-auto mb-space-xl text-left border border-border-subtle">
                {/* Bank Selector Tabs */}
                <div className="flex gap-2 mb-4">
                  {banks.map(bank => (
                    <button
                      key={bank.id}
                      onClick={() => setSelectedBank(bank)}
                      className={`flex-1 text-[12px] font-bold py-2 px-3 rounded-lg transition-colors ${
                        selectedBank.id === bank.id
                          ? 'bg-primary text-on-primary'
                          : 'bg-surface border border-border-subtle text-text-secondary hover:border-primary'
                      }`}
                    >
                      {bank.id}
                    </button>
                  ))}
                </div>

                <div className="flex justify-between items-center mb-3">
                  <span className="font-sku text-[12px] text-text-secondary">Bank Tujuan</span>
                  <span className="font-bold text-text-primary text-[13px]">{selectedBank.name}</span>
                </div>
                <div className="flex justify-between items-center mb-3">
                  <span className="font-sku text-[12px] text-text-secondary">Nomor Rekening</span>
                  <div className="flex items-center gap-2">
                    <span className="font-bold text-primary font-mono text-[16px]">{selectedBank.account_number}</span>
                    <button
                      onClick={() => {
                        navigator.clipboard?.writeText(selectedBank.account_number);
                        setCopiedVA(true);
                        setTimeout(() => setCopiedVA(false), 2000);
                      }}
                      className="text-[11px] text-primary hover:underline font-semibold"
                    >
                      {copiedVA ? '✓ Tersalin' : 'Salin'}
                    </button>
                  </div>
                </div>
                <div className="flex justify-between items-center mb-3">
                  <span className="font-sku text-[12px] text-text-secondary">Total Transfer</span>
                  <div className="flex items-center gap-2">
                    <span className="font-bold text-text-primary">{formatPrice(grandTotal)}</span>
                    <button
                      onClick={() => {
                        navigator.clipboard?.writeText(String(grandTotal));
                        setCopiedAmount(true);
                        setTimeout(() => setCopiedAmount(false), 2000);
                      }}
                      className="text-[11px] text-primary hover:underline font-semibold"
                    >
                      {copiedAmount ? '✓ Tersalin' : 'Salin'}
                    </button>
                  </div>
                </div>
                <div className="bg-amber-50 border border-amber-100 rounded-lg p-3 text-[12px] text-amber-700">
                  ⚠️ Transfer tepat sesuai nominal. Setelah membayar, klik <strong>Saya Sudah Bayar</strong> agar admin dapat memeriksa pembayaran.
                </div>
              </div>

              {/* How to pay instructions */}
              <div className="max-w-[460px] mx-auto mb-space-xl text-left bg-card-bg border border-border-subtle rounded-xl p-4 sm:p-5">
                <h3 className="font-title-card text-[14px] font-bold text-text-primary mb-3">Cara Bayar m-Banking:</h3>
                <ol className="flex flex-col gap-2 text-[12px] sm:text-[13px] text-text-secondary">
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">1</span>
                    <span>Login Mobile Banking → pilih menu <strong>Transfer / Bayar</strong></span>
                  </li>
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">2</span>
                    <span>Pilih kategori <strong>Virtual Account</strong> → masukkan nomor VA</span>
                  </li>
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">3</span>
                    <span>Konfirmasi nominal & selesaikan pembayaran</span>
                  </li>
                  <li className="flex gap-2">
                    <span className="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] flex items-center justify-center shrink-0 font-bold mt-0.5">4</span>
                    <span>Verifikasi manual oleh admin</span>
                  </li>
                </ol>
              </div>

              <div className="flex items-center justify-center gap-space-md flex-wrap">
                <button
                  onClick={handleFinishPayment}
                  className="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm px-8 py-3.5 rounded-lg transition-colors shadow-sm font-semibold flex items-center gap-2"
                >
                  <span>Saya Sudah Bayar</span>
                  <span className="material-symbols-outlined text-[16px]">check</span>
                </button>
                <button
                  onClick={() => setCurrentStep('checkout')}
                  className="bg-surface hover:bg-surface-container text-text-primary font-label-sm text-label-sm px-6 py-3.5 rounded-lg transition-colors border border-border-subtle"
                >
                  Kembali ke Form Checkout
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    );
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // STEP 2: MAIN CHECKOUT FORM (PERSIS STATIC TEMPLATE & SCREENSHOT)
  // ─────────────────────────────────────────────────────────────────────────────
  return (
    <div className="max-w-[1440px] mx-auto px-3 sm:px-gutter lg:px-margin py-4 sm:py-space-xl w-full overflow-x-hidden">
      {/* Breadcrumb + Steps */}
      <div className="mb-4 sm:mb-space-xl">
        <h1 className="font-headline-hero-mobile lg:font-headline-hero text-text-primary font-bold">Checkout Belanja</h1>
        <p className="font-body-md text-[13px] sm:text-[14px] text-text-secondary mt-1">Lengkapi data pengiriman dan metode pembayaran Anda.</p>
        
        {/* Step indicators (Responsive on mobile) */}
        <div className="flex items-center gap-1.5 sm:gap-2 mt-4 max-w-full overflow-x-hidden pb-1">
          <Link to="/keranjang" className="flex items-center gap-1.5 hover:opacity-80 transition-opacity shrink-0">
            <div className="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[11px] sm:text-[12px] font-bold text-text-secondary">✓</div>
            <span className="text-[11px] sm:text-[12px] text-text-secondary hidden sm:inline">Keranjang</span>
          </Link>
          <div className="flex-1 min-w-[8px] h-px bg-primary mx-1"></div>
          <div className="flex items-center gap-1.5 shrink-0">
            <div className="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-primary flex items-center justify-center text-[11px] sm:text-[12px] font-bold text-on-primary">2</div>
            <span className="text-[11px] sm:text-[12px] font-bold text-primary">Checkout</span>
          </div>
          <div className="flex-1 min-w-[8px] h-px bg-border-subtle mx-1"></div>
          <div className="flex items-center gap-1.5 shrink-0">
            <div className="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[11px] sm:text-[12px] font-bold text-text-secondary">3</div>
            <span className="text-[11px] sm:text-[12px] text-text-secondary hidden sm:inline">Pembayaran</span>
          </div>
          <div className="flex-1 min-w-[8px] h-px bg-border-subtle mx-1"></div>
          <div className="flex items-center gap-1.5 shrink-0">
            <div className="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-surface border-2 border-border-subtle flex items-center justify-center text-[11px] sm:text-[12px] font-bold text-text-secondary">4</div>
            <span className="text-[11px] sm:text-[12px] text-text-secondary hidden sm:inline">Selesai</span>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-space-xl">
        {/* Form Left Side (8 cols) */}
        <div className="lg:col-span-8 flex flex-col gap-4 sm:gap-space-xl">

          {/* 1. Produk Dipesan */}
          <div className="bg-card-bg border border-border-subtle rounded-xl p-4 sm:p-6 shadow-sm">
            <h3 className="font-title-card text-[15px] sm:text-[16px] font-bold text-text-primary mb-3 sm:mb-space-lg pb-2 border-b border-border-subtle flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-primary text-on-primary text-[12px] flex items-center justify-center font-bold">1</span>
              <span>Produk Dipesan</span>
            </h3>
            <div className="flex flex-col gap-3">
              {items.map((item, idx) => (
                <div
                  key={item.key}
                  className="flex items-center gap-3 bg-surface border border-border-subtle rounded-lg p-2.5 sm:p-3 hover:border-primary transition-colors"
                >
                  <img
                    src={item.image || '/products/placeholder.png'}
                    alt={item.name}
                    className="w-14 h-14 sm:w-16 sm:h-16 rounded-lg object-cover shrink-0"
                    onError={e => { e.target.src = '/products/placeholder.png'; }}
                  />
                  <div className="flex-1 min-w-0">
                    <div className="font-sku text-[10px] sm:text-[11px] text-text-secondary uppercase truncate">{item.sku || 'LOG-960-001308'}</div>
                    <div className="font-title-card text-[13px] sm:text-[14px] font-bold text-text-primary truncate">{item.name}</div>
                    <div className="flex items-center justify-between mt-1 flex-wrap gap-1">
                      <div className="font-price text-[13px] sm:text-[14px] text-primary font-bold">
                        {formatPrice(item.price)} <span className="text-text-secondary font-normal text-[11px]">× {item.quantity}</span>
                      </div>
                      <div className="text-[11px] sm:text-[12px] text-text-secondary font-semibold">
                        = {formatPrice(item.price * item.quantity)}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* 2. Informasi Pembeli */}
          <div className="bg-card-bg border border-border-subtle rounded-xl p-4 sm:p-6 shadow-sm">
            <h3 className="font-title-card text-[15px] sm:text-[16px] font-bold text-text-primary mb-3 sm:mb-space-lg pb-2 border-b border-border-subtle flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-primary text-on-primary text-[12px] flex items-center justify-center font-bold">2</span>
              <span>Informasi Pembeli</span>
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-space-md">
              <div>
                <label className="block font-label-sm text-[12px] text-text-primary mb-1">Nama Lengkap *</label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={e => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-surface border border-border-subtle rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 text-[13px] sm:text-[14px] text-text-primary focus:outline-none focus:border-primary"
                />
              </div>
              <div>
                <label className="block font-label-sm text-[12px] text-text-primary mb-1">Email *</label>
                <input
                  type="email"
                  value={formData.email}
                  onChange={e => setFormData({ ...formData, email: e.target.value })}
                  className="w-full bg-surface border border-border-subtle rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 text-[13px] sm:text-[14px] text-text-primary focus:outline-none focus:border-primary"
                />
              </div>
              <div>
                <label className="block font-label-sm text-[12px] text-text-primary mb-1">Nama Penerima *</label>
                <input
                  type="text"
                  required
                  value={formData.recipientName}
                  onChange={e => setFormData({ ...formData, recipientName: e.target.value })}
                  className="w-full bg-surface border border-border-subtle rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 text-[13px] sm:text-[14px] text-text-primary focus:outline-none focus:border-primary"
                />
              </div>
              <div>
                <label className="block font-label-sm text-[12px] text-text-primary mb-1">Nomor HP / WhatsApp *</label>
                <input
                  type="text"
                  required
                  value={formData.phone}
                  onChange={e => setFormData({ ...formData, phone: e.target.value })}
                  className="w-full bg-surface border border-border-subtle rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 text-[13px] sm:text-[14px] text-text-primary focus:outline-none focus:border-primary"
                />
              </div>
            </div>
          </div>

          {/* 3. Alamat Pengiriman (Shopee Style) */}
          <div className="bg-card-bg border border-border-subtle rounded-xl p-4 sm:p-6 shadow-sm overflow-hidden relative">
            {/* Shopee-style decorative top border */}
            <div className="h-1 bg-gradient-to-r from-primary via-blue-400 to-indigo-500 -mt-4 sm:-mt-6 -mx-4 sm:-mx-6 mb-4 sm:mb-5"></div>

            <div className="flex items-center justify-between mb-3 sm:mb-4 pb-2.5 border-b border-border-subtle">
              <h3 className="font-title-card text-[15px] sm:text-[16px] font-bold text-text-primary flex items-center gap-2">
                <span className="w-6 h-6 rounded-full bg-primary text-on-primary text-[12px] flex items-center justify-center font-bold">3</span>
                <span className="material-symbols-outlined text-[20px] text-primary">location_on</span>
                <span>Alamat Pengiriman</span>
              </h3>
              {userAddresses.length > 0 && (
                <button
                  type="button"
                  onClick={handleOpenAddressPicker}
                  className="text-xs sm:text-sm font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer"
                >
                  <span className="material-symbols-outlined text-[16px]">swap_horiz</span>
                  Ubah Alamat
                </button>
              )}
            </div>

            {userAddresses.length > 0 ? (
              /* Shopee style saved address display */
              <div className="space-y-3.5">
                {(() => {
                  const currentAddr = userAddresses.find(a => a.id === selectedAddressId) || defaultAddress || userAddresses[0];
                  return (
                    <div className="bg-blue-50/40 border border-blue-200/80 rounded-xl p-3.5 sm:p-4 transition-all">
                      <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div className="space-y-1">
                          <div className="flex flex-wrap items-center gap-2">
                            <span className="font-bold text-[14px] sm:text-[15px] text-text-primary">
                              {currentAddr.recipientName}
                            </span>
                            <span className="text-xs font-semibold text-text-secondary">
                              ({currentAddr.phone})
                            </span>
                            {currentAddr.label && (
                              <span className="bg-gray-100 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded border border-gray-200 uppercase">
                                {currentAddr.label}
                              </span>
                            )}
                            {currentAddr.isDefault && (
                              <span className="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-200">
                                Utama
                              </span>
                            )}
                          </div>
                          <p className="text-xs sm:text-sm text-text-primary leading-relaxed">
                            {currentAddr.address}
                          </p>
                          <p className="text-xs text-text-secondary font-medium">
                            {[currentAddr.subdistrictName, currentAddr.districtName, currentAddr.city, currentAddr.province, currentAddr.postalCode].filter(Boolean).join(', ')}
                          </p>
                        </div>

                        <button
                          type="button"
                          onClick={handleOpenAddressPicker}
                          className="self-start sm:self-center px-3 py-1.5 bg-white border border-border-subtle hover:border-primary text-xs font-semibold text-text-primary hover:text-primary rounded-lg shadow-2xs transition-colors shrink-0 cursor-pointer"
                        >
                          Pilih Alamat Lain
                        </button>
                      </div>
                    </div>
                  );
                })()}

                {/* Catatan Pengiriman */}
                <div>
                  <label className="block font-label-sm text-[12px] text-text-primary mb-1">
                    Catatan Pengiriman (Opsional)
                  </label>
                  <input
                    type="text"
                    placeholder="Cth: Titip di pos satpam atau resepsionis lantai 3"
                    value={formData.notes}
                    onChange={e => setFormData({ ...formData, notes: e.target.value })}
                    className="w-full bg-surface border border-border-subtle rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 text-[13px] sm:text-[14px] text-text-primary focus:outline-none focus:border-primary"
                  />
                </div>
              </div>
            ) : (
              /* Fallback direct input form for users with no saved addresses */
              <div className="flex flex-col gap-3 sm:gap-space-md">
                <div className="flex items-center justify-between gap-3 rounded-lg border border-dashed border-primary/40 bg-blue-50/40 p-3">
                  <p className="text-xs text-text-secondary">Simpan alamat ini agar bisa dipakai kembali di checkout berikutnya.</p>
                  <button
                    type="button"
                    onClick={handleOpenNewAddressModal}
                    className="shrink-0 rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white hover:bg-primary-hover"
                  >
                    Tambah Alamat
                  </button>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-space-md">
                  <div className="md:col-span-2">
                    <AddressLocationPicker value={formData} onLocationChange={location => setFormData(form => ({ ...form, ...location }))} />
                  </div>
                </div>
                <div>
                  <label className="block font-label-sm text-[12px] text-text-primary mb-1">Alamat Lengkap *</label>
                  <textarea
                    rows="3"
                    required
                    value={formData.address}
                    onChange={e => setFormData({ ...formData, address: e.target.value })}
                    className="w-full bg-surface border border-border-subtle rounded-lg p-2.5 sm:p-3 text-[13px] sm:text-[14px] text-text-primary focus:outline-none focus:border-primary"
                  />
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-space-md">
                  <div>
                    <label className="block font-label-sm text-[12px] text-text-primary mb-1">Kode Pos *</label>
                    <input
                      type="text"
                      required
                      value={formData.postalCode}
                      onChange={e => setFormData({ ...formData, postalCode: e.target.value })}
                      className="w-full bg-surface border border-border-subtle rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 text-[13px] sm:text-[14px] text-text-primary focus:outline-none focus:border-primary"
                    />
                  </div>
                  <div>
                    <label className="block font-label-sm text-[12px] text-text-primary mb-1">Catatan Pengiriman</label>
                    <input
                      type="text"
                      placeholder="Cth: Kirim ke lantai 3, hub ke satpam"
                      value={formData.notes}
                      onChange={e => setFormData({ ...formData, notes: e.target.value })}
                      className="w-full bg-surface border border-border-subtle rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 text-[13px] sm:text-[14px] text-text-primary focus:outline-none focus:border-primary"
                    />
                  </div>
                </div>
              </div>
            )}
          </div>

        </div>

        {/* Sidebar (4 cols) — Sticky: Ekspedisi, Pembayaran, Promo, Ringkasan */}
        <div className="lg:col-span-4">
          <div className="flex flex-col gap-4 sm:gap-space-md lg:sticky lg:top-4">

            {/* Sidebar Card: Pilih Ekspedisi */}
            <div className="bg-card-bg border border-border-subtle rounded-xl p-4 shadow-sm">
              <h3 className="font-title-card text-[14px] font-bold text-text-primary mb-3 pb-2 border-b border-border-subtle flex items-center gap-2">
                <span className="material-symbols-outlined text-[18px] text-primary">local_shipping</span>
                Ekspedisi & Pengiriman
              </h3>
              <div className="flex flex-col gap-2">
                {shippingOptions.map(opt => {
                  const isSelected = selectedShipping?.id === opt.id;
                  return (
                    <label
                      key={opt.id}
                      onClick={() => setSelectedShipping(opt)}
                      className={`flex items-center justify-between gap-2 p-3 rounded-xl bg-surface cursor-pointer transition-all ${
                        isSelected
                          ? 'border-2 border-primary bg-primary/[0.02]'
                          : 'border border-border-subtle hover:border-primary'
                      }`}
                    >
                      <div className="flex items-center gap-2 flex-1 min-w-0">
                        <input
                          type="radio"
                          name="shipping"
                          checked={isSelected}
                          onChange={() => setSelectedShipping(opt)}
                          className="accent-primary flex-shrink-0"
                        />
                        <div className="min-w-0">
                          <div className="font-bold text-[12px] text-text-primary leading-tight truncate">{opt.title}</div>
                          <div className="text-[11px] text-text-secondary mt-0.5 leading-tight">{opt.desc}</div>
                        </div>
                      </div>
                      <span className={`font-bold text-[12px] flex-shrink-0 ml-1 ${opt.price === 0 ? 'text-green-600' : isSelected ? 'text-primary' : 'text-text-primary'}`}>
                        {opt.price === 0 ? 'GRATIS' : formatPrice(opt.price)}
                      </span>
                    </label>
                  );
                })}
              </div>
            </div>

            {/* Sidebar Card: Metode Pembayaran */}
            <div className="bg-card-bg border border-border-subtle rounded-xl p-4 shadow-sm">
              <h3 className="font-title-card text-[14px] font-bold text-text-primary mb-3 pb-2 border-b border-border-subtle flex items-center gap-2">
                <span className="material-symbols-outlined text-[18px] text-primary">payments</span>
                Metode Pembayaran
              </h3>
              <div className="flex flex-col gap-2">
                {paymentMethods.map(m => {
                  const isSelected = selectedPayment.id === m.id;
                  return (
                    <label
                      key={m.id}
                      onClick={() => setSelectedPayment(m)}
                      className={`flex items-center gap-2 p-3 rounded-xl bg-surface cursor-pointer transition-all ${
                        isSelected
                          ? 'border-2 border-primary bg-primary/[0.02]'
                          : 'border border-border-subtle hover:border-primary'
                      }`}
                    >
                      <input
                        type="radio"
                        name="payment"
                        checked={isSelected}
                        onChange={() => setSelectedPayment(m)}
                        className="accent-primary flex-shrink-0"
                      />
                      <span className={`material-symbols-outlined text-[18px] flex-shrink-0 ${isSelected ? 'text-primary' : 'text-text-secondary'}`}>
                        {m.icon}
                      </span>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-1.5 flex-wrap">
                          <span className="font-bold text-[12px] text-text-primary leading-tight">{m.title}</span>
                          {m.badge && (
                            <span className="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded-full">
                              {m.badge}
                            </span>
                          )}
                        </div>
                        <div className="text-[11px] text-text-secondary mt-0.5 leading-tight line-clamp-1">{m.desc}</div>
                      </div>
                    </label>
                  );
                })}
              </div>
            </div>

            {/* Sidebar Card: Promo Code */}
            <div className="bg-card-bg border border-border-subtle rounded-xl p-4 shadow-sm">
              <h3 className="font-title-card text-[14px] font-bold text-text-primary mb-3 flex items-center gap-2">
                <span className="material-symbols-outlined text-[18px] text-primary">local_offer</span>
                Kode Promo / Voucher
              </h3>
              <div className="flex gap-2">
                <input
                  type="text"
                  placeholder="Kode promo..."
                  value={promoInput}
                  onChange={e => setPromoInput(e.target.value)}
                  onKeyDown={e => { if (e.key === 'Enter') handleApplyPromo(); }}
                  className="flex-1 bg-surface border border-border-subtle rounded-lg px-3 py-2 text-[13px] text-text-primary focus:outline-none focus:border-primary uppercase"
                />
                <button
                  onClick={() => handleApplyPromo()}
                  className="bg-primary text-on-primary font-label-sm text-[13px] font-bold px-4 py-2 rounded-lg hover:bg-primary-container transition-colors shrink-0"
                >
                  Pakai
                </button>
              </div>

              {promoMessage && (
                <div className={`mt-2 text-[12px] font-medium ${promoMessage.type === 'success' ? 'text-green-600' : 'text-red-500'}`}>
                  {promoMessage.text}
                </div>
              )}

              <div className="mt-3 flex flex-wrap gap-1.5">
                {promoCodes.map(promo => promo.code).map(chip => (
                  <button
                    key={chip}
                    onClick={() => {
                      setPromoInput(chip);
                      handleApplyPromo(chip);
                    }}
                    className={`text-[11px] border border-dashed px-2 py-1 rounded-full transition-colors ${
                      appliedPromo?.code === chip
                        ? 'border-primary bg-primary text-on-primary font-bold'
                        : 'border-primary text-primary hover:bg-primary hover:text-on-primary'
                    }`}
                  >
                    {chip}
                  </button>
                ))}
              </div>

              {availableVouchers.length > 0 && (
                <div className="mt-3 pt-3 border-t border-border-subtle">
                  <p className="text-[12px] font-semibold text-text-primary mb-2">Voucher saya yang belum dipakai</p>
                  <div className="flex flex-wrap gap-1.5">
                    {availableVouchers.map((voucher) => (
                      <button
                        key={voucher.id}
                        onClick={() => {
                          setPromoInput(voucher.code);
                          handleApplyPromo(voucher.code);
                        }}
                        className={`text-[11px] border px-2 py-1 rounded-full transition-colors ${
                          appliedPromo?.code === voucher.code
                            ? 'border-primary bg-primary text-on-primary font-bold'
                            : 'border-primary text-primary hover:bg-primary hover:text-on-primary'
                        }`}
                      >
                        {voucher.code}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {appliedPromo && (
                <div className="mt-2.5 pt-2 border-t border-border-subtle flex items-center justify-between text-[11px] text-text-secondary">
                  <span>Promo aktif: <strong className="text-primary">{appliedPromo.code}</strong></span>
                  <button onClick={() => { setAppliedPromo(null); setPromoInput(''); setPromoMessage(null); }} className="text-red-500 hover:underline">Hapus</button>
                </div>
              )}

              <div className="mt-4 pt-3 border-t border-border-subtle">
                <p className="text-[12px] font-semibold text-text-primary mb-2">Kode Referral (Opsional)</p>
                <div className="flex gap-2">
                  <input type="text" placeholder="Kode referral..." value={referralInput} onChange={(e) => setReferralInput(e.target.value.toUpperCase())} className="flex-1 bg-surface border border-border-subtle rounded-lg px-3 py-2 text-[13px] uppercase focus:outline-none focus:border-primary" />
                  <button onClick={() => applyReferral(referralInput.trim(), { onError: (errors) => showToast({ title: 'Kode referral tidak dapat digunakan', message: errors.code || 'Periksa kembali kode referral.', type: 'error' }) })} className="bg-primary text-on-primary font-bold px-4 py-2 rounded-lg">Pakai</button>
                </div>
                {referralCode && <div className="mt-2 flex items-center justify-between text-[11px] text-green-600">Referral aktif: <strong>{referralCode}</strong><button onClick={() => { removeReferral(); setReferralInput(''); }} className="text-red-500">Hapus</button></div>}
              </div>
            </div>

            {/* Sidebar Card: Ringkasan Pembayaran */}
            <div className="bg-card-bg border border-border-subtle rounded-xl p-4 sm:p-space-xl shadow-sm">
              <h3 className="font-title-card text-[15px] font-bold text-text-primary mb-3 pb-2 border-b border-border-subtle">
                Ringkasan Pembayaran
              </h3>
              <div className="flex flex-col gap-2.5 text-[13px] text-text-secondary mb-4">
                <div className="flex justify-between">
                  <span>Subtotal ({itemCount} item)</span>
                  <span className="font-semibold text-text-primary">{formatPrice(subtotal)}</span>
                </div>
                <div className="flex justify-between">
                  <span>PPN 11%</span>
                  <span className="font-semibold text-text-primary">{formatPrice(ppn)}</span>
                </div>
                <div className="flex justify-between">
                  <span>Ongkos Kirim</span>
                  <span className={`font-semibold ${shippingCost === 0 ? 'text-green-600' : 'text-text-primary'}`}>
                    {shippingCost === 0 ? 'GRATIS' : formatPrice(shippingCost)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Biaya Layanan</span>
                  <span className="font-semibold text-text-primary">{formatPrice(adminFee)}</span>
                </div>

                {discountAmount > 0 && (
                  <div className="flex justify-between text-green-600 font-bold">
                    <span>Diskon ({appliedPromo?.code || 'Voucher'})</span>
                    <span>- {formatPrice(discountAmount)}</span>
                  </div>
                )}

                <div className="border-t border-border-subtle pt-3 flex justify-between items-baseline">
                  <span className="font-bold text-text-primary text-[14px]">Grand Total</span>
                  <span className="font-price text-[18px] sm:text-[20px] text-primary font-bold">
                    {formatPrice(grandTotal)}
                  </span>
                </div>

                {earnedPoints > 0 && (
                  <div className="mt-1 bg-amber-50 border border-amber-200/80 rounded-lg p-2.5 flex items-center justify-between gap-2 text-[12px]">
                    <div className="flex items-center gap-1.5 text-amber-900 font-medium">
                      <span className="material-symbols-outlined text-[17px] text-amber-600 animate-pulse">stars</span>
                      <span>Estimasi Poin Didapat:</span>
                    </div>
                    <span className="font-bold text-amber-800 font-mono text-[13px]">+{earnedPoints.toLocaleString('id-ID')} Poin</span>
                  </div>
                )}
              </div>

              <button
                onClick={handleCheckoutClick}
                className="w-full bg-primary hover:bg-primary-container text-on-primary font-label-sm text-[13px] sm:text-label-sm py-3.5 sm:py-4 rounded-xl flex items-center justify-center gap-2 transition-colors shadow-md font-semibold active:scale-98"
              >
                <span>Proses & Bayar Sekarang</span>
                <span className="material-symbols-outlined text-[18px]">arrow_forward</span>
              </button>

              <p className="text-[11px] text-text-secondary text-center mt-3 flex items-center justify-center gap-1">
                <span className="material-symbols-outlined text-[14px]">lock</span>
                <span>Transaksi aman & terenkripsi SSL</span>
              </p>
            </div>

          </div>
        </div>
      </div>

      {/* MODAL 1: PILIH ALAMAT PENGIRIMAN (SHOPEE STYLE) */}
      {showAddressPickerModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
          <div className="bg-white rounded-2xl w-full max-w-lg shadow-2xl border border-border-subtle overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div className="flex items-center justify-between px-6 py-4 border-b border-border-subtle bg-surface">
              <h4 className="font-bold text-base text-text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-primary text-[20px]">location_on</span>
                Pilih Alamat Pengiriman
              </h4>
              <button
                type="button"
                onClick={() => setShowAddressPickerModal(false)}
                className="text-text-secondary hover:text-text-primary p-1 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer"
              >
                <span className="material-symbols-outlined text-[20px]">close</span>
              </button>
            </div>

            <div className="p-6 max-h-[70vh] overflow-y-auto space-y-3">
              {userAddresses.map((addr) => {
                const isSelected = tempSelectedAddressId === addr.id;
                return (
                  <div
                    key={addr.id}
                    onClick={() => setTempSelectedAddressId(addr.id)}
                    className={`p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3.5 ${
                      isSelected
                        ? 'border-primary bg-blue-50/40 ring-1 ring-primary shadow-xs'
                        : 'border-border-subtle hover:border-gray-300 bg-white'
                    }`}
                  >
                    <input
                      type="radio"
                      name="selected_checkout_address"
                      checked={isSelected}
                      onChange={() => setTempSelectedAddressId(addr.id)}
                      className="mt-1 w-4 h-4 text-primary focus:ring-primary accent-primary cursor-pointer shrink-0"
                    />
                    <div className="flex-1 space-y-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="font-bold text-sm text-text-primary">{addr.recipientName}</span>
                        <span className="text-xs text-text-secondary font-medium">({addr.phone})</span>
                        {addr.label && (
                          <span className="bg-gray-100 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded border border-gray-200 uppercase">
                            {addr.label}
                          </span>
                        )}
                        {addr.isDefault && (
                          <span className="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-200">
                            Utama
                          </span>
                        )}
                      </div>
                      <p className="text-xs text-text-primary leading-relaxed">{addr.address}</p>
                      <p className="text-xs text-text-secondary">
                        {[addr.subdistrictName, addr.districtName, addr.city, addr.province, addr.postalCode].filter(Boolean).join(', ')}
                      </p>
                    </div>
                  </div>
                );
              })}

              <button
                type="button"
                onClick={handleOpenNewAddressModal}
                className="w-full py-3 px-4 border-2 border-dashed border-border-subtle hover:border-primary rounded-xl text-xs font-bold text-primary flex items-center justify-center gap-2 transition-colors cursor-pointer bg-surface/50 hover:bg-blue-50/30"
              >
                <span className="material-symbols-outlined text-[18px]">add</span>
                Tambah Alamat Baru
              </button>
            </div>

            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-border-subtle bg-surface">
              <button
                type="button"
                onClick={() => setShowAddressPickerModal(false)}
                className="px-4 py-2 text-xs font-semibold text-text-secondary hover:text-text-primary hover:bg-gray-200 rounded-lg transition-colors cursor-pointer"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleConfirmAddressPicker}
                className="px-5 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-hover shadow-sm transition-colors cursor-pointer"
              >
                Konfirmasi
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 2: TAMBAH ALAMAT BARU (DARI CHECKOUT) */}
      {showNewAddressModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
          <div className="bg-white rounded-2xl w-full max-w-lg shadow-2xl border border-border-subtle overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div className="flex items-center justify-between px-6 py-4 border-b border-border-subtle bg-surface">
              <h4 className="font-bold text-base text-text-primary flex items-center gap-2">
                <span className="material-symbols-outlined text-primary text-[20px]">add_location</span>
                Tambah Alamat Pengiriman Baru
              </h4>
              <button
                type="button"
                onClick={() => setShowNewAddressModal(false)}
                className="text-text-secondary hover:text-text-primary p-1 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer"
              >
                <span className="material-symbols-outlined text-[20px]">close</span>
              </button>
            </div>

            <form onSubmit={handleSaveNewAddressSubmit} className="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
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
                      onClick={() => setNewAddrForm(f => ({ ...f, label: tag }))}
                      className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                        newAddrForm.label === tag
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
                    value={newAddrForm.recipientName}
                    onChange={e => setNewAddrForm(f => ({ ...f, recipientName: e.target.value }))}
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
                    value={newAddrForm.phone}
                    onChange={e => setNewAddrForm(f => ({ ...f, phone: e.target.value }))}
                    placeholder="Cth: 081234567890"
                    className="w-full bg-surface border border-border-subtle rounded-lg px-3.5 py-2 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                  />
                </div>
              </div>

              <AddressLocationPicker value={newAddrForm} onLocationChange={location => setNewAddrForm(form => ({ ...form, ...location }))} />

              {/* Full Address */}
              <div>
                <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                  Alamat Lengkap *
                </label>
                <textarea
                  rows="3"
                  required
                  value={newAddrForm.address}
                  onChange={e => setNewAddrForm(f => ({ ...f, address: e.target.value }))}
                  placeholder="Nama jalan, gedung, nomor rumah, RT/RW, kelurahan, kecamatan"
                  className="w-full bg-surface border border-border-subtle rounded-lg p-3 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                />
              </div>

              {/* Postal Code */}
              <div>
                <label className="block text-xs font-bold text-text-primary uppercase tracking-wider mb-1">
                  Kode Pos *
                </label>
                <input
                  type="text"
                  required
                  value={newAddrForm.postalCode}
                  onChange={e => setNewAddrForm(f => ({ ...f, postalCode: e.target.value }))}
                  placeholder="Cth: 12345"
                  className="w-full bg-surface border border-border-subtle rounded-lg px-3.5 py-2 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary"
                />
              </div>

              {/* Checkbox Default */}
              <label className="flex items-center gap-2 cursor-pointer pt-1">
                <input
                  type="checkbox"
                  checked={newAddrForm.isDefault}
                  onChange={e => setNewAddrForm(f => ({ ...f, isDefault: e.target.checked }))}
                  className="w-4 h-4 rounded text-primary focus:ring-primary accent-primary cursor-pointer"
                />
                <span className="text-xs font-medium text-text-primary">
                  Atur sebagai alamat utama
                </span>
              </label>

              {/* Actions */}
              <div className="flex items-center justify-end gap-3 pt-4 border-t border-border-subtle">
                <button
                  type="button"
                  onClick={() => setShowNewAddressModal(false)}
                  className="px-4 py-2 text-xs font-semibold text-text-secondary hover:text-text-primary hover:bg-surface rounded-lg transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-hover shadow-sm transition-colors cursor-pointer"
                >
                  Simpan & Gunakan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {showCheckoutConfirmModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" role="dialog" aria-modal="true" aria-labelledby="checkout-confirm-title">
          <div className="w-full max-w-md rounded-2xl bg-card-bg p-5 shadow-2xl">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 id="checkout-confirm-title" className="text-lg font-bold text-text-primary">Konfirmasi Checkout</h2>
                <p className="mt-1 text-xs text-text-secondary">Pastikan pilihan pesanan dan pembayaran sudah benar.</p>
              </div>
              <button type="button" onClick={() => setShowCheckoutConfirmModal(false)} className="text-xl text-text-secondary hover:text-text-primary" aria-label="Tutup">×</button>
            </div>

            <div className="mt-4 space-y-2 rounded-xl border border-border-subtle bg-surface p-3 text-sm">
              <div className="flex justify-between gap-4"><span className="text-text-secondary">Metode pembayaran</span><strong>{selectedPayment?.title || 'Belum dipilih'}</strong></div>
              {selectedPayment?.id === 'bank_transfer' && selectedBank && (
                <div className="flex justify-between gap-4"><span className="text-text-secondary">Bank</span><strong>{selectedBank.name}</strong></div>
              )}
              {selectedPayment?.id === 'qris' && <div className="flex justify-between gap-4"><span className="text-text-secondary">Verifikasi</span><strong>Manual oleh admin</strong></div>}
              <div className="flex justify-between gap-4"><span className="text-text-secondary">Pengiriman</span><strong>{selectedShipping?.title || 'Belum dipilih'}</strong></div>
              <div className="flex justify-between gap-4 border-t border-border-subtle pt-2"><span className="font-semibold">Total</span><strong className="text-primary">{formatPrice(grandTotal)}</strong></div>
            </div>

            <div className="mt-5 flex justify-end gap-2">
              <button type="button" onClick={() => setShowCheckoutConfirmModal(false)} className="rounded-lg border border-border-subtle px-4 py-2 text-sm font-semibold text-text-secondary hover:bg-surface">Kembali</button>
              <button type="button" onClick={(e) => { setShowCheckoutConfirmModal(false); handleProcessPayment(e); }} className="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-on-primary hover:bg-primary-container">Konfirmasi Checkout</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
