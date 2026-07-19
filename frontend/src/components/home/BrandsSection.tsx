"use client";

import { motion } from "framer-motion";
import Link from "next/link";

const brands = [
  {
    id: 1,
    name: "BabuCare",
    tagline: "Care for Him",
    taglineBn: "তার যত্নে",
    emoji: "👜",
    bg: "#0A0A3E",
    textColor: "white",
    accentColor: "#E8470A",
    products: ["👜", "🥤", "⌚"],
  },
  {
    id: 2,
    name: "SonaSteps",
    tagline: "Grow & Learn",
    taglineBn: "বেড়ে উঠুক",
    emoji: "🎒",
    bg: "#f5e8e6",
    textColor: "#0A0A3E",
    accentColor: "#e8a4a0",
    products: ["🎒", "💧", "📚"],
  },
  {
    id: 3,
    name: "BabuSona Home",
    tagline: "For Your Home",
    taglineBn: "ঘরের জন্য",
    emoji: "🛋️",
    bg: "#f7f0e6",
    textColor: "#0A0A3E",
    accentColor: "#c9a84c",
    products: ["🛋️", "🕯️", "🪴"],
  },
  {
    id: 4,
    name: "BabuSona Bespoke",
    tagline: "Personalized with Love",
    taglineBn: "ভালোবাসায় তৈরি",
    emoji: "✨",
    bg: "#1a1a2e",
    textColor: "white",
    accentColor: "#c9a84c",
    products: ["👛", "🏷️", "📝"],
  },
  {
    id: 5,
    name: "LittleSona",
    tagline: "For Little Ones",
    taglineBn: "ছোট্ট সোনার জন্য",
    emoji: "🧸",
    bg: "#fce8f0",
    textColor: "#0A0A3E",
    accentColor: "#e91e8c",
    products: ["🧸", "🎠", "🌈"],
  },
];

export function BrandsSection() {
  return (
    <section
      style={{
        background: "transparent",
        padding: "80px clamp(1.5rem, 5vw, 7rem)",
        borderTop: "1px solid #e8ddd5",
      }}
    >
      <div style={{ maxWidth: "1300px", margin: "0 auto" }}>
        {/* Header */}
        <div
          style={{
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            marginBottom: "48px",
            flexWrap: "wrap",
            gap: "16px",
          }}
        >
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
          >
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "10px",
                fontWeight: "700",
                letterSpacing: "3px",
                textTransform: "uppercase",
                color: "#E8470A",
                marginBottom: "8px",
              }}
            >
              Our Family of Brands
            </p>
            <h2
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "clamp(1.6rem, 3vw, 2.2rem)",
                fontWeight: "700",
                color: "#0A0A3E",
                letterSpacing: "-0.5px",
              }}
            >
              Our BabuSona Brands
            </h2>
          </motion.div>

          <Link
            href="/shop"
            style={{
              textDecoration: "none",
              fontFamily: "var(--font-dm-sans)",
              fontSize: "11px",
              fontWeight: "600",
              letterSpacing: "1px",
              color: "#7a6e65",
              display: "flex",
              alignItems: "center",
              gap: "4px",
              transition: "color 0.2s ease",
            }}
          >
            View all brands →
          </Link>
        </div>

        {/* Brand Cards */}
        <div
          style={{
            display: "grid",
            gridTemplateColumns: "repeat(5, 1fr)",
            gap: "16px",
          }}
        >
          {brands.map((brand, i) => (
            <motion.div
              key={brand.id}
              initial={{ opacity: 0, y: 24 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.08 }}
            >
              <Link href="/shop" style={{ textDecoration: "none" }}>
                <motion.div
                  whileHover={{ y: -8 }}
                  transition={{ type: "spring", stiffness: 300, damping: 20 }}
                  style={{
                    background: brand.bg,
                    borderRadius: "20px",
                    overflow: "hidden",
                    cursor: "pointer",
                    border: "1px solid rgba(0,0,0,0.06)",
                    boxShadow: "0 4px 24px rgba(10,10,62,0.06)",
                  }}
                >
                  {/* Product Area */}
                  <div
                    style={{
                      height: "160px",
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                      gap: "8px",
                      padding: "20px",
                      position: "relative",
                    }}
                  >
                    {brand.products.map((emoji, j) => (
                      <motion.span
                        key={j}
                        initial={{ scale: 0.8, opacity: 0 }}
                        whileInView={{ scale: 1, opacity: 1 }}
                        viewport={{ once: true }}
                        transition={{ delay: i * 0.08 + j * 0.06 }}
                        style={{
                          fontSize: j === 0 ? "3.2rem" : "1.8rem",
                          filter: "drop-shadow(0 4px 8px rgba(0,0,0,0.1))",
                        }}
                      >
                        {emoji}
                      </motion.span>
                    ))}
                  </div>

                  {/* Brand Info */}
                  <div
                    style={{
                      padding: "16px 20px 20px",
                      background: "rgba(255,255,255,0.9)",
                      borderTop: "1px solid rgba(0,0,0,0.04)",
                    }}
                  >
                    <p
                      style={{
                        fontFamily: "var(--font-playfair)",
                        fontSize: "13px",
                        fontWeight: "700",
                        color: "#0A0A3E",
                        marginBottom: "2px",
                      }}
                    >
                      {brand.name}
                    </p>
                    <p
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "10px",
                        color: "#7a6e65",
                        marginBottom: "2px",
                        letterSpacing: "0.5px",
                      }}
                    >
                      {brand.tagline}
                    </p>
                    <p
                      style={{
                        fontFamily: "var(--font-hind)",
                        fontSize: "10px",
                        color: brand.accentColor,
                      }}
                    >
                      {brand.taglineBn}
                    </p>
                  </div>
                </motion.div>
              </Link>
            </motion.div>
          ))}
        </div>
      </div>

      <style>{`
        @media (max-width: 900px) {
          div[style*="repeat(5, 1fr)"] {
            grid-template-columns: repeat(3, 1fr) !important;
          }
        }
        @media (max-width: 560px) {
          div[style*="repeat(5, 1fr)"] {
            grid-template-columns: repeat(2, 1fr) !important;
          }
        }
      `}</style>
    </section>
  );
}
