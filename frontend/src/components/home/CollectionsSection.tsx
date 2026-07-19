"use client";

import { motion } from "framer-motion";
import Link from "next/link";

const collections = [
  {
    number: "01",
    title: "HYDRATION",
    titleBn: "স্মার্ট রিমাইন্ডার বোতল — প্রতিদিন পানি পান অভ্যাস গড়ে তোলে।",
    emoji: "💧",
    bg: "white",
    accent: "#0A0A3E",
    borderColor: "#e8ddd5",
    href: "/shop",
    solution: "Drink. Track. Thrive.",
  },
  {
    number: "02",
    title: "SUN & VITAMIN D",
    titleBn:
      "সান এক্সপোজার ট্র্যাকার ও সান কিট — রোদে বাইরে যাওয়ার অভ্যাস তৈরি করে।",
    emoji: "☀️",
    bg: "white",
    accent: "#E8470A",
    borderColor: "#e8ddd5",
    href: "/shop",
    solution: "Sun. Grow. Thrive.",
  },
  {
    number: "03",
    title: "WEATHER ADAPTIVE",
    titleBn: "আবহাওয়া উপযোগী ব্যাগ ও পোশাক — আরাম, সুরক্ষা, স্বাচ্ছন্দ্য।",
    emoji: "🌦️",
    bg: "white",
    accent: "#0A0A3E",
    borderColor: "#e8ddd5",
    href: "/shop",
    solution: "Ready for every weather.",
  },
  {
    number: "04",
    title: "ROUTINE & DISCIPLINE",
    titleBn: "রুটিন চার্ট ও সিস্টেম — ভালো অভ্যাস সহজ ও আনন্দদায়ক।",
    emoji: "📚",
    bg: "white",
    accent: "#E8470A",
    borderColor: "#e8ddd5",
    href: "/shop",
    solution: "Build habits. Build future.",
  },
  {
    number: "05",
    title: "BABU CARE",
    titleBn: "স্ট্রেস, ঘুম ও স্বাচ্ছের যত্ন — বাবুর জন্য বিশেষ কেয়ার কিট।",
    emoji: "☕",
    bg: "white",
    accent: "#0A0A3E",
    borderColor: "#e8ddd5",
    href: "/shop",
    solution: "Care for his best.",
  },
  {
    number: "06",
    title: "BESPOKE",
    titleBn: "নাম, বার্তা ও স্মৃতিকে করে তোলো চিরস্থায়ী ও অনন্য।",
    emoji: "✨",
    bg: "#0A0A3E",
    accent: "#E8470A",
    borderColor: "transparent",
    href: "/bespoke",
    solution: "Made personal. Made special.",
  },
];

export function CollectionsSection() {
  return (
    <section
      style={{
        background: "transparent",
        padding: "100px clamp(1.5rem, 5vw, 7rem)",
        borderTop: "1px solid #e8ddd5",
      }}
    >
      <div style={{ maxWidth: "1300px", margin: "0 auto" }}>
        {/* Header */}
        <div
          style={{
            display: "flex",
            alignItems: "flex-end",
            justifyContent: "space-between",
            marginBottom: "56px",
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
                marginBottom: "10px",
              }}
            >
              Problem → Solution
            </p>
            <h2
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "clamp(1.8rem, 3vw, 2.4rem)",
                fontWeight: "700",
                color: "#0A0A3E",
                letterSpacing: "-0.5px",
                lineHeight: 1.2,
              }}
            >
              Our Innovative Collections
            </h2>
          </motion.div>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.1 }}
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "13px",
              color: "#7a6e65",
              lineHeight: "1.7",
              maxWidth: "320px",
              textAlign: "right",
            }}
          >
            প্রতিটি collection একটি real problem সমাধান করে।
            <br />
            <span
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "12px",
                color: "#c9a84c",
              }}
            >
              We don&apos;t sell products — we sell better daily life.
            </span>
          </motion.p>
        </div>

        {/* Grid */}
        <div
          style={{
            display: "grid",
            gridTemplateColumns: "repeat(3, 1fr)",
            gap: "20px",
          }}
        >
          {collections.map((col, i) => (
            <motion.div
              key={col.number}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.1, duration: 0.6 }}
            >
              <Link href={col.href} style={{ textDecoration: "none" }}>
                <motion.div
                  whileHover={{ y: -6 }}
                  transition={{ type: "spring", stiffness: 300, damping: 20 }}
                  style={{
                    background: col.bg,
                    borderRadius: "20px",
                    padding: "32px",
                    cursor: "pointer",
                    border: `1px solid ${col.borderColor}`,
                    height: "100%",
                    position: "relative",
                    overflow: "hidden",
                    boxShadow:
                      col.bg === "#0A0A3E"
                        ? "0 20px 60px rgba(10,10,62,0.3)"
                        : "0 4px 24px rgba(10,10,62,0.06)",
                    transition: "box-shadow 0.3s ease",
                  }}
                >
                  {/* Number */}
                  <div
                    style={{
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "space-between",
                      marginBottom: "20px",
                    }}
                  >
                    <span
                      style={{
                        fontFamily: "var(--font-playfair)",
                        fontSize: "2.5rem",
                        fontWeight: "700",
                        color: col.accent,
                        opacity: col.bg === "#0A0A3E" ? 0.4 : 0.15,
                        lineHeight: 1,
                      }}
                    >
                      {col.number}
                    </span>
                    <div
                      style={{
                        width: "44px",
                        height: "44px",
                        background:
                          col.bg === "#0A0A3E"
                            ? "rgba(255,255,255,0.1)"
                            : "#f7f0e6",
                        borderRadius: "12px",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        fontSize: "1.3rem",
                      }}
                    >
                      {col.emoji}
                    </div>
                  </div>

                  {/* Title */}
                  <h3
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "11px",
                      fontWeight: "800",
                      letterSpacing: "2px",
                      color: col.accent,
                      marginBottom: "12px",
                      textTransform: "uppercase",
                    }}
                  >
                    {col.title}
                  </h3>

                  {/* Bangla */}
                  <p
                    style={{
                      fontFamily: "var(--font-hind)",
                      fontSize: "13px",
                      color:
                        col.bg === "#0A0A3E"
                          ? "rgba(255,255,255,0.65)"
                          : "#7a6e65",
                      lineHeight: "1.7",
                      marginBottom: "20px",
                    }}
                  >
                    {col.titleBn}
                  </p>

                  {/* Solution tag */}
                  <div
                    style={{
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "space-between",
                    }}
                  >
                    <span
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "10px",
                        fontWeight: "600",
                        color: col.accent,
                        background:
                          col.bg === "#0A0A3E"
                            ? "rgba(232,71,10,0.15)"
                            : "#f7f0e6",
                        padding: "4px 12px",
                        borderRadius: "20px",
                        letterSpacing: "0.5px",
                      }}
                    >
                      {col.solution}
                    </span>
                    <span
                      style={{
                        color: col.accent,
                        fontSize: "1rem",
                        fontWeight: "700",
                      }}
                    >
                      →
                    </span>
                  </div>

                  {/* Decorative circle */}
                  <div
                    style={{
                      position: "absolute",
                      bottom: "-30px",
                      right: "-30px",
                      width: "100px",
                      height: "100px",
                      borderRadius: "50%",
                      background: col.accent,
                      opacity: 0.06,
                    }}
                  />
                </motion.div>
              </Link>
            </motion.div>
          ))}
        </div>
      </div>

      <style>{`
        @media (max-width: 900px) {
          div[style*="repeat(3, 1fr)"] {
            grid-template-columns: repeat(2, 1fr) !important;
          }
        }
        @media (max-width: 560px) {
          div[style*="repeat(3, 1fr)"] {
            grid-template-columns: 1fr !important;
          }
        }
      `}</style>
    </section>
  );
}
