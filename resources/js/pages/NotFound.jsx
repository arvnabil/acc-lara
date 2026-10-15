export default function NotFound() {
  return (
    <div className="flex flex-col items-center justify-center min-h-[60dvh] text-text-secondary px-4">
      <span className="material-symbols-outlined text-[72px] mb-4 text-outline">error</span>
      <h2 className="font-title-card text-[20px] font-bold text-text-primary mb-2">Halaman tidak ditemukan</h2>
      <a href="/" className="bg-primary text-on-primary font-label-sm text-label-sm px-8 py-3 rounded-lg mt-4 inline-block">
        Kembali ke Beranda
      </a>
    </div>
  );
}
