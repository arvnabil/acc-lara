/**
 * src/pages/CatalogPage.jsx
 * TAHAP 3: Katalog dengan filter, sort, search, pagination. State di URL.
 */
import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { getProducts, getCategories, getBrands, formatPrice } from '../services/productService';
import ProductCard from '../components/ProductCard';
import { ProductGridSkeleton } from '../components/Skeleton';

const SORT_OPTIONS = [
  { value: 'default', label: 'Terbaru' },
  { value: 'featured', label: 'Unggulan' },
  { value: 'price_asc', label: 'Harga: Rendah ke Tinggi' },
  { value: 'price_desc', label: 'Harga: Tinggi ke Rendah' },
  { value: 'name_asc', label: 'Nama A–Z' },
];

// ─── Filter Sidebar (desktop) / Bottom Sheet (mobile) ─────────────────────
function FilterPanel({ categories, brands, params, onChange, onClose, isSheet }) {
  const currentCat = params.get('kategori') || '';
  const currentBrand = params.get('brand') || '';
  const currentSort = params.get('sort') || 'default';
  const inStock = params.get('stok') === '1';
  const minPrice = params.get('min') || '';
  const maxPrice = params.get('max') || '';

  function set(key, val) {
    const next = new URLSearchParams(params);
    if (val) next.set(key, val); else next.delete(key);
    next.delete('halaman');
    onChange(next);
  }

  function reset() {
    onChange(new URLSearchParams());
    onClose?.();
  }

  const topCats = categories.filter(c => !c.parent_id && !c.is_addon);

  return (
    <div className={`flex flex-col gap-4 ${isSheet ? 'p-4 pb-[env(safe-area-inset-bottom)]' : ''}`}>
      {isSheet && (
        <div className="flex items-center justify-between">
          <h3 className="font-title-card text-[16px] font-bold text-text-primary">Filter & Urutan</h3>
          <button onClick={onClose} aria-label="Tutup filter" className="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-surface">
            <span className="material-symbols-outlined text-[22px]">close</span>
          </button>
        </div>
      )}

      {/* Sort */}
      <div>
        <h4 className="font-label-sm text-[12px] font-semibold text-text-secondary uppercase mb-2">Urutkan</h4>
        <div className="flex flex-col gap-1">
          {SORT_OPTIONS.map(o => (
            <label key={o.value} className="flex items-center gap-2 cursor-pointer py-1.5 px-2 rounded-lg hover:bg-surface transition-colors">
              <input
                type="radio"
                name="sort"
                value={o.value}
                checked={currentSort === o.value}
                onChange={() => set('sort', o.value)}
                className="accent-primary"
              />
              <span className="font-body-md text-[13px] text-text-primary">{o.label}</span>
            </label>
          ))}
        </div>
      </div>

      <div className="h-px bg-border-subtle" />

      {/* Category */}
      <div>
        <h4 className="font-label-sm text-[12px] font-semibold text-text-secondary uppercase mb-2">Kategori</h4>
        <button
          onClick={() => set('kategori', '')}
          className={`w-full text-left py-1.5 px-2 rounded-lg text-[13px] font-medium transition-colors ${!currentCat ? 'text-primary font-bold bg-primary/5' : 'text-text-secondary hover:bg-surface'}`}
        >
          Semua Kategori
        </button>
        {topCats.map(c => (
          <button
            key={c.id}
            onClick={() => set('kategori', c.slug)}
            className={`w-full text-left py-1.5 px-2 rounded-lg text-[13px] transition-colors ${currentCat === c.slug ? 'text-primary font-bold bg-primary/5' : 'text-text-secondary hover:bg-surface'}`}
          >
            {c.name}
            <span className="ml-1 text-[11px] text-outline">({c.product_count})</span>
          </button>
        ))}
      </div>

      <div className="h-px bg-border-subtle" />

      {/* Brand */}
      <div>
        <h4 className="font-label-sm text-[12px] font-semibold text-text-secondary uppercase mb-2">Brand</h4>
        <button
          onClick={() => set('brand', '')}
          className={`w-full text-left py-1.5 px-2 rounded-lg text-[13px] font-medium transition-colors ${!currentBrand ? 'text-primary font-bold bg-primary/5' : 'text-text-secondary hover:bg-surface'}`}
        >
          Semua Brand
        </button>
        {brands.map(b => (
          <button
            key={b.id}
            onClick={() => set('brand', b.slug)}
            className={`w-full text-left py-1.5 px-2 rounded-lg text-[13px] transition-colors flex items-center gap-2 ${currentBrand === b.slug ? 'text-primary font-bold bg-primary/5' : 'text-text-secondary hover:bg-surface'}`}
          >
            {b.logo ? (
              <img
                src={b.logo}
                alt={b.name}
                className="w-5 h-4 object-contain flex-shrink-0 grayscale opacity-60"
                onError={e => { e.target.style.display = 'none'; }}
              />
            ) : (
              <span className="w-5 h-4 rounded bg-surface flex items-center justify-center text-[9px] font-bold text-primary flex-shrink-0">
                {b.name.charAt(0)}
              </span>
            )}
            <span className="flex-1 text-left">{b.name}</span>
            <span className="text-[11px] text-outline">({b.product_count})</span>
          </button>
        ))}
      </div>

      <div className="h-px bg-border-subtle" />

      {/* Price */}
      <div>
        <h4 className="font-label-sm text-[12px] font-semibold text-text-secondary uppercase mb-2">Rentang Harga</h4>
        <div className="flex items-center gap-2">
          <input
            type="number"
            placeholder="Min"
            value={minPrice}
            onChange={e => set('min', e.target.value)}
            className="flex-1 w-full min-w-0 border border-border-subtle rounded-lg px-2 py-1.5 text-[13px] bg-page-background focus:outline-none focus:border-primary"
          />
          <span className="text-outline text-[12px] flex-shrink-0">–</span>
          <input
            type="number"
            placeholder="Max"
            value={maxPrice}
            onChange={e => set('max', e.target.value)}
            className="flex-1 w-full min-w-0 border border-border-subtle rounded-lg px-2 py-1.5 text-[13px] bg-page-background focus:outline-none focus:border-primary"
          />
        </div>
      </div>

      <div className="h-px bg-border-subtle" />

      {/* Stock */}
      <label className="flex items-center gap-2 cursor-pointer">
        <input
          type="checkbox"
          checked={inStock}
          onChange={e => set('stok', e.target.checked ? '1' : '')}
          className="accent-primary w-4 h-4"
        />
        <span className="font-body-md text-[13px] text-text-primary">Hanya stok tersedia</span>
      </label>

      <button
        onClick={reset}
        className="w-full mt-2 py-2.5 border border-border-subtle rounded-lg text-text-secondary text-[13px] font-medium hover:bg-surface transition-colors"
      >
        Reset Filter
      </button>
    </div>
  );
}

// ─── Bottom Sheet (mobile) ────────────────────────────────────────────────
function FilterSheet({ open, onClose, ...props }) {
  useEffect(() => {
    if (open) document.body.style.overflow = 'hidden';
    else document.body.style.overflow = '';
    return () => { document.body.style.overflow = ''; };
  }, [open]);

  if (!open) return null;
  return (
    <div className="fixed inset-0 z-[60] flex flex-col justify-end">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />
      <div className="relative bg-card-bg rounded-t-2xl max-h-[85dvh] overflow-y-auto shadow-xl">
        <div className="sticky top-0 bg-card-bg pt-3 pb-1 px-4 flex justify-center">
          <div className="w-12 h-1 bg-border-subtle rounded-full" />
        </div>
        <FilterPanel {...props} onClose={onClose} isSheet />
      </div>
    </div>
  );
}

// ─── Pagination ───────────────────────────────────────────────────────────
function Pagination({ page, total_pages, onChange }) {
  if (total_pages <= 1) return null;
  const pages = [];
  const delta = 2;
  for (let i = 1; i <= total_pages; i++) {
    if (i === 1 || i === total_pages || (i >= page - delta && i <= page + delta)) {
      pages.push(i);
    }
  }
  // Insert ellipses
  const withEllipsis = [];
  let prev = 0;
  for (const p of pages) {
    if (prev && p - prev > 1) withEllipsis.push('...');
    withEllipsis.push(p);
    prev = p;
  }

  return (
    <div className="flex items-center justify-center gap-1 mt-8" role="navigation" aria-label="Pagination">
      <button
        onClick={() => onChange(page - 1)}
        disabled={page <= 1}
        className="w-10 h-10 rounded-lg border border-border-subtle flex items-center justify-center disabled:opacity-40 hover:bg-surface transition-colors"
      >
        <span className="material-symbols-outlined text-[20px]">chevron_left</span>
      </button>
      {withEllipsis.map((p, i) =>
        p === '...' ? (
          <span key={`e${i}`} className="px-2 text-text-secondary text-[13px]">…</span>
        ) : (
          <button
            key={p}
            onClick={() => onChange(p)}
            aria-current={p === page ? 'page' : undefined}
            className={`w-10 h-10 rounded-lg font-label-sm text-label-sm transition-colors ${p === page ? 'bg-primary text-on-primary font-bold' : 'border border-border-subtle hover:bg-surface text-text-primary'}`}
          >
            {p}
          </button>
        )
      )}
      <button
        onClick={() => onChange(page + 1)}
        disabled={page >= total_pages}
        className="w-10 h-10 rounded-lg border border-border-subtle flex items-center justify-center disabled:opacity-40 hover:bg-surface transition-colors"
      >
        <span className="material-symbols-outlined text-[20px]">chevron_right</span>
      </button>
    </div>
  );
}

// ─── Main Page ─────────────────────────────────────────────────────────────
export default function CatalogPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [result, setResult] = useState({ data: [], total: 0, page: 1, per_page: 24, total_pages: 0 });
  const [categories, setCategories] = useState([]);
  const [brands, setBrands] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showFilter, setShowFilter] = useState(false);

  const params = {
    page: parseInt(searchParams.get('halaman') || '1', 10),
    category: searchParams.get('kategori') || null,
    brand: searchParams.get('brand') || null,
    q: searchParams.get('q') || '',
    sort: searchParams.get('sort') || 'default',
    in_stock: searchParams.get('stok') === '1',
    min_price: searchParams.get('min') ? parseInt(searchParams.get('min'), 10) : null,
    max_price: searchParams.get('max') ? parseInt(searchParams.get('max'), 10) : null,
  };

  // Count active filters
  const activeFilterCount = [
    searchParams.get('kategori'),
    searchParams.get('brand'),
    searchParams.get('stok'),
    searchParams.get('min'),
    searchParams.get('max'),
    searchParams.get('sort') && searchParams.get('sort') !== 'default' ? 'sort' : null,
  ].filter(Boolean).length;

  useEffect(() => {
    Promise.all([getCategories(), getBrands()]).then(([cats, brs]) => {
      setCategories(cats.filter(c => !c.is_addon));
      setBrands(brs);
    });
  }, []);

  useEffect(() => {
    setLoading(true);
    getProducts(params).then(res => {
      setResult(res);
      setLoading(false);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }, [searchParams.toString()]);

  function handleParamChange(next) {
    setSearchParams(next);
  }

  function handlePageChange(p) {
    const next = new URLSearchParams(searchParams);
    next.set('halaman', String(p));
    setSearchParams(next);
  }

  const currentCat = categories.find(c => c.slug === params.category);
  const currentBrand = brands.find(b => b.slug === params.brand);

  const pageTitle = params.q
    ? `Hasil pencarian "${params.q}"`
    : currentCat
    ? currentCat.name
    : currentBrand
    ? `Brand: ${currentBrand.name}`
    : 'Semua Produk';

  return (
    <div className="max-w-[1440px] mx-auto px-gutter lg:px-margin py-space-xl w-full">
      {/* Breadcrumb */}
      <nav className="flex items-center gap-1 mb-4 text-[12px] text-text-secondary" aria-label="Breadcrumb">
        <Link to="/" className="hover:text-primary">Beranda</Link>
        <span className="material-symbols-outlined text-[14px]">chevron_right</span>
        <Link to="/katalog" className="hover:text-primary">Katalog</Link>
        {currentCat && (
          <>
            <span className="material-symbols-outlined text-[14px]">chevron_right</span>
            <span className="text-text-primary font-medium">{currentCat.name}</span>
          </>
        )}
      </nav>

      {/* Mobile: Top bar with filter button & sort */}
      <div className="flex lg:hidden items-center justify-between mb-4 gap-2">
        <h1 className="font-title-card text-[16px] font-bold text-text-primary truncate flex-1">{pageTitle}</h1>
        <button
          onClick={() => setShowFilter(true)}
          className="flex items-center gap-1.5 border border-border-subtle rounded-lg px-3 py-2 text-[13px] font-medium text-text-primary hover:bg-surface transition-colors flex-shrink-0 relative"
        >
          <span className="material-symbols-outlined text-[18px]">tune</span>
          Filter & Urut
          {activeFilterCount > 0 && (
            <span className="absolute -top-1.5 -right-1.5 bg-primary text-on-primary text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
              {activeFilterCount}
            </span>
          )}
        </button>
      </div>

      <div className="flex gap-space-xl">
        {/* Sidebar (desktop) */}
        <aside className="hidden lg:block w-[240px] flex-shrink-0">
          <div className="sticky top-[180px] bg-card-bg border border-border-subtle rounded-xl p-4 overflow-y-auto max-h-[calc(100dvh-200px)]">
            <h3 className="font-title-card text-[15px] font-bold text-text-primary mb-4">Filter & Urutan</h3>
            <FilterPanel
              categories={categories}
              brands={brands}
              params={searchParams}
              onChange={handleParamChange}
            />
          </div>
        </aside>

        {/* Product grid */}
        <div className="flex-1 min-w-0">
          {/* Desktop title + result count */}
          <div className="hidden lg:flex items-center justify-between mb-4">
            <div>
              <h1 className="font-headline-section-mobile text-[20px] font-bold text-text-primary">{pageTitle}</h1>
              {!loading && (
                <p className="text-[13px] text-text-secondary">{result.total.toLocaleString('id-ID')} produk ditemukan</p>
              )}
            </div>
          </div>

          {/* Mobile result count */}
          <div className="lg:hidden mb-3">
            {!loading && (
              <p className="text-[12px] text-text-secondary">{result.total.toLocaleString('id-ID')} produk ditemukan</p>
            )}
          </div>

          {loading ? (
            <ProductGridSkeleton count={12} />
          ) : result.data.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-20 text-text-secondary">
              <span className="material-symbols-outlined text-[56px] mb-4 text-outline">search_off</span>
              <h3 className="font-title-card text-[16px] font-bold text-text-primary mb-1">Produk tidak ditemukan</h3>
              <p className="text-[13px] text-center mb-4">Coba ubah filter atau kata kunci pencarian.</p>
              <button
                onClick={() => setSearchParams(new URLSearchParams())}
                className="bg-primary text-on-primary font-label-sm text-label-sm px-6 py-2.5 rounded-lg transition-colors hover:bg-primary-container"
              >
                Reset Semua Filter
              </button>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-4">
                {result.data.map(p => <ProductCard key={p.id} product={p} />)}
              </div>
              <Pagination
                page={result.page}
                total_pages={result.total_pages}
                onChange={handlePageChange}
              />
            </>
          )}
        </div>
      </div>

      {/* Mobile Filter Sheet */}
      <FilterSheet
        open={showFilter}
        onClose={() => setShowFilter(false)}
        categories={categories}
        brands={brands}
        params={searchParams}
        onChange={(next) => { handleParamChange(next); setShowFilter(false); }}
      />
    </div>
  );
}
