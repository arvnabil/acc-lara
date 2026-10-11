import { router as inertiaRouter, usePage } from '@inertiajs/react';
import { useToast as useOriginalToast } from '../context/ToastContext.jsx';

export function useAuth() {
    const props = usePage().props;
    const user = props.auth?.user ? { ...props.auth.user, ...(props.account ?? {}), ...(props.loyalty ?? {}) } : null;

    return {
        user,
        loading: false,
        login: (data, options = {}) => inertiaRouter.post('/login', data, options),
        logout: (options = {}) => inertiaRouter.post('/logout', {}, options),
        register: (data, options = {}) => inertiaRouter.post('/register', data, options),
        updateUser: () => {},
        redeemPointsForWallet: () => false,
        redeemPointsForVoucher: () => false,
        claimEwalletPoints: () => ({ success: false }),
        markOrderItemReviewed: () => {},
        saveAddress: (data, options = {}) => {
            const payload = {
                label: data.label,
                recipient_name: data.recipientName,
                phone: data.phone,
                province: data.province,
                city: data.city,
                address: data.address,
                postal_code: data.postalCode,
                is_default: data.isDefault,
            };
            const method = data.id ? 'put' : 'post';
            const url = data.id ? `/akun/alamat/${data.id}` : '/akun/alamat';
            inertiaRouter[method](url, payload, { preserveScroll: true, ...options });
        },
        deleteAddress: (id, options = {}) => inertiaRouter.delete(`/akun/alamat/${id}`, { preserveScroll: true, ...options }),
        setDefaultAddress: (id, options = {}) => inertiaRouter.post(`/akun/alamat/${id}/utama`, {}, { preserveScroll: true, ...options }),
    };
}

export function useCart() {
    const cart = usePage().props.cart ?? { items: [], count: 0 };
    const visit = (method, url, data = {}) => inertiaRouter[method](url, data, { preserveScroll: true });
    return { items: cart.items ?? [], itemCount: cart.count ?? cart.itemCount ?? 0, totalItems: cart.count ?? 0, subtotal: cart.pricing?.goods_subtotal ?? 0, total: cart.pricing?.goods_subtotal ?? 0, cart, appliedPromo: cart.appliedPromo ?? null, addItem: (item, quantity = 1) => visit('post', '/cart/items', { product_id: item.product_id ?? item.id, variant_id: item.variant_id ?? item.variant?.id, quantity, rental_start: item.rentalInfo?.startDate, rental_end: item.rentalInfo?.endDate, rental_start_time: item.rentalInfo?.startTime, rental_end_time: item.rentalInfo?.endTime }), removeItem: (key) => visit('delete', `/cart/items/${key}`), updateQuantity: (key, quantity) => visit('put', `/cart/items/${key}`, { quantity }), clearCart: () => (cart.items ?? []).forEach((item) => visit('delete', `/cart/items/${item.key}`)), applyPromo: (code) => visit('post', '/cart/promo', { code }), removePromo: () => visit('delete', '/cart/promo'), setAppliedPromo: (promo) => promo ? visit('post', '/cart/promo', { code: promo.code }) : visit('delete', '/cart/promo') };
}

export function useWishlist() {
    const ids = usePage().props.wishlist?.ids ?? [];
    const keys = ids.map((id) => `prod-${id}`);
    return { wishlist: keys, toggleWishlist: (productId) => inertiaRouter.post('/wishlist/toggle', { product_id: String(productId).replace(/^prod-/, '') }, { preserveScroll: true }), isInWishlist: (productId) => keys.includes(productId) };
}

export const useToast = useOriginalToast;
