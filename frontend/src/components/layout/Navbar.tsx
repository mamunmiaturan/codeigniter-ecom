"use client";

import { useState, useEffect, useRef } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { AnimatePresence, motion } from "framer-motion";
import {
  Search,
  User,
  ShoppingBag,
  Menu,
  X,
  ChevronRight,
  LogOut,
  Package,
  Heart,
  MapPin,
} from "lucide-react";
import { useCartStore } from "@/store/cartStore";
import { useAuthStore } from "@/store/authStore";
import { useWishlistStore } from "@/store/wishlistStore";
import { SearchModal } from "@/components/layout/SearchModal";

const navLinks = [
  { label: "SHOP", href: "/shop" },
  { label: "COMMUNITY", href: "/community" },
  { label: "BABUSONA BRANDS", href: "/brands" },
  { label: "BESPOKE LAB", href: "/bespoke" },
];

export function Navbar() {
  const [scrolled, setScrolled] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [accountOpen, setAccountOpen] = useState(false);
  const pathname = usePathname();
  const router = useRouter();
  const { itemCount, openCart, refresh } = useCartStore();
  const { token, customer, logout } = useAuthStore();
  const loadWishlist = useWishlistStore((s) => s.load);
  const [mounted, setMounted] = useState(false);
  const accountRef = useRef<HTMLDivElement>(null);

  // Only trust the persisted session after mount (avoids hydration mismatch).
  useEffect(() => setMounted(true), []);

  // Load the server cart + wishlist once on first mount.
  useEffect(() => {
    refresh();
    loadWishlist();
  }, [refresh, loadWishlist]);
  const isAuth = mounted && Boolean(token);

  const handleLogout = () => {
    logout();
    setAccountOpen(false);
    setMobileOpen(false);
    router.push("/account/login");
  };

  const accountLinks = isAuth
    ? [
        {
          icon: <User size={15} strokeWidth={1.5} />,
          label: "My Account",
          labelBn: "আমার প্রোফাইল",
          href: "/account",
        },
        {
          icon: <Package size={15} strokeWidth={1.5} />,
          label: "My Orders",
          labelBn: "আমার অর্ডার",
          href: "/account/orders",
        },
        {
          icon: <Heart size={15} strokeWidth={1.5} />,
          label: "Wishlist",
          labelBn: "উইশলিস্ট",
          href: "/account/wishlist",
        },
        {
          icon: <MapPin size={15} strokeWidth={1.5} />,
          label: "Addresses",
          labelBn: "ঠিকানা",
          href: "/account/addresses",
        },
      ]
    : [
        {
          icon: <User size={15} strokeWidth={1.5} />,
          label: "Login",
          labelBn: "লগইন",
          href: "/account/login",
        },
        {
          icon: <Heart size={15} strokeWidth={1.5} />,
          label: "Register",
          labelBn: "রেজিস্ট্রেশন",
          href: "/account/register",
        },
      ];

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    setMobileOpen(false);
    setAccountOpen(false);
  }, [pathname]);

  // Close account dropdown when clicking outside
  useEffect(() => {
    const handleClick = (e: MouseEvent) => {
      if (
        accountRef.current &&
        !accountRef.current.contains(e.target as Node)
      ) {
        setAccountOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, []);

  return (
    <>
      <header
        style={{
          position: "sticky",
          top: 0,
          zIndex: 100,
          background: scrolled ? "rgba(250,246,240,0.97)" : "transparent",
          backdropFilter: scrolled ? "blur(20px)" : "none",
          borderBottom: scrolled
            ? "1px solid #e8ddd5"
            : "1px solid transparent",
          transition: "all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)",
        }}
      >
        <div
          style={{
            maxWidth: "1300px",
            margin: "0 auto",
            padding: "0 clamp(1.5rem, 4vw, 5rem)",
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            height: "72px",
          }}
        >
          {/* Logo */}
          <Link href="/" style={{ textDecoration: "none", flexShrink: 0 }}>
            <div>
              <div
                style={{ display: "flex", alignItems: "center", gap: "2px" }}
              >
                <span
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontSize: "1.7rem",
                    fontWeight: "700",
                    color: "#0A0A3E",
                    letterSpacing: "-0.5px",
                    lineHeight: 1,
                  }}
                >
                  Babu
                </span>
                <span
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontSize: "1.7rem",
                    fontWeight: "700",
                    color: "#0A0A3E",
                    fontStyle: "italic",
                    letterSpacing: "-0.5px",
                    lineHeight: 1,
                  }}
                >
                  Sona
                </span>
                <span
                  style={{
                    color: "#E8470A",
                    fontSize: "0.85rem",
                    marginLeft: "3px",
                    lineHeight: 1,
                  }}
                >
                  ♡
                </span>
              </div>
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "9px",
                  color: "#E8470A",
                  letterSpacing: "2.5px",
                  textTransform: "uppercase",
                  marginTop: "3px",
                  lineHeight: 1,
                  fontWeight: "600",
                }}
              >
                LOVE. CARE. EVERYDAY.
              </p>
            </div>
          </Link>

          {/* Desktop Nav */}
          <nav
            style={{
              display: "flex",
              gap: "0px",
              alignItems: "center",
            }}
            className="hide-mobile"
          >
            {navLinks.map((link) => {
              const isActive = pathname === link.href;
              return (
                <Link
                  key={link.label}
                  href={link.href}
                  style={{
                    textDecoration: "none",
                    fontSize: "11px",
                    fontWeight: "600",
                    letterSpacing: "1px",
                    color: isActive ? "#E8470A" : "#0A0A3E",
                    padding: "8px 14px",
                    borderRadius: "6px",
                    transition: "all 0.2s ease",
                    fontFamily: "var(--font-dm-sans)",
                  }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.color = "#E8470A";
                    e.currentTarget.style.background = "rgba(232,71,10,0.06)";
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.color = isActive
                      ? "#E8470A"
                      : "#0A0A3E";
                    e.currentTarget.style.background = "transparent";
                  }}
                >
                  {link.label}
                </Link>
              );
            })}
          </nav>

          {/* Right Icons */}
          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: "4px",
            }}
          >
            {/* Search */}
            <button
              onClick={() => setSearchOpen(true)}
              style={iconBtn}
              onMouseEnter={(e) =>
                (e.currentTarget.style.background = "rgba(232,71,10,0.06)")
              }
              onMouseLeave={(e) =>
                (e.currentTarget.style.background = "transparent")
              }
              title="Search"
            >
              <Search size={18} color="#0A0A3E" strokeWidth={1.5} />
            </button>

            {/* Account */}
            <div ref={accountRef} style={{ position: "relative" }}>
              <button
                onClick={() => setAccountOpen(!accountOpen)}
                style={{
                  ...iconBtn,
                  background: accountOpen
                    ? "rgba(232,71,10,0.06)"
                    : "transparent",
                }}
                onMouseEnter={(e) =>
                  (e.currentTarget.style.background = "rgba(232,71,10,0.06)")
                }
                onMouseLeave={(e) => {
                  if (!accountOpen)
                    e.currentTarget.style.background = "transparent";
                }}
                title="Account"
              >
                <User size={18} color="#0A0A3E" strokeWidth={1.5} />
              </button>

              {/* Account Dropdown */}
              <AnimatePresence>
                {accountOpen && (
                  <motion.div
                    initial={{ opacity: 0, y: 8, scale: 0.95 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    exit={{ opacity: 0, y: 8, scale: 0.95 }}
                    transition={{ duration: 0.15 }}
                    style={{
                      position: "absolute",
                      top: "calc(100% + 8px)",
                      right: 0,
                      background: "white",
                      borderRadius: "16px",
                      border: "1px solid #e8ddd5",
                      boxShadow: "0 20px 60px rgba(10,10,62,0.12)",
                      minWidth: "220px",
                      overflow: "hidden",
                      zIndex: 50,
                    }}
                  >
                    {/* Header */}
                    <div
                      style={{
                        padding: "16px 20px",
                        borderBottom: "1px solid #e8ddd5",
                        background: "#faf6f0",
                      }}
                    >
                      <p
                        style={{
                          fontFamily: "var(--font-playfair)",
                          fontSize: "1rem",
                          fontWeight: "700",
                          color: "#0A0A3E",
                          marginBottom: "2px",
                        }}
                      >
                        {isAuth && customer ? customer.name : "My Account"}
                      </p>
                      <p
                        style={{
                          fontFamily: "var(--font-hind)",
                          fontSize: "11px",
                          color: "#c9a84c",
                          overflow: "hidden",
                          textOverflow: "ellipsis",
                          whiteSpace: "nowrap",
                        }}
                      >
                        {isAuth && customer
                          ? customer.email
                          : "আপনার অ্যাকাউন্ট"}
                      </p>
                    </div>

                    {/* Menu Items */}
                    <div style={{ padding: "8px" }}>
                      {accountLinks.map((item, i) => (
                        <Link
                          key={i}
                          href={item.href}
                          onClick={() => setAccountOpen(false)}
                          style={{ textDecoration: "none" }}
                        >
                          <div
                            style={{
                              display: "flex",
                              alignItems: "center",
                              gap: "12px",
                              padding: "10px 12px",
                              borderRadius: "10px",
                              cursor: "pointer",
                              transition: "all 0.2s ease",
                            }}
                            onMouseEnter={(e) => {
                              e.currentTarget.style.background =
                                "rgba(232,71,10,0.06)";
                            }}
                            onMouseLeave={(e) => {
                              e.currentTarget.style.background = "transparent";
                            }}
                          >
                            <span style={{ color: "#E8470A" }}>
                              {item.icon}
                            </span>
                            <div>
                              <p
                                style={{
                                  fontFamily: "var(--font-dm-sans)",
                                  fontSize: "13px",
                                  fontWeight: "600",
                                  color: "#0A0A3E",
                                }}
                              >
                                {item.label}
                              </p>
                              <p
                                style={{
                                  fontFamily: "var(--font-hind)",
                                  fontSize: "10px",
                                  color: "#c9a84c",
                                }}
                              >
                                {item.labelBn}
                              </p>
                            </div>
                            <ChevronRight
                              size={14}
                              color="#c9a84c"
                              style={{ marginLeft: "auto" }}
                            />
                          </div>
                        </Link>
                      ))}

                      {isAuth && (
                        <button
                          onClick={handleLogout}
                          style={{
                            width: "100%",
                            display: "flex",
                            alignItems: "center",
                            gap: "12px",
                            padding: "10px 12px",
                            borderRadius: "10px",
                            cursor: "pointer",
                            background: "transparent",
                            border: "none",
                            marginTop: "4px",
                            borderTop: "1px solid #f0e8e0",
                          }}
                          onMouseEnter={(e) => {
                            e.currentTarget.style.background =
                              "rgba(232,71,10,0.06)";
                          }}
                          onMouseLeave={(e) => {
                            e.currentTarget.style.background = "transparent";
                          }}
                        >
                          <span style={{ color: "#E8470A" }}>
                            <LogOut size={15} strokeWidth={1.5} />
                          </span>
                          <div style={{ textAlign: "left" }}>
                            <p
                              style={{
                                fontFamily: "var(--font-dm-sans)",
                                fontSize: "13px",
                                fontWeight: "600",
                                color: "#0A0A3E",
                              }}
                            >
                              Sign Out
                            </p>
                            <p
                              style={{
                                fontFamily: "var(--font-hind)",
                                fontSize: "10px",
                                color: "#c9a84c",
                              }}
                            >
                              সাইন আউট
                            </p>
                          </div>
                        </button>
                      )}
                    </div>

                    {/* Footer */}
                    <div
                      style={{
                        padding: "12px 16px",
                        borderTop: "1px solid #e8ddd5",
                        textAlign: "center",
                      }}
                    >
                      <p
                        style={{
                          fontFamily: "var(--font-hind)",
                          fontSize: "11px",
                          color: "#7a6e65",
                        }}
                      >
                        ভালোবাসা, যতু, প্রতিদিন ♡
                      </p>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>
            </div>

            {/* Cart */}
            <button
              onClick={openCart}
              style={{ ...iconBtn, position: "relative" }}
              onMouseEnter={(e) =>
                (e.currentTarget.style.background = "rgba(232,71,10,0.06)")
              }
              onMouseLeave={(e) =>
                (e.currentTarget.style.background = "transparent")
              }
              title="Cart"
            >
              <ShoppingBag size={18} color="#0A0A3E" strokeWidth={1.5} />
              {mounted && itemCount > 0 && (
                <motion.span
                  initial={{ scale: 0 }}
                  animate={{ scale: 1 }}
                  style={{
                    position: "absolute",
                    top: "4px",
                    right: "4px",
                    background: "#E8470A",
                    color: "white",
                    fontSize: "9px",
                    fontWeight: "700",
                    width: "16px",
                    height: "16px",
                    borderRadius: "50%",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    fontFamily: "var(--font-dm-sans)",
                  }}
                >
                  {itemCount}
                </motion.span>
              )}
            </button>

            {/* Mobile Menu */}
            <button
              onClick={() => setMobileOpen(true)}
              style={{ ...iconBtn, display: "none" }}
              className="show-mobile"
            >
              <Menu size={20} color="#0A0A3E" strokeWidth={1.5} />
            </button>
          </div>
        </div>
      </header>

      {/* Mobile Menu */}
      <AnimatePresence>
        {mobileOpen && (
          <>
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMobileOpen(false)}
              style={{
                position: "fixed",
                inset: 0,
                background: "rgba(10,10,62,0.4)",
                zIndex: 200,
              }}
            />
            <motion.div
              initial={{ x: "100%" }}
              animate={{ x: 0 }}
              exit={{ x: "100%" }}
              transition={{ type: "spring", damping: 30, stiffness: 300 }}
              style={{
                position: "fixed",
                top: 0,
                right: 0,
                bottom: 0,
                width: "75vw",
                maxWidth: "320px",
                background: "#faf6f0",
                zIndex: 201,
                display: "flex",
                flexDirection: "column",
                boxShadow: "-4px 0 40px rgba(10,10,62,0.15)",
              }}
            >
              <div
                style={{
                  padding: "24px",
                  borderBottom: "1px solid #e8ddd5",
                  display: "flex",
                  justifyContent: "space-between",
                  alignItems: "center",
                }}
              >
                <div>
                  <div style={{ display: "flex", alignItems: "center" }}>
                    <span
                      style={{
                        fontFamily: "var(--font-playfair)",
                        fontSize: "1.4rem",
                        fontWeight: "700",
                        color: "#0A0A3E",
                      }}
                    >
                      Babu
                    </span>
                    <span
                      style={{
                        fontFamily: "var(--font-playfair)",
                        fontSize: "1.4rem",
                        fontWeight: "700",
                        color: "#0A0A3E",
                        fontStyle: "italic",
                      }}
                    >
                      Sona
                    </span>
                    <span
                      style={{
                        color: "#E8470A",
                        fontSize: "0.75rem",
                        marginLeft: "3px",
                      }}
                    >
                      ♡
                    </span>
                  </div>
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "8px",
                      color: "#E8470A",
                      letterSpacing: "2px",
                      textTransform: "uppercase",
                      marginTop: "2px",
                      fontWeight: "600",
                    }}
                  >
                    LOVE. CARE. EVERYDAY.
                  </p>
                </div>
                <button onClick={() => setMobileOpen(false)} style={iconBtn}>
                  <X size={20} color="#0A0A3E" strokeWidth={1.5} />
                </button>
              </div>

              <nav style={{ flex: 1, padding: "16px", overflowY: "auto" }}>
                {navLinks.map((link, i) => (
                  <motion.div
                    key={link.label}
                    initial={{ opacity: 0, x: 20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: i * 0.06 }}
                  >
                    <Link
                      href={link.href}
                      style={{
                        display: "block",
                        padding: "14px 16px",
                        textDecoration: "none",
                        color: pathname === link.href ? "#E8470A" : "#0A0A3E",
                        fontWeight: "600",
                        fontSize: "12px",
                        letterSpacing: "1.5px",
                        borderRadius: "10px",
                        marginBottom: "4px",
                        fontFamily: "var(--font-dm-sans)",
                        background:
                          pathname === link.href
                            ? "rgba(232,71,10,0.06)"
                            : "transparent",
                      }}
                    >
                      {link.label}
                    </Link>
                  </motion.div>
                ))}

                {/* Mobile Account Links */}
                <div
                  style={{
                    marginTop: "16px",
                    paddingTop: "16px",
                    borderTop: "1px solid #e8ddd5",
                  }}
                >
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "10px",
                      fontWeight: "700",
                      letterSpacing: "2px",
                      textTransform: "uppercase",
                      color: "#7a6e65",
                      padding: "0 16px",
                      marginBottom: "8px",
                    }}
                  >
                    Account
                  </p>
                  {accountLinks.map((item, i) => (
                    <Link
                      key={i}
                      href={item.href}
                      style={{
                        display: "block",
                        padding: "12px 16px",
                        textDecoration: "none",
                        color: "#0A0A3E",
                        fontWeight: "600",
                        fontSize: "12px",
                        letterSpacing: "1px",
                        borderRadius: "10px",
                        marginBottom: "4px",
                        fontFamily: "var(--font-dm-sans)",
                      }}
                    >
                      {item.label}
                    </Link>
                  ))}
                  {isAuth && (
                    <button
                      onClick={handleLogout}
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: "8px",
                        width: "100%",
                        padding: "12px 16px",
                        background: "transparent",
                        border: "none",
                        color: "#E8470A",
                        fontWeight: "600",
                        fontSize: "12px",
                        letterSpacing: "1px",
                        borderRadius: "10px",
                        cursor: "pointer",
                        fontFamily: "var(--font-dm-sans)",
                      }}
                    >
                      <LogOut size={15} strokeWidth={1.5} /> Sign Out
                    </button>
                  )}
                </div>
              </nav>

              <div
                style={{
                  padding: "20px 24px",
                  borderTop: "1px solid #e8ddd5",
                  textAlign: "center",
                }}
              >
                <p
                  style={{
                    fontFamily: "var(--font-hind)",
                    fontSize: "12px",
                    color: "#E8470A",
                    letterSpacing: "1px",
                  }}
                >
                  ভালোবাসা, যতু, প্রতিদিন ❤️
                </p>
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>

      {/* Search Modal */}
      <SearchModal isOpen={searchOpen} onClose={() => setSearchOpen(false)} />

      <style>{`
        @media (max-width: 900px) {
          .hide-mobile { display: none !important; }
          .show-mobile { display: flex !important; }
        }
      `}</style>
    </>
  );
}

const iconBtn: React.CSSProperties = {
  background: "transparent",
  border: "none",
  cursor: "pointer",
  padding: "10px",
  borderRadius: "8px",
  display: "flex",
  alignItems: "center",
  justifyContent: "center",
  transition: "background 0.2s ease",
};
