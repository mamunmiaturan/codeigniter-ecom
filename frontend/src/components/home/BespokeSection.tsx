"use client";

import { motion } from "framer-motion";
import Link from "next/link";
import { ArrowRight } from "lucide-react";

const bespokeOptions = [
  {
    id: 1,
    title: "Name Emboss",
    titleBn: "নাম এমবস",
    preview: "Arham's\nBaba",
    bg: "#0A0A3E",
    textColor: "white",
    accentColor: "#E8470A",
    font: "serif",
  },
  {
    id: 2,
    title: "Embroidery",
    titleBn: "এমব্রয়ডারি",
    preview: "My\nSona",
    bg: "#f5e8e6",
    textColor: "#0A0A3E",
    accentColor: "#e8a4a0",
    font: "cursive",
  },
  {
    id: 3,
    title: "Handwritten Note",
    titleBn: "হাতে লেখা চিঠি",
    preview: "I love\nyou ❤️",
    bg: "#faf6f0",
    textColor: "#0A0A3E",
    accentColor: "#c9a84c",
    font: "cursive",
  },
  {
    id: 4,
    title: "Doodle Engraving",
    titleBn: "ডুডল এনগ্রেভিং",
    preview: "Best\nBabu",
    bg: "#12124e",
    textColor: "white",
    accentColor: "#c9a84c",
    font: "serif",
  },
  {
    id: 5,
    title: "Voice QR Tag",
    titleBn: "ভয়েস QR ট্যাগ",
    preview: "Scan\nMe ▶",
    bg: "#E8470A",
    textColor: "white",
    accentColor: "rgba(255,255,255,0.7)",
    font: "sans-serif",
  },
];

export function BespokeSection() {
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
            display: "grid",
            gridTemplateColumns: "1fr 1fr",
            gap: "48px",
            alignItems: "center",
            marginBottom: "48px",
          }}
        >
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            whileInView={{ opacity: 1, x: 0 }}
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
                marginBottom: "12px",
              }}
            >
              Bespoke Studio
            </p>
            <h2
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "clamp(1.8rem, 3vw, 2.4rem)",
                fontWeight: "700",
                color: "#0A0A3E",
                lineHeight: "1.3",
                marginBottom: "16px",
                letterSpacing: "-0.5px",
              }}
            >
              Make it Uniquely Theirs ❤️
            </h2>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "14px",
                color: "#7a6e65",
                lineHeight: "1.8",
                marginBottom: "8px",
              }}
            >
              Add names, doodles, messages or voice — turn every product into a
              memory.
            </p>
            <p
              style={{
                fontFamily: "var(--font-hind)",
                fontSize: "13px",
                color: "#c9a84c",
                lineHeight: "1.7",
                marginBottom: "28px",
              }}
            >
              নাম, বার্তা, কণ্ঠস্বর — প্রতিটি উপহারকে করুন অনন্য।
            </p>
            <Link href="/bespoke" style={{ textDecoration: "none" }}>
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: "8px",
                  background: "#0A0A3E",
                  color: "white",
                  border: "none",
                  borderRadius: "30px",
                  padding: "14px 28px",
                  fontSize: "11px",
                  fontWeight: "700",
                  cursor: "pointer",
                  fontFamily: "var(--font-dm-sans)",
                  letterSpacing: "1.5px",
                  textTransform: "uppercase",
                  boxShadow: "0 8px 32px rgba(10,10,62,0.2)",
                  transition: "all 0.3s ease",
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.background = "#E8470A";
                  e.currentTarget.style.boxShadow =
                    "0 8px 32px rgba(232,71,10,0.3)";
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.background = "#0A0A3E";
                  e.currentTarget.style.boxShadow =
                    "0 8px 32px rgba(10,10,62,0.2)";
                }}
              >
                Visit Bespoke Lab
                <ArrowRight size={15} />
              </motion.button>
            </Link>
          </motion.div>

          {/* Right Preview */}
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.1 }}
            style={{
              background: "linear-gradient(135deg, #0A0A3E 0%, #12124e 100%)",
              borderRadius: "24px",
              padding: "48px 40px",
              display: "flex",
              flexDirection: "column",
              alignItems: "center",
              justifyContent: "center",
              minHeight: "200px",
              position: "relative",
              overflow: "hidden",
              boxShadow: "0 20px 60px rgba(10,10,62,0.25)",
            }}
          >
            {/* Decorative */}
            <div
              style={{
                position: "absolute",
                top: "-30px",
                right: "-30px",
                width: "120px",
                height: "120px",
                borderRadius: "50%",
                background: "rgba(232,71,10,0.15)",
              }}
            />
            <div
              style={{
                position: "absolute",
                bottom: "-20px",
                left: "-20px",
                width: "80px",
                height: "80px",
                borderRadius: "50%",
                background: "rgba(201,168,76,0.1)",
              }}
            />

            <motion.p
              animate={{ opacity: [0.7, 1, 0.7] }}
              transition={{ duration: 3, repeat: Infinity }}
              style={{
                fontFamily: "Georgia, serif",
                fontSize: "2.8rem",
                fontWeight: "700",
                color: "white",
                textAlign: "center",
                lineHeight: "1.3",
                position: "relative",
                zIndex: 1,
              }}
            >
              Arham&apos;s
              <br />
              <span style={{ color: "#E8470A" }}>Baba</span>
            </motion.p>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "10px",
                color: "rgba(255,255,255,0.4)",
                marginTop: "16px",
                letterSpacing: "2px",
                textTransform: "uppercase",
                position: "relative",
                zIndex: 1,
              }}
            >
              Name Emboss Preview
            </p>
          </motion.div>
        </div>

        {/* Options Row */}
        <div
          style={{
            display: "grid",
            gridTemplateColumns: "repeat(5, 1fr)",
            gap: "16px",
          }}
        >
          {bespokeOptions.map((option, i) => (
            <motion.div
              key={option.id}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.08 }}
              whileHover={{ y: -6 }}
              style={{
                background: option.bg,
                borderRadius: "16px",
                padding: "24px 20px",
                cursor: "pointer",
                border: "1px solid rgba(0,0,0,0.06)",
                boxShadow: "0 4px 16px rgba(10,10,62,0.08)",
                transition: "box-shadow 0.3s ease",
              }}
            >
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "9px",
                  fontWeight: "700",
                  color: option.accentColor,
                  textTransform: "uppercase",
                  letterSpacing: "1.5px",
                  marginBottom: "12px",
                }}
              >
                {option.title}
              </p>
              <p
                style={{
                  fontFamily: option.font,
                  fontSize: "1.5rem",
                  fontWeight: "700",
                  color: option.textColor,
                  lineHeight: "1.3",
                  whiteSpace: "pre-line",
                  marginBottom: "10px",
                }}
              >
                {option.preview}
              </p>
              <p
                style={{
                  fontFamily: "var(--font-hind)",
                  fontSize: "10px",
                  color: option.accentColor,
                  opacity: 0.7,
                }}
              >
                {option.titleBn}
              </p>
            </motion.div>
          ))}
        </div>
      </div>

      <style>{`
        @media (max-width: 900px) {
          div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
          }
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
