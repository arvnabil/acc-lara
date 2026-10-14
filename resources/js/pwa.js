export async function clearPageCache() {
  if (typeof window !== 'undefined' && 'caches' in window) {
    await window.caches.delete('pages');
  }
}
