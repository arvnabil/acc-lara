import { Link as InertiaLink, router as inertiaRouter, usePage } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';

let currentOutlet = null;

function hrefFrom(to) {
    return typeof to === 'string' ? to : to?.pathname ?? '/';
}

export function Link({ to, href, children, ...props }) {
    return <InertiaLink href={hrefFrom(href ?? to)} {...props}>{children}</InertiaLink>;
}

export function NavLink({ to, children, ...props }) {
    return <Link to={to} {...props}>{children}</Link>;
}

export function useNavigate() {
    return (to, options = {}) => typeof to === 'number' && to === -1
        ? window.history.back()
        : inertiaRouter.visit(hrefFrom(to), options);
}

export function useLocation() {
    const { url } = usePage();
    const parsed = useMemo(() => new URL(url, window.location.origin), [url]);
    return { pathname: parsed.pathname, search: parsed.search, hash: parsed.hash, state: null, key: parsed.href };
}

export function useParams() {
    const { pathname } = useLocation();
    const match = pathname.match(/^\/produk\/([^/]+)/);
    return { slug: match ? decodeURIComponent(match[1]) : undefined };
}

export function useSearchParams() {
    const { search } = useLocation();
    const params = useMemo(() => new URLSearchParams(search), [search]);
    const setSearchParams = (next) => inertiaRouter.get(window.location.pathname, Object.fromEntries(new URLSearchParams(next)), { preserveState: true, preserveScroll: false });
    return [params, setSearchParams];
}

export function Navigate({ to, replace = false }) {
    useEffect(() => { inertiaRouter.visit(hrefFrom(to), { replace }); }, [to, replace]);
    return null;
}

export function setOutlet(element) {
    currentOutlet = element;
}

export function Outlet() {
    return currentOutlet;
}

export { inertiaRouter as router };
