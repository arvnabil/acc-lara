/**
 * src/context/CartContext.jsx
 * Cart state backed by localStorage.
 */
import { createContext, useContext, useState, useEffect } from 'react';

const CartContext = createContext(null);
const STORAGE_KEY = 'accommerce_cart';
const PROMO_STORAGE_KEY = 'accommerce_promo';

const DEFAULT_DEMO_ITEMS = [
  {
    key: 'demo-logitech-rally',
    product_id: 'logitech-rally-bar',
    sku: 'LOG-960-001308',
    name: 'Logitech Rally Bar All-in-One',
    price: 45500000,
    image: 'https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04?w=200&q=80',
    quantity: 1,
    stock: 12,
  },
  {
    key: 'demo-ubiquiti-unifi',
    product_id: 'ubiquiti-unifi-pro',
    sku: 'UBQ-U6-PRO',
    name: 'Ubiquiti UniFi Access Point WiFi 6',
    price: 3450000,
    image: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=200&q=80',
    quantity: 2,
    stock: 25,
  }
];

export function CartProvider({ children }) {
  const [items, setItems] = useState(() => {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        const parsed = JSON.parse(saved);
        return Array.isArray(parsed) ? parsed : DEFAULT_DEMO_ITEMS;
      }
      return DEFAULT_DEMO_ITEMS;
    } catch {
      return DEFAULT_DEMO_ITEMS;
    }
  });

  const [appliedPromo, setAppliedPromo] = useState(() => {
    try {
      const saved = localStorage.getItem(PROMO_STORAGE_KEY);
      return saved ? JSON.parse(saved) : null;
    } catch {
      return null;
    }
  });

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
  }, [items]);

  useEffect(() => {
    if (appliedPromo) {
      localStorage.setItem(PROMO_STORAGE_KEY, JSON.stringify(appliedPromo));
    } else {
      localStorage.removeItem(PROMO_STORAGE_KEY);
    }
  }, [appliedPromo]);

  function addItem(product, quantity = 1, variant = null) {
    const key = product.cartKey || (variant ? `${product.id}-v${variant.id}` : `${product.id}`);
    setItems(prev => {
      const existing = prev.find(i => i.key === key);
      if (existing) {
        return prev.map(i => i.key === key ? { ...i, quantity: i.quantity + quantity } : i);
      }
      return [...prev, {
        key,
        product_id: product.id,
        variant_id: variant?.id || null,
        sku: variant?.sku || product.sku,
        name: variant?.name || product.name,
        price: variant?.sale_price || variant?.regular_price || product.sale_price || product.regular_price,
        image: (variant?.images?.[0] || product.images?.[0]) || '',
        quantity,
        stock: variant?.stock ?? product.stock,
        rentalInfo: product.rentalInfo || null,
      }];
    });
  }

  function removeItem(key) {
    setItems(prev => prev.filter(i => i.key !== key));
  }

  function updateQuantity(key, quantity) {
    if (quantity <= 0) return removeItem(key);
    setItems(prev => prev.map(i => i.key === key ? { ...i, quantity } : i));
  }

  function clearCart() {
    setItems([]);
    setAppliedPromo(null);
  }

  const itemCount = items.reduce((sum, i) => sum + i.quantity, 0);
  const total = items.reduce((sum, i) => sum + i.price * i.quantity, 0);

  return (
    <CartContext.Provider value={{
      items,
      addItem,
      removeItem,
      updateQuantity,
      clearCart,
      itemCount,
      total,
      appliedPromo,
      setAppliedPromo
    }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error('useCart must be used within CartProvider');
  return ctx;
}
