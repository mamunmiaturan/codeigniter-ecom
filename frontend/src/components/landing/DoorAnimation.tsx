"use client";

import { useEffect, useRef, useState } from "react";
import {
  motion,
  useScroll,
  useTransform,
  AnimatePresence,
} from "framer-motion";
import { useRouter } from "next/navigation";

export function DoorAnimation() {
  const router = useRouter();
  const containerRef = useRef<HTMLDivElement>(null);
  const [entered, setEntered] = useState(false);
  const [showHint, setShowHint] = useState(true);

  const { scrollYProgress } = useScroll({
    target: containerRef,
    offset: ["start start", "end start"],
  });

  // Door panels
  const leftDoorX = useTransform(scrollYProgress, [0, 0.6], ["0%", "-100%"]);
  const rightDoorX = useTransform(scrollYProgress, [0, 0.6], ["0%", "100%"]);

  // Light behind door
  const lightOpacity = useTransform(
    scrollYProgress,
    [0, 0.3, 0.6],
    [0, 0.5, 1],
  );
  const lightScale = useTransform(scrollYProgress, [0, 0.6], [0.8, 1.2]);

  // Logo reveal
  const logoOpacity = useTransform(scrollYProgress, [0.3, 0.6], [0, 1]);
  const logoScale = useTransform(scrollYProgress, [0.3, 0.6], [0.8, 1]);

  // Overlay fade
  const overlayOpacity = useTransform(scrollYProgress, [0.55, 0.7], [0, 1]);

  useEffect(() => {
    const unsubscribe = scrollYProgress.on("change", (v) => {
      if (v > 0.1) setShowHint(false);
      if (v >= 0.68 && !entered) {
        setEntered(true);
        setTimeout(() => router.push("/"), 600);
      }
    });
    return () => unsubscribe();
  }, [scrollYProgress, entered, router]);

  return (
    <div
      ref={containerRef}
      style={{
        height: "300vh",
        position: "relative",
      }}
    >
      {/* Sticky viewport */}
      <div
        style={{
          position: "sticky",
          top: 0,
          height: "100vh",
          overflow: "hidden",
          background: "#0A0A3E",
        }}
      >
        {/* Background stars/particles */}
        <div
          style={{
            position: "absolute",
            inset: 0,
            background:
              "radial-gradient(ellipse at center, #12124e 0%, #0A0A3E 70%)",
            zIndex: 0,
          }}
        >
          {Array.from({ length: 40 }).map((_, i) => (
            <motion.div
              key={i}
              animate={{
                opacity: [0.1, 0.6, 0.1],
                scale: [1, 1.2, 1],
              }}
              transition={{
                duration: 2 + Math.random() * 3,
                repeat: Infinity,
                delay: Math.random() * 3,
              }}
              style={{
                position: "absolute",
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
                width: `${1 + Math.random() * 2}px`,
                height: `${1 + Math.random() * 2}px`,
                borderRadius: "50%",
                background: "#c9a84c",
                opacity: 0.2,
              }}
            />
          ))}
        </div>

        {/* Light behind door */}
        <motion.div
          style={{
            position: "absolute",
            inset: 0,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 1,
            opacity: lightOpacity,
            scale: lightScale,
          }}
        >
          <div
            style={{
              width: "400px",
              height: "580px",
              background:
                "radial-gradient(ellipse at center, rgba(212,168,76,0.6) 0%, rgba(250,246,240,0.4) 40%, transparent 70%)",
              filter: "blur(20px)",
            }}
          />
        </motion.div>

        {/* BabuSona Logo behind door */}
        <motion.div
          style={{
            position: "absolute",
            inset: 0,
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 2,
            opacity: logoOpacity,
            scale: logoScale,
          }}
        >
          <div style={{ textAlign: "center" }}>
            <div
              style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                gap: "4px",
              }}
            >
              <span
                style={{
                  fontFamily: "var(--font-playfair)",
                  fontSize: "clamp(3rem, 6vw, 5rem)",
                  fontWeight: "700",
                  color: "white",
                  letterSpacing: "-1px",
                  lineHeight: 1,
                }}
              >
                Babu
              </span>
              <span
                style={{
                  fontFamily: "var(--font-playfair)",
                  fontSize: "clamp(3rem, 6vw, 5rem)",
                  fontWeight: "700",
                  color: "#c9a84c",
                  fontStyle: "italic",
                  letterSpacing: "-1px",
                  lineHeight: 1,
                }}
              >
                Sona
              </span>
              <span
                style={{
                  color: "#E8470A",
                  fontSize: "2rem",
                  marginLeft: "4px",
                }}
              >
                ♡
              </span>
            </div>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "11px",
                color: "#c9a84c",
                letterSpacing: "4px",
                textTransform: "uppercase",
                marginTop: "8px",
              }}
            >
              LOVE. CARE. EVERYDAY.
            </p>
            <p
              style={{
                fontFamily: "var(--font-hind)",
                fontSize: "16px",
                color: "rgba(255,255,255,0.7)",
                marginTop: "12px",
              }}
            >
              ভালোবাসা, খুনসুটি আর যতু
            </p>
          </div>
        </motion.div>

        {/* Door Frame */}
        <div
          style={{
            position: "absolute",
            inset: 0,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 3,
            pointerEvents: "none",
          }}
        >
          <div
            style={{
              position: "relative",
              width: "clamp(280px, 35vw, 420px)",
              height: "clamp(420px, 65vh, 620px)",
            }}
          >
            {/* Outer frame */}
            <div
              style={{
                position: "absolute",
                inset: "-20px",
                border: "3px solid #c9a84c",
                borderRadius: "8px",
                boxShadow:
                  "0 0 40px rgba(201,168,76,0.3), inset 0 0 40px rgba(201,168,76,0.05)",
              }}
            >
              {/* Corner ornaments */}
              {[
                { top: "-8px", left: "-8px" },
                { top: "-8px", right: "-8px" },
                { bottom: "-8px", left: "-8px" },
                { bottom: "-8px", right: "-8px" },
              ].map((pos, i) => (
                <div
                  key={i}
                  style={{
                    position: "absolute",
                    width: "16px",
                    height: "16px",
                    background: "#c9a84c",
                    borderRadius: "50%",
                    ...pos,
                  }}
                />
              ))}
            </div>

            {/* Inner frame decorative line */}
            <div
              style={{
                position: "absolute",
                inset: "-12px",
                border: "1px solid rgba(201,168,76,0.3)",
                borderRadius: "6px",
              }}
            />
          </div>
        </div>

        {/* LEFT DOOR PANEL */}
        <motion.div
          style={{
            position: "absolute",
            top: 0,
            left: 0,
            right: "50%",
            bottom: 0,
            x: leftDoorX,
            zIndex: 4,
            transformOrigin: "left center",
          }}
        >
          <div
            style={{
              width: "100%",
              height: "100%",
              background:
                "linear-gradient(135deg, #0d0d45 0%, #0A0A3E 50%, #080830 100%)",
              display: "flex",
              alignItems: "center",
              justifyContent: "flex-end",
              position: "relative",
              overflow: "hidden",
            }}
          >
            {/* Door wood texture lines */}
            {Array.from({ length: 8 }).map((_, i) => (
              <div
                key={i}
                style={{
                  position: "absolute",
                  left: 0,
                  right: 0,
                  top: `${10 + i * 11}%`,
                  height: "1px",
                  background: "rgba(201,168,76,0.08)",
                }}
              />
            ))}

            {/* Door panel design */}
            <div
              style={{
                position: "absolute",
                inset: "clamp(20px, 4vw, 40px)",
                right: "clamp(10px, 2vw, 20px)",
                border: "1px solid rgba(201,168,76,0.25)",
                borderRadius: "4px",
                display: "flex",
                flexDirection: "column",
                gap: "8px",
                padding: "clamp(10px, 2vw, 20px)",
              }}
            >
              {/* Upper panel */}
              <div
                style={{
                  flex: 1,
                  border: "1px solid rgba(201,168,76,0.15)",
                  borderRadius: "3px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  position: "relative",
                  overflow: "hidden",
                }}
              >
                {/* Ornate design */}
                <svg width="60%" height="60%" viewBox="0 0 100 100" fill="none">
                  <circle
                    cx="50"
                    cy="50"
                    r="30"
                    stroke="rgba(201,168,76,0.3)"
                    strokeWidth="1"
                  />
                  <circle
                    cx="50"
                    cy="50"
                    r="20"
                    stroke="rgba(201,168,76,0.2)"
                    strokeWidth="0.5"
                  />
                  <path
                    d="M50 20 L80 50 L50 80 L20 50 Z"
                    stroke="rgba(201,168,76,0.25)"
                    strokeWidth="0.5"
                    fill="none"
                  />
                  <circle cx="50" cy="50" r="5" fill="rgba(201,168,76,0.4)" />
                </svg>
              </div>

              {/* Lower panel */}
              <div
                style={{
                  flex: 1.5,
                  border: "1px solid rgba(201,168,76,0.15)",
                  borderRadius: "3px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                <svg width="50%" height="50%" viewBox="0 0 100 60" fill="none">
                  <path
                    d="M10 30 Q50 5 90 30 Q50 55 10 30Z"
                    stroke="rgba(201,168,76,0.25)"
                    strokeWidth="0.5"
                    fill="rgba(201,168,76,0.05)"
                  />
                  <path
                    d="M25 30 Q50 15 75 30 Q50 45 25 30Z"
                    stroke="rgba(201,168,76,0.2)"
                    strokeWidth="0.5"
                    fill="none"
                  />
                </svg>
              </div>
            </div>

            {/* Right edge gold line */}
            <div
              style={{
                position: "absolute",
                top: 0,
                right: 0,
                bottom: 0,
                width: "3px",
                background:
                  "linear-gradient(to bottom, transparent, #c9a84c, #E8470A, #c9a84c, transparent)",
                boxShadow: "0 0 20px rgba(201,168,76,0.5)",
              }}
            />
          </div>
        </motion.div>

        {/* RIGHT DOOR PANEL */}
        <motion.div
          style={{
            position: "absolute",
            top: 0,
            left: "50%",
            right: 0,
            bottom: 0,
            x: rightDoorX,
            zIndex: 4,
            transformOrigin: "right center",
          }}
        >
          <div
            style={{
              width: "100%",
              height: "100%",
              background:
                "linear-gradient(225deg, #0d0d45 0%, #0A0A3E 50%, #080830 100%)",
              display: "flex",
              alignItems: "center",
              justifyContent: "flex-start",
              position: "relative",
              overflow: "hidden",
            }}
          >
            {/* Door wood texture lines */}
            {Array.from({ length: 8 }).map((_, i) => (
              <div
                key={i}
                style={{
                  position: "absolute",
                  left: 0,
                  right: 0,
                  top: `${10 + i * 11}%`,
                  height: "1px",
                  background: "rgba(201,168,76,0.08)",
                }}
              />
            ))}

            {/* Door panel design */}
            <div
              style={{
                position: "absolute",
                inset: "clamp(20px, 4vw, 40px)",
                left: "clamp(10px, 2vw, 20px)",
                border: "1px solid rgba(201,168,76,0.25)",
                borderRadius: "4px",
                display: "flex",
                flexDirection: "column",
                gap: "8px",
                padding: "clamp(10px, 2vw, 20px)",
              }}
            >
              {/* Upper panel */}
              <div
                style={{
                  flex: 1,
                  border: "1px solid rgba(201,168,76,0.15)",
                  borderRadius: "3px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                <svg width="60%" height="60%" viewBox="0 0 100 100" fill="none">
                  <circle
                    cx="50"
                    cy="50"
                    r="30"
                    stroke="rgba(201,168,76,0.3)"
                    strokeWidth="1"
                  />
                  <circle
                    cx="50"
                    cy="50"
                    r="20"
                    stroke="rgba(201,168,76,0.2)"
                    strokeWidth="0.5"
                  />
                  <path
                    d="M50 20 L80 50 L50 80 L20 50 Z"
                    stroke="rgba(201,168,76,0.25)"
                    strokeWidth="0.5"
                    fill="none"
                  />
                  <circle cx="50" cy="50" r="5" fill="rgba(201,168,76,0.4)" />
                </svg>
              </div>

              {/* Door handle area */}
              <div
                style={{
                  position: "absolute",
                  left: "clamp(8px, 2vw, 16px)",
                  top: "50%",
                  transform: "translateY(-50%)",
                  display: "flex",
                  flexDirection: "column",
                  alignItems: "center",
                  gap: "6px",
                }}
              >
                {/* Handle */}
                <div
                  style={{
                    width: "clamp(14px, 2vw, 20px)",
                    height: "clamp(40px, 6vh, 60px)",
                    background:
                      "linear-gradient(to bottom, #c9a84c, #E8470A, #c9a84c)",
                    borderRadius: "10px",
                    boxShadow: "0 4px 20px rgba(201,168,76,0.5)",
                  }}
                />
                <div
                  style={{
                    width: "clamp(20px, 3vw, 28px)",
                    height: "clamp(20px, 3vw, 28px)",
                    borderRadius: "50%",
                    background: "radial-gradient(circle, #c9a84c, #8B6914)",
                    boxShadow: "0 4px 16px rgba(201,168,76,0.6)",
                  }}
                />
              </div>

              {/* Lower panel */}
              <div
                style={{
                  flex: 1.5,
                  border: "1px solid rgba(201,168,76,0.15)",
                  borderRadius: "3px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                <svg width="50%" height="50%" viewBox="0 0 100 60" fill="none">
                  <path
                    d="M10 30 Q50 5 90 30 Q50 55 10 30Z"
                    stroke="rgba(201,168,76,0.25)"
                    strokeWidth="0.5"
                    fill="rgba(201,168,76,0.05)"
                  />
                  <path
                    d="M25 30 Q50 15 75 30 Q50 45 25 30Z"
                    stroke="rgba(201,168,76,0.2)"
                    strokeWidth="0.5"
                    fill="none"
                  />
                </svg>
              </div>
            </div>

            {/* Left edge gold line */}
            <div
              style={{
                position: "absolute",
                top: 0,
                left: 0,
                bottom: 0,
                width: "3px",
                background:
                  "linear-gradient(to bottom, transparent, #c9a84c, #E8470A, #c9a84c, transparent)",
                boxShadow: "0 0 20px rgba(201,168,76,0.5)",
              }}
            />
          </div>
        </motion.div>

        {/* White transition overlay */}
        <motion.div
          style={{
            position: "absolute",
            inset: 0,
            background: "#f7f0e6",
            zIndex: 10,
            opacity: overlayOpacity,
            pointerEvents: "none",
          }}
        />

        {/* Scroll hint */}
        <AnimatePresence>
          {showHint && (
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ delay: 1.5 }}
              style={{
                position: "absolute",
                bottom: "40px",
                left: 0,
                right: 0,
                display: "flex",
                flexDirection: "column",
                alignItems: "center",
                gap: "8px",
                zIndex: 5,
                pointerEvents: "none",
              }}
            >
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: "600",
                  letterSpacing: "3px",
                  textTransform: "uppercase",
                  color: "rgba(201,168,76,0.8)",
                }}
              >
                Scroll to Enter
              </p>
              <motion.div
                animate={{ y: [0, 8, 0] }}
                transition={{ duration: 1.5, repeat: Infinity }}
                style={{
                  width: "1px",
                  height: "40px",
                  background:
                    "linear-gradient(to bottom, rgba(201,168,76,0.8), transparent)",
                }}
              />
            </motion.div>
          )}
        </AnimatePresence>

        {/* Top label */}
        <div
          style={{
            position: "absolute",
            top: "40px",
            left: 0,
            right: 0,
            textAlign: "center",
            zIndex: 5,
            pointerEvents: "none",
          }}
        >
          <motion.p
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.5 }}
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "clamp(13px, 2vw, 16px)",
              color: "rgba(201,168,76,0.7)",
              letterSpacing: "1px",
            }}
          >
            ভালোবাসা, খুনসুটি আর যতু — সবই বাবুসোনা।
          </motion.p>
        </div>
      </div>
    </div>
  );
}
