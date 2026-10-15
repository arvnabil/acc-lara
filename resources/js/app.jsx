import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import Layout from './components/Layout.jsx';
import { ToastProvider } from './context/ToastContext.jsx';
import { setOutlet } from './compat/router.jsx';
import HomePage from './pages/HomePage.jsx';
import CatalogPage from './pages/CatalogPage.jsx';
import ProductDetailPage from './pages/ProductDetailPage.jsx';
import ProductNotFound from './pages/ProductNotFound.jsx';
import CategoryMenuPage from './pages/CategoryMenuPage.jsx';
import SearchPage from './pages/SearchPage.jsx';
import LoginPage from './pages/LoginPage.jsx';
import RegisterPage from './pages/RegisterPage.jsx';
import ForgotPasswordPage from './pages/ForgotPasswordPage.jsx';
import DashboardPage from './pages/DashboardPage.jsx';
import CheckoutPage from './pages/CheckoutPage.jsx';
import CartPage from './pages/CartPage.jsx';
import WishlistPage from './pages/WishlistPage.jsx';
import LacakPesananPage from './pages/LacakPesananPage.jsx';
import RfqPage from './pages/RfqPage.jsx';
import BantuanPage from './pages/BantuanPage.jsx';
import NotFound from './pages/NotFound.jsx';
import { clearPageCache } from './pwa';

const pageFiles = {
    'Catalog::HomePage': HomePage,
    'Catalog::CatalogPage': CatalogPage,
    'Catalog::ProductDetailPage': ProductDetailPage,
    'Catalog::ProductNotFound': ProductNotFound,
    'Catalog::CategoryMenuPage': CategoryMenuPage,
    'Catalog::SearchPage': SearchPage,
    'Catalog::LoginPage': LoginPage,
    'Catalog::RegisterPage': RegisterPage,
    'Catalog::ForgotPasswordPage': ForgotPasswordPage,
    'Catalog::DashboardPage': DashboardPage,
    'Catalog::CheckoutPage': CheckoutPage,
    'Catalog::CartPage': CartPage,
    'Catalog::WishlistPage': WishlistPage,
    'Catalog::LacakPesananPage': LacakPesananPage,
    'Catalog::RfqPage': RfqPage,
    'Catalog::BantuanPage': BantuanPage,
    'Catalog::NotFound': NotFound,
};

function resolve(name) {
    const Component = pageFiles[name];
    if (!Component) throw new Error(`Inertia page not found: ${name}`);

    if (!['Catalog::CategoryMenuPage', 'Catalog::SearchPage', 'Catalog::LoginPage', 'Catalog::RegisterPage', 'Catalog::ForgotPasswordPage'].includes(name)) {
        Component.layout = (page) => {
            setOutlet(page);
            return <Layout />;
        };
    }

    return Component;
}

createInertiaApp({
    resolve,
    setup({ el, App, props }) {
        createRoot(el).render(<ToastProvider><App {...props} /></ToastProvider>);
    },
    progress: { color: '#003599' },
});

const clearSensitivePageCache = ({ detail }) => {
    const status = detail?.response?.status;
    if (status === 401 || status === 419) clearPageCache();
};

document.addEventListener('inertia:invalid', clearSensitivePageCache);
