"use client";

import { motion } from "framer-motion";
import Link from "next/link";
import { Share2, HelpCircle, Smile, BookOpen, Heart } from "lucide-react";

const communityFeatures = [
  {
    icon: <Share2 size={20} strokeWidth={1.5} />,
    label: "Share",
    labelBn: "শেয়ার",
  },
  {
    icon: <HelpCircle size={20} strokeWidth={1.5} />,
    label: "Ask",
    labelBn: "জিজ্ঞেস",
  },
  {
    icon: <Smile size={20} strokeWidth={1.5} />,
    label: "Laugh",
    labelBn: "হাসুন",
  },
  {
    icon: <BookOpen size={20} strokeWidth={1.5} />,
    label: "Learn",
    labelBn: "শিখুন",
  },
  {
    icon: <Heart size={20} strokeWidth={1.5} />,
    label: "Support",
    labelBn: "সাপোর্ট",
  },
];

export function CommunitySection() {
  return (
    <section
      style={{
        background: "transparent",
        padding: "80px clamp(1.5rem, 5vw, 7rem)",
        borderTop: "1px solid #e8ddd5",
      }}
    >
      <div
        style={{
          maxWidth: "1300px",
          margin: "0 auto",
          display: "grid",
          gridTemplateColumns: "1fr 1fr",
          gap: "60px",
          alignItems: "center",
        }}
      >
        {/* Left — Image */}
        <motion.div
          initial={{ opacity: 0, x: -30 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.7 }}
          style={{
            position: "relative",
            borderRadius: "24px",
            overflow: "hidden",
            aspectRatio: "4/3",
            cursor: "pointer",
            boxShadow: "0 20px 60px rgba(10,10,62,0.12)",
          }}
        >
          <img
            src="/images/home/3.jpeg"
            alt="BabuSona Community"
            style={{
              width: "100%",
              height: "100%",
              objectFit: "cover",
              objectPosition: "center top",
            }}
          />

          {/* Dark overlay */}
          <div
            style={{
              position: "absolute",
              inset: 0,
              background:
                "linear-gradient(to top, rgba(10,10,62,0.6) 0%, transparent 60%)",
            }}
          />

          {/* Play Button */}
          <div
            style={{
              position: "absolute",
              bottom: "24px",
              left: "24px",
              display: "flex",
              alignItems: "center",
              gap: "10px",
              background: "rgba(255,255,255,0.95)",
              borderRadius: "30px",
              padding: "10px 18px",
              boxShadow: "0 4px 20px rgba(0,0,0,0.12)",
              backdropFilter: "blur(10px)",
            }}
          >
            <div
              style={{
                width: "32px",
                height: "32px",
                background: "#E8470A",
                borderRadius: "50%",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                fontSize: "0.8rem",
                color: "white",
                boxShadow: "0 4px 12px rgba(232,71,10,0.3)",
              }}
            >
              ▶
            </div>
            <span
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "12px",
                fontWeight: "700",
                color: "#0A0A3E",
                letterSpacing: "0.5px",
              }}
            >
              Watch our story
            </span>
          </div>

          {/* Member count badge */}
          <div
            style={{
              position: "absolute",
              top: "20px",
              right: "20px",
              background: "#E8470A",
              borderRadius: "20px",
              padding: "8px 16px",
            }}
          >
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "11px",
                fontWeight: "700",
                color: "white",
                letterSpacing: "0.5px",
              }}
            >
              10K+ Members
            </p>
          </div>
        </motion.div>

        {/* Right — Content */}
        <motion.div
          initial={{ opacity: 0, x: 30 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.7, delay: 0.1 }}
        >
          <p
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "10px",
              fontWeight: "700",
              letterSpacing: "3px",
              textTransform: "uppercase",
              color: "#E8470A",
              marginBottom: "16px",
            }}
          >
            Community
          </p>

          <h2
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "clamp(1.8rem, 3vw, 2.6rem)",
              fontWeight: "700",
              color: "#0A0A3E",
              marginBottom: "12px",
              lineHeight: "1.3",
              letterSpacing: "-0.5px",
            }}
          >
            BabuSona Community
          </h2>

          <p
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "16px",
              color: "#0A0A3E",
              fontWeight: "600",
              marginBottom: "8px",
            }}
          >
            এখানে আমরা এক অপরের মানুষ।
          </p>

          <p
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "14px",
              color: "#7a6e65",
              lineHeight: "1.8",
              marginBottom: "36px",
            }}
          >
            A safe, fun & supportive space for women who live for their
            BabuSona. Share, laugh, learn & grow together.
          </p>

          {/* Features */}
          <div
            style={{
              display: "flex",
              gap: "16px",
              marginBottom: "40px",
              flexWrap: "wrap",
            }}
          >
            {communityFeatures.map((feature, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 16 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: i * 0.08 }}
                style={{
                  display: "flex",
                  flexDirection: "column",
                  alignItems: "center",
                  gap: "6px",
                }}
              >
                <div
                  style={{
                    width: "48px",
                    height: "48px",
                    background: "white",
                    borderRadius: "14px",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    color: "#E8470A",
                    border: "1px solid #e8ddd5",
                    boxShadow: "0 4px 16px rgba(10,10,62,0.06)",
                  }}
                >
                  {feature.icon}
                </div>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    fontWeight: "600",
                    color: "#0A0A3E",
                    letterSpacing: "0.5px",
                  }}
                >
                  {feature.label}
                </p>
                <p
                  style={{
                    fontFamily: "var(--font-hind)",
                    fontSize: "10px",
                    color: "#c9a84c",
                  }}
                >
                  {feature.labelBn}
                </p>
              </motion.div>
            ))}
          </div>

          {/* CTA */}
          <Link href="/community" style={{ textDecoration: "none" }}>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              style={{
                background: "#0A0A3E",
                color: "white",
                border: "none",
                borderRadius: "30px",
                padding: "14px 32px",
                fontSize: "11px",
                fontWeight: "700",
                cursor: "pointer",
                fontFamily: "var(--font-dm-sans)",
                letterSpacing: "2px",
                textTransform: "uppercase",
                display: "flex",
                alignItems: "center",
                gap: "8px",
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
              Join Our Community →
            </motion.button>
          </Link>
        </motion.div>
      </div>

      <style>{`
        @media (max-width: 768px) {
          div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
            gap: 32px !important;
          }
        }
      `}</style>
    </section>
  );
}
