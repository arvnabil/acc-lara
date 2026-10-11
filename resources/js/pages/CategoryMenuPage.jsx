/**
 * src/pages/CategoryMenuPage.jsx
 * TAHAP 3: Full-screen category menu for mobile.
 * Route: /kategori
 * Uses drill-down 2-level nav, URL-based level, search, and Back button support.
 */
import { useState, useEffect, useMemo } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { getCategories } from '../services/productService';

function getCatIcon(slug = '') {
  const map = {
    'video-conference': 'video_call',
    'video': 'video_call',
    'interactive-display': 'tv',
    'display': 'tv',
    'server': 'dns',
    'storage': 'storage',
    'network': 'router',
    'cctv': 'videocam',
    'surveillance': 'videocam',
    'ip-phone': 'call',
    'phone': 'call',
    'headset': 'headset_mic',
    'webcam': 'webcam',
    'camera': 'camera_alt',
    'aksesoris': 'cable',
    'kabel': 'cable',
    'logitech': 'domain',
    'yealink': 'domain',
    'jabra': 'domain',
    'poly': 'domain',
    'aver': 'domain',
    'brand': 'domain',
    'workspace': 'desk',
    'personal': 'desk',
    'ups': 'battery_charging_full',
    'printer': 'print',
    'projector': 'duo',
  };
  for (const [key, icon] of Object.entries(map)) {
    if (slug.includes(key)) return icon;
  }
  return 'category';
}

export default function CategoryMenuPage() {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState('');
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();

  // Level stored in URL: ?level=parent_id (or absent = root)
  const activeLevelId = searchParams.get('level') ? parseInt(searchParams.get('level'), 10) : null;

  useEffect(() => {
    getCategories().then(cats => {
      setCategories(cats.filter(c => !c.is_addon));
      setLoading(false);
    });
  }, []);

  // Build tree: root nodes and children
  const rootNodes = useMemo(
    () => categories.filter(c => !c.parent_id && !c.is_addon),
    [categories]
  );

  const activeParent = useMemo(
    () => (activeLevelId ? categories.find(c => c.id === activeLevelId) : null),
    [categories, activeLevelId]
  );

  const children = useMemo(
    () => (activeLevelId ? categories.filter(c => c.parent_id === activeLevelId) : []),
    [categories, activeLevelId]
  );

  // Filtered list depending on level and search query
  const displayList = useMemo(() => {
    const base = activeLevelId ? children : rootNodes;
    if (!query.trim()) return base;
    const q = query.toLowerCase();
    // When searching from root, search all categories
    const searchBase = query && !activeLevelId ? categories : base;
    return searchBase.filter(c =>
      c.name.toLowerCase().includes(q) ||
      c.full_path.toLowerCase().includes(q)
    );
  }, [rootNodes, children, activeLevelId, query, categories]);

  function goToLevel(catId) {
    setQuery('');
    setSearchParams({ level: String(catId) });
  }

  function goBack() {
    if (activeLevelId) {
      // Go back to root
      setQuery('');
      setSearchParams({});
    } else {
      // If at root level, go directly to Home to avoid history loops
      navigate('/');
    }
  }

  function selectLeaf(cat) {
    navigate(`/katalog?kategori=${cat.slug}`);
  }

  const hasChildren = (catId) => categories.some(c => c.parent_id === catId);

  if (loading) {
    return (
      <div className="flex flex-col w-full min-h-[100dvh] bg-page-background">
        {Array.from({ length: 10 }).map((_, i) => (
          <div key={i} className="h-14 border-b border-border-subtle animate-pulse bg-surface mx-4 my-1 rounded-lg" />
        ))}
      </div>
    );
  }

  return (
    <div className="flex flex-col w-full min-h-[100dvh] bg-page-background">
      {/* Sticky search + back header */}
      <div className="sticky top-0 z-10 bg-card-bg border-b border-border-subtle shadow-sm">
        {/* Level breadcrumb / back */}
        <div className="flex items-center gap-2 px-4 py-3">
          {activeLevelId ? (
            <>
              <button
                onClick={goBack}
                aria-label="Kembali ke daftar kategori"
                className="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-surface transition-colors -ml-1 flex-shrink-0"
              >
                <span className="material-symbols-outlined text-[22px] text-text-primary">arrow_back</span>
              </button>
              <div className="flex-1 min-w-0">
                <p className="font-sku text-[11px] text-text-secondary uppercase">Kategori</p>
                <h2 className="font-title-card text-[16px] font-bold text-text-primary truncate">{activeParent?.name}</h2>
              </div>
            </>
          ) : (
            <>
              <button
                onClick={goBack}
                aria-label="Tutup menu kategori"
                className="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-surface transition-colors -ml-1 flex-shrink-0"
              >
                <span className="material-symbols-outlined text-[22px] text-text-primary">arrow_back</span>
              </button>
              <h2 className="font-title-card text-[18px] font-bold text-text-primary flex-1">Semua Kategori</h2>
            </>
          )}
        </div>

        {/* Search box */}
        <div className="px-4 pb-3">
          <div className="flex items-center bg-surface rounded-xl border border-border-subtle focus-within:border-primary transition-colors gap-2 px-3">
            <span className="material-symbols-outlined text-[20px] text-outline">search</span>
            <input
              type="search"
              value={query}
              onChange={e => setQuery(e.target.value)}
              placeholder={activeLevelId ? `Cari di ${activeParent?.name || ''}...` : 'Cari kategori...'}
              className="flex-1 py-2.5 bg-transparent text-[14px] text-text-primary placeholder:text-outline focus:outline-none"
            />
            {query && (
              <button onClick={() => setQuery('')} aria-label="Hapus pencarian">
                <span className="material-symbols-outlined text-[18px] text-outline">close</span>
              </button>
            )}
          </div>
        </div>
      </div>

      {/* List */}
      <div className="flex-1 overflow-y-auto pb-[env(safe-area-inset-bottom)] pb-4">
        {/* "Lihat semua" option at top of sub-level */}
        {activeLevelId && !query && (
          <Link
            to={`/katalog?kategori=${activeParent?.slug}`}
            className="flex items-center justify-between px-4 py-4 border-b border-border-subtle hover:bg-surface transition-colors"
          >
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                <span className="material-symbols-outlined text-[20px]">grid_view</span>
              </div>
              <div>
                <p className="font-label-sm text-[14px] font-semibold text-text-primary">Lihat Semua {activeParent?.name}</p>
                <p className="font-sku text-[11px] text-text-secondary">{activeParent?.product_count} produk</p>
              </div>
            </div>
            <span className="material-symbols-outlined text-[20px] text-outline">arrow_forward</span>
          </Link>
        )}

        {displayList.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-16 text-text-secondary">
            <span className="material-symbols-outlined text-[48px] mb-2 text-outline">search_off</span>
            <p className="font-label-sm text-[14px]">Kategori tidak ditemukan</p>
          </div>
        ) : (
          displayList.map(cat => {
            const isParent = hasChildren(cat.id);
            const icon = getCatIcon(cat.slug);

            // If this is a search result showing full path, link directly to catalog
            const isSearchResult = query && !activeLevelId;

            return (
              <div key={cat.id}>
                {isParent && !isSearchResult ? (
                  <button
                    onClick={() => goToLevel(cat.id)}
                    className="w-full flex items-center justify-between px-4 py-4 border-b border-border-subtle hover:bg-surface transition-colors text-left"
                    aria-label={`Buka sub-kategori ${cat.name}`}
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-lg bg-surface flex items-center justify-center text-primary flex-shrink-0">
                        <span className="material-symbols-outlined text-[20px]">{icon}</span>
                      </div>
                      <div>
                        <p className="font-label-sm text-[14px] font-semibold text-text-primary">{cat.name}</p>
                        <p className="font-sku text-[11px] text-text-secondary">{cat.product_count} produk</p>
                      </div>
                    </div>
                    <span className="material-symbols-outlined text-[20px] text-outline flex-shrink-0">chevron_right</span>
                  </button>
                ) : (
                  <Link
                    to={`/katalog?kategori=${cat.slug}`}
                    className="flex items-center justify-between px-4 py-4 border-b border-border-subtle hover:bg-surface transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-lg bg-surface flex items-center justify-center text-primary flex-shrink-0">
                        <span className="material-symbols-outlined text-[20px]">{icon}</span>
                      </div>
                      <div>
                        <p className="font-label-sm text-[14px] font-semibold text-text-primary">{cat.name}</p>
                        <p className="font-sku text-[11px] text-text-secondary">
                          {isSearchResult && cat.parent_id
                            ? `${cat.full_path.split(' > ').slice(0, -1).join(' > ')} · `
                            : ''
                          }
                          {cat.product_count} produk
                        </p>
                      </div>
                    </div>
                    <span className="material-symbols-outlined text-[20px] text-outline flex-shrink-0">arrow_forward</span>
                  </Link>
                )}
              </div>
            );
          })
        )}
      </div>
    </div>
  );
}
