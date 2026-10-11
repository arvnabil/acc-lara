import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { Link } from '../compat/router';
import { useWishlist } from '../compat/context';
import ProductCard from '../components/ProductCard';

export default function WishlistPage() {
  const { wishlist } = useWishlist();
  const wishlistProducts = usePage().props.wishlistProducts ?? [];

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'instant' });
  }, []);

  return (
    <div className="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8 min-h-[70vh]">
      <div className="bg-white rounded-xl border border-border-subtle p-6 shadow-sm">
        <h3 className="text-xl font-bold text-text-primary mb-6 flex items-center gap-2">
          <span className="material-symbols-outlined text-primary text-[28px]" style={{ fontVariationSettings: "'FILL' 1" }}>favorite</span>
          Wishlist Produk Disimpan
        </h3>

        {wishlistProducts.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            {wishlistProducts.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        ) : (
          <div className="py-16 text-center text-text-secondary flex flex-col items-center">
            <span className="material-symbols-outlined text-[64px] text-outline mb-4 opacity-50">favorite_border</span>
            <h4 className="text-lg font-bold text-text-primary mb-2">Wishlist Anda masih kosong</h4>
            <p className="mb-6 max-w-md mx-auto">Silakan cari dan tambahkan produk yang Anda sukai ke dalam wishlist agar mudah ditemukan nanti.</p>
            <Link to="/katalog" className="bg-primary text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-primary/90 transition-colors">
              Mulai Belanja
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}
