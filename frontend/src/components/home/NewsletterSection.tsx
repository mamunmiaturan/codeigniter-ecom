"use client";

import { useState } from "react";
import { motion } from "framer-motion";

export function NewsletterSection() {
  const [email, setEmail] = useState("");
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = () => {
    if (email) {
      setSubmitted(true);
      setEmail("");
    }
  };

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
        {/* Left — Image + Text */}
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
        >
          <div
            style={{
              borderRadius: "24px",
              overflow: "hidden",
              aspectRatio: "4/3",
              marginBottom: "24px",
              boxShadow: "0 20px 60px rgba(10,10,62,0.12)",
            }}
          >
            <img
              src="/images/home/4.jpeg"
              alt="BabuSona Family"
              style={{
                width: "100%",
                height: "100%",
                objectFit: "cover",
                objectPosition: "center",
              }}
            />
          </div>

          <h3
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "1.6rem",
              fontWeight: "700",
              color: "#0A0A3E",
              marginBottom: "8px",
            }}
          >
            বাবুসোনা মানে—
          </h3>
          <p
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "14px",
              color: "#7a6e65",
              lineHeight: "1.8",
            }}
          >
            ভালোবাসা, খুনসুটি আর যতু।
            <br />
            সব কিছু একসাথে, একটাই জায়গায়।
          </p>
        </motion.div>

        {/* Right — Newsletter */}
        <motion.div
          initial={{ opacity: 0, x: 20 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.1 }}
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
            Stay Connected
          </p>

          <h2
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "clamp(1.6rem, 3vw, 2.2rem)",
              fontWeight: "700",
              color: "#0A0A3E",
              lineHeight: "1.3",
              marginBottom: "12px",
              letterSpacing: "-0.5px",
            }}
          >
            Stay Connected with BabuSona
          </h2>

          <p
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "14px",
              color: "#7a6e65",
              lineHeight: "1.8",
              marginBottom: "8px",
            }}
          >
            নতুন কালেকশন, অফার, টিপস আর হৃদয় ছুঁয়ে যাওয়া গল্প — সব পাবেন
            আপনার ইনবক্সে।
          </p>

          <p
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "13px",
              color: "#c9a84c",
              marginBottom: "32px",
            }}
          >
            New arrivals, offers & heartwarming stories — in your inbox.
          </p>

          {!submitted ? (
            <div style={{ display: "flex", gap: "12px" }}>
              <input
                type="email"
                placeholder="Enter your email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && handleSubmit()}
                style={{
                  flex: 1,
                  padding: "14px 18px",
                  borderRadius: "12px",
                  border: "1.5px solid #e8ddd5",
                  outline: "none",
                  fontSize: "14px",
                  fontFamily: "var(--font-dm-sans)",
                  color: "#0A0A3E",
                  background: "white",
                  transition: "border 0.2s ease",
                  boxShadow: "0 4px 16px rgba(10,10,62,0.04)",
                }}
                onFocus={(e) => (e.target.style.borderColor = "#E8470A")}
                onBlur={(e) => (e.target.style.borderColor = "#e8ddd5")}
              />
              <motion.button
                whileTap={{ scale: 0.97 }}
                onClick={handleSubmit}
                style={{
                  background: "#E8470A",
                  color: "white",
                  border: "none",
                  borderRadius: "12px",
                  padding: "14px 24px",
                  fontSize: "11px",
                  fontWeight: "700",
                  cursor: "pointer",
                  fontFamily: "var(--font-dm-sans)",
                  whiteSpace: "nowrap",
                  letterSpacing: "1px",
                  textTransform: "uppercase",
                  boxShadow: "0 8px 24px rgba(232,71,10,0.3)",
                  transition: "all 0.2s ease",
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.background = "#c23a08";
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.background = "#E8470A";
                }}
              >
                Subscribe
              </motion.button>
            </div>
          ) : (
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              style={{
                background: "white",
                border: "1.5px solid #c9a84c",
                borderRadius: "12px",
                padding: "16px 20px",
                display: "flex",
                alignItems: "center",
                gap: "12px",
                boxShadow: "0 4px 16px rgba(201,168,76,0.1)",
              }}
            >
              <span style={{ fontSize: "1.5rem" }}>💕</span>
              <div>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "14px",
                    fontWeight: "700",
                    color: "#0A0A3E",
                    marginBottom: "2px",
                  }}
                >
                  You&apos;re in the family!
                </p>
                <p
                  style={{
                    fontFamily: "var(--font-hind)",
                    fontSize: "12px",
                    color: "#c9a84c",
                  }}
                >
                  বাবুসোনা পরিবারে আপনাকে স্বাগতম ❤️
                </p>
              </div>
            </motion.div>
          )}

          {/* Trust */}
          <div
            style={{
              display: "flex",
              gap: "24px",
              marginTop: "20px",
              flexWrap: "wrap",
            }}
          >
            {[
              "🔒 No spam, ever",
              "💕 Weekly love notes",
              "🎁 Exclusive offers",
            ].map((item, i) => (
              <p
                key={i}
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  color: "#7a6e65",
                  letterSpacing: "0.5px",
                }}
              >
                {item}
              </p>
            ))}
          </div>
        </motion.div>
      </div>

      {/* Brand Signature */}
      <motion.div
        initial={{ opacity: 0, y: 30 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        style={{
          maxWidth: "1300px",
          margin: "80px auto 0",
          textAlign: "center",
          paddingTop: "64px",
          borderTop: "1px solid #e8ddd5",
        }}
      >
        <p
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "13px",
            color: "#7a6e65",
            marginBottom: "16px",
            lineHeight: "1.8",
          }}
        >
          Because in every woman&apos;s heart, there are always one or two
          people she lovingly calls —
        </p>

        <h2
          style={{
            fontFamily: "var(--font-playfair)",
            fontSize: "clamp(2.5rem, 5vw, 4rem)",
            fontWeight: "700",
            color: "#0A0A3E",
            letterSpacing: "-1px",
            marginBottom: "8px",
          }}
        >
          Babu. Sona. <span style={{ color: "#E8470A" }}>♡</span>
        </h2>

        <p
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "11px",
            fontWeight: "700",
            letterSpacing: "4px",
            textTransform: "uppercase",
            color: "#c9a84c",
            marginBottom: "32px",
          }}
        >
          WE EXIST FOR THEM.
        </p>

        {/* Divider line */}
        <div
          style={{
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            gap: "16px",
          }}
        >
          <div
            style={{
              width: "60px",
              height: "1px",
              background: "#e8ddd5",
            }}
          />
          <span style={{ color: "#E8470A", fontSize: "1rem" }}>♡</span>
          <div
            style={{
              width: "60px",
              height: "1px",
              background: "#e8ddd5",
            }}
          />
        </div>
      </motion.div>

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
