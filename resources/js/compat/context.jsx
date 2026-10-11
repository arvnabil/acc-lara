import { usePage } from '@inertiajs/react';
import { useToast as useOriginalToast } from '../context/ToastContext.jsx';

export function useAuth() {
    const user = usePage().props.auth?.user ?? null;
    return { user, loading: false, login: async () => {}, logout: async () => {}, register: async () => {}, updateUser: () => {} };
}

export function useCart() {
    return { items: [], itemCount: 0, totalItems: 0, subtotal: 0, total: 0, cart: null, addItem: () => {}, removeItem: () => {}, updateQuantity: () => {}, clearCart: () => {}, applyPromo: () => {}, removePromo: () => {}, appliedPromo: null };
}

export function useWishlist() {
    return { wishlist: [], toggleWishlist: () => {}, isInWishlist: () => false };
}

export const useToast = useOriginalToast;
