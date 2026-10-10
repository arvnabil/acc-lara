import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

// Halaman inti: resources/js/Pages/Home.jsx            -> 'Home'
// Halaman modul: Modules/Catalog/resources/js/Pages/Index.jsx -> 'Catalog::Index'
const corePages = import.meta.glob('./Pages/**/*.jsx');
const modulePages = import.meta.glob('../../Modules/*/resources/js/Pages/**/*.jsx');

function resolvePage(name) {
  if (name.includes('::')) {
    const [module, page] = name.split('::');
    const key = `../../Modules/${module}/resources/js/Pages/${page}.jsx`;
    if (!modulePages[key]) throw new Error(`Halaman modul tidak ditemukan: ${name}`);
    return modulePages[key]().then((m) => m.default);
  }
  const key = `./Pages/${name}.jsx`;
  if (!corePages[key]) throw new Error(`Halaman tidak ditemukan: ${name}`);
  return corePages[key]().then((m) => m.default);
}

createInertiaApp({
  resolve: resolvePage,
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
