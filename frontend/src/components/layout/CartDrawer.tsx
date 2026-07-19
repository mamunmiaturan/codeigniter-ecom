"use client";

import { motion, AnimatePresence } from "framer-motion";
import { X, ShoppingBag, Trash2, Plus, Minus } from "lucide-react";
import Link from "next/link";
import { useCartStore } from "@/store/cartStore";

function formatPrice(price: string | number) {
  const n = typeof price === "string" ? parseFloat(price) : price;
  return `৳ ${(n || 0).toLocaleString()}`;
}

export function CartDrawer() {
  const {
    isOpen,
    closeCart,
    items,
    itemCount,
    subtotal,
    total,
    removeItem,
    updateQuantity,
  } = useCartStore();

  return (
    <AnimatePresence>
      {isOpen && (
        <>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={closeCart}
            style={{
              position: "fixed",
              inset: 0,
              background: "rgba(10,10,62,0.5)",
              zIndex: 300,
              backdropFilter: "blur(4px)",
            }}
          />

          {/* Drawer */}
          <motion.div
            initial={{ x: "100%" }}
            animate={{ x: 0 }}
            exit={{ x: "100%" }}
            transition={{ type: "spring", damping: 30, stiffness: 300 }}
            style={{
              position: "fixed",
              top: 0,
              right: 0,
              bottom: 0,
              width: "100%",
              maxWidth: "420px",
              background: "#faf6f0",
              zIndex: 301,
              display: "flex",
              flexDirection: "column",
              boxShadow: "-4px 0 60px rgba(10,10,62,0.15)",
            }}
          >
            {/* Header */}
            <div
              style={{
                padding: "24px",
                borderBottom: "1px solid #e8ddd5",
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between",
                background: "#faf6f0",
              }}
            >
              <div
                style={{ display: "flex", alignItems: "center", gap: "10px" }}
              >
                <ShoppingBag size={20} color="#E8470A" strokeWidth={1.5} />
                <span
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontSize: "1.2rem",
                    fontWeight: "700",
                    color: "#0A0A3E",
                  }}
                >
                  Your Cart
                  {itemCount > 0 && (
                    <span
                      style={{
                        marginLeft: "8px",
                        fontSize: "0.85rem",
                        fontFamily: "var(--font-dm-sans)",
                        fontWeight: "400",
                        color: "#7a6e65",
                      }}
                    >
                      ({itemCount} {itemCount === 1 ? "item" : "items"})
                    </span>
                  )}
                </span>
              </div>
              <button
                onClick={closeCart}
                style={{
                  background: "transparent",
                  border: "none",
                  cursor: "pointer",
                  padding: "8px",
                  borderRadius: "8px",
                  display: "flex",
                  transition: "background 0.2s ease",
                }}
                onMouseEnter={(e) =>
                  (e.currentTarget.style.background = "rgba(10,10,62,0.06)")
                }
                onMouseLeave={(e) =>
                  (e.currentTarget.style.background = "transparent")
                }
              >
                <X size={20} color="#7a6e65" strokeWidth={1.5} />
              </button>
            </div>

            {/* Items */}
            <div
              style={{
                flex: 1,
                overflowY: "auto",
                padding: "16px 24px",
              }}
            >
              {items.length === 0 ? (
                <div
                  style={{
                    display: "flex",
                    flexDirection: "column",
                    alignItems: "center",
                    justifyContent: "center",
                    height: "100%",
                    gap: "16px",
                    textAlign: "center",
                  }}
                >
                  <div
                    style={{
                      width: "80px",
                      height: "80px",
                      background: "white",
                      borderRadius: "50%",
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                      fontSize: "2.5rem",
                      boxShadow: "0 4px 20px rgba(10,10,62,0.08)",
                    }}
                  >
                    🛍️
                  </div>
                  <div>
                    <p
                      style={{
                        fontFamily: "var(--font-playfair)",
                        fontSize: "1.2rem",
                        fontWeight: "600",
                        color: "#0A0A3E",
                        marginBottom: "6px",
                      }}
                    >
                      Your cart is empty
                    </p>
                    <p
                      style={{
                        fontFamily: "var(--font-hind)",
                        fontSize: "13px",
                        color: "#7a6e65",
                      }}
                    >
                      আপনার কার্ট খালি আছে
                    </p>
                  </div>
                  <button
                    onClick={closeCart}
                    style={{
                      background: "#E8470A",
                      color: "white",
                      border: "none",
                      borderRadius: "25px",
                      padding: "12px 28px",
                      fontSize: "11px",
                      fontWeight: "700",
                      cursor: "pointer",
                      letterSpacing: "1.5px",
                      textTransform: "uppercase",
                      fontFamily: "var(--font-dm-sans)",
                      boxShadow: "0 8px 24px rgba(232,71,10,0.3)",
                      marginTop: "8px",
                    }}
                  >
                    Start Shopping
                  </button>
                </div>
              ) : (
                <AnimatePresence>
                  {items.map((item) => (
                    <motion.div
                      key={item.id}
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, x: 40 }}
                      style={{
                        display: "flex",
                        gap: "12px",
                        padding: "16px",
                        marginBottom: "12px",
                        background: "white",
                        borderRadius: "16px",
                        border: "1px solid #e8ddd5",
                        boxShadow: "0 2px 12px rgba(10,10,62,0.04)",
                      }}
                    >
                      {/* Image */}
                      <div
                        style={{
                          width: "72px",
                          height: "72px",
                          borderRadius: "12px",
                          background:
                            "linear-gradient(135deg, #faf6f0, #f5e8e6)",
                          display: "flex",
                          alignItems: "center",
                          justifyContent: "center",
                          fontSize: "2rem",
                          flexShrink: 0,
                          overflow: "hidden",
                          border: "1px solid #e8ddd5",
                        }}
                      >
                        {item.product.thumbnail ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img
                            src={item.product.thumbnail}
                            alt={item.product.name}
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

                      {/* Info */}
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <p
                          style={{
                            fontFamily: "var(--font-playfair)",
                            fontWeight: "700",
                            fontSize: "13px",
                            color: "#0A0A3E",
                            marginBottom: "2px",
                            overflow: "hidden",
                            textOverflow: "ellipsis",
                            whiteSpace: "nowrap",
                          }}
                        >
                          {item.product.name}
                        </p>
                        {item.variant && (
                          <p
                            style={{
                              fontFamily: "var(--font-hind)",
                              fontSize: "11px",
                              color: "#c9a84c",
                              marginBottom: "6px",
                            }}
                          >
                            {item.variant.name}
                          </p>
                        )}

                        <div
                          style={{
                            display: "flex",
                            alignItems: "center",
                            justifyContent: "space-between",
                          }}
                        >
                          {/* Quantity */}
                          <div
                            style={{
                              display: "flex",
                              alignItems: "center",
                              gap: "8px",
                              background: "#faf6f0",
                              borderRadius: "20px",
                              border: "1px solid #e8ddd5",
                              padding: "4px 8px",
                            }}
                          >
                            <button
                              onClick={() =>
                                updateQuantity(item.id, item.quantity - 1)
                              }
                              style={{
                                background: "transparent",
                                border: "none",
                                cursor: "pointer",
                                display: "flex",
                                padding: "2px",
                                color: "#0A0A3E",
                              }}
                            >
                              <Minus size={12} strokeWidth={2} />
                            </button>
                            <span
                              style={{
                                fontSize: "13px",
                                fontWeight: "700",
                                minWidth: "16px",
                                textAlign: "center",
                                color: "#0A0A3E",
                                fontFamily: "var(--font-dm-sans)",
                              }}
                            >
                              {item.quantity}
                            </span>
                            <button
                              onClick={() =>
                                updateQuantity(item.id, item.quantity + 1)
                              }
                              style={{
                                background: "transparent",
                                border: "none",
                                cursor: "pointer",
                                display: "flex",
                                padding: "2px",
                                color: "#0A0A3E",
                              }}
                            >
                              <Plus size={12} strokeWidth={2} />
                            </button>
                          </div>

                          <div
                            style={{
                              display: "flex",
                              alignItems: "center",
                              gap: "10px",
                            }}
                          >
                            <span
                              style={{
                                fontFamily: "var(--font-playfair)",
                                fontWeight: "700",
                                fontSize: "14px",
                                color: "#0A0A3E",
                              }}
                            >
                              {formatPrice(item.line_total)}
                            </span>
                            <button
                              onClick={() => removeItem(item.id)}
                              style={{
                                background: "transparent",
                                border: "none",
                                cursor: "pointer",
                                display: "flex",
                                padding: "4px",
                                transition: "color 0.2s ease",
                              }}
                              onMouseEnter={(e) =>
                                (e.currentTarget.style.color = "#E8470A")
                              }
                              onMouseLeave={(e) =>
                                (e.currentTarget.style.color = "#aaa")
                              }
                            >
                              <Trash2
                                size={14}
                                color="#aaa"
                                strokeWidth={1.5}
                              />
                            </button>
                          </div>
                        </div>
                      </div>
                    </motion.div>
                  ))}
                </AnimatePresence>
              )}
            </div>

            {/* Footer */}
            {items.length > 0 && (
              <div
                style={{
                  padding: "20px 24px",
                  borderTop: "1px solid #e8ddd5",
                  background: "#faf6f0",
                }}
              >
                <div
                  style={{
                    display: "flex",
                    justifyContent: "space-between",
                    marginBottom: "8px",
                    fontSize: "13px",
                    color: "#7a6e65",
                    fontFamily: "var(--font-dm-sans)",
                  }}
                >
                  <span>Subtotal</span>
                  <span>{formatPrice(subtotal)}</span>
                </div>
                <div
                  style={{
                    display: "flex",
                    justifyContent: "space-between",
                    marginBottom: "16px",
                    fontSize: "13px",
                    fontFamily: "var(--font-dm-sans)",
                  }}
                >
                  <span style={{ color: "#7a6e65" }}>Delivery</span>
                  <span style={{ color: "#22c55e", fontWeight: "600" }}>
                    Free
                  </span>
                </div>
                <div
                  style={{
                    display: "flex",
                    justifyContent: "space-between",
                    marginBottom: "20px",
                    paddingTop: "12px",
                    borderTop: "1px solid #e8ddd5",
                  }}
                >
                  <span
                    style={{
                      fontFamily: "var(--font-playfair)",
                      fontSize: "1.1rem",
                      fontWeight: "700",
                      color: "#0A0A3E",
                    }}
                  >
                    Total
                  </span>
                  <span
                    style={{
                      fontFamily: "var(--font-playfair)",
                      fontSize: "1.1rem",
                      fontWeight: "700",
                      color: "#E8470A",
                    }}
                  >
                    {formatPrice(total)}
                  </span>
                </div>

                <Link
                  href="/checkout"
                  onClick={closeCart}
                  style={{ textDecoration: "none", display: "block" }}
                >
                  <button
                    style={{
                      width: "100%",
                      background: "#0A0A3E",
                      color: "white",
                      border: "none",
                      borderRadius: "25px",
                      padding: "16px",
                      fontSize: "11px",
                      fontWeight: "700",
                      cursor: "pointer",
                      marginBottom: "10px",
                      letterSpacing: "2px",
                      textTransform: "uppercase",
                      fontFamily: "var(--font-dm-sans)",
                      transition: "all 0.3s ease",
                      boxShadow: "0 8px 24px rgba(10,10,62,0.2)",
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.background = "#E8470A";
                      e.currentTarget.style.boxShadow =
                        "0 8px 24px rgba(232,71,10,0.3)";
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.background = "#0A0A3E";
                      e.currentTarget.style.boxShadow =
                        "0 8px 24px rgba(10,10,62,0.2)";
                    }}
                  >
                    Proceed to Checkout
                  </button>
                </Link>

                <button
                  onClick={closeCart}
                  style={{
                    width: "100%",
                    background: "transparent",
                    color: "#7a6e65",
                    border: "1.5px solid #e8ddd5",
                    borderRadius: "25px",
                    padding: "14px",
                    fontSize: "11px",
                    fontWeight: "600",
                    cursor: "pointer",
                    letterSpacing: "1px",
                    textTransform: "uppercase",
                    fontFamily: "var(--font-dm-sans)",
                    transition: "all 0.2s ease",
                  }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.borderColor = "#0A0A3E";
                    e.currentTarget.style.color = "#0A0A3E";
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.borderColor = "#e8ddd5";
                    e.currentTarget.style.color = "#7a6e65";
                  }}
                >
                  Continue Shopping
                </button>

                <p
                  style={{
                    fontFamily: "var(--font-hind)",
                    textAlign: "center",
                    fontSize: "11px",
                    color: "#c9a84c",
                    marginTop: "12px",
                  }}
                >
                  ভালোবাসার সাথে প্যাক করা হয়েছে ♡
                </p>
              </div>
            )}
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );
}
