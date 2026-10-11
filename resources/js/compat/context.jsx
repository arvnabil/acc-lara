import { router as inertiaRouter, usePage } from '@inertiajs/react';
import { useToast as useOriginalToast } from '../context/ToastContext.jsx';

export function useAuth() {
    const props = usePage().props;
    const user = props.auth?.user ? { ...props.auth.user, ...(props.account ?? {}) } : null;

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
    return { items: [], itemCount: 0, totalItems: 0, subtotal: 0, total: 0, cart: null, addItem: () => {}, removeItem: () => {}, updateQuantity: () => {}, clearCart: () => {}, applyPromo: () => {}, removePromo: () => {}, appliedPromo: null };
}

export function useWishlist() {
    return { wishlist: [], toggleWishlist: () => {}, isInWishlist: () => false };
}

export const useToast = useOriginalToast;
