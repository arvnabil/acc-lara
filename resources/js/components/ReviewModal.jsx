import React, { useState } from 'react';

export default function ReviewModal({ isOpen, onClose, product, order, user, onSubmitReview }) {
  const [rating, setRating] = useState(5);
  const [hoverRating, setHoverRating] = useState(0);
  const [comment, setComment] = useState('');
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [images, setImages] = useState([]);
  const [video, setVideo] = useState(null);
  const [loading, setLoading] = useState(false);

  if (!isOpen || !product) return null;

  function handleImageUpload(e) {
    const files = Array.from(e.target.files || []);
    if (files.length === 0) return;
    
    files.slice(0, 5 - images.length).forEach(file => {
      const reader = new FileReader();
      reader.onload = (event) => {
        setImages(prev => [...prev, event.target.result]);
      };
      reader.readAsDataURL(file);
    });
  }

  function handleRemoveImage(index) {
    setImages(prev => prev.filter((_, i) => i !== index));
  }

  function handleVideoUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.size > 25 * 1024 * 1024) {
      alert('Ukuran video maksimal 25MB');
      return;
    }
    const reader = new FileReader();
    reader.onload = (event) => {
      setVideo(event.target.result);
    };
    reader.readAsDataURL(file);
  }

  function handleRemoveVideo() {
    setVideo(null);
  }

  function handleSubmit(e) {
    e.preventDefault();
    if (!comment.trim()) return;

    setLoading(true);
    const newReview = {
      id: Date.now(),
      orderId: order?.id || `ORD-${Date.now()}`,
      productId: product.id || product.slug || 'prod-1',
      productName: product.name,
      userEmail: user?.email || 'user@example.com',
      userName: isAnonymous ? 'Pengguna Anonim' : (user?.name || 'Pengguna Accommerce'),
      isAnonymous,
      rating,
      comment: comment.trim(),
      images,
      video,
      date: new Date().toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      }),
    };

    setTimeout(() => {
      if (onSubmitReview) {
        onSubmitReview(newReview);
      }
      setLoading(false);
      onClose();
    }, 400);
  }

  const ratingLabels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Bagus', 'Sangat Bagus'];

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-xs">
      <div className="bg-white rounded-2xl w-full max-w-lg shadow-2xl border border-border-subtle overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-border-subtle bg-surface">
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-primary text-[22px]">rate_review</span>
            <h3 className="font-bold text-base text-text-primary">Beri Ulasan Produk</h3>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-text-secondary hover:text-text-primary p-1 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* Content Form */}
        <form onSubmit={handleSubmit} className="p-6 overflow-y-auto space-y-4 text-xs sm:text-sm">
          {/* Product Snippet */}
          <div className="flex items-center gap-3 p-3 bg-surface rounded-xl border border-border-subtle">
            {product.image && (
              <img src={product.image} alt={product.name} className="w-12 h-12 object-contain rounded-lg bg-white p-1 border border-border-subtle" />
            )}
            <div className="flex-1 min-w-0">
              <h4 className="font-bold text-text-primary text-xs sm:text-sm truncate">{product.name}</h4>
              <p className="text-[11px] text-text-secondary">Pesanan #{order?.id || 'Terverifikasi'}</p>
            </div>
          </div>

          {/* Loyalty points info */}
          <div className="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200/80 rounded-xl text-amber-900 text-xs font-medium">
            <span className="material-symbols-outlined text-amber-600 text-[18px]">stars</span>
            <span>Dapatkan <strong>+50 Poin</strong> setelah mengirimkan ulasan terverifikasi ini!</span>
          </div>

          {/* Star Rating */}
          <div className="text-center py-2">
            <label className="block font-bold text-text-primary mb-2 text-xs uppercase tracking-wider">
              Bagaimana kualitas produk secara keseluruhan?
            </label>
            <div className="flex items-center justify-center gap-1.5">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  key={star}
                  type="button"
                  onClick={() => setRating(star)}
                  onMouseEnter={() => setHoverRating(star)}
                  onMouseLeave={() => setHoverRating(0)}
                  className="transition-transform hover:scale-115 p-1 cursor-pointer"
                >
                  <span
                    className="material-symbols-outlined text-[32px]"
                    style={{
                      color: star <= (hoverRating || rating) ? '#f59e0b' : '#d1d5db',
                      fontVariationSettings: star <= (hoverRating || rating) ? "'FILL' 1" : "'FILL' 0",
                    }}
                  >
                    star
                  </span>
                </button>
              ))}
            </div>
            <p className="font-bold text-amber-600 mt-1 text-xs">{ratingLabels[hoverRating || rating]}</p>
          </div>

          {/* Text Review */}
          <div>
            <label className="block font-bold text-text-primary mb-1 text-xs uppercase tracking-wider">
              Ulasan Anda *
            </label>
            <textarea
              required
              rows={3}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              placeholder="Ceritakan kepuasan Anda mengenai kualitas produk, kemasan pengiriman, dan respon penjual..."
              className="w-full bg-surface border border-border-subtle rounded-xl p-3 text-xs sm:text-sm text-text-primary focus:outline-none focus:border-primary resize-none"
            />
          </div>

          {/* Upload Foto (Optional) */}
          <div>
            <label className="block font-bold text-text-primary mb-1 text-xs uppercase tracking-wider flex items-center justify-between">
              <span>Foto Produk (Opsional - Maks. 5)</span>
              <span className="text-[11px] text-text-secondary font-normal">{images.length}/5 Foto</span>
            </label>
            <div className="flex flex-wrap gap-2 items-center">
              {images.map((img, idx) => (
                <div key={idx} className="relative w-16 h-16 rounded-lg overflow-hidden border border-border-subtle group">
                  <img src={img} alt={`Upload ${idx}`} className="w-full h-full object-cover" />
                  <button
                    type="button"
                    onClick={() => handleRemoveImage(idx)}
                    className="absolute top-0.5 right-0.5 bg-black/70 text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px] hover:bg-red-600 transition-colors"
                  >
                    ×
                  </button>
                </div>
              ))}
              {images.length < 5 && (
                <label className="w-16 h-16 rounded-lg border-2 border-dashed border-border-subtle hover:border-primary flex flex-col items-center justify-center text-text-secondary hover:text-primary cursor-pointer transition-colors bg-surface">
                  <span className="material-symbols-outlined text-[20px]">add_photo_alternate</span>
                  <span className="text-[9px] font-semibold mt-0.5">Tambah</span>
                  <input type="file" accept="image/*" multiple onChange={handleImageUpload} className="hidden" />
                </label>
              )}
            </div>
          </div>

          {/* Upload Video (Optional) */}
          <div>
            <label className="block font-bold text-text-primary mb-1 text-xs uppercase tracking-wider">
              Video Produk (Opsional - Maks. 1 Video)
            </label>
            {video ? (
              <div className="relative rounded-xl overflow-hidden border border-border-subtle bg-black max-h-36">
                <video src={video} controls className="w-full max-h-36 object-contain" />
                <button
                  type="button"
                  onClick={handleRemoveVideo}
                  className="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded font-bold shadow-md cursor-pointer"
                >
                  Hapus Video
                </button>
              </div>
            ) : (
              <label className="flex items-center gap-2 p-3 border-2 border-dashed border-border-subtle hover:border-primary rounded-xl text-text-secondary hover:text-primary cursor-pointer transition-colors bg-surface">
                <span className="material-symbols-outlined text-[20px]">videocam</span>
                <span className="text-xs font-semibold">Upload Video Ulasan (Maks. 25MB)</span>
                <input type="file" accept="video/*" onChange={handleVideoUpload} className="hidden" />
              </label>
            )}
          </div>

          {/* Anonymous Checkbox */}
          <label className="flex items-center gap-2.5 cursor-pointer pt-1">
            <input
              type="checkbox"
              checked={isAnonymous}
              onChange={(e) => setIsAnonymous(e.target.checked)}
              className="w-4 h-4 rounded text-primary focus:ring-primary accent-primary cursor-pointer"
            />
            <div>
              <span className="font-bold text-text-primary text-xs block">Sembunyikan nama saya (Kirim sebagai Anonim)</span>
              <span className="text-[11px] text-text-secondary block">Nama Anda akan disamarkan sebagai Pengguna Anonim di ulasan publik.</span>
            </div>
          </label>

          {/* Actions */}
          <div className="flex items-center justify-end gap-3 pt-3 border-t border-border-subtle">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-xs font-semibold text-text-secondary hover:text-text-primary hover:bg-surface rounded-lg transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-lg shadow-sm transition-colors cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
            >
              <span className="material-symbols-outlined text-[16px]">send</span>
              {loading ? 'Mengirim...' : 'Kirim Ulasan'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
