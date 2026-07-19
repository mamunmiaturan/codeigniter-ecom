"use client";

import { motion } from "framer-motion";

const trustBadges = [
  {
    icon: "♡",
    label: "Designed with Love",
    labelBn: "ভালোবাসা দিয়ে ডিজাইন করা",
  },
  {
    icon: "◎",
    label: "Health Focused",
    labelBn: "স্বাস্থ্যের জীবনধারা গড়ে তোলে",
  },
  {
    icon: "◈",
    label: "Premium Quality",
    labelBn: "দীর্ঘস্থায়ী ও নির্ভরযোগ্য মান",
  },
  {
    icon: "⊞",
    label: "Perfect for Gifting",
    labelBn: "উপহারের জন্য সেরা পছন্দ",
  },
];

export function MissionSection() {
  return (
    <section
      style={{
        background: "transparent",
        padding: "100px clamp(1.5rem, 5vw, 7rem)",
        borderTop: "1px solid #e8ddd5",
      }}
    >
      <div
        style={{
          maxWidth: "1300px",
          margin: "0 auto",
          display: "grid",
          gridTemplateColumns: "1fr 1fr",
          gap: "80px",
          alignItems: "center",
        }}
      >
        {/* Left */}
        <motion.div
          initial={{ opacity: 0, x: -30 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.8 }}
        >
          {/* Logo mark */}
          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: "6px",
              marginBottom: "32px",
            }}
          >
            <span
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "1.1rem",
                fontWeight: "700",
                color: "#0A0A3E",
                fontStyle: "italic",
              }}
            >
              BabuSona
            </span>
            <span style={{ color: "#E8470A", fontSize: "0.7rem" }}>♡</span>
            <span
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "8px",
                color: "#E8470A",
                letterSpacing: "2px",
                textTransform: "uppercase",
                fontWeight: "600",
              }}
            >
              LOVE. CARE. FOREVER.
            </span>
          </div>

          {/* Big Bangla Headline */}
          <motion.h2
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.2, duration: 0.8 }}
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "clamp(2rem, 4vw, 3rem)",
              fontWeight: "700",
              color: "#0A0A3E",
              lineHeight: "1.3",
              marginBottom: "20px",
              letterSpacing: "-0.5px",
            }}
          >
            ভালোবাসা থেকে উদ্ভাবন,
            <br />
            যতু থেকে সমাধান।
          </motion.h2>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.3 }}
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "15px",
              color: "#7a6e65",
              lineHeight: "1.9",
              marginBottom: "48px",
            }}
          >
            বাবু ও সোনার জন্য প্রতিটি মুহূর্তকে আরও সহজ, স্বাস্থ্যকর ও সুন্দর
            করে তোলার আমাদের উদ্ভাবনী পণ্যসমূহ।
          </motion.p>

          {/* Product Image */}
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            whileInView={{ opacity: 1, scale: 1 }}
            viewport={{ once: true }}
            transition={{ delay: 0.4, duration: 0.8 }}
            style={{
              borderRadius: "24px",
              overflow: "hidden",
              aspectRatio: "4/3",
              position: "relative",
              border: "1px solid #e8ddd5",
              boxShadow: "0 20px 60px rgba(10,10,62,0.1)",
            }}
          >
            <img
              src="/images/home/2.jpeg"
              alt="BabuSona Products"
              style={{
                width: "100%",
                height: "100%",
                objectFit: "cover",
                objectPosition: "center top",
              }}
            />

            {/* Floating tag */}
            <motion.div
              animate={{ y: [0, -8, 0] }}
              transition={{ duration: 4, repeat: Infinity }}
              style={{
                position: "absolute",
                bottom: "20px",
                left: "20px",
                background: "rgba(255,255,255,0.95)",
                borderRadius: "16px",
                padding: "12px 16px",
                boxShadow: "0 8px 32px rgba(0,0,0,0.1)",
                backdropFilter: "blur(10px)",
              }}
            >
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "10px",
                  fontWeight: "700",
                  color: "#E8470A",
                  letterSpacing: "1.5px",
                  textTransform: "uppercase",
                  marginBottom: "2px",
                }}
              >
                BabuSona
              </p>
              <p
                style={{
                  fontFamily: "var(--font-hind)",
                  fontSize: "12px",
                  color: "#0A0A3E",
                  fontWeight: "600",
                }}
              >
                Smart Hydration Bottle
              </p>
            </motion.div>
          </motion.div>
        </motion.div>

        {/* Right */}
        <motion.div
          initial={{ opacity: 0, x: 30 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.8, delay: 0.2 }}
        >
          {/* Trust Badges */}
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "1fr 1fr",
              gap: "16px",
              marginBottom: "32px",
            }}
          >
            {trustBadges.map((badge, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: 0.3 + i * 0.08 }}
                style={{
                  background: "white",
                  borderRadius: "16px",
                  padding: "20px",
                  border: "1px solid #e8ddd5",
                  display: "flex",
                  alignItems: "flex-start",
                  gap: "12px",
                  boxShadow: "0 4px 16px rgba(10,10,62,0.04)",
                }}
              >
                <span
                  style={{
                    fontSize: "1.2rem",
                    color: "#E8470A",
                    flexShrink: 0,
                    lineHeight: 1,
                    marginTop: "2px",
                  }}
                >
                  {badge.icon}
                </span>
                <div>
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "12px",
                      fontWeight: "700",
                      color: "#0A0A3E",
                      marginBottom: "4px",
                    }}
                  >
                    {badge.label}
                  </p>
                  <p
                    style={{
                      fontFamily: "var(--font-hind)",
                      fontSize: "11px",
                      color: "#c9a84c",
                      lineHeight: "1.5",
                    }}
                  >
                    {badge.labelBn}
                  </p>
                </div>
              </motion.div>
            ))}
          </div>

          {/* Mission Quote */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.6 }}
            style={{
              background: "linear-gradient(135deg, #0A0A3E 0%, #12124e 100%)",
              borderRadius: "24px",
              padding: "40px 36px",
              position: "relative",
              overflow: "hidden",
              boxShadow: "0 20px 60px rgba(10,10,62,0.2)",
            }}
          >
            {/* Decorative circles */}
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

            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "10px",
                fontWeight: "700",
                letterSpacing: "2px",
                textTransform: "uppercase",
                color: "#E8470A",
                marginBottom: "16px",
                position: "relative",
                zIndex: 1,
              }}
            >
              Our Mission
            </p>

            <h3
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "1.4rem",
                fontWeight: "700",
                color: "white",
                lineHeight: "1.6",
                marginBottom: "20px",
                position: "relative",
                zIndex: 1,
              }}
            >
              "মায়ের ভালোবাসাকে
              <br />
              intelligent solution এ
              <br />
              রূপ দেওয়া।"
            </h3>

            <div
              style={{
                display: "flex",
                alignItems: "center",
                gap: "12px",
                position: "relative",
                zIndex: 1,
              }}
            >
              <div
                style={{
                  width: "32px",
                  height: "1px",
                  background: "#E8470A",
                }}
              />
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "10px",
                  color: "rgba(255,255,255,0.5)",
                  letterSpacing: "2px",
                  textTransform: "uppercase",
                }}
              >
                WE EXIST FOR THEM
              </p>
            </div>
          </motion.div>

          {/* Stats */}
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "repeat(4, 1fr)",
              gap: "16px",
              marginTop: "24px",
            }}
          >
            {[
              { value: "50K+", label: "Families", icon: "👨‍👩‍👧" },
              { value: "100+", label: "Products", icon: "✨" },
              { value: "4.9", label: "Rating", icon: "⭐" },
              { value: "🇧🇩", label: "Bangladesh", icon: "" },
            ].map((stat, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 16 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: 0.7 + i * 0.08 }}
                style={{
                  background: "white",
                  borderRadius: "14px",
                  padding: "16px 12px",
                  textAlign: "center",
                  border: "1px solid #e8ddd5",
                  boxShadow: "0 4px 16px rgba(10,10,62,0.04)",
                }}
              >
                <p
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontSize: "1.4rem",
                    fontWeight: "700",
                    color: "#0A0A3E",
                    lineHeight: 1,
                    marginBottom: "4px",
                  }}
                >
                  {stat.value}
                </p>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "10px",
                    color: "#E8470A",
                    letterSpacing: "0.5px",
                    fontWeight: "600",
                  }}
                >
                  {stat.label}
                </p>
              </motion.div>
            ))}
          </div>
        </motion.div>
      </div>

      <style>{`
        @media (max-width: 768px) {
          div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
            gap: 40px !important;
          }
          div[style*="repeat(4, 1fr)"] {
            grid-template-columns: repeat(2, 1fr) !important;
          }
        }
      `}</style>
    </section>
  );
}
