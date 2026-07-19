"use client";

import { useEffect, useState } from "react";
import { motion } from "framer-motion";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Package, Heart, MapPin, Settings, LogOut } from "lucide-react";
import { useAuthStore } from "@/store/authStore";

const menuItems = [
  {
    icon: <Package size={20} strokeWidth={1.5} />,
    label: "My Orders",
    labelBn: "আমার অর্ডার",
    href: "/account/orders",
    count: "",
  },
  {
    icon: <Heart size={20} strokeWidth={1.5} />,
    label: "Wishlist",
    labelBn: "উইশলিস্ট",
    href: "/account/wishlist",
    count: "",
  },
  {
    icon: <MapPin size={20} strokeWidth={1.5} />,
    label: "Addresses",
    labelBn: "ঠিকানা",
    href: "/account/addresses",
    count: "",
  },
  {
    icon: <Settings size={20} strokeWidth={1.5} />,
    label: "Settings",
    labelBn: "সেটিংস",
    href: "/account",
    count: "",
  },
];

export default function AccountPage() {
  const router = useRouter();
  const { token, customer, refreshProfile, logout } = useAuthStore();
  const [mounted, setMounted] = useState(false);

  // Only trust the persisted token after the client has mounted (avoids an
  // SSR/CSR hydration mismatch, since localStorage isn't available on the server).
  useEffect(() => setMounted(true), []);

  // Guard: bounce to login when there's no session.
  useEffect(() => {
    if (!mounted) return;
    if (!token) {
      router.replace("/account/login");
    } else {
      refreshProfile();
    }
  }, [mounted, token, router, refreshProfile]);

  const handleLogout = () => {
    logout();
    router.replace("/account/login");
  };

  if (!mounted || !token) return null;

  return (
    <div
      style={{
        minHeight: "100vh",
        background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
        padding: "60px clamp(1.5rem, 4vw, 5rem)",
      }}
    >
      <div style={{ maxWidth: "800px", margin: "0 auto" }}>
        {/* Header */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          style={{
            background: "linear-gradient(135deg, #0A0A3E, #12124e)",
            borderRadius: "24px",
            padding: "40px",
            marginBottom: "24px",
            display: "flex",
            alignItems: "center",
            gap: "24px",
            boxShadow: "0 20px 60px rgba(10,10,62,0.2)",
          }}
        >
          <div
            style={{
              width: "80px",
              height: "80px",
              borderRadius: "50%",
              background: "linear-gradient(135deg, #E8470A, #c23a08)",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              fontSize: "2rem",
              flexShrink: 0,
              boxShadow: "0 8px 24px rgba(232,71,10,0.3)",
            }}
          >
            👩
          </div>
          <div>
            <h1
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "1.6rem",
                fontWeight: "700",
                color: "white",
                marginBottom: "4px",
                letterSpacing: "-0.5px",
              }}
            >
              {customer ? `Hello, ${customer.name}!` : "Welcome Back!"}
            </h1>
            <p
              style={{
                fontFamily: "var(--font-hind)",
                fontSize: "14px",
                color: "rgba(255,255,255,0.7)",
                marginBottom: "8px",
              }}
            >
              আবার স্বাগতম বাবুসোনা পরিবারে ❤️
            </p>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "12px",
                color: "#E8470A",
                letterSpacing: "0.5px",
              }}
            >
              {customer?.email ?? "…"}
            </p>
          </div>
        </motion.div>

        {/* Menu Items */}
        <div
          style={{
            display: "grid",
            gridTemplateColumns: "1fr 1fr",
            gap: "16px",
            marginBottom: "24px",
          }}
        >
          {menuItems.map((item, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.08 }}
            >
              <Link href={item.href} style={{ textDecoration: "none" }}>
                <motion.div
                  whileHover={{ y: -4 }}
                  style={{
                    background: "white",
                    borderRadius: "16px",
                    padding: "24px",
                    border: "1px solid #e8ddd5",
                    boxShadow: "0 4px 16px rgba(10,10,62,0.04)",
                    cursor: "pointer",
                    transition: "box-shadow 0.3s ease",
                    display: "flex",
                    alignItems: "center",
                    gap: "16px",
                  }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.boxShadow =
                      "0 12px 40px rgba(232,71,10,0.1)";
                    e.currentTarget.style.borderColor = "#E8470A";
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.boxShadow =
                      "0 4px 16px rgba(10,10,62,0.04)";
                    e.currentTarget.style.borderColor = "#e8ddd5";
                  }}
                >
                  <div
                    style={{
                      width: "48px",
                      height: "48px",
                      background: "rgba(232,71,10,0.08)",
                      borderRadius: "12px",
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                      color: "#E8470A",
                      flexShrink: 0,
                    }}
                  >
                    {item.icon}
                  </div>
                  <div>
                    <p
                      style={{
                        fontFamily: "var(--font-playfair)",
                        fontSize: "1rem",
                        fontWeight: "700",
                        color: "#0A0A3E",
                        marginBottom: "2px",
                      }}
                    >
                      {item.label}
                    </p>
                    <p
                      style={{
                        fontFamily: "var(--font-hind)",
                        fontSize: "11px",
                        color: "#c9a84c",
                      }}
                    >
                      {item.labelBn}
                    </p>
                    {item.count && (
                      <p
                        style={{
                          fontFamily: "var(--font-dm-sans)",
                          fontSize: "11px",
                          color: "#7a6e65",
                          marginTop: "4px",
                        }}
                      >
                        {item.count}
                      </p>
                    )}
                  </div>
                </motion.div>
              </Link>
            </motion.div>
          ))}
        </div>

        {/* Logout */}
        <motion.button
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 0.4 }}
          onClick={handleLogout}
          style={{
            width: "100%",
            background: "white",
            border: "1.5px solid #e8ddd5",
            borderRadius: "16px",
            padding: "16px 24px",
            display: "flex",
            alignItems: "center",
            gap: "12px",
            cursor: "pointer",
            fontFamily: "var(--font-dm-sans)",
            fontSize: "13px",
            fontWeight: "600",
            color: "#7a6e65",
            letterSpacing: "0.5px",
            transition: "all 0.2s ease",
          }}
          onMouseEnter={(e) => {
            e.currentTarget.style.borderColor = "#E8470A";
            e.currentTarget.style.color = "#E8470A";
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.borderColor = "#e8ddd5";
            e.currentTarget.style.color = "#7a6e65";
          }}
        >
          <LogOut size={18} strokeWidth={1.5} />
          Sign Out
        </motion.button>
      </div>

      <style>{`
        @media (max-width: 560px) {
          div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
          }
        }
      `}</style>
    </div>
  );
}
