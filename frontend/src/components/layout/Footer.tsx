"use client";

import Link from "next/link";
import { Heart, Play, Globe } from "lucide-react";

export function Footer() {
  return (
    <footer
      style={{
        background: "#0A0A3E",
        borderTop: "1px solid rgba(255,255,255,0.08)",
        padding: "32px clamp(1.5rem, 4vw, 5rem)",
      }}
    >
      <div
        style={{
          maxWidth: "1300px",
          margin: "0 auto",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          flexWrap: "wrap",
          gap: "16px",
        }}
      >
        {/* Left */}
        <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
          <Heart size={16} color="#E8470A" fill="#E8470A" />
          <p
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "11px",
              color: "rgba(255,255,255,0.4)",
              letterSpacing: "0.5px",
            }}
          >
            © 2025 BabuSona. Made with love in Bangladesh.
          </p>
        </div>

        {/* Center */}
        <p
          style={{
            fontFamily: "var(--font-hind)",
            fontSize: "13px",
            color: "rgba(255,255,255,0.5)",
            textAlign: "center",
            flex: 1,
          }}
        >
          ভালোবাসা, খুনসুটি আর যতু — সবই বাবুসোনা।
        </p>

        {/* Right — Social */}
        <div
          style={{
            display: "flex",
            alignItems: "center",
            gap: "16px",
          }}
        >
          {[Globe, Play, Heart].map((Icon, i) => (
            <Link
              key={i}
              href="#"
              style={{
                color: "rgba(255,255,255,0.4)",
                transition: "color 0.2s ease",
                display: "flex",
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.color = "#E8470A";
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.color = "rgba(255,255,255,0.4)";
              }}
            >
              <Icon size={18} strokeWidth={1.5} />
            </Link>
          ))}
        </div>
      </div>
    </footer>
  );
}
