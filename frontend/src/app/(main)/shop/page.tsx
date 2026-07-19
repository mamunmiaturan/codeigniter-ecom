"use client";

import { useState } from "react";
import { motion } from "framer-motion";
import { ProductGrid } from "@/components/shop/ProductGrid";

export default function ShopPage() {
  const [activeZone, setActiveZone] = useState<"all" | "babu" | "sona">("all");

  return (
    <div style={{ background: "#f7f0e6", minHeight: "100vh" }}>
      {/* Shop Header */}
      <div
        style={{
          background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
          padding: "64px clamp(1.5rem, 4vw, 5rem) 32px",
          borderBottom: "1px solid #e8ddd5",
        }}
      >
        <div style={{ maxWidth: "1300px", margin: "0 auto" }}>
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
              marginBottom: "12px",
            }}
          >
            Shop
          </motion.p>

          <motion.h1
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "clamp(2rem, 4vw, 3rem)",
              fontWeight: "700",
              color: "#0A0A3E",
              marginBottom: "8px",
              letterSpacing: "-0.5px",
            }}
          >
            Made with Love,{" "}
            <span style={{ color: "#E8470A", fontStyle: "italic" }}>
              For Your BabuSona
            </span>
          </motion.h1>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "15px",
              color: "#7a6e65",
              marginBottom: "32px",
            }}
          >
            ভালোবাসার সাথে তৈরি, আপনার বাবুসোনার জন্য
          </motion.p>

          {/* Zone Tabs */}
          <div
            style={{
              display: "flex",
              gap: "8px",
              flexWrap: "wrap",
            }}
          >
            {[
              { id: "all", label: "🛍️ All Products", labelBn: "সব পণ্য" },
              { id: "babu", label: "💙 Babu Zone", labelBn: "বাবু জোন" },
              { id: "sona", label: "⭐ Sona Zone", labelBn: "সোনা জোন" },
            ].map((zone) => (
              <motion.button
                key={zone.id}
                whileTap={{ scale: 0.97 }}
                onClick={() => setActiveZone(zone.id as any)}
                style={{
                  padding: "12px 24px",
                  borderRadius: "25px",
                  border: "1.5px solid",
                  borderColor: activeZone === zone.id ? "#0A0A3E" : "#e8ddd5",
                  background:
                    activeZone === zone.id ? "#0A0A3E" : "transparent",
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "12px",
                  fontWeight: "700",
                  color: activeZone === zone.id ? "white" : "#7a6e65",
                  cursor: "pointer",
                  letterSpacing: "0.5px",
                  transition: "all 0.2s ease",
                  display: "flex",
                  alignItems: "center",
                  gap: "6px",
                }}
              >
                {zone.label}
                <span
                  style={{
                    fontFamily: "var(--font-hind)",
                    fontSize: "10px",
                    opacity: 0.7,
                  }}
                >
                  {zone.labelBn}
                </span>
              </motion.button>
            ))}
          </div>
        </div>
      </div>

      {/* Zone Banner */}
      {activeZone === "babu" && (
        <motion.div
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          style={{
            background: "linear-gradient(135deg, #0A0A3E 0%, #12124e 100%)",
            padding: "20px clamp(1.5rem, 4vw, 5rem)",
          }}
        >
          <div style={{ maxWidth: "1300px", margin: "0 auto" }}>
            <p
              style={{
                fontFamily: "var(--font-hind)",
                fontSize: "1rem",
                color: "white",
                fontWeight: "600",
              }}
            >
              💙 Babu Zone —{" "}
              <span style={{ color: "#E8470A" }}>
                তার প্রতিদিনের যত্নে আপনার ভালোবাসা
              </span>
            </p>
          </div>
        </motion.div>
      )}

      {activeZone === "sona" && (
        <motion.div
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          style={{
            background: "linear-gradient(135deg, #E8470A 0%, #c23a08 100%)",
            padding: "20px clamp(1.5rem, 4vw, 5rem)",
          }}
        >
          <div style={{ maxWidth: "1300px", margin: "0 auto" }}>
            <p
              style={{
                fontFamily: "var(--font-hind)",
                fontSize: "1rem",
                color: "white",
                fontWeight: "600",
              }}
            >
              ⭐ Sona Zone —{" "}
              <span style={{ color: "rgba(255,255,255,0.8)" }}>
                তার ছোট্ট পদক্ষেপ, তার বড় হতে থাকা স্বপ্ন
              </span>
            </p>
          </div>
        </motion.div>
      )}

      {/* Products */}
      <div
        style={{
          padding: "48px clamp(1.5rem, 4vw, 5rem)",
          maxWidth: "1300px",
          margin: "0 auto",
        }}
      >
        <ProductGrid activeZone={activeZone} />
      </div>
    </div>
  );
}
