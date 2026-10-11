/**
 * src/components/Layout.jsx
 * Responsive layout: mobile bottom nav + desktop header with mega menu.
 * Safe area, 100dvh, 44px touch targets.
 */
import { Outlet, Link, useLocation, useNavigate } from "react-router-dom";
import { useState, useEffect, useRef } from "react";
import { useCart } from "../context/CartContext";
import { getCategories } from "../services/productService";
import { useAuth } from "../context/AuthContext";
import Footer from "./Footer";

// ─── Category icons ──────────────────────────────────────────────────────────
function getCatIcon(slug = "") {
  const map = {
    video: "video_call",
    conference: "video_call",
    display: "tv",
    interactive: "tv",
    server: "dns",
    storage: "storage",
    network: "router",
    switch: "router",
    cctv: "videocam",
    surveillance: "videocam",
    phone: "call",
    headset: "headset_mic",
    webcam: "webcam",
    camera: "camera_alt",
    aksesoris: "cable",
    kabel: "cable",
    workspace: "desk",
    logitech: "domain",
    brand: "domain",
  };
  for (const [k, v] of Object.entries(map)) {
    if (slug.includes(k)) return v;
  }
  return "category";
}

// ─── Mega Menu ───────────────────────────────────────────────────────────────
function MegaMenu({ categories }) {
  const [hovered, setHovered] = useState(null);
  const topCats = categories
    .filter((c) => !c.parent_id && !c.is_addon && !c.is_brand_group)
    .slice(0, 8);
  const activeCat = hovered ?? topCats[0];
  const children = activeCat
    ? categories.filter((c) => c.parent_id === activeCat.id).slice(0, 6)
    : [];

  return (
    <div
      className="absolute top-full left-0 flex bg-card-bg rounded-xl shadow-xl border border-border-subtle z-50 overflow-hidden"
      style={{ width: "1140px", maxHeight: "480px" }}
    >
      {/* Left: category list */}
      <div className="w-[260px] bg-page-background border-r border-border-subtle flex flex-col py-2 overflow-y-auto flex-shrink-0">
        {topCats.map((cat) => (
          <Link
            key={cat.id}
            to={`/katalog?kategori=${cat.slug}`}
            onMouseEnter={() => setHovered(cat)}
            className={`flex items-center justify-between px-4 py-3 transition-colors ${activeCat?.id === cat.id ? "bg-surface border-l-4 border-primary text-primary font-bold" : "text-text-secondary hover:bg-surface hover:text-primary"}`}
          >
            <div className="flex items-center gap-3">
              <span className="material-symbols-outlined text-[20px]">
                {getCatIcon(cat.slug)}
              </span>
              <div>
                <div className="text-[13px] font-bold">{cat.name}</div>
                <div className="text-[10px] text-text-secondary font-normal opacity-80">
                  {cat.product_count} produk
                </div>
              </div>
            </div>
            <span className="material-symbols-outlined text-[16px] opacity-60">
              chevron_right
            </span>
          </Link>
        ))}
      </div>

      {/* Right: sub-categories */}
      <div className="flex-1 p-6 flex flex-col justify-between overflow-y-auto">
        {activeCat && (
          <div>
            <div className="mb-4 border-b border-border-subtle pb-3 flex justify-between items-start">
              <div>
                <h3 className="font-title-card text-[16px] font-bold text-text-primary">
                  {activeCat.name}
                </h3>
                <p className="text-[12px] text-text-secondary">
                  Eksplorasi katalog {activeCat.name} untuk menemukan solusi
                  terbaik.
                </p>
              </div>
              <Link
                to={`/katalog?kategori=${activeCat.slug}`}
                className="text-[12px] text-primary font-semibold hover:underline flex items-center gap-0.5"
              >
                Lihat Semua{" "}
                <span className="material-symbols-outlined text-[14px]">
                  arrow_forward
                </span>
              </Link>
            </div>
            <div className="grid grid-cols-3 gap-4">
              {children.length > 0 ? (
                children.map((child, i) => (
                  <Link
                    key={child.id}
                    to={`/katalog?kategori=${child.slug}`}
                    className="bg-card-bg border border-border-subtle rounded-lg p-3 hover:border-primary transition-all group flex flex-col justify-between"
                  >
                    <div
                      className="w-full h-[80px] rounded bg-surface mb-2 bg-cover bg-center"
                      style={{
                        backgroundImage: `url('https://picsum.photos/seed/${child.id}/300/100')`,
                      }}
                    />
                    <div>
                      <h4 className="text-[13px] font-bold text-text-primary group-hover:text-primary">
                        {child.name}
                      </h4>
                      <span className="text-[11px] text-text-secondary">
                        {child.product_count} produk
                      </span>
                    </div>
                  </Link>
                ))
              ) : (
                <div className="col-span-3 py-8 text-center text-text-secondary">
                  <p className="text-[13px]">
                    Silakan lihat semua produk di kategori ini.
                  </p>
                </div>
              )}
            </div>
          </div>
        )}

        <div className="pt-4 mt-4 border-t border-border-subtle flex items-center justify-between text-[12px] text-text-secondary">
          <span className="font-bold text-text-primary">
            Brand Partner Utama:
          </span>
          <div className="flex items-center gap-6 font-semibold text-primary">
            <Link to="/katalog?brand=logitech" className="hover:underline">
              Logitech
            </Link>
            <Link to="/katalog?brand=yealink" className="hover:underline">
              Yealink
            </Link>
            <Link to="/katalog?brand=poly" className="hover:underline">
              Poly
            </Link>
            <Link to="/katalog?brand=jabra" className="hover:underline">
              Jabra
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

// ─── Header ──────────────────────────────────────────────────────────────────
function Header({ categories }) {
  const { itemCount } = useCart();
  const { user } = useAuth();
  const [showMega, setShowMega] = useState(false);
  const [searchQ, setSearchQ] = useState("");
  const [searchCat, setSearchCat] = useState("");
  const navigate = useNavigate();
  const megaRef = useRef(null);
  const megaTimer = useRef(null);

  function handleSearchSubmit(e) {
    e.preventDefault();
    if (searchQ.trim()) {
      let url = `/katalog?q=${encodeURIComponent(searchQ.trim())}`;
      if (searchCat) url += `&kategori=${searchCat}`;
      navigate(url);
    }
  }

  function openMega() {
    clearTimeout(megaTimer.current);
    setShowMega(true);
  }
  function closeMega() {
    megaTimer.current = setTimeout(() => setShowMega(false), 150);
  }

  const topCats = categories.filter(
    (c) => !c.parent_id && !c.is_addon && !c.is_brand_group,
  );

  return (
    <header className="fixed top-0 left-0 right-0 z-40 bg-card-bg shadow-[0_1px_4px_rgba(11,28,48,0.06)]">
      {/* Top bar (desktop) */}
      <div className="hidden lg:block h-[36px] bg-surface border-b border-border-subtle">
        <div className="max-w-[1440px] mx-auto px-margin h-full flex items-center justify-between font-label-sm text-label-sm text-text-secondary">
          <div className="flex items-center gap-space-lg">
            <span>Belanja Produk IT & Audio Visual</span>
            <span className="text-outline-variant">|</span>
            <span className="flex items-center gap-space-xs text-primary font-medium">
              <span className="material-symbols-outlined text-[16px]">
                verified
              </span>
              Produk Original & Garansi Resmi
            </span>
          </div>
          <div className="flex items-center gap-space-xl">
            <Link
              to="/lacak-pesanan"
              className="hover:text-primary transition-colors flex items-center gap-space-xs"
            >
              <span className="material-symbols-outlined text-[15px]">
                local_shipping
              </span>
              Lacak Pesanan
            </Link>
            <Link
              to="/bantuan"
              className="hover:text-primary transition-colors flex items-center gap-space-xs"
            >
              <span className="material-symbols-outlined text-[15px]">
                help
              </span>
              Bantuan
            </Link>
            <span className="flex items-center gap-space-xs text-text-primary font-semibold">
              <span className="material-symbols-outlined text-[15px] text-primary">
                call
              </span>
              Hubungi CS: (021) 5088-6800
            </span>
          </div>
        </div>
      </div>

      {/* Main header */}
      <div className="h-[64px] lg:h-[80px] bg-card-bg border-b border-border-subtle">
        <div className="max-w-[1440px] mx-auto px-gutter lg:px-margin h-full flex items-center justify-between gap-space-lg">
          {/* Logo */}
          <div className="flex items-center flex-shrink-0">
            <Link to="/" className="flex items-center gap-3 flex-shrink-0 py-1">
              <img
                src="/accommerce-blue.png"
                alt="Accommerce"
                className="h-10 sm:h-11 lg:h-11 w-auto max-w-[200px] sm:max-w-[240px] lg:max-w-[260px] object-contain object-left"
              />
              <span className="hidden xl:inline-flex items-center bg-surface text-primary border border-primary-fixed text-[11px] font-semibold px-2 py-0.5 rounded tracking-tight">
                Enterprise IT & AV
              </span>
            </Link>
          </div>

          {/* Desktop search */}
          <form
            onSubmit={handleSearchSubmit}
            className="hidden lg:flex flex-1 max-w-[580px] mx-space-md"
          >
            <div className="w-full flex items-center bg-page-background rounded-lg border border-border-subtle focus-within:border-primary">
              <div className="relative flex items-center border-r border-border-subtle">
                <select
                  className="appearance-none bg-transparent pl-3 pr-7 py-2.5 font-label-sm text-label-sm text-text-primary font-medium focus:outline-none cursor-pointer"
                  value={searchCat}
                  onChange={(e) => setSearchCat(e.target.value)}
                >
                  <option value="">Semua Kategori</option>
                  {topCats.slice(0, 5).map((c) => (
                    <option key={c.id} value={c.slug}>
                      {c.name}
                    </option>
                  ))}
                </select>
                <span className="material-symbols-outlined pointer-events-none absolute right-2 text-outline text-[16px]">
                  expand_more
                </span>
              </div>
              <input
                className="w-full px-3 py-2 bg-transparent text-text-primary text-[14px] placeholder:text-outline focus:outline-none"
                placeholder="Cari produk, brand, SKU, atau part number..."
                type="search"
                value={searchQ}
                onChange={(e) => setSearchQ(e.target.value)}
              />
              <button
                aria-label="Cari"
                type="submit"
                className="bg-primary-container hover:bg-primary text-on-primary px-4 py-2.5 rounded-r-lg flex items-center justify-center transition-colors"
              >
                <span className="material-symbols-outlined text-[20px]">
                  search
                </span>
              </button>
            </div>
          </form>

          {/* Right actions */}
          <div className="flex items-center gap-1 xl:gap-space-lg lg:gap-space-md">
            {/* Mobile: search icon */}
            <Link
              to="/cari"
              className="lg:hidden w-11 h-11 flex items-center justify-center rounded-lg hover:bg-surface transition-colors text-text-primary"
            >
              <span className="material-symbols-outlined text-[26px]">
                search
              </span>
            </Link>

            {/* RFQ / Penawaran */}
            <Link
              to="/minta-penawaran"
              className="hidden lg:flex relative items-center gap-space-xs text-text-primary hover:text-primary px-2 py-1.5 rounded transition-colors"
            >
              <span className="material-symbols-outlined text-[26px]">
                request_quote
              </span>
              <div className="hidden md:flex flex-col text-left">
                <span className="font-label-sm text-[11px] text-text-secondary leading-tight">
                  Daftar Produk
                </span>
                <span className="font-label-sm text-label-sm font-semibold text-text-primary leading-tight">
                  Penawaran
                </span>
              </div>
              <span className="absolute -top-1 -right-1 bg-tertiary-container text-on-tertiary font-label-sm text-[11px] w-5 h-5 rounded-full flex items-center justify-center font-bold">
                0
              </span>
            </Link>

            {/* Wishlist */}
            <Link
              to={user ? "/akun" : "/login"}
              className="hidden lg:flex relative items-center gap-space-xs text-text-primary hover:text-primary px-2 py-1.5 rounded transition-colors"
              aria-label="Wishlist Produk"
            >
              <span
                className="material-symbols-outlined text-[26px]"
                style={{ fontVariationSettings: "'FILL' 0" }}
              >
                favorite
              </span>
              <div className="hidden md:flex flex-col text-left">
                <span className="font-label-sm text-[11px] text-text-secondary leading-tight">
                  Produk Saya
                </span>
                <span className="font-label-sm text-label-sm font-semibold text-text-primary leading-tight">
                  Wishlist
                </span>
              </div>
              {user && (user.wishlist || []).length > 0 && (
                <span className="absolute -top-1 -right-1 bg-red-500 text-white font-label-sm text-[11px] min-w-[20px] h-5 rounded-full flex items-center justify-center font-bold px-1">
                  {(user.wishlist || []).length}
                </span>
              )}
            </Link>

            {/* Cart */}
            <Link
              to="/keranjang"
              className="relative flex items-center gap-space-xs text-text-primary hover:text-primary px-2 py-1.5 rounded transition-colors"
              aria-label={`Keranjang (${itemCount} item)`}
            >
              <span className="material-symbols-outlined text-[26px]">
                shopping_bag
              </span>
              <div className="hidden md:flex flex-col text-left">
                <span className="font-label-sm text-[11px] text-text-secondary leading-tight">
                  Troli Belanja
                </span>
                <span className="font-label-sm text-label-sm font-semibold text-text-primary leading-tight">
                  Keranjang
                </span>
              </div>
              {itemCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-primary text-on-primary font-label-sm text-[11px] min-w-[20px] h-5 rounded-full flex items-center justify-center font-bold px-1">
                  {itemCount > 99 ? "99+" : itemCount}
                </span>
              )}
            </Link>

            <div className="hidden lg:block w-[1px] h-8 bg-border-subtle" />

            {/* User Account */}
            <div className="hidden lg:flex items-center gap-space-sm pl-1">
              {user ? (
                <Link
                  to="/akun"
                  className="flex items-center gap-space-sm group"
                >
                  <div className="flex flex-col text-right cursor-pointer group-hover:opacity-80 transition-opacity">
                    <span className="font-label-sm text-[11px] text-text-secondary leading-tight truncate max-w-[120px]">
                      Halo, {user.name.split(" ")[0]}
                    </span>
                    <span className="font-label-sm text-label-sm font-semibold text-primary leading-tight">
                      Akun Saya
                    </span>
                  </div>
                  <div className="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold text-[14px] cursor-pointer group-hover:opacity-80 transition-opacity">
                    {user.name.charAt(0).toUpperCase()}
                  </div>
                </Link>
              ) : (
                <Link
                  to="/login"
                  className="flex items-center gap-space-sm group"
                >
                  <div className="flex flex-col text-right cursor-pointer group-hover:opacity-80 transition-opacity">
                    <span className="font-label-sm text-[11px] text-text-secondary leading-tight">
                      Tamu
                    </span>
                    <span className="font-label-sm text-label-sm font-semibold text-primary leading-tight">
                      Masuk / Daftar
                    </span>
                  </div>
                  <div className="w-8 h-8 rounded-full bg-surface text-primary flex hover:text-white items-center justify-center cursor-pointer group-hover:bg-primary-container transition-colors">
                    <span className="material-symbols-outlined hover:text-white text-[18px]">
                      person
                    </span>
                  </div>
                </Link>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Desktop navigation bar with mega menu */}
      <div className="hidden lg:block h-[48px] bg-card-bg border-b border-border-subtle">
        <div className="max-w-[1440px] mx-auto px-margin h-full flex items-center gap-space-xl">
          {/* Mega menu trigger */}
          <div
            ref={megaRef}
            className="relative h-full flex items-center"
            onMouseEnter={openMega}
            onMouseLeave={closeMega}
          >
            <button
              className="h-full bg-primary hover:bg-primary-container text-on-primary px-4 flex items-center gap-space-sm font-label-sm text-label-sm transition-colors"
              aria-expanded={showMega}
              aria-haspopup="true"
            >
              <span className="material-symbols-outlined text-[20px]">
                grid_view
              </span>
              <span>Semua Kategori</span>
              <span className="material-symbols-outlined text-[16px]">
                keyboard_arrow_down
              </span>
            </button>
            {showMega && categories.length > 0 && (
              <div onMouseEnter={openMega} onMouseLeave={closeMega}>
                <MegaMenu categories={categories} />
              </div>
            )}
          </div>

          {/* Nav links */}
          <NavLink to="/">Beranda</NavLink>
          <NavLink to="/katalog">Katalog Produk</NavLink>
          <NavLink to="/kategori">Semua Kategori</NavLink>
          <NavLink to="/katalog?sort=sale">Promo & Diskon</NavLink>
        </div>
      </div>
    </header>
  );
}

function NavLink({ to, children }) {
  const location = useLocation();
  const isActive =
    location.pathname === to ||
    (to !== "/" && location.pathname.startsWith(to.split("?")[0]));
  return (
    <Link
      to={to}
      className={`h-full flex items-center font-label-sm text-label-sm transition-colors border-b-2 ${isActive ? "text-primary font-bold border-primary" : "text-on-surface-variant hover:text-on-surface border-transparent"}`}
    >
      {children}
    </Link>
  );
}

// ─── Bottom Nav ──────────────────────────────────────────────────────────────
function BottomNav() {
  const location = useLocation();
  const path = location.pathname;
  const { itemCount } = useCart();
  const { user } = useAuth();
  const [showAkunDrawer, setShowAkunDrawer] = useState(false);

  useEffect(() => {
    setShowAkunDrawer(false);
  }, [location.pathname]);

  const navItems = [
    { path: "/", icon: "home", label: "Beranda", exact: true },
    { path: "/kategori", icon: "category", label: "Kategori" },
    { path: "/cari", icon: "search", label: "Cari" },
    {
      path: "/keranjang",
      icon: "shopping_cart",
      label: "Keranjang",
      badge: itemCount,
    },
  ];

  const akunMenuItems = [
    {
      icon: "person",
      label: user ? "Profil Saya" : "Masuk / Daftar",
      path: user ? "/akun" : "/login",
      color: "text-primary",
    },
    {
      icon: "favorite",
      label: "Wishlist",
      path: "/wishlist",
      color: "text-red-500",
    },
    {
      icon: "local_shipping",
      label: "Lacak Pesanan",
      path: "/lacak-pesanan",
      color: "text-green-600",
    },
    {
      icon: "help",
      label: "Bantuan",
      path: "/bantuan",
      color: "text-amber-500",
    },
    {
      icon: "chat",
      label: "Chat CS (WA)",
      href: "https://wa.me/6287780116800",
      color: "text-[#128C7E]",
    },
  ];

  return (
    <>
      {/* Backdrop */}
      <div
        className={`fixed inset-0 bg-black/40 z-[60] md:hidden transition-opacity duration-300 ${
          showAkunDrawer
            ? "opacity-100 pointer-events-auto"
            : "opacity-0 pointer-events-none"
        }`}
        onClick={() => setShowAkunDrawer(false)}
      />

      {/* Slide-up Akun Drawer Sheet */}
      <div
        className={`fixed bottom-0 left-0 right-0 bg-card-bg rounded-t-2xl z-[61] md:hidden shadow-[0_-4px_24px_rgba(11,28,48,0.18)] transition-transform duration-300 ease-out will-change-transform ${
          showAkunDrawer
            ? "translate-y-0"
            : "translate-y-full pointer-events-none"
        }`}
        style={{
          paddingBottom: "calc(16px + env(safe-area-inset-bottom, 0px))",
        }}
      >
        {/* Handle bar & Close button */}
        <div className="relative pt-3 pb-1 px-4 flex items-center justify-between">
          <div className="w-8" />
          <button
            onClick={() => setShowAkunDrawer(false)}
            aria-label="Tutup Menu"
            className="w-10 h-1.5 rounded-full bg-border-subtle hover:bg-outline transition-colors cursor-pointer"
          />
          <button
            onClick={() => setShowAkunDrawer(false)}
            className="w-8 h-8 rounded-full flex items-center justify-center text-text-secondary hover:text-text-primary active:bg-surface transition-colors"
            aria-label="Tutup Menu"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* User greeting */}
        <div className="flex items-center gap-3 px-5 py-3 border-b border-border-subtle">
          <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
            {user ? (
              <span className="font-bold text-primary text-[16px]">
                {user.name.charAt(0).toUpperCase()}
              </span>
            ) : (
              <span className="material-symbols-outlined text-[22px] text-primary">
                person
              </span>
            )}
          </div>
          <div className="flex-1 min-w-0">
            <div className="font-bold text-[14px] text-text-primary truncate">
              {user ? `Halo, ${user.name.split(" ")[0]}` : "Selamat datang!"}
            </div>
            <div className="text-[11px] text-text-secondary truncate">
              {user ? user.email : "Masuk untuk menikmati lebih banyak fitur"}
            </div>
          </div>
        </div>

        {/* Menu items */}
        <div className="grid grid-cols-5 gap-1 p-4">
          {akunMenuItems.map((item) => {
            const Wrapper = item.href ? "a" : Link;
            const props = item.href
              ? {
                  href: item.href,
                  target: "_blank",
                  rel: "noopener noreferrer",
                }
              : { to: item.path };
            return (
              <Wrapper
                key={item.label}
                {...props}
                onClick={() => setShowAkunDrawer(false)}
                className="flex flex-col items-center gap-1.5 py-3 px-1 rounded-xl hover:bg-surface active:bg-surface transition-colors"
              >
                <div
                  className={`w-12 h-12 rounded-full bg-surface flex items-center justify-center ${item.color}`}
                >
                  <span className="material-symbols-outlined text-[24px]">
                    {item.icon}
                  </span>
                </div>
                <span className="text-[10px] font-semibold text-text-primary text-center leading-tight">
                  {item.label}
                </span>
              </Wrapper>
            );
          })}
        </div>
      </div>

      {/* Bottom Nav Bar */}
      <nav
        className={`fixed bottom-0 left-0 right-0 bg-card-bg border-t border-border-subtle z-50 md:hidden transition-transform duration-300 ease-out will-change-transform ${
          showAkunDrawer
            ? "translate-y-full pointer-events-none"
            : "translate-y-0"
        }`}
        style={{ paddingBottom: "env(safe-area-inset-bottom)" }}
        aria-label="Navigasi utama"
      >
        <div className="flex items-center justify-around h-[60px]">
          {navItems.map((item) => {
            const isActive = item.exact
              ? path === item.path
              : path.startsWith(item.path);
            return (
              <Link
                key={item.path}
                to={item.path}
                onClick={() => setShowAkunDrawer(false)}
                aria-label={item.label}
                aria-current={isActive ? "page" : undefined}
                className={`relative flex flex-col items-center justify-center flex-1 h-full gap-0.5 transition-colors min-w-[44px] ${isActive ? "text-primary" : "text-text-secondary"}`}
              >
                <span
                  className="material-symbols-outlined text-[24px]"
                  style={{
                    fontVariationSettings: isActive ? "'FILL' 1" : "'FILL' 0",
                  }}
                >
                  {item.icon}
                </span>
                <span className="text-[10px] font-semibold">{item.label}</span>
                {item.badge > 0 && (
                  <span className="absolute top-1.5 right-[calc(50%-20px)] bg-primary text-on-primary text-[9px] font-bold min-w-[16px] h-4 rounded-full flex items-center justify-center px-0.5">
                    {item.badge > 99 ? "99+" : item.badge}
                  </span>
                )}
              </Link>
            );
          })}

          {/* Akun tab (5th) */}
          <button
            onClick={() => setShowAkunDrawer((v) => !v)}
            aria-label="Akun Saya"
            className={`relative flex flex-col items-center justify-center flex-1 h-full gap-0.5 transition-colors min-w-[44px] ${showAkunDrawer ? "text-primary" : "text-text-secondary"}`}
          >
            <span
              className="material-symbols-outlined text-[24px]"
              style={{
                fontVariationSettings: showAkunDrawer ? "'FILL' 1" : "'FILL' 0",
              }}
            >
              person
            </span>
            <span className="text-[10px] font-semibold">Akun</span>
            {user && (
              <span className="absolute top-1.5 right-[calc(50%-20px)] bg-primary text-on-primary text-[9px] font-bold min-w-[16px] h-4 rounded-full flex items-center justify-center px-0.5">
                ✓
              </span>
            )}
          </button>
        </div>
      </nav>
    </>
  );
}

// ─── Layout ──────────────────────────────────────────────────────────────────
export default function Layout() {
  const [categories, setCategories] = useState([]);

  useEffect(() => {
    getCategories().then((cats) =>
      setCategories(cats.filter((c) => !c.is_addon)),
    );
  }, []);

  // Close mega menu & scroll to top on route change
  const location = useLocation();

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: "instant" });
  }, [location.pathname]);

  const isProductDetail = location.pathname.startsWith("/produk/");

  return (
    <div className="min-h-[100dvh] flex flex-col bg-page-background">
      <Header categories={categories} />
      <main className="flex-1 w-full pt-[64px] lg:pt-[164px] pb-[60px] md:pb-0 relative overflow-x-hidden">
        <div key={location.pathname} className="animate-fade">
          <Outlet />
        </div>
      </main>
      <Footer />
      <BottomNav />

      {/* Floating WhatsApp Bubble Chat (Sesuai Static Template) */}
      <aside
        className={`fixed right-4 lg:right-8 z-40 transition-all duration-300 ${isProductDetail ? "bottom-[126px] lg:bottom-8" : "bottom-[72px] lg:bottom-8"}`}
      >
        {/* Desktop version: Pill badge dengan teks CS (24 Jam) */}
        <a
          href="https://wa.me/6287780116800"
          target="_blank"
          rel="noopener noreferrer"
          className="hidden lg:flex items-center gap-space-sm bg-[#128C7E] hover:bg-[#075E54] text-white px-4 py-2.5 rounded-full shadow-[0_8px_20px_rgba(11,28,48,0.18)] transition-all transform hover:-translate-y-0.5 group"
          aria-label="Chat WhatsApp CS"
        >
          <span className="material-symbols-outlined text-[24px]">chat</span>
          <div className="flex flex-col text-left">
            <span className="font-label-sm text-[10px] leading-none opacity-90">
              Chat CS (24 Jam)
            </span>
            <span className="font-label-sm text-label-sm font-bold leading-tight mt-0.5">
              +62 877-8011-6800
            </span>
          </div>
        </a>

        {/* Mobile version: Round FAB button */}
        <a
          href="https://wa.me/6287780116800"
          target="_blank"
          rel="noopener noreferrer"
          className="lg:hidden w-[52px] h-[52px] rounded-full bg-[#128C7E] active:bg-[#075E54] text-white flex items-center justify-center shadow-lg transition-transform active:scale-95"
          aria-label="WhatsApp Support"
        >
          <span className="material-symbols-outlined text-[26px]">chat</span>
        </a>
      </aside>
    </div>
  );
}
