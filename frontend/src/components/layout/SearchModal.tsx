"use client";

import { useState, useEffect, useRef } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Search, X, ArrowRight } from "lucide-react";
import Link from "next/link";
import { searchProducts } from "@/lib/api";
import { type Product } from "@/types";

interface SearchModalProps {
  isOpen: boolean;
  onClose: () => void;
}

const popularSearches = [
  "Leather Wallet",
  "Baby Blanket",
  "Embroidered Robe",
  "Memory Frame",
  "Surprise Box",
  "Grooming Kit",
];

export function SearchModal({ isOpen, onClose }: SearchModalProps) {
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<Product[]>([]);
  const inputRef = useRef<HTMLInputElement>(null);

  // Debounced live search against the backend catalog.
  useEffect(() => {
    if (query.trim().length <= 1) {
      setResults([]);
      return;
    }
    const controller = new AbortController();
    const timer = setTimeout(() => {
      searchProducts(query.trim(), controller.signal)
        .then(setResults)
        .catch((err) => {
          if (err?.name !== "AbortError") console.error(err);
        });
    }, 250);
    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [query]);

  useEffect(() => {
    if (isOpen) {
      setTimeout(() => inputRef.current?.focus(), 100);
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
      setQuery("");
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [isOpen]);

  useEffect(() => {
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    window.addEventListener("keydown", handleKey);
    return () => window.removeEventListener("keydown", handleKey);
  }, [onClose]);

  return (
    <AnimatePresence>
      {isOpen && (
        <>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={onClose}
            style={{
              position: "fixed",
              inset: 0,
              background: "rgba(10,10,62,0.5)",
              zIndex: 200,
              backdropFilter: "blur(4px)",
            }}
          />

          {/* Search Panel */}
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            transition={{ type: "spring", damping: 30, stiffness: 300 }}
            style={{
              position: "fixed",
              top: 0,
              left: 0,
              right: 0,
              zIndex: 201,
              background: "#faf6f0",
              boxShadow: "0 20px 60px rgba(10,10,62,0.15)",
              borderBottom: "1px solid #e8ddd5",
            }}
          >
            {/* Search Input */}
            <div
              style={{
                maxWidth: "800px",
                margin: "0 auto",
                padding: "24px clamp(1.5rem, 4vw, 5rem)",
              }}
            >
              <div
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: "16px",
                  background: "white",
                  borderRadius: "16px",
                  padding: "16px 20px",
                  border: "1.5px solid #E8470A",
                  boxShadow: "0 8px 32px rgba(232,71,10,0.1)",
                }}
              >
                <Search size={20} color="#E8470A" strokeWidth={1.5} />
                <input
                  ref={inputRef}
                  type="text"
                  placeholder="Search for products, gifts, collections..."
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                  style={{
                    flex: 1,
                    border: "none",
                    outline: "none",
                    fontSize: "16px",
                    fontFamily: "var(--font-dm-sans)",
                    color: "#0A0A3E",
                    background: "transparent",
                  }}
                />
                {query && (
                  <button
                    onClick={() => setQuery("")}
                    style={{
                      background: "transparent",
                      border: "none",
                      cursor: "pointer",
                      display: "flex",
                      padding: "4px",
                    }}
                  >
                    <X size={16} color="#7a6e65" />
                  </button>
                )}
                <button
                  onClick={onClose}
                  style={{
                    background: "transparent",
                    border: "none",
                    cursor: "pointer",
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    fontWeight: "700",
                    color: "#7a6e65",
                    letterSpacing: "1px",
                    padding: "4px 8px",
                  }}
                >
                  ESC
                </button>
              </div>

              {/* Results or Popular */}
              <div style={{ marginTop: "20px", paddingBottom: "24px" }}>
                {query.length > 1 ? (
                  <>
                    <p
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "10px",
                        fontWeight: "700",
                        letterSpacing: "2px",
                        textTransform: "uppercase",
                        color: "#7a6e65",
                        marginBottom: "12px",
                      }}
                    >
                      {results.length} Results for &quot;{query}&quot;
                    </p>

                    {results.length === 0 ? (
                      <div style={{ textAlign: "center", padding: "32px" }}>
                        <p
                          style={{
                            fontFamily: "var(--font-playfair)",
                            fontSize: "1.1rem",
                            color: "#0A0A3E",
                            marginBottom: "8px",
                          }}
                        >
                          No products found
                        </p>
                        <p
                          style={{
                            fontFamily: "var(--font-hind)",
                            fontSize: "13px",
                            color: "#7a6e65",
                          }}
                        >
                          কোনো পণ্য পাওয়া যায়নি
                        </p>
                      </div>
                    ) : (
                      <div
                        style={{
                          display: "flex",
                          flexDirection: "column",
                          gap: "8px",
                        }}
                      >
                        {results.map((product) => (
                          <Link
                            key={product.id}
                            href={`/shop/${product.slug}`}
                            onClick={onClose}
                            style={{ textDecoration: "none" }}
                          >
                            <motion.div
                              whileHover={{ x: 4 }}
                              style={{
                                display: "flex",
                                alignItems: "center",
                                gap: "16px",
                                padding: "12px 16px",
                                borderRadius: "12px",
                                background: "white",
                                border: "1px solid #e8ddd5",
                                cursor: "pointer",
                                transition: "all 0.2s ease",
                              }}
                              onMouseEnter={(e) => {
                                e.currentTarget.style.borderColor = "#E8470A";
                                e.currentTarget.style.boxShadow =
                                  "0 4px 16px rgba(232,71,10,0.1)";
                              }}
                              onMouseLeave={(e) => {
                                e.currentTarget.style.borderColor = "#e8ddd5";
                                e.currentTarget.style.boxShadow = "none";
                              }}
                            >
                              <div
                                style={{
                                  width: "48px",
                                  height: "48px",
                                  background:
                                    "linear-gradient(135deg, #faf6f0, #f5e8e6)",
                                  borderRadius: "10px",
                                  display: "flex",
                                  alignItems: "center",
                                  justifyContent: "center",
                                  fontSize: "1.5rem",
                                  overflow: "hidden",
                                  flexShrink: 0,
                                }}
                              >
                                {product.images[0] ? (
                                  // eslint-disable-next-line @next/next/no-img-element
                                  <img
                                    src={product.images[0]}
                                    alt={product.name}
                                    style={{
                                      width: "100%",
                                      height: "100%",
                                      objectFit: "cover",
                                    }}
                                  />
                                ) : (
                                  "🎁"
                                )}
                              </div>
                              <div style={{ flex: 1 }}>
                                <p
                                  style={{
                                    fontFamily: "var(--font-playfair)",
                                    fontSize: "14px",
                                    fontWeight: "700",
                                    color: "#0A0A3E",
                                    marginBottom: "2px",
                                  }}
                                >
                                  {product.name}
                                </p>
                                <p
                                  style={{
                                    fontFamily: "var(--font-hind)",
                                    fontSize: "11px",
                                    color: "#c9a84c",
                                  }}
                                >
                                  {product.nameBn}
                                </p>
                              </div>
                              <div style={{ textAlign: "right" }}>
                                <p
                                  style={{
                                    fontFamily: "var(--font-playfair)",
                                    fontSize: "14px",
                                    fontWeight: "700",
                                    color: "#E8470A",
                                  }}
                                >
                                  ৳ {product.price.toLocaleString()}
                                </p>
                              </div>
                              <ArrowRight size={14} color="#E8470A" />
                            </motion.div>
                          </Link>
                        ))}
                      </div>
                    )}
                  </>
                ) : (
                  <>
                    <p
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "10px",
                        fontWeight: "700",
                        letterSpacing: "2px",
                        textTransform: "uppercase",
                        color: "#7a6e65",
                        marginBottom: "12px",
                      }}
                    >
                      Popular Searches
                    </p>
                    <div
                      style={{
                        display: "flex",
                        flexWrap: "wrap",
                        gap: "8px",
                      }}
                    >
                      {popularSearches.map((term, i) => (
                        <button
                          key={i}
                          onClick={() => setQuery(term)}
                          style={{
                            background: "white",
                            border: "1px solid #e8ddd5",
                            borderRadius: "25px",
                            padding: "8px 16px",
                            fontSize: "12px",
                            fontWeight: "600",
                            color: "#0A0A3E",
                            cursor: "pointer",
                            fontFamily: "var(--font-dm-sans)",
                            transition: "all 0.2s ease",
                          }}
                          onMouseEnter={(e) => {
                            e.currentTarget.style.borderColor = "#E8470A";
                            e.currentTarget.style.color = "#E8470A";
                          }}
                          onMouseLeave={(e) => {
                            e.currentTarget.style.borderColor = "#e8ddd5";
                            e.currentTarget.style.color = "#0A0A3E";
                          }}
                        >
                          {term}
                        </button>
                      ))}
                    </div>
                  </>
                )}
              </div>
            </div>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );
}
