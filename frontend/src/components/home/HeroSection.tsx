"use client";

import { motion } from "framer-motion";
import Link from "next/link";

const trustBadges = [
  { icon: "👥", label: "Trusted by 500K+", sub: "BabuSona Families" },
  { icon: "✨", label: "Personalized", sub: "Just for Them" },
  { icon: "💕", label: "Curated with Love", sub: "By Bengali Moms" },
  { icon: "🚚", label: "Fast & Reliable", sub: "Delivery" },
  { icon: "😊", label: "Happy Moments", sub: "Guaranteed" },
];

export function HeroSection() {
  return (
    <section style={{ background: "#f5f0e8" }}>
      {/* Top Label */}
      <div style={{ textAlign: "center", paddingTop: "56px" }}>
        <motion.p
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "10px",
            fontWeight: "700",
            letterSpacing: "4px",
            textTransform: "uppercase",
            color: "#8B6914",
            marginBottom: "20px",
          }}
        >
          Welcome to BabuSona
        </motion.p>

        {/* Bangla Headline */}
        <motion.h1
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          style={{
            fontFamily: "var(--font-hind)",
            fontSize: "clamp(2.2rem, 5vw, 3.8rem)",
            fontWeight: "700",
            color: "#1a1a1a",
            lineHeight: "1.25",
            marginBottom: "16px",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            gap: "12px",
          }}
        >
          আজ কার জন্য ভালোবাসা পাঠাবেন?
          <span style={{ color: "#E8470A", fontSize: "2rem" }}>♡</span>
        </motion.h1>

        {/* Subtext */}
        <motion.p
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "15px",
            color: "#6b6b6b",
            marginBottom: "48px",
          }}
        >
          Choose who you want to shop, love & plan for today.
        </motion.p>
      </div>

      {/* Two Zone Cards */}
      <motion.div
        initial={{ opacity: 0, y: 30 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.3 }}
        style={{
          maxWidth: "1100px",
          margin: "0 auto",
          padding: "0 clamp(1rem, 4vw, 4rem)",
          display: "grid",
          gridTemplateColumns: "1fr auto 1fr",
          gap: "0",
          alignItems: "center",
        }}
      >
        {/* BABU Zone Card */}
        <Link href="/shop" style={{ textDecoration: "none" }}>
          <motion.div
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            style={{
              position: "relative",
              borderRadius: "24px",
              overflow: "hidden",
              aspectRatio: "3/4",
              background: "linear-gradient(135deg, #0A0A3E 0%, #12124e 100%)",
              cursor: "pointer",
            }}
          >
            <img
              src="/images/home/2.jpeg"
              alt="For My Babu"
              style={{
                width: "100%",
                height: "100%",
                objectFit: "cover",
                objectPosition: "center top",
                mixBlendMode: "luminosity",
                opacity: 0.4,
              }}
            />

            {/* Overlay */}
            <div
              style={{
                position: "absolute",
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                padding: "36px",
                display: "flex",
                flexDirection: "column",
                justifyContent: "space-between",
              }}
            >
              <div>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "10px",
                    fontWeight: "700",
                    letterSpacing: "3px",
                    textTransform: "uppercase",
                    color: "rgba(255,255,255,0.6)",
                    marginBottom: "8px",
                  }}
                >
                  FOR MY
                </p>
                <h2
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontSize: "clamp(2rem, 4vw, 3.5rem)",
                    fontWeight: "700",
                    color: "white",
                    lineHeight: 1,
                    marginBottom: "12px",
                  }}
                >
                  BABU <span style={{ color: "#E8470A" }}>♡</span>
                </h2>
                <p
                  style={{
                    fontFamily: "var(--font-hind)",
                    fontSize: "14px",
                    color: "rgba(255,255,255,0.75)",
                    lineHeight: "1.6",
                  }}
                >
                  তার যতুও তো
                  <br />
                  কেউ নেবে ❤️
                </p>
              </div>

              <div
                style={{
                  width: "48px",
                  height: "48px",
                  background: "#E8470A",
                  borderRadius: "50%",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  fontSize: "1.2rem",
                  color: "white",
                  fontWeight: "700",
                  boxShadow: "0 4px 20px rgba(232,71,10,0.4)",
                }}
              >
                →
              </div>
            </div>
          </motion.div>
        </Link>

        {/* OR Divider */}
        <div
          style={{
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            justifyContent: "center",
            padding: "0 20px",
            zIndex: 1,
          }}
        >
          <div
            style={{
              width: "40px",
              height: "40px",
              background: "#f5f0e8",
              borderRadius: "50%",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              fontFamily: "var(--font-dm-sans)",
              fontSize: "11px",
              fontWeight: "600",
              color: "#8B6914",
              boxShadow: "0 4px 20px rgba(0,0,0,0.08)",
              border: "1px solid #e8e0d5",
            }}
          >
            or
          </div>
        </div>

        {/* SONA Zone Card */}
        <Link href="/shop" style={{ textDecoration: "none" }}>
          <motion.div
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            style={{
              position: "relative",
              borderRadius: "24px",
              overflow: "hidden",
              aspectRatio: "3/4",
              background: "linear-gradient(135deg, #f9e8f0 0%, #fce4ee 100%)",
              cursor: "pointer",
            }}
          >
            <img
              src="/images/home/1.jpeg"
              alt="For My Sona"
              style={{
                width: "100%",
                height: "100%",
                objectFit: "cover",
                objectPosition: "center top",
                opacity: 0.6,
              }}
            />

            {/* Overlay */}
            <div
              style={{
                position: "absolute",
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                padding: "36px",
                display: "flex",
                flexDirection: "column",
                justifyContent: "space-between",
              }}
            >
              <div>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "10px",
                    fontWeight: "700",
                    letterSpacing: "3px",
                    textTransform: "uppercase",
                    color: "#888",
                    marginBottom: "8px",
                  }}
                >
                  FOR MY
                </p>
                <h2
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontSize: "clamp(2rem, 4vw, 3.5rem)",
                    fontWeight: "700",
                    color: "#1a1a1a",
                    lineHeight: 1,
                    marginBottom: "12px",
                  }}
                >
                  SONA <span style={{ fontSize: "2rem" }}>🌸</span>
                </h2>
                <p
                  style={{
                    fontFamily: "var(--font-hind)",
                    fontSize: "14px",
                    color: "#555",
                    lineHeight: "1.6",
                  }}
                >
                  পৃথিবীর সবচেয়ে
                  <br />
                  আদুরে মানুষটা 🥰
                </p>
              </div>

              <div
                style={{
                  width: "48px",
                  height: "48px",
                  background: "#e91e8c",
                  borderRadius: "50%",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  fontSize: "1.2rem",
                  color: "white",
                  fontWeight: "700",
                  boxShadow: "0 4px 20px rgba(233,30,140,0.3)",
                }}
              >
                →
              </div>
            </div>
          </motion.div>
        </Link>
      </motion.div>

      {/* Trust Badges */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.5 }}
        style={{
          maxWidth: "1100px",
          margin: "0 auto",
          padding: "40px clamp(1rem, 4vw, 4rem)",
          display: "flex",
          justifyContent: "space-between",
          alignItems: "center",
          flexWrap: "wrap",
          gap: "16px",
          borderTop: "1px solid #e8e0d5",
          marginTop: "48px",
        }}
      >
        {trustBadges.map((badge, i) => (
          <div
            key={i}
            style={{
              display: "flex",
              alignItems: "center",
              gap: "10px",
            }}
          >
            <span style={{ fontSize: "1.1rem", opacity: 0.7 }}>
              {badge.icon}
            </span>
            <div>
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: "700",
                  color: "#1a1a1a",
                  lineHeight: 1.3,
                }}
              >
                {badge.label}
              </p>
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "10px",
                  color: "#8B6914",
                  lineHeight: 1.3,
                }}
              >
                {badge.sub}
              </p>
            </div>
          </div>
        ))}
      </motion.div>

      <style>{`
        @media (max-width: 768px) {
          div[style*="grid-template-columns: 1fr auto 1fr"] {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
          }
        }
      `}</style>
    </section>
  );
}
