/**
 * src/context/ToastContext.jsx
 * Global toast notification system with responsive mobile pill banner.
 */
import { createContext, useContext, useState, useCallback, useRef } from 'react';
import { Link } from 'react-router-dom';

const ToastContext = createContext(null);

export function ToastProvider({ children }) {
  const [toast, setToast] = useState(null);
  const timerRef = useRef(null);

  const hideToast = useCallback(() => {
    if (timerRef.current) clearTimeout(timerRef.current);
    setToast(null);
  }, []);

  const showToast = useCallback(({ type = 'success', title, message, link, linkText = 'Lihat', duration = 3000 }) => {
    if (timerRef.current) clearTimeout(timerRef.current);

    const id = Date.now();
    setToast({
      id,
      type,
      title,
      message,
      link,
      linkText
    });

    if (duration > 0) {
      timerRef.current = setTimeout(() => {
        setToast(prev => (prev?.id === id ? null : prev));
      }, duration);
    }
  }, []);

  return (
    <ToastContext.Provider value={{ showToast, hideToast }}>
      {children}

      {/* Floating Toast Notification on Mobile & Desktop */}
      {toast && (
        <aside
          role="status"
          aria-live="polite"
          className="fixed top-4 left-1/2 -translate-x-1/2 z-[100] w-[92%] max-w-[420px] bg-card-bg/95 backdrop-blur-md border border-border-subtle rounded-2xl shadow-[0_12px_36px_rgba(11,28,48,0.22)] p-3 sm:p-3.5 flex items-center gap-3 animate-fade transition-all"
        >
          {/* Icon Badge */}
          <div className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${
            toast.type === 'wishlist' ? 'bg-red-50 text-red-500'
            : toast.type === 'info' ? 'bg-blue-50 text-primary'
            : toast.type === 'error' ? 'bg-red-50 text-red-500'
            : toast.type === 'success' ? 'bg-emerald-50 text-emerald-600'
            : 'bg-emerald-50 text-emerald-600'
          }`}>
            <span className="material-symbols-outlined text-[22px]">
              {toast.type === 'wishlist' ? 'favorite' 
               : toast.type === 'info' ? 'info' 
               : toast.type === 'error' ? 'error'
               : toast.type === 'success' ? 'check_circle'
               : 'shopping_bag'}
            </span>
          </div>

          {/* Content */}
          <div className="flex-1 min-w-0">
            <div className="font-bold text-[13px] text-text-primary leading-tight">
              {toast.title}
            </div>
            {toast.message && (
              <div className="text-[11px] text-text-secondary truncate mt-0.5">
                {toast.message}
              </div>
            )}
          </div>

          {/* Optional Action Link */}
          {toast.link && (
            <Link
              to={toast.link}
              onClick={hideToast}
              className="px-2.5 py-1.5 rounded-lg bg-surface hover:bg-surface-container text-primary text-[11px] font-bold border border-border-subtle flex-shrink-0 transition-colors shadow-2xs"
            >
              {toast.linkText}
            </Link>
          )}

          {/* Close button */}
          <button
            onClick={hideToast}
            className="w-7 h-7 rounded-full flex items-center justify-center text-text-secondary hover:text-text-primary active:bg-surface flex-shrink-0 transition-colors"
            aria-label="Tutup notifikasi"
          >
            <span className="material-symbols-outlined text-[18px]">close</span>
          </button>
        </aside>
      )}
    </ToastContext.Provider>
  );
}

export function useToast() {
  const ctx = useContext(ToastContext);
  return ctx || { showToast: () => {}, hideToast: () => {} };
}
