"use client";

import { motion } from "framer-motion";
import Link from "next/link";

export function BrandStorySection() {
  return (
    <section
      style={{
        background: "transparent",
        padding: "100px clamp(1.5rem, 5vw, 7rem)",
        textAlign: "center",
        borderTop: "1px solid #e8ddd5",
      }}
    >
      <div style={{ maxWidth: "600px", margin: "0 auto" }}>
        <motion.p
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "10px",
            fontWeight: "700",
            letterSpacing: "4px",
            textTransform: "uppercase",
            color: "#E8470A",
            marginBottom: "24px",
          }}
        >
          Our Promise
        </motion.p>

        <motion.h2
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
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
          Everything for your BabuSona.
        </motion.h2>

        <motion.p
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.2 }}
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "16px",
            color: "#7a6e65",
            lineHeight: "1.9",
            marginBottom: "12px",
          }}
        >
          Love. Care. Laughter. Memories.
          <br />
          All in one place.
        </motion.p>

        <motion.p
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.25 }}
          style={{
            fontFamily: "var(--font-hind)",
            fontSize: "14px",
            color: "#c9a84c",
            marginBottom: "36px",
          }}
        >
          ভালোবাসা, যতু, প্রতিদিন — সবই বাবুসোনা।
        </motion.p>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.3 }}
        >
          <Link href="/shop" style={{ textDecoration: "none" }}>
            <button
              style={{
                background: "transparent",
                border: "1.5px solid #E8470A",
                borderRadius: "30px",
                padding: "14px 40px",
                fontSize: "11px",
                fontWeight: "700",
                color: "#E8470A",
                cursor: "pointer",
                fontFamily: "var(--font-dm-sans)",
                letterSpacing: "2px",
                textTransform: "uppercase",
                transition: "all 0.3s ease",
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.background = "#E8470A";
                e.currentTarget.style.color = "white";
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.background = "transparent";
                e.currentTarget.style.color = "#E8470A";
              }}
            >
              Explore Collection
            </button>
          </Link>
        </motion.div>
      </div>
    </section>
  );
}
