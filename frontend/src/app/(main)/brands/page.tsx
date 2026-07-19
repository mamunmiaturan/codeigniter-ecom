"use client";

import { motion } from "framer-motion";
import Link from "next/link";

const brands = [
  {
    id: 1,
    name: "BabuCare",
    tagline: "Care for Him",
    taglineBn: "তার যত্নে আপনার ভালোবাসা",
    description:
      "Premium grooming, wellness and comfort products designed specifically for the modern Bangladeshi husband.",
    descriptionBn:
      "আধুনিক বাংলাদেশি বাবুর জন্য প্রিমিয়াম গ্রুমিং, সুস্থতা ও কমফোর্ট পণ্য।",
    emoji: ["👜", "🥤", "⌚", "☕"],
    bg: "#0A0A3E",
    textColor: "white",
    accent: "#E8470A",
    href: "/shop",
    products: "24 Products",
    tag: "For Babu",
  },
  {
    id: 2,
    name: "SonaSteps",
    tagline: "Grow & Learn",
    taglineBn: "বেড়ে উঠুক, শিখুক, জয় করুক",
    description:
      "Educational tools, growth trackers and learning kits that help your Sona build better habits and a brighter future.",
    descriptionBn:
      "শিক্ষামূলক সরঞ্জাম ও গ্রোথ ট্র্যাকার যা আপনার সোনার ভবিষ্যৎ গড়তে সাহায্য করে।",
    emoji: ["🎒", "📚", "✏️", "🌟"],
    bg: "#f5e8e6",
    textColor: "#0A0A3E",
    accent: "#E8470A",
    href: "/shop",
    products: "31 Products",
    tag: "For Sona",
  },
  {
    id: 3,
    name: "BabuSona Home",
    tagline: "For Your Home",
    taglineBn: "আপনার ঘরকে করুন আরও সুন্দর",
    description:
      "Thoughtfully curated home comfort products that bring warmth, love and functionality to your family space.",
    descriptionBn:
      "আপনার পরিবারের জায়গায় উষ্ণতা ও ভালোবাসা আনতে কিউরেটেড হোম কমফোর্ট পণ্য।",
    emoji: ["🛋️", "🕯️", "🪴", "🛏️"],
    bg: "#faf6f0",
    textColor: "#0A0A3E",
    accent: "#c9a84c",
    href: "/shop",
    products: "18 Products",
    tag: "For Home",
  },
  {
    id: 4,
    name: "BabuSona Bespoke",
    tagline: "Personalized with Love",
    taglineBn: "ভালোবাসায় তৈরি, স্মৃতিতে চিরস্থায়ী",
    description:
      "Custom engraving, embroidery, voice QR tags and handwritten notes — turn every product into a memory.",
    descriptionBn:
      "কাস্টম এনগ্রেভিং, এমব্রয়ডারি, ভয়েস QR ট্যাগ — প্রতিটি উপহারকে করুন অনন্য।",
    emoji: ["✨", "👛", "📝", "🏷️"],
    bg: "#1a1a2e",
    textColor: "white",
    accent: "#c9a84c",
    href: "/bespoke",
    products: "50+ Options",
    tag: "Personalized",
  },
  {
    id: 5,
    name: "LittleSona",
    tagline: "For Little Ones",
    taglineBn: "ছোট্ট সোনার জন্য বিশেষ কিছু",
    description:
      "Safe, fun and developmental toys, comfort items and essentials for babies and toddlers.",
    descriptionBn:
      "শিশু ও ছোট বাচ্চাদের জন্য নিরাপদ, মজাদার ও উন্নয়নমূলক খেলনা ও প্রয়োজনীয় জিনিস।",
    emoji: ["🧸", "🌈", "🎠", "💧"],
    bg: "#fce8f0",
    textColor: "#0A0A3E",
    accent: "#e91e8c",
    href: "/shop",
    products: "22 Products",
    tag: "For Baby",
  },
];

export default function BrandsPage() {
  return (
    <div style={{ background: "#f7f0e6", minHeight: "100vh" }}>
      {/* Hero */}
      <div
        style={{
          background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
          padding: "80px clamp(1.5rem, 4vw, 5rem)",
          textAlign: "center",
          borderBottom: "1px solid #e8ddd5",
        }}
      >
        <div style={{ maxWidth: "700px", margin: "0 auto" }}>
          <motion.p
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "10px",
              fontWeight: "700",
              letterSpacing: "4px",
              textTransform: "uppercase",
              color: "#E8470A",
              marginBottom: "16px",
            }}
          >
            Our Family of Brands
          </motion.p>

          <motion.h1
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "clamp(2rem, 4vw, 3.2rem)",
              fontWeight: "700",
              color: "#0A0A3E",
              lineHeight: "1.3",
              marginBottom: "20px",
              letterSpacing: "-0.5px",
            }}
          >
            BabuSona Brands <span style={{ color: "#E8470A" }}>♡</span>
          </motion.h1>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "15px",
              color: "#7a6e65",
              lineHeight: "1.8",
              marginBottom: "12px",
            }}
          >
            Five unique brands. One mission — to make every day better for your
            Babu & Sona.
          </motion.p>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "14px",
              color: "#c9a84c",
            }}
          >
            পাঁচটি ব্র্যান্ড। একটি লক্ষ্য — আপনার বাবু ও সোনার প্রতিদিনকে আরও
            সুন্দর করা।
          </motion.p>
        </div>
      </div>

      {/* Brands */}
      <div
        style={{
          maxWidth: "1300px",
          margin: "0 auto",
          padding: "64px clamp(1.5rem, 4vw, 5rem)",
          display: "flex",
          flexDirection: "column",
          gap: "32px",
        }}
      >
        {brands.map((brand, i) => (
          <motion.div
            key={brand.id}
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: i * 0.1 }}
            style={{
              borderRadius: "28px",
              overflow: "hidden",
              border: "1px solid #e8ddd5",
              boxShadow: "0 4px 24px rgba(10,10,62,0.06)",
            }}
          >
            <div
              style={{
                display: "grid",
                gridTemplateColumns: i % 2 === 0 ? "1fr 1fr" : "1fr 1fr",
                direction: i % 2 === 0 ? "ltr" : "rtl",
              }}
            >
              {/* Image/Visual Side */}
              <div
                style={{
                  background: brand.bg,
                  padding: "60px 48px",
                  display: "flex",
                  flexDirection: "column",
                  justifyContent: "space-between",
                  minHeight: "320px",
                  direction: "ltr",
                }}
              >
                {/* Tag */}
                <div>
                  <span
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "10px",
                      fontWeight: "700",
                      letterSpacing: "2px",
                      textTransform: "uppercase",
                      color: brand.accent,
                      background: "rgba(255,255,255,0.1)",
                      padding: "4px 12px",
                      borderRadius: "20px",
                      border: `1px solid ${brand.accent}40`,
                    }}
                  >
                    {brand.tag}
                  </span>
                </div>

                {/* Brand Name */}
                <div>
                  <h2
                    style={{
                      fontFamily: "var(--font-playfair)",
                      fontSize: "clamp(2rem, 4vw, 3rem)",
                      fontWeight: "700",
                      color: brand.textColor,
                      lineHeight: 1.1,
                      marginBottom: "8px",
                      letterSpacing: "-0.5px",
                    }}
                  >
                    {brand.name}
                  </h2>
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "13px",
                      fontWeight: "600",
                      color: brand.accent,
                      letterSpacing: "1px",
                      textTransform: "uppercase",
                    }}
                  >
                    {brand.tagline}
                  </p>
                </div>

                {/* Emojis */}
                <div
                  style={{
                    display: "flex",
                    gap: "12px",
                    alignItems: "center",
                  }}
                >
                  {brand.emoji.map((e, j) => (
                    <motion.span
                      key={j}
                      initial={{ scale: 0 }}
                      whileInView={{ scale: 1 }}
                      viewport={{ once: true }}
                      transition={{ delay: i * 0.1 + j * 0.08, type: "spring" }}
                      style={{
                        fontSize: j === 0 ? "3rem" : "2rem",
                        filter: "drop-shadow(0 4px 8px rgba(0,0,0,0.15))",
                      }}
                    >
                      {e}
                    </motion.span>
                  ))}
                </div>
              </div>

              {/* Content Side */}
              <div
                style={{
                  background: "white",
                  padding: "60px 48px",
                  display: "flex",
                  flexDirection: "column",
                  justifyContent: "center",
                  gap: "24px",
                  direction: "ltr",
                }}
              >
                <div>
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "14px",
                      color: "#7a6e65",
                      lineHeight: "1.8",
                      marginBottom: "12px",
                    }}
                  >
                    {brand.description}
                  </p>
                  <p
                    style={{
                      fontFamily: "var(--font-hind)",
                      fontSize: "13px",
                      color: "#c9a84c",
                      lineHeight: "1.8",
                    }}
                  >
                    {brand.descriptionBn}
                  </p>
                </div>

                <div
                  style={{
                    display: "flex",
                    alignItems: "center",
                    gap: "16px",
                    paddingTop: "16px",
                    borderTop: "1px solid #e8ddd5",
                  }}
                >
                  <span
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "12px",
                      fontWeight: "600",
                      color: "#7a6e65",
                      letterSpacing: "0.5px",
                    }}
                  >
                    {brand.products}
                  </span>

                  <Link
                    href={brand.href}
                    style={{ textDecoration: "none", marginLeft: "auto" }}
                  >
                    <motion.button
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      style={{
                        background: "#0A0A3E",
                        color: "white",
                        border: "none",
                        borderRadius: "25px",
                        padding: "12px 24px",
                        fontSize: "11px",
                        fontWeight: "700",
                        cursor: "pointer",
                        fontFamily: "var(--font-dm-sans)",
                        letterSpacing: "1.5px",
                        textTransform: "uppercase",
                        boxShadow: "0 8px 24px rgba(10,10,62,0.15)",
                        transition: "all 0.3s ease",
                      }}
                      onMouseEnter={(e) => {
                        e.currentTarget.style.background = "#E8470A";
                        e.currentTarget.style.boxShadow =
                          "0 8px 24px rgba(232,71,10,0.3)";
                      }}
                      onMouseLeave={(e) => {
                        e.currentTarget.style.background = "#0A0A3E";
                        e.currentTarget.style.boxShadow =
                          "0 8px 24px rgba(10,10,62,0.15)";
                      }}
                    >
                      Explore Brand →
                    </motion.button>
                  </Link>
                </div>
              </div>
            </div>
          </motion.div>
        ))}
      </div>

      <style>{`
        @media (max-width: 768px) {
          div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
            direction: ltr !important;
          }
        }
      `}</style>
    </div>
  );
}
