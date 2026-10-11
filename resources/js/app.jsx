import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import Layout from './components/Layout.jsx';
import { ToastProvider } from './context/ToastContext.jsx';
import { setOutlet } from './compat/router.jsx';
import HomePage from './pages/HomePage.jsx';
import CatalogPage from './pages/CatalogPage.jsx';
import ProductDetailPage from './pages/ProductDetailPage.jsx';
import CategoryMenuPage from './pages/CategoryMenuPage.jsx';
import SearchPage from './pages/SearchPage.jsx';
import LoginPage from './pages/LoginPage.jsx';

const pageFiles = {
    'Catalog::HomePage': HomePage,
    'Catalog::CatalogPage': CatalogPage,
    'Catalog::ProductDetailPage': ProductDetailPage,
    'Catalog::CategoryMenuPage': CategoryMenuPage,
    'Catalog::SearchPage': SearchPage,
    'Catalog::LoginPage': LoginPage,
};

function resolve(name) {
    const Component = pageFiles[name];
    if (!Component) throw new Error(`Inertia page not found: ${name}`);

    if (!['Catalog::CategoryMenuPage', 'Catalog::SearchPage', 'Catalog::LoginPage'].includes(name)) {
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
