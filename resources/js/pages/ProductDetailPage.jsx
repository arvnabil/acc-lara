/**
 * src/pages/ProductDetailPage.jsx
 * Detail produk – redesign sesuai static template Accommerce.
 */
import { useState, useEffect, useRef } from "react";
import { useParams, Link, useNavigate } from "react-router-dom";
import {
  getProduct,
  getVariants,
  getRelatedProducts,
  getBrands,
  getCategories,
  formatPrice,
} from "../services/productService";
import { useCart } from "../context/CartContext";
import { useAuth } from "../context/AuthContext";
import { useWishlist } from "../context/WishlistContext";
import { useToast } from "../context/ToastContext";
import ProductCard from "../components/ProductCard";

// ─── Review Section ────────────────────────────────────────────────────────
function ReviewSection({ product, user, updateUser }) {
  const STORAGE_KEY = `accommerce_reviews_${product.id}`;
  const [reviews, setReviews] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
    } catch {
      return [];
    }
  });
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState("");
  const [hoverRating, setHoverRating] = useState(0);
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [images, setImages] = useState([]);
  const [video, setVideo] = useState(null);
  const [submitted, setSubmitted] = useState(false);
  const [selectedImageModal, setSelectedImageModal] = useState(null);

  // Check matching orders for repeat purchases support
  const matchingOrders = user
    ? (user.orders || []).filter((o) =>
        (o.items || []).some((item) =>
          item.name.toLowerCase().includes(product.name.split(" ")[0].toLowerCase()) ||
          product.name.toLowerCase().includes(item.name.toLowerCase())
        )
      )
    : [];

  // Filter which orders have NOT yet been reviewed
  const reviewedOrderIds = reviews
    .filter((r) => r.userEmail === user?.email)
    .map((r) => r.orderId);

  const unreviewedOrders = matchingOrders.filter(
    (o) => !reviewedOrderIds.includes(o.id)
  );

  const canWriteReview = user && unreviewedOrders.length > 0;
  const targetOrder = unreviewedOrders[0];

  function handleImageUpload(e) {
    const files = Array.from(e.target.files || []);
    if (files.length === 0) return;
    files.slice(0, 5 - images.length).forEach((file) => {
      const reader = new FileReader();
      reader.onload = (event) => {
        setImages((prev) => [...prev, event.target.result]);
      };
      reader.readAsDataURL(file);
    });
  }

  function handleVideoUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.size > 25 * 1024 * 1024) {
      alert("Ukuran video maksimal 25MB");
      return;
    }
    const reader = new FileReader();
    reader.onload = (event) => {
      setVideo(event.target.result);
    };
    reader.readAsDataURL(file);
  }

  function handleSubmit(e) {
    e.preventDefault();
    if (!comment.trim()) return;
    const newReview = {
      id: Date.now(),
      orderId: targetOrder?.id || `ORD-${Date.now()}`,
      userEmail: user.email,
      userName: isAnonymous ? "Pengguna Anonim" : (user.name || "Pelanggan Accommerce"),
      isAnonymous,
      rating,
      comment: comment.trim(),
      images,
      video,
      date: new Date().toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
      }),
    };
    const updated = [newReview, ...reviews];
    setReviews(updated);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(updated));
    setComment("");
    setImages([]);
    setVideo(null);
    setSubmitted(true);
    if (user && updateUser) {
      updateUser({
        points: (user.points || 0) + 50,
        reviewsPending: Math.max(0, (user.reviewsPending || 0) - 1),
      });
    }
  }

  const avgRating = reviews.length
    ? (reviews.reduce((s, r) => s + r.rating, 0) / reviews.length).toFixed(1)
    : "4.9";
  const totalReviews = reviews.length || 33;
  const totalSold = 120;

  return (
    <div id="section-ulasan" className="bg-white border border-gray-200 rounded-xl p-6 mt-6 scroll-mt-24">
      {/* Header */}
      <div className="flex items-center gap-3 mb-5">
        <span className="material-symbols-outlined text-primary text-[22px]">
          rate_review
        </span>
        <h2 className="text-lg font-bold text-gray-900">Ulasan Produk</h2>
        <div className="flex items-center gap-1.5 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full ml-auto">
          <span
            className="material-symbols-outlined text-amber-500 text-[16px]"
            style={{ fontVariationSettings: "'FILL' 1" }}
          >
            star
          </span>
          <span className="font-bold text-amber-700 text-sm">{avgRating}</span>
          <span className="text-xs text-gray-500">
            ({totalReviews} penilaian · {totalSold}+ terjual)
          </span>
        </div>
      </div>

      {/* Write review form with photos, video, anonymous, and repeat purchase support */}
      {canWriteReview && !submitted && (
        <div className="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
          <div className="flex items-center justify-between mb-3">
            <h3 className="font-bold text-[14px] text-gray-900 flex items-center gap-2">
              <span className="material-symbols-outlined text-primary text-[18px]">
                rate_review
              </span>
              Tulis Ulasan Pesanan #{targetOrder?.id}
            </h3>
            <span className="text-[11px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full flex items-center gap-1">
              <span className="material-symbols-outlined text-[13px]">stars</span>
              Bonus +50 Poin
            </span>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Star selector */}
            <div className="flex items-center gap-1">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  key={star}
                  type="button"
                  onClick={() => setRating(star)}
                  onMouseEnter={() => setHoverRating(star)}
                  onMouseLeave={() => setHoverRating(0)}
                  className="transition-transform hover:scale-110 p-0.5 cursor-pointer"
                >
                  <span
                    className="material-symbols-outlined text-[28px]"
                    style={{
                      color:
                        star <= (hoverRating || rating) ? "#f59e0b" : "#d1d5db",
                      fontVariationSettings:
                        star <= (hoverRating || rating)
                          ? "'FILL' 1"
                          : "'FILL' 0",
                    }}
                  >
                    star
                  </span>
                </button>
              ))}
              <span className="ml-2 text-xs font-semibold text-gray-600">
                {
                  [
                    "",
                    "Sangat Buruk",
                    "Buruk",
                    "Cukup",
                    "Bagus",
                    "Sangat Bagus",
                  ][hoverRating || rating]
                }
              </span>
            </div>

            {/* Comment */}
            <div>
              <textarea
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                rows={3}
                placeholder="Bagikan pengalaman Anda menggunakan produk ini (kualitas, kepuasan, performa)..."
                className="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:border-primary resize-none bg-white"
                required
              />
            </div>

            {/* Media Upload (Photos & Video) */}
            <div className="space-y-2">
              <div className="flex flex-wrap gap-2 items-center">
                {images.map((img, idx) => (
                  <div key={idx} className="relative w-14 h-14 rounded-lg overflow-hidden border border-gray-300">
                    <img src={img} alt={`Preview ${idx}`} className="w-full h-full object-cover" />
                    <button
                      type="button"
                      onClick={() => setImages((imgs) => imgs.filter((_, i) => i !== idx))}
                      className="absolute top-0.5 right-0.5 bg-black/70 text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px] hover:bg-red-600"
                    >
                      ×
                    </button>
                  </div>
                ))}
                {images.length < 5 && (
                  <label className="w-14 h-14 rounded-lg border-2 border-dashed border-gray-300 hover:border-primary flex flex-col items-center justify-center text-gray-500 hover:text-primary cursor-pointer transition-colors bg-white">
                    <span className="material-symbols-outlined text-[18px]">add_a_photo</span>
                    <span className="text-[9px] font-semibold">Foto</span>
                    <input type="file" accept="image/*" multiple onChange={handleImageUpload} className="hidden" />
                  </label>
                )}
                {!video && (
                  <label className="h-14 px-3 rounded-lg border-2 border-dashed border-gray-300 hover:border-primary flex items-center gap-1.5 text-gray-500 hover:text-primary cursor-pointer transition-colors bg-white">
                    <span className="material-symbols-outlined text-[18px]">videocam</span>
                    <span className="text-xs font-semibold">Tambah Video</span>
                    <input type="file" accept="video/*" onChange={handleVideoUpload} className="hidden" />
                  </label>
                )}
              </div>

              {video && (
                <div className="relative rounded-lg overflow-hidden border border-gray-300 bg-black max-w-xs max-h-32">
                  <video src={video} controls className="w-full max-h-32 object-contain" />
                  <button
                    type="button"
                    onClick={() => setVideo(null)}
                    className="absolute top-1 right-1 bg-red-600 text-white text-[10px] px-2 py-0.5 rounded font-bold"
                  >
                    Hapus Video
                  </button>
                </div>
              )}
            </div>

            {/* Checkbox Anonymous & Submit */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-blue-200">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={isAnonymous}
                  onChange={(e) => setIsAnonymous(e.target.checked)}
                  className="w-4 h-4 rounded text-primary focus:ring-primary accent-primary cursor-pointer"
                />
                <span className="text-xs font-medium text-gray-700">
                  Sembunyikan nama (Kirim sebagai Pengguna Anonim)
                </span>
              </label>

              <button
                type="submit"
                className="bg-primary hover:bg-primary/90 text-white font-bold px-5 py-2 rounded-lg text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer shadow-sm self-start sm:self-auto"
              >
                <span className="material-symbols-outlined text-[16px]">send</span>
                Kirim Ulasan (+50 Poin)
              </button>
            </div>
          </form>
        </div>
      )}

      {submitted && (
        <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-5 flex items-center gap-3">
          <span className="material-symbols-outlined text-emerald-600">
            check_circle
          </span>
          <span className="text-sm font-semibold text-emerald-800">
            Ulasan Anda berhasil dikirimkan! Bonus +50 Poin telah ditambahkan ke akun Anda.
          </span>
        </div>
      )}

      {!user && (
        <div className="bg-gray-50 rounded-xl p-4 mb-5 text-center text-sm text-gray-500 border border-gray-200">
          <Link to="/login" className="text-primary font-semibold underline">
            Masuk
          </Link>{" "}
          untuk menulis ulasan produk ini jika sudah pernah memesan.
        </div>
      )}

      {user && matchingOrders.length === 0 && (
        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-center text-sm text-amber-800">
          <span className="material-symbols-outlined text-[15px] align-middle mr-1">
            info
          </span>
          Ulasan hanya dapat ditulis setelah Anda memesan produk ini.
        </div>
      )}

      {user && matchingOrders.length > 0 && !canWriteReview && !submitted && (
        <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-5 flex items-center gap-2 text-sm text-emerald-800">
          <span className="material-symbols-outlined text-[16px]">
            check_circle
          </span>
          Semua pesanan Anda untuk produk ini ({matchingOrders.length}x pembelian) sudah diulas. Jika memesan ulang, Anda dapat mengulas kembali.
        </div>
      )}

      {/* Reviews List */}
      {reviews.length === 0 ? (
        <div className="py-8 text-center text-gray-400 border-2 border-dashed border-gray-200 rounded-xl">
          <span className="material-symbols-outlined text-[40px] mb-2">
            rate_review
          </span>
          <p className="text-sm">
            Belum ada ulasan. Jadilah yang pertama mengulas!
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {reviews.map((r) => (
            <div key={r.id} className="bg-gray-50 rounded-xl p-4 border border-gray-100">
              <div className="flex items-center gap-3 mb-2">
                <div className="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                  {r.isAnonymous ? "?" : r.userName?.charAt(0)?.toUpperCase() || "U"}
                </div>
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold text-sm text-gray-900">
                      {r.isAnonymous ? "Pengguna Anonim" : r.userName}
                    </span>
                    <span className="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.2 rounded border border-emerald-200">
                      ✓ Pembeli Terverifikasi
                    </span>
                  </div>
                  <div className="text-xs text-gray-400 flex items-center gap-2">
                    <span>{r.date}</span>
                    {r.orderId && <span>• Pesanan #{r.orderId}</span>}
                  </div>
                </div>
                <div className="flex">
                  {[1, 2, 3, 4, 5].map((s) => (
                    <span
                      key={s}
                      className="material-symbols-outlined text-[15px]"
                      style={{
                        color: s <= r.rating ? "#f59e0b" : "#d1d5db",
                        fontVariationSettings:
                          s <= r.rating ? "'FILL' 1" : "'FILL' 0",
                      }}
                    >
                      star
                    </span>
                  ))}
                </div>
              </div>

              <p className="text-sm text-gray-700 leading-relaxed">
                {r.comment}
              </p>

              {/* Photos attached */}
              {r.images && r.images.length > 0 && (
                <div className="flex flex-wrap gap-2 mt-3">
                  {r.images.map((img, idx) => (
                    <img
                      key={idx}
                      src={img}
                      alt="Ulasan foto"
                      onClick={() => setSelectedImageModal(img)}
                      className="w-16 h-16 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-85 transition-opacity"
                    />
                  ))}
                </div>
              )}

              {/* Video attached */}
              {r.video && (
                <div className="mt-3 max-w-xs rounded-lg overflow-hidden border border-gray-300 bg-black">
                  <video src={r.video} controls className="w-full max-h-40 object-contain" />
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Lightbox Modal for Full Image View */}
      {selectedImageModal && (
        <div
          onClick={() => setSelectedImageModal(null)}
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 cursor-pointer"
        >
          <div className="relative max-w-2xl max-h-[90vh]">
            <img src={selectedImageModal} alt="Preview" className="max-w-full max-h-[85vh] object-contain rounded-xl" />
            <button
              onClick={() => setSelectedImageModal(null)}
              className="absolute top-2 right-2 bg-black/60 text-white rounded-full p-2 hover:bg-black/90"
            >
              <span className="material-symbols-outlined text-[20px]">close</span>
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Image Gallery ────────────────────────────────────────────────────────
function Gallery({ images, badges, isWishlisted, onToggleWishlist }) {
  const [active, setActive] = useState(0);
  const src = images[active] || "";

  return (
    <div className="flex flex-col gap-3">
      {/* Main image */}
      <div className="relative aspect-square bg-gray-50 rounded-xl overflow-hidden border border-gray-200 flex items-center justify-center">
        {/* Badges overlay on image */}
        {badges?.left && (
          <span className="absolute top-3 left-3 z-10 bg-red-600 text-white text-[11px] font-bold px-2.5 py-1 rounded">
            {badges.left}
          </span>
        )}
        {badges?.right && (
          <span className="absolute top-3 right-3 z-10 bg-emerald-600 text-white text-[11px] font-bold px-2.5 py-1 rounded">
            {badges.right}
          </span>
        )}
        <img
          key={src}
          src={src}
          alt="Product"
          className="max-w-full max-h-full object-contain p-4 transition-all duration-300"
          onError={(e) => {
            e.target.src = "https://picsum.photos/seed/product/800/800";
          }}
        />
      </div>
      {/* Thumbnails */}
      {images.length > 1 && (
        <div className="flex gap-2 overflow-x-auto pb-1">
          {images.map((img, i) => (
            <button
              key={i}
              onClick={() => setActive(i)}
              aria-label={`Gambar ${i + 1}`}
              className={`w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden border-2 transition-all ${i === active ? "border-primary shadow-sm" : "border-gray-200 hover:border-primary/50"}`}
            >
              <img
                src={img}
                alt=""
                className="w-full h-full object-contain p-1"
                onError={(e) => {
                  e.target.src = "https://picsum.photos/seed/thumb/200/200";
                }}
              />
            </button>
          ))}
        </div>
      )}
      {/* Share & Wishlist row */}
      <div className="flex items-center justify-between text-xs text-gray-500 pt-1">
        <div className="flex items-center gap-4">
          <button className="flex items-center gap-1 hover:text-primary transition-colors">
            <span className="material-symbols-outlined text-[16px]">share</span>{" "}
            Bagikan
          </button>
          <button
            className="flex items-center gap-1 hover:text-primary transition-colors"
            onClick={() => {
              navigator.clipboard?.writeText(window.location.href);
              alert("Link produk disalin ke clipboard!");
            }}
          >
            <span className="material-symbols-outlined text-[16px]">link</span>{" "}
            Salin Link
          </button>
        </div>
        {onToggleWishlist && (
          <button
            type="button"
            onClick={onToggleWishlist}
            className={`flex items-center gap-1 font-medium transition-colors ${isWishlisted ? "text-red-500 font-bold" : "text-gray-500 hover:text-red-500"}`}
          >
            <span
              className="material-symbols-outlined text-[18px]"
              style={{
                fontVariationSettings: isWishlisted ? "'FILL' 1" : "'FILL' 0",
              }}
            >
              favorite
            </span>
            <span>
              {isWishlisted ? "Tersimpan di Wishlist" : "Favoritkan Produk"}
            </span>
          </button>
        )}
      </div>
    </div>
  );
}

// ─── Variant Selector ─────────────────────────────────────────────────────
function VariantSelector({ variants, selectedId, onSelect }) {
  if (!variants.length) return null;
  const attrGroups = {};
  for (const v of variants) {
    for (const attr of v.attributes || []) {
      if (!attrGroups[attr.name]) attrGroups[attr.name] = new Set();
      attrGroups[attr.name].add(attr.value);
    }
  }
  const selected = variants.find((v) => v.id === selectedId);

  return (
    <div className="flex flex-col gap-3">
      {Object.entries(attrGroups).map(([name, values]) => (
        <div key={name}>
          <p className="text-[12px] font-semibold text-gray-500 uppercase mb-2">
            {name}:{" "}
            <span className="text-gray-800 normal-case font-medium">
              {selected?.attributes?.find((a) => a.name === name)?.value ||
                "Pilih"}
            </span>
          </p>
          <div className="flex flex-wrap gap-2">
            {[...values].map((val) => {
              const v = variants.find((vr) =>
                vr.attributes?.some((a) => a.name === name && a.value === val),
              );
              const isSelected = selected?.attributes?.some(
                (a) => a.name === name && a.value === val,
              );
              const isOOS = v?.stock === 0;
              return (
                <button
                  key={val}
                  onClick={() => v && onSelect(v.id)}
                  disabled={isOOS}
                  className={`px-3 py-2 rounded-lg border-2 text-[13px] font-medium transition-all flex items-center gap-1.5
                    ${isSelected ? "border-primary bg-primary/5 text-primary" : isOOS ? "border-gray-200 text-gray-300 cursor-not-allowed" : "border-gray-200 text-gray-700 hover:border-primary/50"}`}
                >
                  {/* Color dot if name is "Warna" */}
                  {name.toLowerCase().includes("warna") && (
                    <span
                      className="w-3 h-3 rounded-full border border-gray-300 inline-block"
                      style={{
                        background:
                          val.toLowerCase().includes("hitam") ||
                          val.toLowerCase().includes("graphite")
                            ? "#374151"
                            : val.toLowerCase().includes("putih")
                              ? "#f9fafb"
                              : "#d1d5db",
                      }}
                    />
                  )}
                  {val}
                  {isOOS && <span className="text-[10px]">(Habis)</span>}
                </button>
              );
            })}
          </div>
        </div>
      ))}
    </div>
  );
}

// Helper date utilities without timezone shift issues
function getISODate(d = new Date()) {
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function addDaysToDate(baseDateStr, days) {
  const [y, m, d] = (baseDateStr || getISODate()).split('-').map(Number);
  const date = new Date(y, m - 1, d);
  date.setDate(date.getDate() + days);
  return getISODate(date);
}

function formatDateDisplay(dateStr) {
  if (!dateStr) return '';
  const [y, m, d] = dateStr.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  return date.toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
}

function getRentalDays(startDateStr, endDateStr) {
  if (!startDateStr || !endDateStr) return 1;
  const [y1, m1, d1] = startDateStr.split('-').map(Number);
  const [y2, m2, d2] = endDateStr.split('-').map(Number);
  const date1 = new Date(y1, m1 - 1, d1);
  const date2 = new Date(y2, m2 - 1, d2);
  const diffTime = date2.getTime() - date1.getTime();
  const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
  if (diffDays <= 0) return 1;
  return diffDays;
}

const RENTAL_DURATION_PRESETS = [
  { days: 1, label: '1 Hari' },
  { days: 2, label: '2 Hari' },
  { days: 3, label: '3 Hari', isPopular: true },
  { days: 5, label: '5 Hari' },
  { days: 7, label: '1 Minggu' },
  { days: 14, label: '2 Minggu' },
  { days: 30, label: '1 Bulan' },
];

const RENTAL_QUICK_STARTS = [
  { label: 'Hari Ini', offsetDays: 0 },
  { label: 'Besok', offsetDays: 1 },
  { label: 'Lusa', offsetDays: 2 },
];

const RENTAL_QUICK_TIMES = ['08:00', '09:00', '10:00', '13:00', '14:00', '16:00', '18:00'];
const LICENSE_SEAT_PRESETS = [1, 5, 10, 25, 50, 100];

// ─── Flash Sale Countdown Hook ─────────────────────────────────────────────
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
  }, [targetDate]);
  return time;
}

// ─── Main Page ─────────────────────────────────────────────────────────────
export default function ProductDetailPage() {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { addItem } = useCart();
  const { user, updateUser } = useAuth();
  const { toggleWishlist, isInWishlist } = useWishlist();
  const { showToast } = useToast();

  const flashSaleEnd = useRef((() => {
    const d = new Date();
    d.setHours(23, 59, 59, 999);
    return d.getTime();
  })());
  const countdown = useCountdown(flashSaleEnd.current);

  const [product, setProduct] = useState(null);
  const [variants, setVariants] = useState([]);
  const [selectedVariantId, setSelectedVariantId] = useState(null);
  const [relatedProducts, setRelatedProducts] = useState([]);
  const [quantity, setQuantity] = useState(1);
  const [loading, setLoading] = useState(true);
  const [added, setAdded] = useState(false);
  const [brands, setBrands] = useState([]);
  const [categories, setCategories] = useState([]);

  const todayStr = getISODate();
  const tomorrowStr = addDaysToDate(todayStr, 1);

  // Rental (Sewa) state
  const [rentalStartDate, setRentalStartDate] = useState(todayStr);
  const [rentalEndDate, setRentalEndDate] = useState(tomorrowStr);
  const [rentalStartTime, setRentalStartTime] = useState('09:00');
  const [rentalEndTime, setRentalEndTime] = useState('17:00');
  const [rentalDurationPreset, setRentalDurationPreset] = useState(1);

  // License (Cloud/Software) state
  const LICENSE_DURATIONS = [
    { id: '1m', label: '1 Bulan', multiplier: 0.1, badge: 'Fleksibel' },
    { id: '1y', label: '1 Tahun', multiplier: 1, badge: 'Populer', isPopular: true },
    { id: '2y', label: '2 Tahun', multiplier: 1.8, badge: 'Hemat 10%' },
    { id: '3y', label: '3 Tahun', multiplier: 2.5, badge: 'Hemat 17%' },
    { id: 'lifetime', label: 'Lifetime', multiplier: 4, badge: 'Permanen' },
  ];
  const [licenseDuration, setLicenseDuration] = useState('1y');
  const [licenseSeats, setLicenseSeats] = useState(1);

  // Scroll to top on slug change
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: "instant" });
  }, [slug]);

  useEffect(() => {
    setLoading(true);
    setProduct(null);
    setVariants([]);
    setSelectedVariantId(null);
    setQuantity(1);
    setAdded(false);

    Promise.all([getProduct(slug), getBrands(), getCategories()]).then(
      async ([p, brs, cats]) => {
        setBrands(brs);
        setCategories(cats);
        if (!p) {
          setLoading(false);
          return;
        }
        setProduct(p);

        if (p.type === "variable") {
          const vars = await getVariants(p.id);
          setVariants(vars);
          if (vars.length > 0) setSelectedVariantId(vars[0].id);
        }
        const related = await getRelatedProducts(p, 4);
        setRelatedProducts(related);
        setLoading(false);
      },
    );
  }, [slug]);

  const selectedVariant = variants.find((v) => v.id === selectedVariantId);
  const displayImages = (() => {
    const raw = selectedVariant?.images?.length
      ? selectedVariant.images
      : product?.images || [];
    if (!raw.length) return [];
    if (typeof raw[0] === "string" && raw[0].includes(",")) {
      return raw[0]
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean);
    }
    return raw;
  })();

  const regularPrice = selectedVariant?.regular_price ?? product?.regular_price;
  const salePrice = selectedVariant?.sale_price ?? product?.sale_price;
  const stock = selectedVariant?.stock ?? product?.stock ?? 0;
  const isOutOfStock = stock === 0;
  const displayPrice = salePrice || regularPrice;
  const hasDiscount = salePrice && salePrice < regularPrice;
  const discountPct = hasDiscount
    ? Math.round((1 - salePrice / regularPrice) * 100)
    : 0;

  // Brand & categories from product
  const brand = brands.find((b) => b.id === product?.brand_id);
  const productCats = categories.filter((c) =>
    (product?.category_ids || []).includes(c.id),
  );
  const primaryCat = productCats.find((c) => !c.parent_id) || productCats[0];
  const subCat = productCats.find((c) => c.parent_id);

  // Detect product type
  const RENTAL_CAT_IDS = [13, 14]; // 'Sewa', 'Sewa Produk'
  const LICENSE_SKUS = ['CFQ7TTC0LH18', 'CFQ7TTC0LDPB'];
  const isRental = (product?.category_ids || []).some(id => RENTAL_CAT_IDS.includes(id))
    || (product?.name || '').toLowerCase().includes('sewa ');
  const isLicense = LICENSE_SKUS.includes((product?.sku || '').toUpperCase());
  const isFlashSale = Boolean(product?.is_flash_sale || (hasDiscount && !isRental && !isLicense));

  // Rental price calculation
  const rentalDays = getRentalDays(rentalStartDate, rentalEndDate);
  const rentalTotal = rentalDays > 0 ? displayPrice * rentalDays : displayPrice;

  // License price calculation
  const selectedLicenseDuration = LICENSE_DURATIONS.find(d => d.id === licenseDuration);
  const licensePricePerSeat = Math.round(displayPrice * (selectedLicenseDuration?.multiplier || 1));
  const licenseTotal = licensePricePerSeat * licenseSeats;

  function handleAddToCart() {
    if (isOutOfStock) return;

    if (isRental) {
      if (!rentalStartDate || !rentalEndDate || rentalDays <= 0) {
        showToast({ type: 'error', title: 'Pilih Tanggal Sewa', message: 'Pilih tanggal mulai dan selesai sewa terlebih dahulu.' });
        return;
      }
      addItem({
        ...product,
        cartKey: `${product.id}-rental-${rentalStartDate}-${rentalEndDate}-${rentalStartTime}-${rentalEndTime}`,
        name: `${product.name} (Sewa ${rentalDays} Hari: ${rentalStartDate} s/d ${rentalEndDate})`,
        regular_price: rentalTotal,
        sale_price: null,
        rentalInfo: {
          isRental: true,
          days: rentalDays,
          startDate: rentalStartDate,
          endDate: rentalEndDate,
          startTime: rentalStartTime,
          endTime: rentalEndTime,
          formattedStart: `${formatDateDisplay(rentalStartDate)} ${rentalStartTime}`,
          formattedEnd: `${formatDateDisplay(rentalEndDate)} ${rentalEndTime}`,
          pricePerDay: displayPrice,
          rentalTotal,
        }
      }, 1, selectedVariant);
    } else if (isLicense) {
      addItem({
        ...product,
        cartKey: `${product.id}-license-${licenseDuration}-${licenseSeats}`,
        name: `${product.name} - Lisensi ${selectedLicenseDuration?.label} (${licenseSeats} Seat)`,
        regular_price: licenseTotal,
        sale_price: null,
        licenseInfo: {
          isLicense: true,
          duration: selectedLicenseDuration?.label,
          seats: licenseSeats,
          pricePerSeat: licensePricePerSeat,
          licenseTotal,
        }
      }, 1, selectedVariant);
    } else {
      addItem(product, quantity, selectedVariant);
    }

    setAdded(true);
    setTimeout(() => setAdded(false), 2000);
    showToast({
      type: 'cart',
      title: isRental ? `Booking ${rentalDays} Hari Ditambahkan!` : isLicense ? `Lisensi ${licenseSeats} Seat Ditambahkan!` : 'Berhasil Masuk Keranjang',
      message: product.name,
      link: '/keranjang',
      linkText: 'Lihat Keranjang',
    });
  }

  function handleBuyNow() {
    if (isOutOfStock) return;
    if (isRental) {
      if (!rentalStartDate || !rentalEndDate || rentalDays <= 0) {
        showToast({ type: 'error', title: 'Pilih Tanggal Sewa', message: 'Pilih tanggal mulai dan selesai sewa terlebih dahulu.' });
        return;
      }
      addItem({
        ...product,
        cartKey: `${product.id}-rental-${rentalStartDate}-${rentalEndDate}-${rentalStartTime}-${rentalEndTime}`,
        name: `${product.name} (Sewa ${rentalDays} Hari: ${rentalStartDate} s/d ${rentalEndDate})`,
        regular_price: rentalTotal,
        sale_price: null,
        rentalInfo: {
          isRental: true,
          days: rentalDays,
          startDate: rentalStartDate,
          endDate: rentalEndDate,
          startTime: rentalStartTime,
          endTime: rentalEndTime,
          formattedStart: `${formatDateDisplay(rentalStartDate)} ${rentalStartTime}`,
          formattedEnd: `${formatDateDisplay(rentalEndDate)} ${rentalEndTime}`,
          pricePerDay: displayPrice,
          rentalTotal,
        }
      }, 1, selectedVariant);
    } else if (isLicense) {
      addItem({
        ...product,
        cartKey: `${product.id}-license-${licenseDuration}-${licenseSeats}`,
        name: `${product.name} - Lisensi ${selectedLicenseDuration?.label} (${licenseSeats} Seat)`,
        regular_price: licenseTotal,
        sale_price: null,
        licenseInfo: {
          isLicense: true,
          duration: selectedLicenseDuration?.label,
          seats: licenseSeats,
          pricePerSeat: licensePricePerSeat,
          licenseTotal,
        }
      }, 1, selectedVariant);
    } else {
      addItem(product, quantity, selectedVariant);
    }
    navigate('/checkout');
  }

  if (loading) {
    return (
      <div className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-6 w-full">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div className="aspect-square bg-gray-100 rounded-xl animate-pulse" />
          <div className="flex flex-col gap-4">
            <div className="h-6 bg-gray-100 rounded w-1/2 animate-pulse" />
            <div className="h-8 bg-gray-100 rounded animate-pulse" />
            <div className="h-4 bg-gray-100 rounded w-2/3 animate-pulse" />
            <div className="h-16 bg-gray-100 rounded animate-pulse" />
            <div className="h-12 bg-gray-100 rounded animate-pulse" />
          </div>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[50dvh] px-gutter">
        <span className="material-symbols-outlined text-[56px] mb-4 text-gray-300">
          inventory_2
        </span>
        <h2 className="text-lg font-bold text-gray-800 mb-2">
          Produk tidak ditemukan
        </h2>
        <p className="text-sm text-gray-500 mb-6">
          Produk yang Anda cari tidak tersedia atau telah dihapus.
        </p>
        <Link
          to="/katalog"
          className="bg-primary text-white font-semibold px-6 py-2.5 rounded-lg text-sm"
        >
          Kembali ke Katalog
        </Link>
      </div>
    );
  }

  return (
    <div className="flex flex-col w-full pb-[80px] lg:pb-0 bg-gray-50 min-h-screen">
      <div className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-4 w-full">
        {/* ── Breadcrumb ──────────────────────────────────────────────── */}
        <nav
          className="flex items-center gap-1 mb-4 text-[12px] text-gray-500 flex-wrap"
          aria-label="Breadcrumb"
        >
          <Link to="/" className="hover:text-primary transition-colors">
            Beranda
          </Link>
          <span className="material-symbols-outlined text-[14px]">
            chevron_right
          </span>
          {primaryCat && (
            <>
              <Link
                to={`/katalog?kategori=${primaryCat.slug}`}
                className="hover:text-primary transition-colors line-clamp-1"
              >
                {primaryCat.name}
              </Link>
              <span className="material-symbols-outlined text-[14px]">
                chevron_right
              </span>
            </>
          )}
          {subCat && primaryCat && subCat.id !== primaryCat.id && (
            <>
              <Link
                to={`/katalog?kategori=${subCat.slug}`}
                className="hover:text-primary transition-colors"
              >
                {subCat.name}
              </Link>
              <span className="material-symbols-outlined text-[14px]">
                chevron_right
              </span>
            </>
          )}
          {brand && (
            <>
              <Link
                to={`/katalog?brand=${brand.slug}`}
                className="hover:text-primary transition-colors"
              >
                {brand.name}
              </Link>
              <span className="material-symbols-outlined text-[14px]">
                chevron_right
              </span>
            </>
          )}
          <span className="text-gray-800 font-medium line-clamp-1">
            {product.name}
          </span>
        </nav>

        {/* ── Main Content Card ────────────────────────────────────────── */}
        <div className="bg-white border border-gray-200 rounded-xl p-4 sm:p-6 mb-4">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">
            {/* ── Left: Gallery ─────────────────────────────────────── */}
            <Gallery
              images={
                displayImages.length
                  ? displayImages
                  : [`https://picsum.photos/seed/${product.id}/600/600`]
              }
              badges={{
                left: product.is_featured ? "OFFICIAL MALL" : null,
                right: product.attributes?.find((a) => a.name === "Garansi")
                  ?.value
                  ? `Garansi ${product.attributes.find((a) => a.name === "Garansi")?.value}`
                  : "Garansi Resmi",
              }}
              isWishlisted={isInWishlist(`prod-${product.id}`)}
              onToggleWishlist={() => {
                const key = `prod-${product.id}`;
                const willAdd = !isInWishlist(key);
                toggleWishlist(key);
                showToast({
                  type: willAdd ? 'wishlist' : 'info',
                  title: willAdd ? 'Ditambahkan ke Wishlist' : 'Dihapus dari Wishlist',
                  message: product.name,
                  link: '/wishlist',
                  linkText: 'Lihat Wishlist',
                });
              }}
            />

            {/* ── Right: Product Info ───────────────────────────────── */}
            <div className="flex flex-col gap-4">
              {/* Badges row */}
              <div className="flex items-center flex-wrap gap-2">
                {isFlashSale && (
                  <span className="bg-gradient-to-r from-red-600 via-rose-600 to-red-500 text-white text-[11px] font-bold px-2.5 py-0.5 rounded flex items-center gap-1 shadow-sm animate-pulse">
                    <span className="material-symbols-outlined text-[13px]">local_fire_department</span>
                    Flash Sale
                  </span>
                )}
                {isRental ? (
                  <span className="bg-orange-600 text-white text-[11px] font-bold px-2.5 py-0.5 rounded flex items-center gap-1 shadow-sm">
                    <span className="material-symbols-outlined text-[13px]">calendar_month</span>
                    Layanan Sewa
                  </span>
                ) : isLicense ? (
                  <span className="bg-purple-600 text-white text-[11px] font-bold px-2.5 py-0.5 rounded flex items-center gap-1 shadow-sm">
                    <span className="material-symbols-outlined text-[13px]">key</span>
                    Lisensi Digital
                  </span>
                ) : (
                  <span className="bg-primary text-white text-[11px] font-bold px-2.5 py-0.5 rounded">
                    Hot
                  </span>
                )}
                <span className="bg-emerald-600 text-white text-[11px] font-bold px-2.5 py-0.5 rounded">
                  100% Original
                </span>
                {product.sku && (
                  <span className="text-[11px] text-gray-500">
                    SKU{" "}
                    <span className="font-mono font-semibold text-gray-700">
                      {product.sku}
                    </span>
                  </span>
                )}
              </div>

              {/* Product name */}
              <h1 className="text-[20px] lg:text-[22px] font-bold text-gray-900 leading-snug">
                {product.name}
              </h1>

              {/* Rating row */}
              <div className="flex items-center flex-wrap gap-3 text-sm">
                <a
                  href="#section-ulasan"
                  onClick={(e) => {
                    e.preventDefault();
                    document.getElementById('section-ulasan')?.scrollIntoView({ behavior: 'smooth' });
                  }}
                  className="flex items-center gap-1.5 cursor-pointer group hover:opacity-85 transition-opacity"
                >
                  <div className="flex items-center gap-0.5">
                    {[1, 2, 3, 4, 5].map((s) => (
                      <span
                        key={s}
                        className="material-symbols-outlined text-[16px]"
                        style={{
                          color: "#f59e0b",
                          fontVariationSettings: "'FILL' 1",
                        }}
                      >
                        star
                      </span>
                    ))}
                  </div>
                  <span className="font-bold text-gray-800 ml-0.5">4.9</span>
                  <span className="text-gray-400 mx-1">|</span>
                  <span className="text-primary font-semibold text-[13px] underline group-hover:text-primary-hover">
                    Lihat Ulasan (33 Penilaian)
                  </span>
                </a>
                <span className="text-gray-400">|</span>
                <span className="text-gray-500 text-[13px]">120+ Terjual</span>
              </div>

              {/* Flash Sale Box with Accommerce Red Gradient & Countdown */}
              {isFlashSale ? (
                <div className="rounded-xl overflow-hidden shadow-sm border border-red-200">
                  {/* Accommerce Red Gradient Header */}
                  <div className="bg-gradient-to-r from-red-600 via-rose-600 to-red-500 px-4 py-2.5 text-white flex items-center justify-between flex-wrap gap-2 shadow-sm">
                    <div className="flex items-center gap-2">
                      <span className="material-symbols-outlined text-[20px] text-yellow-300 animate-pulse">local_fire_department</span>
                      <span className="font-extrabold text-[15px] sm:text-[16px] tracking-wider uppercase">FLASH SALE</span>
                    </div>
                    <div className="flex items-center gap-2 ml-auto">
                      <span className="text-[11px] font-semibold text-white/90 uppercase tracking-wider flex items-center gap-1">
                        <span className="material-symbols-outlined text-[15px]">schedule</span>
                        Berakhir dalam:
                      </span>
                      <div className="flex items-center gap-1">
                        {[countdown.h, countdown.m, countdown.s].map((v, i) => (
                          <span
                            key={i}
                            className="bg-black/40 backdrop-blur-sm border border-white/20 text-white text-[12px] font-mono font-bold px-1.5 py-0.5 rounded min-w-[24px] text-center shadow-inner"
                          >
                            {String(v).padStart(2, '0')}
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Price Body */}
                  <div className="bg-[#fff9f9] p-4">
                    <div className="flex items-baseline gap-3 flex-wrap">
                      <div className="text-[30px] sm:text-[34px] font-black text-red-600 leading-none">
                        {formatPrice(displayPrice)}
                      </div>
                      {hasDiscount && (
                        <span className="text-[15px] sm:text-[16px] text-gray-400 line-through">
                          {formatPrice(regularPrice)}
                        </span>
                      )}
                      {discountPct > 0 && (
                        <span className="bg-red-500 text-white text-[12px] font-bold px-1.5 py-0.5 rounded shadow-sm">
                          -{discountPct}%
                        </span>
                      )}
                      <span className="text-[12px] text-gray-500 font-normal">+ PPN 11%</span>
                    </div>

                    {displayPrice > 0 && (
                      <div className="mt-2.5 flex items-center gap-1.5 text-amber-700 font-semibold text-[12px] bg-amber-100/60 w-max px-2.5 py-1 rounded">
                        <span className="material-symbols-outlined text-[16px] animate-pulse">
                          stars
                        </span>
                        Dapatkan hingga {Math.floor(displayPrice / 10000)} Poin Reward
                      </div>
                    )}
                    {product.is_dummy_price && (
                      <p className="text-[11px] text-gray-400 mt-2">
                        * Harga estimasi. Hubungi CS untuk penawaran resmi.
                      </p>
                    )}

                    {/* Benefits row */}
                    <div className="flex flex-wrap gap-3 mt-3 pt-3 border-t border-orange-100">
                      <span className="flex items-center gap-1.5 text-[12px] text-gray-600">
                        <span className="material-symbols-outlined text-primary text-[16px]">
                          verified
                        </span>
                        100% Produk Original
                      </span>
                      <span className="flex items-center gap-1.5 text-[12px] text-gray-600">
                        <span className="material-symbols-outlined text-primary text-[16px]">
                          local_shipping
                        </span>
                        Siap Kirim ke Seluruh Indonesia
                      </span>
                      <span className="flex items-center gap-1.5 text-[12px] text-gray-600">
                        <span className="material-symbols-outlined text-primary text-[16px]">
                          support_agent
                        </span>
                        Garansi & Support Resmi
                      </span>
                    </div>
                  </div>
                </div>
              ) : (
                /* Regular / Rental / License Price box */
                <div className="bg-[#fff8f0] border border-orange-200 rounded-xl p-4">
                  {hasDiscount && (
                    <div className="flex items-center gap-2 mb-1">
                      <span className="text-[13px] text-gray-400 line-through">
                        {formatPrice(regularPrice)}
                      </span>
                      {isRental && <span className="text-[11px] text-orange-600 font-semibold">/ hari</span>}
                      {isLicense && !isRental && <span className="text-[11px] text-purple-600 font-semibold">/ seat / thn</span>}
                      <span className="bg-red-500 text-white text-[11px] font-bold px-2 py-0.5 rounded">
                        DISKON {discountPct}%
                      </span>
                    </div>
                  )}
                  <div className="flex items-baseline gap-2 flex-wrap">
                    <div className={`text-[28px] font-extrabold leading-none ${
                      isRental ? 'text-orange-600' : isLicense ? 'text-purple-700' : 'text-primary'
                    }`}>
                      {formatPrice(displayPrice)}
                    </div>
                    {isRental && (
                      <span className="text-[13px] font-bold text-orange-700 bg-orange-100 px-2 py-0.5 rounded">
                        / hari
                      </span>
                    )}
                    {isLicense && !isRental && (
                      <span className="text-[13px] font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded">
                        / seat / tahun
                      </span>
                    )}
                    <span className="text-[12px] text-gray-500 font-normal">+ PPN 11%</span>
                  </div>
                  {displayPrice > 0 && (
                    <div className="mt-2.5 flex items-center gap-1.5 text-amber-700 font-semibold text-[12px] bg-amber-100/60 w-max px-2.5 py-1 rounded">
                      <span className="material-symbols-outlined text-[16px] animate-pulse">
                        stars
                      </span>
                      Dapatkan hingga {Math.floor(displayPrice / 10000)} Poin
                      Reward
                    </div>
                  )}
                  {product.is_dummy_price && (
                    <p className="text-[11px] text-gray-400 mt-2">
                      * Harga estimasi. Hubungi CS untuk penawaran resmi.
                    </p>
                  )}

                  {/* Benefits row */}
                  <div className="flex flex-wrap gap-3 mt-3 pt-3 border-t border-orange-100">
                    <span className="flex items-center gap-1.5 text-[12px] text-gray-600">
                      <span className="material-symbols-outlined text-primary text-[16px]">
                        verified
                      </span>
                      100% Produk Original
                    </span>
                    <span className="flex items-center gap-1.5 text-[12px] text-gray-600">
                      <span className="material-symbols-outlined text-primary text-[16px]">
                        local_shipping
                      </span>
                      Siap Kirim ke Seluruh Indonesia
                    </span>
                    <span className="flex items-center gap-1.5 text-[12px] text-gray-600">
                      <span className="material-symbols-outlined text-primary text-[16px]">
                        support_agent
                      </span>
                      Garansi & Support Resmi
                    </span>
                  </div>
                </div>
              )}

              {/* Voucher row */}
              <div className="flex items-center flex-wrap gap-2 text-[12px]">
                <span className="text-gray-500 font-medium flex-shrink-0">
                  Voucher Toko:
                </span>
                <span className="bg-red-50 border border-red-200 text-red-600 font-bold px-2 py-0.5 rounded text-[11px]">
                  Diskon 10% (SAVING10)
                </span>
                <span className="bg-blue-50 border border-blue-200 text-primary font-bold px-2 py-0.5 rounded text-[11px]">
                  Diskon Rp 100RB (TECH100K)
                </span>
              </div>

              {/* Shipping info */}
              <div className="flex flex-col gap-2 text-[13px]">
                <div className="flex gap-3">
                  <span className="text-gray-500 w-20 flex-shrink-0">
                    Pengiriman
                  </span>
                  <div className="flex flex-col gap-0.5">
                    <span className="flex items-center gap-1.5 text-gray-700">
                      <span className="material-symbols-outlined text-[15px] text-primary">
                        warehouse
                      </span>
                      Dikirim dari:{" "}
                      <strong>Gudang BSD, Tangerang Selatan</strong>
                    </span>
                    <span className="flex items-center gap-1.5 text-gray-700">
                      <span className="material-symbols-outlined text-[15px] text-emerald-600">
                        local_shipping
                      </span>
                      Ongkos kirim:{" "}
                      <strong className="text-emerald-600">
                        Gratis Ongkir Jabodetabek
                      </strong>
                      <span className="text-gray-400">
                        {" "}
                        / Luar kota via JNE, Trucking
                      </span>
                    </span>
                    <span className="flex items-center gap-1.5 text-gray-500">
                      <span className="material-symbols-outlined text-[15px]">
                        schedule
                      </span>
                      Estimasi tiba 1–2 hari kerja
                    </span>
                  </div>
                </div>
              </div>

              {/* Variants */}
              {variants.length > 0 && (
                <div className="flex flex-col gap-1">
                  <span className="text-gray-500 text-[13px] font-medium">
                    Varian:
                  </span>
                  <VariantSelector
                    variants={variants}
                    selectedId={selectedVariantId}
                    onSelect={setSelectedVariantId}
                  />
                </div>
              )}

              {/* ── Rental: Interactive Date & Time Picker ──────────────── */}
              {isRental && (
                <div className="flex flex-col gap-3.5 bg-orange-50/40 border border-orange-200/80 rounded-2xl p-4 sm:p-5">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <span className="w-8 h-8 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-sm shadow-orange-500/20">
                        <span className="material-symbols-outlined text-[18px]">event_available</span>
                      </span>
                      <div>
                        <h4 className="text-[13px] sm:text-[14px] font-bold text-gray-900 leading-tight">
                          Jadwal Sewa & Booking
                        </h4>
                        <p className="text-[11px] text-gray-500">Pilih tanggal dan jam sewa (tinggal klik)</p>
                      </div>
                    </div>
                    <span className="text-[12px] font-extrabold text-orange-600 bg-orange-100 px-2.5 py-1 rounded-lg">
                      {rentalDays} Hari Terpilih
                    </span>
                  </div>

                  {/* Preset Durasi Sewa */}
                  <div>
                    <label className="text-[11px] font-bold text-gray-700 uppercase tracking-wide block mb-1.5">
                      Pilihan Durasi Sewa (Preset Cepat):
                    </label>
                    <div className="grid grid-cols-4 sm:grid-cols-7 gap-1.5">
                      {RENTAL_DURATION_PRESETS.map((p) => {
                        const isSelected = rentalDurationPreset === p.days;
                        return (
                          <button
                            key={p.days}
                            type="button"
                            onClick={() => {
                              setRentalDurationPreset(p.days);
                              const newEnd = addDaysToDate(rentalStartDate, p.days);
                              setRentalEndDate(newEnd);
                            }}
                            className={`py-2 px-1 rounded-xl text-center text-[11px] font-semibold transition-all relative border
                              ${isSelected
                                ? 'bg-orange-500 text-white border-orange-500 shadow-sm shadow-orange-500/30 scale-[1.02]'
                                : 'bg-white hover:bg-orange-50/50 text-gray-700 border-gray-200'
                              }`}
                          >
                            {p.isPopular && !isSelected && (
                              <span className="absolute -top-1.5 text-[8px] bg-amber-400 text-amber-950 px-1 rounded-full font-bold">
                                Populer
                              </span>
                            )}
                            <span>{p.label}</span>
                          </button>
                        );
                      })}
                    </div>
                  </div>

                  {/* Section 1: Mulai Sewa (Ambil / Kirim) */}
                  <div className="bg-white p-3 rounded-xl border border-gray-200 space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="text-[12px] font-bold text-gray-800 flex items-center gap-1.5">
                        <span className="material-symbols-outlined text-[16px] text-emerald-600">calendar_today</span>
                        1. Mulai Sewa (Ambil / Kirim)
                      </span>
                      <span className="text-[11px] text-emerald-700 font-semibold">
                        {formatDateDisplay(rentalStartDate)}
                      </span>
                    </div>

                    {/* Quick chips start date */}
                    <div className="flex items-center gap-1.5">
                      <span className="text-[10px] text-gray-400 font-medium uppercase mr-1">Cepat:</span>
                      {RENTAL_QUICK_STARTS.map((opt) => {
                        const targetDate = addDaysToDate(todayStr, opt.offsetDays);
                        const isSelected = rentalStartDate === targetDate;
                        return (
                          <button
                            key={opt.label}
                            type="button"
                            onClick={() => {
                              setRentalStartDate(targetDate);
                              const newEnd = addDaysToDate(targetDate, rentalDurationPreset || 1);
                              setRentalEndDate(newEnd);
                            }}
                            className={`px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors border
                              ${isSelected
                                ? 'bg-emerald-600 text-white border-emerald-600'
                                : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-emerald-300'
                              }`}
                          >
                            {opt.label}
                          </button>
                        );
                      })}
                    </div>

                    <div className="grid grid-cols-2 gap-2 pt-1">
                      <div>
                        <label className="text-[10px] text-gray-500 font-semibold block mb-1">Tanggal Mulai</label>
                        <input
                          type="date"
                          min={todayStr}
                          value={rentalStartDate}
                          onChange={(e) => {
                            setRentalStartDate(e.target.value);
                            if (e.target.value >= rentalEndDate) {
                              const newEnd = addDaysToDate(e.target.value, rentalDurationPreset || 1);
                              setRentalEndDate(newEnd);
                            } else {
                              setRentalDurationPreset(getRentalDays(e.target.value, rentalEndDate));
                            }
                          }}
                          className="w-full bg-white border border-gray-300 rounded-xl px-2.5 py-1.5 text-[12px] text-gray-800 focus:outline-none focus:border-orange-500 font-medium"
                        />
                      </div>
                      <div>
                        <label className="text-[10px] text-gray-500 font-semibold block mb-1">Jam Ambil</label>
                        <input
                          type="time"
                          value={rentalStartTime}
                          onChange={(e) => setRentalStartTime(e.target.value)}
                          className="w-full bg-white border border-gray-300 rounded-xl px-2.5 py-1.5 text-[12px] text-gray-800 focus:outline-none focus:border-orange-500 font-medium"
                        />
                      </div>
                    </div>

                    {/* Quick chips Jam Mulai */}
                    <div className="pt-0.5">
                      <span className="text-[10px] text-gray-400 block mb-1">Pilihan Jam Cepat:</span>
                      <div className="flex flex-wrap gap-1">
                        {RENTAL_QUICK_TIMES.map((t) => (
                          <button
                            key={t}
                            type="button"
                            onClick={() => setRentalStartTime(t)}
                            className={`px-2 py-0.5 rounded text-[10px] font-semibold transition-colors
                              ${rentalStartTime === t
                                ? 'bg-emerald-600 text-white'
                                : 'bg-gray-50 text-gray-600 border border-gray-200 hover:border-gray-400'
                              }`}
                          >
                            {t}
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Section 2: Selesai Sewa (Pengembalian) */}
                  <div className="bg-white p-3 rounded-xl border border-gray-200 space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="text-[12px] font-bold text-gray-800 flex items-center gap-1.5">
                        <span className="material-symbols-outlined text-[16px] text-rose-500">event_repeat</span>
                        2. Selesai Sewa (Pengembalian)
                      </span>
                      <span className="text-[11px] text-rose-700 font-semibold">
                        {formatDateDisplay(rentalEndDate)}
                      </span>
                    </div>

                    <div className="grid grid-cols-2 gap-2">
                      <div>
                        <label className="text-[10px] text-gray-500 font-semibold block mb-1">Tanggal Selesai</label>
                        <input
                          type="date"
                          min={rentalStartDate}
                          value={rentalEndDate}
                          onChange={(e) => {
                            setRentalEndDate(e.target.value);
                            setRentalDurationPreset(getRentalDays(rentalStartDate, e.target.value));
                          }}
                          className="w-full bg-white border border-gray-300 rounded-xl px-2.5 py-1.5 text-[12px] text-gray-800 focus:outline-none focus:border-orange-500 font-medium"
                        />
                      </div>
                      <div>
                        <label className="text-[10px] text-gray-500 font-semibold block mb-1">Jam Kembali</label>
                        <input
                          type="time"
                          value={rentalEndTime}
                          onChange={(e) => setRentalEndTime(e.target.value)}
                          className="w-full bg-white border border-gray-300 rounded-xl px-2.5 py-1.5 text-[12px] text-gray-800 focus:outline-none focus:border-orange-500 font-medium"
                        />
                      </div>
                    </div>

                    {/* Quick chips Jam Selesai */}
                    <div className="pt-0.5">
                      <span className="text-[10px] text-gray-400 block mb-1">Pilihan Jam Cepat:</span>
                      <div className="flex flex-wrap gap-1">
                        {RENTAL_QUICK_TIMES.map((t) => (
                          <button
                            key={t}
                            type="button"
                            onClick={() => setRentalEndTime(t)}
                            className={`px-2 py-0.5 rounded text-[10px] font-semibold transition-colors
                              ${rentalEndTime === t
                                ? 'bg-rose-600 text-white'
                                : 'bg-gray-50 text-gray-600 border border-gray-200 hover:border-gray-400'
                              }`}
                          >
                            {t}
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Ringkasan Biaya Sewa */}
                  <div className="p-3.5 rounded-xl bg-gradient-to-br from-orange-100/80 to-amber-100/60 border border-orange-200">
                    <div className="flex items-center justify-between text-[11px] text-orange-950/80 mb-1">
                      <span>Rincian Biaya Sewa ({rentalDays} Hari):</span>
                      <span className="font-semibold">{rentalDays} hari × {formatPrice(displayPrice)}</span>
                    </div>
                    <div className="flex items-baseline justify-between pt-1 border-t border-orange-200/60">
                      <span className="text-[13px] font-bold text-gray-800">Total Biaya Sewa</span>
                      <div className="text-right">
                        <span className="text-[20px] font-black text-orange-600 leading-none">
                          {formatPrice(rentalTotal)}
                        </span>
                        <span className="text-[10px] text-gray-500 block">+PPN 11%</span>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* ── License: Duration + Seats + Benefits ──────────────── */}
              {isLicense && !isRental && (
                <div className="flex flex-col gap-3.5 bg-purple-50/50 border border-purple-200 rounded-2xl p-4 sm:p-5">
                  <div className="flex items-center gap-2">
                    <span className="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-sm shadow-purple-600/20">
                      <span className="material-symbols-outlined text-[18px]">key</span>
                    </span>
                    <div>
                      <h4 className="text-[13px] sm:text-[14px] font-bold text-gray-900 leading-tight">
                        Lisensi Software & Cloud
                      </h4>
                      <p className="text-[11px] text-gray-500">Pilih durasi masa aktif dan jumlah akun user/seat</p>
                    </div>
                  </div>

                  {/* Pilihan Durasi */}
                  <div className="flex flex-col gap-1.5">
                    <label className="text-[11px] font-bold text-gray-700 uppercase tracking-wide">
                      Durasi Masa Aktif
                    </label>
                    <div className="grid grid-cols-2 sm:grid-cols-5 gap-2">
                      {LICENSE_DURATIONS.map((d) => (
                        <button
                          key={d.id}
                          type="button"
                          onClick={() => setLicenseDuration(d.id)}
                          className={`py-2 px-2 rounded-xl border text-center transition-all relative ${
                            licenseDuration === d.id
                              ? 'border-purple-600 bg-purple-600 text-white shadow-md shadow-purple-600/20'
                              : 'border-gray-200 bg-white text-gray-700 hover:border-purple-300'
                          }`}
                        >
                          {d.isPopular && licenseDuration !== d.id && (
                            <span className="absolute -top-1.5 left-1/2 -translate-x-1/2 text-[8px] bg-amber-400 text-amber-950 px-1.5 rounded-full font-bold whitespace-nowrap">
                              Populer
                            </span>
                          )}
                          <div className="text-[12px] font-bold leading-tight">{d.label}</div>
                          <span className={`text-[10px] block mt-0.5 ${
                            licenseDuration === d.id ? 'text-purple-100' : 'text-purple-600 font-semibold'
                          }`}>
                            {d.badge}
                          </span>
                        </button>
                      ))}
                    </div>
                  </div>

                  {/* Pilihan User / Seat */}
                  <div className="flex flex-col gap-1.5">
                    <label className="text-[11px] font-bold text-gray-700 uppercase tracking-wide">
                      Jumlah User / Seat
                    </label>
                    <div className="flex flex-wrap items-center gap-2">
                      {/* Stepper */}
                      <div className="flex items-center border border-gray-300 rounded-xl overflow-hidden bg-white">
                        <button
                          type="button"
                          onClick={() => setLicenseSeats((s) => Math.max(1, s - 1))}
                          className="w-9 h-9 flex items-center justify-center hover:bg-gray-50 transition-colors disabled:opacity-40"
                          disabled={licenseSeats <= 1}
                        >
                          <span className="material-symbols-outlined text-[17px]">remove</span>
                        </button>
                        <input
                          type="number"
                          min={1}
                          max={999}
                          value={licenseSeats}
                          onChange={(e) => setLicenseSeats(Math.max(1, parseInt(e.target.value) || 1))}
                          className="w-14 text-center font-bold text-[14px] text-gray-800 border-x border-gray-200 h-9 focus:outline-none"
                        />
                        <button
                          type="button"
                          onClick={() => setLicenseSeats((s) => s + 1)}
                          className="w-9 h-9 flex items-center justify-center hover:bg-gray-50 transition-colors"
                        >
                          <span className="material-symbols-outlined text-[17px]">add</span>
                        </button>
                      </div>

                      {/* Quick Seat Chips */}
                      <div className="flex items-center gap-1 flex-wrap">
                        {LICENSE_SEAT_PRESETS.map((cnt) => (
                          <button
                            key={cnt}
                            type="button"
                            onClick={() => setLicenseSeats(cnt)}
                            className={`px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition-colors border ${
                              licenseSeats === cnt
                                ? 'bg-purple-600 text-white border-purple-600'
                                : 'bg-white text-gray-700 border-gray-200 hover:border-purple-300'
                            }`}
                          >
                            {cnt} {cnt >= 100 ? 'Enterprise' : 'User'}
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Benefit Lisensi */}
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                    <div className="flex items-center gap-1.5 bg-white p-2 rounded-lg border border-purple-100 text-[11px] text-gray-700">
                      <span className="material-symbols-outlined text-[15px] text-emerald-600">bolt</span>
                      <span>Aktivasi Cepat</span>
                    </div>
                    <div className="flex items-center gap-1.5 bg-white p-2 rounded-lg border border-purple-100 text-[11px] text-gray-700">
                      <span className="material-symbols-outlined text-[15px] text-blue-600">verified</span>
                      <span>100% Resmi</span>
                    </div>
                    <div className="flex items-center gap-1.5 bg-white p-2 rounded-lg border border-purple-100 text-[11px] text-gray-700">
                      <span className="material-symbols-outlined text-[15px] text-purple-600">support_agent</span>
                      <span>Bantuan Setup</span>
                    </div>
                  </div>

                  {/* Ringkasan Biaya Lisensi */}
                  <div className="p-3.5 rounded-xl bg-purple-100/70 border border-purple-200">
                    <div className="flex items-center justify-between text-[11px] text-purple-950 mb-1">
                      <span>Rincian Biaya Lisensi:</span>
                      <span className="font-semibold">{licenseSeats} Seat × {selectedLicenseDuration?.label}</span>
                    </div>
                    <div className="flex items-baseline justify-between pt-1 border-t border-purple-200">
                      <span className="text-[13px] font-bold text-gray-800">Total Biaya Lisensi</span>
                      <div className="text-right">
                        <span className="text-[20px] font-black text-purple-700 leading-none">
                          {formatPrice(licenseTotal)}
                        </span>
                        <span className="text-[10px] text-gray-500 block">+PPN 11%</span>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* ── Regular Product: Quantity ────────────────────────── */}
              {!isRental && !isLicense && (
                <div className="flex items-center gap-3">
                  <span className="text-[13px] text-gray-500 w-20 flex-shrink-0">Kuantitas:</span>
                  <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                    <button
                      onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                      disabled={quantity <= 1}
                      className="w-9 h-10 flex items-center justify-center hover:bg-gray-50 transition-colors disabled:opacity-40"
                    >
                      <span className="material-symbols-outlined text-[18px]">remove</span>
                    </button>
                    <span className="w-12 text-center font-semibold text-[15px] text-gray-800">{quantity}</span>
                    <button
                      onClick={() => setQuantity((q) => Math.min(stock, q + 1))}
                      disabled={quantity >= stock}
                      className="w-9 h-10 flex items-center justify-center hover:bg-gray-50 transition-colors disabled:opacity-40"
                    >
                      <span className="material-symbols-outlined text-[18px]">add</span>
                    </button>
                  </div>
                  {!isOutOfStock && (
                    <span className="text-[12px] text-gray-500">
                      Tersisa <strong>{stock} unit</strong>{stock <= 15 ? " (Stok Gudang BSD)" : ""}
                    </span>
                  )}
                  {isOutOfStock && (
                    <span className="text-[12px] font-bold text-red-600">Stok Habis</span>
                  )}
                </div>
              )}

              {/* Main CTA buttons */}
              <div className="flex gap-2.5 sm:gap-3 mt-2">
                <button
                  onClick={handleAddToCart}
                  disabled={isOutOfStock}
                  className={`flex-1 min-w-0 h-12 rounded-xl font-bold text-[13px] sm:text-[14px] flex items-center justify-center gap-1.5 transition-all border-2
                    ${
                      added
                        ? "bg-emerald-50 border-emerald-500 text-emerald-700"
                        : isOutOfStock
                          ? "bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed"
                          : "bg-white border-primary text-primary hover:bg-primary/5 active:scale-98"
                    }`}
                >
                  <span className="material-symbols-outlined text-[19px] flex-shrink-0">
                    {added ? "check" : isRental ? "event_available" : isLicense ? "key" : "add_shopping_cart"}
                  </span>
                  <span className="truncate">
                    {added ? "Ditambahkan!" : isRental ? "+ Booking Sekarang" : isLicense ? "+ Tambah Lisensi" : "+ Masukkan Keranjang"}
                  </span>
                </button>
                <button
                  onClick={handleBuyNow}
                  disabled={isOutOfStock}
                  className="flex-1 min-w-0 h-12 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold text-[13px] sm:text-[14px] flex items-center justify-center gap-1.5 transition-all disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed shadow-md shadow-primary/20 active:scale-98"
                >
                  <span className="truncate">
                    {isRental ? "Booking & Bayar" : isLicense ? "Aktifkan Lisensi" : "Beli Sekarang"}
                  </span>
                  <span className="material-symbols-outlined text-[19px] flex-shrink-0">arrow_forward</span>
                </button>
              </div>

              {/* Secondary actions */}
              <div className="grid grid-cols-2 gap-2 sm:gap-3 mt-2.5">
                <Link
                  to="/minta-penawaran"
                  className="h-10 sm:h-11 border border-border-subtle bg-surface/30 rounded-xl text-[11px] sm:text-[12px] font-semibold text-text-primary hover:border-primary hover:text-primary flex items-center justify-center gap-1.5 transition-colors px-2 text-center"
                >
                  <span className="material-symbols-outlined text-[16px] flex-shrink-0 text-primary">
                    request_quote
                  </span>
                  <span className="truncate">Minta Penawaran (RFQ)</span>
                </Link>
                <a
                  href="https://wa.me/6287780116800"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="h-10 sm:h-11 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[11px] sm:text-[12px] font-semibold flex items-center justify-center gap-1.5 transition-colors px-2 text-center shadow-sm"
                >
                  <span className="material-symbols-outlined text-[16px] flex-shrink-0">
                    chat
                  </span>
                  <span className="truncate">Chat Sales WA</span>
                </a>
              </div>

              {/* Trust badges */}
              <div className="grid grid-cols-3 gap-2 pt-2 border-t border-gray-100">
                {[
                  {
                    icon: "verified",
                    label: "100% Original BNIB",
                    color: "text-primary",
                  },
                  {
                    icon: "undo",
                    label: "7 Hari Pengembalian",
                    color: "text-emerald-600",
                  },
                  {
                    icon: "receipt_long",
                    label: "Faktur Pajak PKP",
                    color: "text-amber-600",
                  },
                ].map(({ icon, label, color }) => (
                  <div
                    key={label}
                    className="flex flex-col items-center gap-1 text-center"
                  >
                    <span
                      className={`material-symbols-outlined text-[20px] ${color}`}
                    >
                      {icon}
                    </span>
                    <span className="text-[10px] text-gray-500 leading-tight">
                      {label}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* ── Seller / Store Info ──────────────────────────────────────── */}
        <div className="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 mb-4">
          <div className="flex flex-col sm:flex-row sm:items-center gap-4">
            <div className="flex items-center gap-4 flex-1">
              {/* Store avatar */}
              <div className="w-14 h-14 rounded-full bg-white border border-gray-200 flex items-center justify-center p-1.5 shadow-sm overflow-hidden flex-shrink-0">
                <img
                  src="/favicon.png"
                  alt="Accommerce Official Store"
                  className="w-full h-full object-contain"
                />
              </div>
              <div>
                <div className="flex items-center gap-2 mb-0.5">
                  <span className="font-bold text-[15px] text-gray-900">
                    Accommerce Official Store
                  </span>
                  <span className="material-symbols-outlined text-primary text-[16px]">
                    verified
                  </span>
                </div>
                <p className="text-[12px] text-gray-500">
                  Aktif beberapa menit lalu • Authorized Principal Distributor
                </p>
                <span className="inline-block mt-1 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded">
                  MALL
                </span>
              </div>
            </div>
            {/* Stats */}
            <div className="flex gap-6 text-center text-[13px]">
              <div>
                <div className="text-amber-500 font-bold text-[15px]">
                  4.9 (2.4rb)
                </div>
                <div className="text-gray-400 text-[11px]">Penilaian</div>
              </div>
              <div>
                <div className="text-gray-800 font-bold text-[15px]">99%</div>
                <div className="text-gray-400 text-[11px]">Chat Dibalas</div>
              </div>
              <div>
                <div className="text-gray-800 font-bold text-[15px]">
                  3 Tahun
                </div>
                <div className="text-gray-400 text-[11px]">Bergabung</div>
              </div>
            </div>
            {/* Action buttons */}
            <div className="flex gap-2">
              <a
                href="https://wa.me/6287780116800"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-1.5 bg-primary text-white font-semibold px-4 py-2 rounded-lg text-[13px] hover:bg-primary/90 transition-colors"
              >
                <span className="material-symbols-outlined text-[16px]">
                  chat
                </span>
                Chat Penjual
              </a>
              <button className="flex items-center gap-1.5 border border-gray-200 text-gray-600 font-semibold px-4 py-2 rounded-lg text-[13px] hover:border-primary hover:text-primary transition-colors">
                <span className="material-symbols-outlined text-[16px]">
                  storefront
                </span>
                Kunjungi Toko
              </button>
            </div>
          </div>
        </div>

        {/* ── Spesifikasi Produk ───────────────────────────────────────── */}
        <div className="bg-white border border-gray-200 rounded-xl p-4 sm:p-6 mb-4">
          <h2 className="text-[16px] font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span className="material-symbols-outlined text-primary text-[20px]">
              fact_check
            </span>
            Spesifikasi Produk
          </h2>
          <div className="overflow-x-hidden">
            <table className="w-full text-[13px] table-fixed">
              <tbody>
                {/* Category */}
                <tr className="border-b border-gray-100">
                  <td className="py-2.5 pr-3 text-gray-500 w-[30%] align-top break-words">
                    Kategori
                  </td>
                  <td className="py-2.5 text-primary font-medium break-words">
                    {productCats.map((c, i) => (
                      <span key={c.id}>
                        {i > 0 && " > "}
                        <Link
                          to={`/katalog?kategori=${c.slug}`}
                          className="hover:underline"
                        >
                          {c.name}
                        </Link>
                      </span>
                    ))}
                  </td>
                  <td className="py-2.5 pr-3 text-gray-500 w-[20%] pl-3 align-top break-words">
                    Merek
                  </td>
                  <td className="py-2.5 text-primary font-medium break-words">
                    {brand ? (
                      <Link
                        to={`/katalog?brand=${brand.slug}`}
                        className="hover:underline"
                      >
                        {brand.name}
                      </Link>
                    ) : (
                      "—"
                    )}
                  </td>
                </tr>
                {/* SKU / Part Number */}
                <tr className="border-b border-gray-100">
                  <td className="py-2.5 pr-4 text-gray-500 align-top">
                    Part Number / SKU
                  </td>
                  <td className="py-2.5 font-mono text-gray-800">
                    {product.sku || "—"}
                  </td>
                  <td className="py-2.5 pr-3 text-gray-500 pl-3 align-top break-words w-[20%]">
                    Kondisi
                  </td>
                  <td className="py-2.5 text-gray-800 font-medium break-words">
                    Baru (100% BNIB Segel Resmi)
                  </td>
                </tr>
                {/* Warranty */}
                <tr className="border-b border-gray-100">
                  <td className="py-2.5 pr-4 text-gray-500 align-top">
                    Masa Garansi
                  </td>
                  <td className="py-2.5 text-gray-800">
                    {product.attributes?.find((a) =>
                      a.name.toLowerCase().includes("garansi"),
                    )?.value || "12 Bulan (1 Tahun)"}
                  </td>
                  <td className="py-2.5 pr-3 text-gray-500 pl-3 align-top break-words w-[20%]">
                    Jenis Garansi
                  </td>
                  <td className="py-2.5 text-gray-800 break-words">
                    Garansi Resmi Distributor Indonesia
                  </td>
                </tr>
                {/* Stock */}
                <tr className="border-b border-gray-100">
                  <td className="py-2.5 pr-4 text-gray-500 align-top">
                    Ketersediaan
                  </td>
                  <td className="py-2.5 text-gray-800">
                    {isOutOfStock ? (
                      <span className="text-red-600 font-semibold">
                        Stok Habis
                      </span>
                    ) : (
                      <span className="text-emerald-600 font-semibold">
                        {stock} Unit Tersedia
                      </span>
                    )}
                  </td>
                  <td className="py-2.5 pr-3 text-gray-500 pl-3 align-top break-words w-[20%]">
                    Dikirim dari
                  </td>
                  <td className="py-2.5 text-gray-800 break-words">
                    Kota Tangerang Selatan, Banten
                  </td>
                </tr>
                {/* Extra attributes */}
                {(product.attributes || [])
                  .filter((a) => !a.name.toLowerCase().includes("garansi"))
                  .map((a) => (
                    <tr
                      key={a.name}
                      className="border-b border-gray-100 last:border-0"
                    >
                      <td className="py-2.5 pr-4 text-gray-500 align-top">
                        {a.name}
                      </td>
                      <td
                        className="py-2.5 text-gray-800 font-medium"
                        colSpan={3}
                      >
                        {a.value}
                      </td>
                    </tr>
                  ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* ── Deskripsi Produk ─────────────────────────────────────────── */}
        {product.description && (
          <div className="bg-white border border-gray-200 rounded-xl p-4 sm:p-6 mb-4">
            <h2 className="text-[16px] font-bold text-gray-900 mb-4 flex items-center gap-2">
              <span className="material-symbols-outlined text-primary text-[20px]">
                description
              </span>
              Deskripsi Produk
            </h2>
            <div
              className="prose prose-sm max-w-none text-gray-600 leading-relaxed text-[13px]"
              dangerouslySetInnerHTML={{ __html: product.description }}
              style={{ wordBreak: 'break-word', overflowWrap: 'break-word' }}
            />
          </div>
        )}

        {/* ── Ulasan Produk ────────────────────────────────────────────── */}
        <ReviewSection product={product} user={user} updateUser={updateUser} />

        {/* ── Produk Terkait ───────────────────────────────────────────── */}
        {relatedProducts.length > 0 && (
          <div className="mt-6">
            <h2 className="text-[16px] font-bold text-gray-900 mb-4">
              Produk Terkait
            </h2>
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
              {relatedProducts.map((p) => (
                <ProductCard key={p.id} product={p} />
              ))}
            </div>
          </div>
        )}
      </div>

      {/* ── Mobile sticky Add-to-Cart bar ──────────────────────────────── */}
      <div className="fixed bottom-[60px] left-0 right-0 bg-white border-t border-border-subtle px-3 py-2 flex items-center gap-2 lg:hidden z-30 shadow-[0_-4px_16px_rgba(11,28,48,0.08)]" style={{ paddingBottom: 'calc(env(safe-area-inset-bottom) + 8px)' }}>
        {/* Compact Stepper */}
        {!isRental && !isLicense && (
          <div className="flex items-center border border-border-subtle rounded-xl bg-surface/50 h-10 px-1 flex-shrink-0">
            <button
              onClick={() => setQuantity((q) => Math.max(1, q - 1))}
              disabled={quantity <= 1}
              className="w-6 h-full flex items-center justify-center text-text-secondary hover:text-text-primary disabled:opacity-30 active:scale-95"
              aria-label="Kurangi jumlah"
            >
              <span className="material-symbols-outlined text-[15px]">remove</span>
            </button>
            <span className="w-6 text-center font-bold text-[12px] text-text-primary">{quantity}</span>
            <button
              onClick={() => setQuantity((q) => Math.min(stock, q + 1))}
              disabled={quantity >= stock}
              className="w-6 h-full flex items-center justify-center text-text-secondary hover:text-text-primary disabled:opacity-30 active:scale-95"
              aria-label="Tambah jumlah"
            >
              <span className="material-symbols-outlined text-[15px]">add</span>
            </button>
          </div>
        )}

        {/* Add to Cart button */}
        <button
          onClick={handleAddToCart}
          disabled={isOutOfStock}
          className={`flex-1 min-w-0 h-10 rounded-xl font-bold text-[12px] flex items-center justify-center gap-1 border-2 transition-all active:scale-98
            ${added ? "bg-emerald-50 border-emerald-500 text-emerald-700" : isOutOfStock ? "bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed" : "border-primary text-primary hover:bg-primary/5"}`}
        >
          <span className="material-symbols-outlined text-[16px] flex-shrink-0">{added ? "check" : isRental ? "event_available" : isLicense ? "key" : "add_shopping_cart"}</span>
          <span className="truncate">{added ? "Ditambahkan!" : isRental ? "+ Booking" : isLicense ? "+ Lisensi" : "+ Keranjang"}</span>
        </button>

        {/* Buy Now button */}
        <button
          onClick={handleBuyNow}
          disabled={isOutOfStock}
          className="flex-1 min-w-0 h-10 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold text-[12px] flex items-center justify-center gap-1 transition-all disabled:bg-gray-200 disabled:text-gray-400 shadow-sm shadow-primary/20 active:scale-98 px-2"
        >
          <span className="whitespace-nowrap">{isRental ? "Booking & Bayar" : isLicense ? "Aktifkan Lisensi" : "Beli Sekarang"}</span>
          <span className="material-symbols-outlined text-[15px] flex-shrink-0">arrow_forward</span>
        </button>
      </div>
    </div>
  );
}
