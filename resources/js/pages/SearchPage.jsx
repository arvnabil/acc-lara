/**
 * src/pages/SearchPage.jsx
 * TAHAP 3: Mobile full-screen search with recent history.
 */
import { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';

const HISTORY_KEY = 'accommerce_search_history';

export default function SearchPage() {
  const navigate = useNavigate();
  const inputRef = useRef(null);
  const [query, setQuery] = useState('');
  const [history, setHistory] = useState(() => {
    try { return JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]'); }
    catch { return []; }
  });

  useEffect(() => { inputRef.current?.focus(); }, []);

  function saveHistory(q) {
    const updated = [q, ...history.filter(h => h !== q)].slice(0, 10);
    setHistory(updated);
    localStorage.setItem(HISTORY_KEY, JSON.stringify(updated));
  }

  function handleSearch(q) {
    const trimmed = (q || query).trim();
    if (!trimmed) return;
    saveHistory(trimmed);
    navigate(`/katalog?q=${encodeURIComponent(trimmed)}`);
  }

  function removeHistory(item) {
    const updated = history.filter(h => h !== item);
    setHistory(updated);
    localStorage.setItem(HISTORY_KEY, JSON.stringify(updated));
  }

  return (
    <div className="flex flex-col w-full min-h-[100dvh] bg-page-background">
      {/* Search Input */}
      <div className="sticky top-0 bg-card-bg border-b border-border-subtle px-4 py-3">
        <div className="flex items-center gap-2">
          <button onClick={() => navigate(-1)} aria-label="Kembali" className="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-surface -ml-1 flex-shrink-0">
            <span className="material-symbols-outlined text-[22px]">arrow_back</span>
          </button>
          <div className="flex-1 flex items-center bg-surface rounded-xl border border-border-subtle focus-within:border-primary px-3 gap-2">
            <span className="material-symbols-outlined text-[20px] text-outline">search</span>
            <input
              ref={inputRef}
              type="search"
              value={query}
              onChange={e => setQuery(e.target.value)}
              onKeyDown={e => { if (e.key === 'Enter') handleSearch(); }}
              placeholder="Cari produk, brand, SKU..."
              className="flex-1 py-2.5 bg-transparent text-[14px] text-text-primary placeholder:text-outline focus:outline-none"
            />
            {query && (
              <button onClick={() => setQuery('')} aria-label="Hapus">
                <span className="material-symbols-outlined text-[18px] text-outline">close</span>
              </button>
            )}
          </div>
          <button
            onClick={() => handleSearch()}
            className="bg-primary text-on-primary px-4 py-2 rounded-xl font-label-sm text-label-sm flex-shrink-0"
          >
            Cari
          </button>
        </div>
      </div>

      {/* Suggestions / history */}
      <div className="flex-1 px-4 py-3">
        {history.length > 0 && (
          <>
            <div className="flex items-center justify-between mb-2">
              <h3 className="font-label-sm text-[12px] font-semibold text-text-secondary uppercase">Pencarian Terakhir</h3>
              <button
                onClick={() => { setHistory([]); localStorage.removeItem(HISTORY_KEY); }}
                className="text-[11px] text-primary font-medium"
              >
                Hapus semua
              </button>
            </div>
            <div className="flex flex-col">
              {history.map(item => (
                <div key={item} className="flex items-center gap-2 py-2 border-b border-border-subtle">
                  <span className="material-symbols-outlined text-[18px] text-outline flex-shrink-0">history</span>
                  <button
                    onClick={() => handleSearch(item)}
                    className="flex-1 text-left text-[14px] text-text-primary hover:text-primary transition-colors"
                  >
                    {item}
                  </button>
                  <button
                    onClick={() => removeHistory(item)}
                    aria-label={`Hapus riwayat: ${item}`}
                    className="w-7 h-7 flex items-center justify-center rounded hover:bg-surface"
                  >
                    <span className="material-symbols-outlined text-[16px] text-outline">close</span>
                  </button>
                </div>
              ))}
            </div>
          </>
        )}

        {/* Popular categories as quick access */}
        <div className="mt-4">
          <h3 className="font-label-sm text-[12px] font-semibold text-text-secondary uppercase mb-2">Kategori Populer</h3>
          <div className="flex flex-wrap gap-2">
            {['Video Conference', 'Webcam', 'Headset', 'IP Phone', 'Network', 'CCTV', 'Interactive Display'].map(cat => (
              <button
                key={cat}
                onClick={() => handleSearch(cat)}
                className="px-3 py-1.5 bg-surface border border-border-subtle rounded-full text-[12px] font-medium text-text-primary hover:bg-primary/5 hover:border-primary hover:text-primary transition-colors"
              >
                {cat}
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
