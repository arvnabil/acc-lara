import { createContext, useContext, useState, useEffect } from 'react';

const WishlistContext = createContext();

export function WishlistProvider({ children }) {
  const [wishlist, setWishlist] = useState([]);

  useEffect(() => {
    const stored = localStorage.getItem('accommerce_wishlist');
    if (stored) {
      try {
        setWishlist(JSON.parse(stored));
      } catch (e) {
        setWishlist([]);
      }
    } else {
      setWishlist(['prod-1', 'prod-3']);
    }
  }, []);

  function toggleWishlist(productId) {
    setWishlist(prev => {
      const isExist = prev.includes(productId);
      let next;
      if (isExist) {
        next = prev.filter(id => id !== productId);
      } else {
        next = [...prev, productId];
      }
      localStorage.setItem('accommerce_wishlist', JSON.stringify(next));
      return next;
    });
  }

  function isInWishlist(productId) {
    return wishlist.includes(productId);
  }

  return (
    <WishlistContext.Provider value={{ wishlist, toggleWishlist, isInWishlist }}>
      {children}
    </WishlistContext.Provider>
  );
}

export function useWishlist() {
  return useContext(WishlistContext);
}
