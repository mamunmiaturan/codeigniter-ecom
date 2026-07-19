"use client";

import { useEffect, useState } from "react";
import { motion } from "framer-motion";

export function CustomCursor() {
  const [position, setPosition] = useState({ x: 0, y: 0 });
  const [dotPosition, setDotPosition] = useState({ x: 0, y: 0 });
  const [isHovering, setIsHovering] = useState(false);
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      setPosition({ x: e.clientX, y: e.clientY });
      setIsVisible(true);
    };

    const handleMouseOver = (e: MouseEvent) => {
      const target = e.target as HTMLElement;
      if (
        target.tagName === "BUTTON" ||
        target.tagName === "A" ||
        target.closest("button") ||
        target.closest("a")
      ) {
        setIsHovering(true);
      } else {
        setIsHovering(false);
      }
    };

    const handleMouseLeave = () => {
      setIsVisible(false);
    };

    window.addEventListener("mousemove", handleMouseMove);
    window.addEventListener("mouseover", handleMouseOver);
    document.documentElement.addEventListener("mouseleave", handleMouseLeave);

    return () => {
      window.removeEventListener("mousemove", handleMouseMove);
      window.removeEventListener("mouseover", handleMouseOver);
      document.documentElement.removeEventListener(
        "mouseleave",
        handleMouseLeave,
      );
    };
  }, []);

  if (typeof window !== "undefined" && window.innerWidth < 768) {
    return null;
  }

  return (
    <>
      {/* Outer Ring */}
      <motion.div
        animate={{
          x: position.x - 20,
          y: position.y - 20,
          scale: isHovering ? 1.8 : 1,
          opacity: isVisible ? 1 : 0,
        }}
        transition={{
          type: "spring",
          stiffness: 150,
          damping: 20,
          mass: 0.5,
        }}
        style={{
          position: "fixed",
          top: 0,
          left: 0,
          width: "40px",
          height: "40px",
          borderRadius: "50%",
          border: `1.5px solid ${isHovering ? "#e91e8c" : "rgba(26,26,26,0.4)"}`,
          pointerEvents: "none",
          zIndex: 9999,
          mixBlendMode: "difference",
        }}
      />

      {/* Inner Dot */}
      <motion.div
        animate={{
          x: position.x - 4,
          y: position.y - 4,
          scale: isHovering ? 0 : 1,
          opacity: isVisible ? 1 : 0,
        }}
        transition={{
          type: "spring",
          stiffness: 300,
          damping: 25,
          mass: 0.3,
        }}
        style={{
          position: "fixed",
          top: 0,
          left: 0,
          width: "8px",
          height: "8px",
          borderRadius: "50%",
          background: "#e91e8c",
          pointerEvents: "none",
          zIndex: 9999,
        }}
      />
    </>
  );
}
