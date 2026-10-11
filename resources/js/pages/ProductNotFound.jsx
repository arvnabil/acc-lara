import { Link } from '../compat/router';

export default function ProductNotFound() {
    return (
        <main className="min-h-screen flex items-center justify-center bg-white px-6 text-center">
            <div>
                <p className="text-sm font-semibold text-primary">404</p>
                <h1 className="mt-2 text-2xl font-bold text-text-primary">Produk Tidak Ditemukan</h1>
                <p className="mt-2 text-text-secondary">Produk yang Anda cari tidak tersedia.</p>
                <Link to="/katalog" className="mt-6 inline-block rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white">
                    Kembali ke Katalog
                </Link>
            </div>
        </main>
    );
}
