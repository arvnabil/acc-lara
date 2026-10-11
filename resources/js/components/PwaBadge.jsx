/**
 * src/components/PwaBadge.jsx
 * PWA Install and Update banners using virtual modules from vite-plugin-pwa.
 */
import { useRegisterSW } from 'virtual:pwa-register/react';

export default function PwaBadge() {
  const {
    needRefresh: [needRefresh, setNeedRefresh],
    updateServiceWorker,
  } = useRegisterSW({
    onRegistered(r) {
      // Setup periodic update check if needed
    },
    onRegisterError(error) {
      console.error('SW registration error', error);
    },
  });

  if (!needRefresh) return null;

  return (
    <div className="fixed bottom-[80px] left-1/2 -translate-x-1/2 z-[100] max-w-[400px] w-[90%] bg-card-bg border border-border-subtle shadow-xl rounded-xl p-4 flex items-center justify-between gap-4 transition-all animate-in fade-in slide-in-from-bottom-5">
      <div>
        <h4 className="font-title-card text-[14px] font-bold text-text-primary">Pembaruan Tersedia</h4>
        <p className="font-body-md text-[12px] text-text-secondary">Versi baru aplikasi telah siap. Muat ulang untuk memperbarui.</p>
      </div>
      <div className="flex flex-col gap-2 flex-shrink-0">
        <button
          onClick={() => updateServiceWorker(true)}
          className="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-[12px] px-4 py-2 rounded-lg transition-colors"
        >
          Muat Ulang
        </button>
        <button
          onClick={() => setNeedRefresh(false)}
          className="text-text-secondary hover:text-text-primary font-label-sm text-[12px] px-4 py-1 transition-colors"
        >
          Nanti Saja
        </button>
      </div>
    </div>
  );
}
