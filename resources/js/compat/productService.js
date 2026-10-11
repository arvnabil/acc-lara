export function formatPrice(amount) {
    if (amount === null || amount === undefined) return '—';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
}
