import { router, usePage } from '@inertiajs/react';

// Parity: CatalogPage.jsx. Filter -> router.get dengan partial reload.
export default function Index({ products, filters }) {
  const apply = (next) =>
    router.get(route('catalog.index'), { ...filters, ...next, halaman: undefined }, {
      only: ['products', 'filters'], preserveState: true, replace: false,
    });

  return (
    <div className="mx-auto max-w-7xl p-4">
      <select value={filters.sort ?? ''} onChange={(e) => apply({ sort: e.target.value })}>
        <option value="">Unggulan</option>
        <option value="price_asc">Harga terendah</option>
        <option value="price_desc">Harga tertinggi</option>
        <option value="name_asc">Nama A-Z</option>
      </select>
      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        {products.data.map((p) => <a key={p.id} href={route('catalog.show', p.slug)}>{p.name}</a>)}
      </div>
    </div>
  );
}
