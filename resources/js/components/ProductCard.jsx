/**
 * src/components/ProductCard.jsx
 * Reusable product card – matches user's static template screenshot.
 */
import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { formatPrice } from '../services/productService';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { useWishlist } from '../context/WishlistContext';
import { useToast } from '../context/ToastContext';
import RentalBookingModal from './RentalBookingModal';
import LicenseConfigModal from './LicenseConfigModal';

const RENTAL_CAT_IDS = [13, 14]; // 'Sewa', 'Sewa Produk'
const LICENSE_SKUS = ['CFQ7TTC0LH18', 'CFQ7TTC0LDPB'];

// ─── Badge top-left ───────────────────────────────────────────────────────
function CardBadge({ product, isRental, isLicense, isFlashSale }) {
  if (isRental) return (
    <span className="bg-orange-600 text-white text-[10px] font-bold px-2 py-0.5 rounded flex items-center gap-1 shadow-sm">
      <span className="material-symbols-outlined text-[12px]">calendar_month</span>
      Layanan Sewa
    </span>
  );
  if (isLicense) return (
    <span className="bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded flex items-center gap-1 shadow-sm">
      <span className="material-symbols-outlined text-[12px]">key</span>
      Lisensi Digital
    </span>
  );
  if (isFlashSale || product.is_flash_sale) return (
    <span className="bg-gradient-to-r from-red-600 via-rose-600 to-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded flex items-center gap-1 shadow-sm animate-pulse">
      <span className="material-symbols-outlined text-[12px]">local_fire_department</span>
      Flash Sale
    </span>
  );
  if (product.is_featured) return (
    <span className="bg-primary text-on-primary text-[10px] font-bold px-2 py-0.5 rounded">Best Seller</span>
  );
  if (product.sale_price && product.sale_price < product.regular_price) {
    const pct = Math.round((1 - product.sale_price / product.regular_price) * 100);
    return (
      <span className="bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded">DISKON {pct}%</span>
    );
  }
  if (product.stock > 10) return (
    <span className="bg-emerald-700 text-white text-[10px] font-bold px-2 py-0.5 rounded">Garansi Resmi</span>
  );
  return (
    <span className="bg-slate-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">Enterprise Grade</span>
  );
}

// ─── Stock badge ──────────────────────────────────────────────────────────
function StockBadge({ stock, isRental, isLicense }) {
  if (stock === 0) return (
    <span className="inline-block text-[10px] sm:text-[11px] font-bold bg-red-50 text-red-700 px-2 py-0.5 rounded leading-tight">
      {isRental ? 'Stok Sewa Habis' : 'Stok Habis'}
    </span>
  );
  if (isRental) return (
    <span className="inline-block text-[10px] sm:text-[11px] font-bold bg-orange-50 text-orange-800 border border-orange-200/60 px-2 py-0.5 rounded leading-tight">
      Siap Sewa: {stock} Unit
    </span>
  );
  if (isLicense) return (
    <span className="inline-block text-[10px] sm:text-[11px] font-bold bg-purple-50 text-purple-800 border border-purple-200/60 px-2 py-0.5 rounded leading-tight">
      Aktivasi Instan
    </span>
  );
  if (stock <= 5) return (
    <span className="inline-block text-[10px] sm:text-[11px] font-bold bg-amber-50 text-amber-800 px-2 py-0.5 rounded leading-tight">
      Pre-Order: {stock} Unit
    </span>
  );
  if (stock <= 15) return (
    <span className="inline-block text-[10px] sm:text-[11px] font-bold bg-amber-50 text-amber-800 px-2 py-0.5 rounded leading-tight">
      Gudang BSD: {stock} Unit
    </span>
  );
  return (
    <span className="inline-block text-[10px] sm:text-[11px] font-bold bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded leading-tight">
      Ready: {stock} Unit
    </span>
  );
}

// ─── Main Card ────────────────────────────────────────────────────────────
export default function ProductCard({ product }) {
  const { addItem } = useCart();
  const { user, updateUser } = useAuth();
  const navigate = useNavigate();
  const [imageLoaded, setImageLoaded] = useState(false);
  const [added, setAdded] = useState(false);
  const [wishlistAnim, setWishlistAnim] = useState(false);
  const [showRentalModal, setShowRentalModal] = useState(false);
  const [showLicenseModal, setShowLicenseModal] = useState(false);

  const { wishlist, toggleWishlist: toggleWishlistGlobal, isInWishlist } = useWishlist();
  const { showToast } = useToast();

  const isRental = (product?.category_ids || []).some(id => RENTAL_CAT_IDS.includes(id))
    || (product?.name || '').toLowerCase().includes('sewa ');
  const isLicense = LICENSE_SKUS.includes((product?.sku || '').toUpperCase());
  const isOutOfStock = product.stock === 0;
  const price = product.sale_price || product.regular_price;
  const hasDiscount = Boolean(product.sale_price && product.sale_price < product.regular_price);
  const isFlashSale = Boolean(product?.is_flash_sale || (hasDiscount && !isRental && !isLicense));

  const image = (product.images?.[0] || '').split(',')[0].trim() ||
    `https://picsum.photos/seed/${product.sku || product.id}/400/400`;

  // Wishlist state
  const wishlistKey = `prod-${product.id}`;
  const isWishlisted = isInWishlist(wishlistKey);

  function toggleWishlist(e) {
    e.preventDefault();
    e.stopPropagation();

    // Animate
    setWishlistAnim(true);
    setTimeout(() => setWishlistAnim(false), 400);

    const willAdd = !isWishlisted;
    toggleWishlistGlobal(wishlistKey);

    showToast({
      type: willAdd ? 'wishlist' : 'info',
      title: willAdd ? 'Ditambahkan ke Wishlist' : 'Dihapus dari Wishlist',
      message: product.name,
      link: '/wishlist',
      linkText: 'Lihat Wishlist',
    });
  }

  function handleAddToCart(e) {
    e.preventDefault();
    e.stopPropagation();
    if (isOutOfStock) return;

    // Jika produk rental / sewa, buka popup set tanggal & jam
    if (isRental) {
      setShowRentalModal(true);
      return;
    }

    // Jika produk lisensi, buka popup pilih durasi & seat
    if (isLicense) {
      setShowLicenseModal(true);
      return;
    }

    addItem(product, 1);
    setAdded(true);
    setTimeout(() => setAdded(false), 1800);

    showToast({
      type: 'cart',
      title: 'Berhasil Masuk Keranjang',
      message: product.name,
      link: '/keranjang',
      linkText: 'Lihat Keranjang',
    });
  }

  return (
    <div className={`group bg-white border rounded-xl overflow-hidden flex flex-col transition-all hover:shadow-lg ${
      isRental
        ? 'border-orange-200/80 hover:border-orange-400'
        : isLicense
        ? 'border-purple-200/80 hover:border-purple-400'
        : 'border-gray-200 hover:border-primary/40'
    }`}>
      {/* Image area – 1:1 square, white bg, object-contain (Shopee/Tokopedia style) */}
      <Link to={`/produk/${product.slug}`} className="block">
        <div className="relative bg-white overflow-hidden" style={{ aspectRatio: '1/1' }}>
          
          {/* Top-left: label badge */}
          <div className="absolute top-2 left-2 z-10">
            <CardBadge product={product} isRental={isRental} isLicense={isLicense} isFlashSale={isFlashSale} />
          </div>

          {/* Top-right: wishlist heart button */}
          <button
            onClick={toggleWishlist}
            aria-label={isWishlisted ? 'Hapus dari wishlist' : 'Tambah ke wishlist'}
            className={`absolute top-2 right-2 z-10 w-8 h-8 rounded-full flex items-center justify-center transition-all shadow-sm
              ${wishlistAnim ? 'scale-125' : 'scale-100'}
              ${isWishlisted
                ? 'bg-red-50 text-red-500'
                : 'bg-white/90 text-gray-400 hover:text-red-400 hover:bg-red-50'
              }`}
          >
            <span
              className="material-symbols-outlined text-[18px] transition-all"
              style={{ fontVariationSettings: isWishlisted ? "'FILL' 1" : "'FILL' 0" }}
            >
              favorite
            </span>
          </button>

          {/* Image skeleton */}
          {!imageLoaded && (
            <div className="absolute inset-0 bg-gray-100 animate-pulse" />
          )}
          <img
            src={image}
            alt={product.name}
            onLoad={() => setImageLoaded(true)}
            className={`w-full h-full object-contain p-3 transition-all duration-300 group-hover:scale-[1.04] ${imageLoaded ? 'opacity-100' : 'opacity-0'}`}
            loading="lazy"
            onError={e => {
              e.target.src = `https://picsum.photos/seed/${product.id}/400/400`;
              setImageLoaded(true);
            }}
          />

          {/* Out of stock overlay */}
          {isOutOfStock && (
            <div className="absolute inset-0 bg-white/70 flex items-center justify-center z-10">
              <span className="bg-red-600 text-white text-[11px] font-bold px-3 py-1 rounded-full">
                {isRental ? 'Stok Sewa Habis' : 'Stok Habis'}
              </span>
            </div>
          )}
        </div>
      </Link>

      {/* Card body */}
      <div className="p-3 flex flex-col flex-1 gap-1.5">
        {/* SKU */}
        <span className="font-mono text-[10px] text-gray-400 uppercase tracking-wide">
          {product.sku || '—'}
        </span>

        {/* Product name */}
        <Link to={`/produk/${product.slug}`}>
          <h3 className={`text-[13px] font-semibold line-clamp-2 leading-snug transition-colors min-h-[2.6em] ${
            isRental
              ? 'text-gray-800 group-hover:text-orange-600'
              : isLicense
              ? 'text-gray-800 group-hover:text-purple-700'
              : 'text-gray-800 group-hover:text-primary'
          }`}>
            {product.name}
          </h3>
        </Link>

        {/* Stock badge */}
        <div className="mt-0.5">
          <StockBadge stock={product.stock} isRental={isRental} isLicense={isLicense} />
        </div>

        {/* Price section */}
        <div className="mt-auto pt-2">
          {hasDiscount && (
            <div className="flex items-center gap-1.5 mb-0.5">
              <span className="text-[11px] text-gray-400 line-through">
                {formatPrice(product.regular_price)}
              </span>
              {isRental && (
                <span className="text-[10px] text-orange-600 font-medium">/ hari</span>
              )}
              {isLicense && !isRental && (
                <span className="text-[10px] text-purple-600 font-medium">/ thn</span>
              )}
              <span className="text-[10px] text-gray-400">+PPN 11%</span>
            </div>
          )}
          <div className="flex items-baseline gap-1.5 flex-wrap">
            <span className={`text-[15px] font-bold ${
              isRental ? 'text-orange-600' : isLicense ? 'text-purple-700' : 'text-primary'
            }`}>
              {formatPrice(price)}
            </span>
            {isRental ? (
              <span className="text-[11px] font-bold text-orange-700 bg-orange-100/70 px-1.5 py-0.2 rounded leading-tight">
                / hari
              </span>
            ) : isLicense ? (
              <span className="text-[11px] font-bold text-purple-700 bg-purple-100/70 px-1.5 py-0.2 rounded leading-tight">
                / seat / thn
              </span>
            ) : (
              !hasDiscount && (
                <span className="text-[10px] text-gray-400">+PPN 11%</span>
              )
            )}
            {(isRental || isLicense) && !hasDiscount && (
              <span className="text-[10px] text-gray-400">+PPN 11%</span>
            )}
          </div>
        </div>

        {/* Action buttons */}
        <div className="mt-2.5 flex items-center gap-1.5 sm:gap-2">
          <button
            onClick={handleAddToCart}
            disabled={isOutOfStock}
            aria-label={
              isRental
                ? `Atur sewa ${product.name}`
                : isLicense
                ? `Pilih lisensi ${product.name}`
                : `Tambah ${product.name} ke keranjang`
            }
            className={`flex-1 min-w-0 h-9 rounded-lg text-[11px] sm:text-[12px] font-bold flex items-center justify-center gap-1 px-1.5 sm:px-2 transition-all shadow-sm
              ${added
                ? 'bg-emerald-600 text-white'
                : isOutOfStock
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : isRental
                ? 'bg-orange-600 hover:bg-orange-700 text-white active:scale-95 shadow-orange-600/20'
                : isLicense
                ? 'bg-purple-600 hover:bg-purple-700 text-white active:scale-95 shadow-purple-600/20'
                : 'bg-primary hover:bg-primary/90 text-white active:scale-95'
              }`}
          >
            <span className="material-symbols-outlined text-[15px] flex-shrink-0">
              {added ? 'check' : isRental ? 'event_available' : isLicense ? 'key' : 'add_shopping_cart'}
            </span>
            <span className="truncate">
              {added ? 'Ditambahkan' : isRental ? '+ Sewa' : isLicense ? '+ Lisensi' : '+Keranjang'}
            </span>
          </button>

          <Link
            to={`/produk/${product.slug}`}
            className="h-9 px-2.5 sm:px-3 rounded-lg border border-border-subtle bg-surface/50 text-[11px] sm:text-[12px] font-semibold text-text-secondary hover:text-primary hover:border-primary hover:bg-surface flex items-center justify-center flex-shrink-0 transition-colors"
          >
            Detail
          </Link>
        </div>
      </div>

      {/* Popup / Modal Set Tanggal & Jam Sewa */}
      {isRental && (
        <RentalBookingModal
          product={product}
          isOpen={showRentalModal}
          onClose={() => setShowRentalModal(false)}
          onSuccess={() => {
            setAdded(true);
            setTimeout(() => setAdded(false), 2000);
          }}
        />
      )}

      {/* Popup / Modal Konfigurasi Lisensi */}
      {isLicense && (
        <LicenseConfigModal
          product={product}
          isOpen={showLicenseModal}
          onClose={() => setShowLicenseModal(false)}
          onSuccess={() => {
            setAdded(true);
            setTimeout(() => setAdded(false), 2000);
          }}
        />
      )}
    </div>
  );
}
