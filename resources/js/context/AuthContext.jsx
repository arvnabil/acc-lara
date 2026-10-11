import { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);

  useEffect(() => {
    // Check local storage for dummy auth
    const stored = localStorage.getItem('accommerce_user');
    if (stored) {
      try {
        const parsed = JSON.parse(stored);
        // Ensure defaults exist even if older session
        setUser({
          name: parsed.name || 'John Doe',
          email: parsed.email || 'johndoe@email.com',
          memberTier: parsed.memberTier || 'MEMBER PLATINUM',
          memberSince: parsed.memberSince || '2024',
          walletBalance: parsed.walletBalance ?? 1500000,
          points: parsed.points ?? 2450,
          tierPoints: parsed.tierPoints ?? 16800,
          vouchers: parsed.vouchers || [
            { id: 'VCH-1', code: 'DISKON50K', title: 'Voucher Potongan Rp 50.000', minOrder: 500000, discount: 50000, expiry: '31 Des 2026' },
            { id: 'VCH-2', code: 'GRATISONGKIR', title: 'Gratis Ongkir s/d Rp 100.000', minOrder: 1000000, discount: 100000, expiry: '15 Jan 2027' },
            { id: 'VCH-3', code: 'AVSOLUTION10', title: 'Diskon 10% Audio Visual', minOrder: 2000000, discount: 200000, expiry: '28 Feb 2027' }
          ],
          addresses: parsed.addresses || [
            {
              id: 'addr-1',
              label: 'Kantor',
              recipientName: parsed.name || 'John Doe',
              phone: '+62 812-3456-7890',
              province: 'Banten',
              city: 'Tangerang Selatan',
              address: 'Green Office Park 9, BSD City, Pagedangan, Tangerang Selatan 15345',
              postalCode: '15345',
              isDefault: true,
            }
          ],
          orders: parsed.orders || [
            {
              id: 'ACC-98214',
              title: 'Logitech Rally Bar & UniFi AP Pro',
              date: '24 Oktober 2026',
              total: 58514000,
              status: 'Dalam Pengiriman',
              items: [
                { name: 'Logitech Rally Bar Graphite', qty: 1, price: 48500000 },
                { name: 'Ubiquiti UniFi AP Pro', qty: 4, price: 2503500 }
              ]
            },
            {
              id: 'ACC-97810',
              title: 'Samsung Smart Signage 55 Inch QM55B',
              date: '10 Oktober 2026',
              total: 21500000,
              status: 'Selesai',
              items: [
                { name: 'Samsung Smart Signage 55 Inch QM55B', qty: 1, price: 21500000 }
              ]
            }
          ],
          wishlist: parsed.wishlist || ['prod-1', 'prod-3'],
          reviewsPending: parsed.reviewsPending ?? 12,
          ewalletClaims: parsed.ewalletClaims || [
            {
              id: 'CLM-EW-89102',
              date: '2 Oktober 2026, 14:20',
              platform: 'GoPay',
              phone: '0812-3456-7890',
              accountName: parsed.name || 'John Doe',
              points: 500,
              amount: 50000,
              status: 'Berhasil Ditransfer',
              refNumber: 'TRX-98271635'
            }
          ],
          reviewedOrderItems: parsed.reviewedOrderItems || []
        });
      } catch (e) {
        setUser(null);
      }
    }
  }, []);

  const DEFAULT_EWALLET_CLAIMS = (name) => [
    {
      id: 'CLM-EW-89102',
      date: '2 Oktober 2026, 14:20',
      platform: 'GoPay',
      phone: '0812-3456-7890',
      accountName: name || 'John Doe',
      points: 500,
      amount: 50000,
      status: 'Berhasil Ditransfer',
      refNumber: 'TRX-98271635'
    }
  ];

  const DEFAULT_ADDRESSES = (name) => [
    {
      id: 'addr-1',
      label: 'Kantor',
      recipientName: name || 'John Doe',
      phone: '+62 812-3456-7890',
      province: 'Banten',
      city: 'Tangerang Selatan',
      address: 'Green Office Park 9, BSD City, Pagedangan, Tangerang Selatan 15345',
      postalCode: '15345',
      isDefault: true,
    }
  ];

  function login(userData) {
    const fullUser = {
      name: userData.name || 'John Doe',
      email: userData.email || 'johndoe@email.com',
      memberTier: userData.memberTier || 'MEMBER PLATINUM',
      memberSince: userData.memberSince || '2024',
      walletBalance: userData.walletBalance ?? 1500000,
      points: userData.points ?? 2450,
      tierPoints: userData.tierPoints ?? 16800,
      vouchers: userData.vouchers || [
        { id: 'VCH-1', code: 'DISKON50K', title: 'Voucher Potongan Rp 50.000', minOrder: 500000, discount: 50000, expiry: '31 Des 2026' },
        { id: 'VCH-2', code: 'GRATISONGKIR', title: 'Gratis Ongkir s/d Rp 100.000', minOrder: 1000000, discount: 100000, expiry: '15 Jan 2027' },
        { id: 'VCH-3', code: 'AVSOLUTION10', title: 'Diskon 10% Audio Visual', minOrder: 2000000, discount: 200000, expiry: '28 Feb 2027' }
      ],
      addresses: userData.addresses || DEFAULT_ADDRESSES(userData.name),
      orders: userData.orders || [
        {
          id: 'ACC-98214',
          title: 'Logitech Rally Bar & UniFi AP Pro',
          date: '24 Oktober 2026',
          total: 58514000,
          status: 'Dalam Pengiriman',
          items: [
            { name: 'Logitech Rally Bar Graphite', qty: 1, price: 48500000 },
            { name: 'Ubiquiti UniFi AP Pro', qty: 4, price: 2503500 }
          ]
        },
        {
          id: 'ACC-97810',
          title: 'Samsung Smart Signage 55 Inch QM55B',
          date: '10 Oktober 2026',
          total: 21500000,
          status: 'Selesai',
          items: [
            { name: 'Samsung Smart Signage 55 Inch QM55B', qty: 1, price: 21500000 }
          ]
        }
      ],
      wishlist: userData.wishlist || ['prod-1', 'prod-3'],
      reviewsPending: userData.reviewsPending ?? 12,
      ewalletClaims: userData.ewalletClaims || DEFAULT_EWALLET_CLAIMS(userData.name),
      reviewedOrderItems: userData.reviewedOrderItems || []
    };
    setUser(fullUser);
    localStorage.setItem('accommerce_user', JSON.stringify(fullUser));
  }

  function saveAddress(addressData) {
    // If new, generate an id; if existing, update by id
    const isNew = !addressData.id;
    const newAddr = isNew ? { ...addressData, id: `addr-${Date.now()}` } : addressData;
    setUser(prev => {
      let updated = prev.addresses ? [...prev.addresses] : [];
      if (newAddr.isDefault) {
        updated = updated.map(a => ({ ...a, isDefault: false }));
      }
      if (isNew) {
        if (updated.length === 0) newAddr.isDefault = true;
        updated = [...updated, newAddr];
      } else {
        updated = updated.map(a => a.id === newAddr.id ? newAddr : a);
      }
      const next = { ...prev, addresses: updated };
      localStorage.setItem('accommerce_user', JSON.stringify(next));
      return next;
    });
  }

  function deleteAddress(addressId) {
    setUser(prev => {
      let updated = (prev.addresses || []).filter(a => a.id !== addressId);
      // If we deleted the default and there are others, make the first one default
      if (updated.length > 0 && !updated.some(a => a.isDefault)) {
        updated[0] = { ...updated[0], isDefault: true };
      }
      const next = { ...prev, addresses: updated };
      localStorage.setItem('accommerce_user', JSON.stringify(next));
      return next;
    });
  }

  function setDefaultAddress(addressId) {
    setUser(prev => {
      const updated = (prev.addresses || []).map(a => ({ ...a, isDefault: a.id === addressId }));
      const next = { ...prev, addresses: updated };
      localStorage.setItem('accommerce_user', JSON.stringify(next));
      return next;
    });
  }

  function updateUser(updates) {
    setUser(prev => {
      const updated = { ...prev, ...updates };
      localStorage.setItem('accommerce_user', JSON.stringify(updated));
      return updated;
    });
  }

  function claimEwalletPoints(claimData) {
    if (!user || (user.points || 0) < claimData.points) {
      return { success: false, message: 'Poin tidak mencukupi untuk klaim ini.' };
    }
    const newClaim = {
      id: `CLM-EW-${Math.floor(10000 + Math.random() * 90000)}`,
      date: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) + ', ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
      platform: claimData.platform,
      phone: claimData.phone,
      accountName: claimData.accountName,
      points: claimData.points,
      amount: claimData.rupiah,
      status: 'Berhasil Ditransfer',
      refNumber: `TRX-${Date.now().toString().slice(-8)}`
    };
    const currentClaims = user.ewalletClaims || [];
    const updatedClaims = [newClaim, ...currentClaims];
    updateUser({
      points: (user.points || 0) - claimData.points,
      walletBalance: (user.walletBalance || 0) + claimData.rupiah,
      ewalletClaims: updatedClaims
    });
    return { success: true, claim: newClaim };
  }

  function markOrderItemReviewed(itemKey) {
    if (!user) return;
    const reviewed = user.reviewedOrderItems || [];
    if (!reviewed.includes(itemKey)) {
      updateUser({
        reviewedOrderItems: [...reviewed, itemKey],
        points: (user.points || 0) + 50,
        reviewsPending: Math.max(0, (user.reviewsPending || 0) - 1)
      });
    }
  }

  function redeemPointsForWallet(pointsToRedeem, rupiahValue) {
    if (!user || (user.points || 0) < pointsToRedeem) return false;
    updateUser({
      points: (user.points || 0) - pointsToRedeem,
      walletBalance: (user.walletBalance || 0) + rupiahValue
    });
    return true;
  }

  function redeemPointsForVoucher(pointsCost, voucherData) {
    if (!user || (user.points || 0) < pointsCost) return false;
    const newVouchers = [...(user.vouchers || []), voucherData];
    updateUser({
      points: (user.points || 0) - pointsCost,
      vouchers: newVouchers
    });
    return true;
  }

  function addPoints(earnedPoints) {
    if (!user || earnedPoints <= 0) return;
    updateUser({
      points: (user.points || 0) + earnedPoints
    });
  }

  function logout() {
    setUser(null);
    localStorage.removeItem('accommerce_user');
  }

  return (
    <AuthContext.Provider value={{ 
      user, 
      login, 
      logout, 
      updateUser, 
      addPoints,
      redeemPointsForWallet, 
      redeemPointsForVoucher,
      claimEwalletPoints,
      markOrderItemReviewed,
      saveAddress,
      deleteAddress,
      setDefaultAddress,
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
