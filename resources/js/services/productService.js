/**
 * src/services/productService.js
 * Simulates server-side filtering, sorting, and pagination.
 * Drop-in replacement: swap fetch(API) for JSON imports when migrating to Laravel+Inertia.
 */

import productsIndex from '../data/products-index.json';
import categoriesData from '../data/categories.json';
import brandsData from '../data/brands.json';

// Lazy-loaded large data cache
let _products = null;
let _variants = null;

async function loadIndex() {
  return productsIndex;
}

async function loadProducts() {
  if (!_products) {
    const m = await import('../data/products.json');
    _products = m.default;
  }
  return _products;
}

async function loadVariants() {
  if (!_variants) {
    const m = await import('../data/variants.json');
    _variants = m.default;
  }
  return _variants;
}

export async function getCategories() {
  return categoriesData;
}

export async function getBrands() {
  return brandsData;
}

/**
 * getProducts({ page, per_page, category, brand, q, sort, in_stock, min_price, max_price })
 * Returns: { data: [], total, page, per_page, total_pages }
 */
export async function getProducts({
  page = 1,
  per_page = 24,
  category = null,   // category slug (full_path slug)
  brand = null,      // brand slug
  q = '',
  sort = 'default',  // default | price_asc | price_desc | name_asc
  in_stock = false,
  min_price = null,
  max_price = null,
} = {}) {
  const index = await loadIndex();
  const cats = await getCategories();
  const brands = await getBrands();

  let items = index.filter(p => !p.is_addon);

  // Filter by category
  if (category) {
    const cat = cats.find(c => c.slug === category || c.full_path === category);
    if (cat) {
      // include this category and all descendants
      const catIds = getAllDescendantIds(cats, cat.id);
      items = items.filter(p => p.category_ids.some(id => catIds.includes(id)));
    }
  }

  // Filter by brand
  if (brand) {
    const b = brands.find(br => br.slug === brand);
    if (b) items = items.filter(p => p.brand_id === b.id);
  }

  // Search
  if (q && q.trim()) {
    const qLower = q.trim().toLowerCase();
    items = items.filter(p =>
      p.name.toLowerCase().includes(qLower) ||
      p.sku.toLowerCase().includes(qLower)
    );
  }

  // In-stock filter
  if (in_stock) {
    items = items.filter(p => p.stock > 0);
  }

  // Price filter
  if (min_price !== null) items = items.filter(p => p.regular_price >= min_price);
  if (max_price !== null) items = items.filter(p => p.regular_price <= max_price);

  // Sort
  items = [...items];
  switch (sort) {
    case 'price_asc':
      items.sort((a, b) => a.regular_price - b.regular_price);
      break;
    case 'price_desc':
      items.sort((a, b) => b.regular_price - a.regular_price);
      break;
    case 'name_asc':
      items.sort((a, b) => a.name.localeCompare(b.name, 'id'));
      break;
    case 'featured':
      items.sort((a, b) => (b.is_featured ? 1 : 0) - (a.is_featured ? 1 : 0));
      break;
    default:
      break;
  }

  const total = items.length;
  const total_pages = Math.max(1, Math.ceil(total / per_page));
  const safePage = Math.min(Math.max(1, page), total_pages);
  const start = (safePage - 1) * per_page;
  const data = items.slice(start, start + per_page);

  return { data, total, page: safePage, per_page, total_pages };
}

/** Get full product with description */
export async function getProduct(slug) {
  const products = await loadProducts();
  return products.find(p => p.slug === slug) || null;
}

/** Get variants for a product id */
export async function getVariants(productId) {
  const variants = await loadVariants();
  return variants.filter(v => v.product_id === productId);
}

/** Get related products (same category, different product) */
export async function getRelatedProducts(product, limit = 6) {
  const index = await loadIndex();
  const catId = product.category_ids[0];
  return index
    .filter(p => p.id !== product.id && !p.is_addon && p.category_ids.includes(catId))
    .slice(0, limit);
}

/** Get featured products */
export async function getFeaturedProducts(limit = 8) {
  const index = await loadIndex();
  const featured = index.filter(p => p.is_featured && !p.is_addon);
  if (featured.length > 0) return featured.slice(0, limit);
  return index.filter(p => !p.is_addon).slice(0, limit);
}

/** Get flash sale products */
export async function getFlashSaleProducts(limit = 8) {
  const index = await loadIndex();
  const flash = index
    .filter(p => p.is_flash_sale && !p.is_addon && p.sale_price)
    .sort((a, b) => {
      const discA = (a.regular_price - a.sale_price) / a.regular_price;
      const discB = (b.regular_price - b.sale_price) / b.regular_price;
      return discB - discA;
    });
  if (flash.length > 0) return flash.slice(0, limit);
  return index
    .filter(p => p.sale_price && !p.is_addon)
    .sort((a, b) => {
      const discA = (a.regular_price - a.sale_price) / a.regular_price;
      const discB = (b.regular_price - b.sale_price) / b.regular_price;
      return discB - discA;
    })
    .slice(0, limit);
}

/** Get sale/discount products */
export async function getSaleProducts(limit = 8) {
  const index = await loadIndex();
  return index
    .filter(p => p.sale_price && !p.is_addon)
    .sort((a, b) => {
      const discA = (a.regular_price - a.sale_price) / a.regular_price;
      const discB = (b.regular_price - b.sale_price) / b.regular_price;
      return discB - discA;
    })
    .slice(0, limit);
}

// Helper: get all category IDs including descendants
function getAllDescendantIds(cats, rootId) {
  const ids = [rootId];
  const children = cats.filter(c => c.parent_id === rootId);
  for (const child of children) {
    ids.push(...getAllDescendantIds(cats, child.id));
  }
  return ids;
}

export function formatPrice(amount) {
  if (amount === null || amount === undefined) return '—';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}
