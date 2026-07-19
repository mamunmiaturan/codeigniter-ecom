"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { motion } from "framer-motion";
import { ChevronLeft, Heart, Trash2 } from "lucide-react";
import { useRequireAuth } from "@/lib/useRequireAuth";
import {
  fetchWishlist,
  removeWishlist,
  type WishlistItem,
} from "@/lib/api";
import { useWishlistStore } from "@/store/wishlistStore";

function taka(v: string) {
  return `৳ ${parseFloat(v).toLocaleString()}`;
}

export default function WishlistPage() {
  const { token, ready } = useRequireAuth();
  const [items, setItems] = useState<WishlistItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState<number | null>(null);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchWishlist(token)
      .then(setItems)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [token]);

  const remove = async (productId: number) => {
    if (!token) return;
    setBusy(productId);
    try {
      await removeWishlist(token, productId);
      setItems((prev) => prev.filter((i) => i.product_id !== productId));
      useWishlistStore.getState().load(); // keep hearts in sync elsewhere
    } catch (e) {
      console.error(e);
    } finally {
      setBusy(null);
    }
  };

  if (!ready) return null;

  return (
    <div
      style={{
        minHeight: "100vh",
        background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
        padding: "60px clamp(1.5rem, 4vw, 5rem)",
      }}
    >
      <div style={{ maxWidth: "800px", margin: "0 auto" }}>
        <Link
          href="/account"
          style={{
            display: "inline-flex",
            alignItems: "center",
            gap: "4px",
            textDecoration: "none",
            fontFamily: "var(--font-dm-sans)",
            fontSize: "12px",
            fontWeight: 600,
            letterSpacing: "1px",
            color: "#7a6e65",
            marginBottom: "24px",
          }}
        >
          <ChevronLeft size={14} /> Back to Account
        </Link>

        <h1
          style={{
            fontFamily: "var(--font-playfair)",
            fontSize: "1.8rem",
            fontWeight: 700,
            color: "#0A0A3E",
            marginBottom: "24px",
          }}
        >
          Wishlist
        </h1>

        {loading ? (
          <p style={{ fontFamily: "var(--font-dm-sans)", color: "#aaa" }}>
            Loading wishlist…
          </p>
        ) : items.length === 0 ? (
          <div
            style={{
              background: "white",
              borderRadius: "20px",
              border: "1px solid #e8ddd5",
              padding: "64px 24px",
              textAlign: "center",
            }}
          >
            <Heart
              size={40}
              color="#E8470A"
              strokeWidth={1.3}
              style={{ marginBottom: "16px" }}
            />
            <p
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "1.1rem",
                fontWeight: 700,
                color: "#0A0A3E",
                marginBottom: "8px",
              }}
            >
              Your wishlist is empty
            </p>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                color: "#7a6e65",
                marginBottom: "20px",
              }}
            >
              Save your favourites and find them here.
            </p>
            <Link href="/shop" style={{ textDecoration: "none" }}>
              <span
                style={{
                  display: "inline-block",
                  background: "#0A0A3E",
                  color: "white",
                  borderRadius: "25px",
                  padding: "12px 28px",
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: 700,
                  letterSpacing: "1.5px",
                  textTransform: "uppercase",
                }}
              >
                Browse Products
              </span>
            </Link>
          </div>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: "12px" }}>
            {items.map((item, i) => (
              <motion.div
                key={item.product_id}
                initial={{ opacity: 0, y: 16 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: i * 0.05 }}
                style={{
                  background: "white",
                  borderRadius: "16px",
                  border: "1px solid #e8ddd5",
                  padding: "16px",
                  display: "flex",
                  alignItems: "center",
                  gap: "16px",
                }}
              >
                <Link
                  href={`/shop/${item.slug}`}
                  style={{ textDecoration: "none", flexShrink: 0 }}
                >
                  <div
                    style={{
                      width: "64px",
                      height: "64px",
                      borderRadius: "12px",
                      overflow: "hidden",
                      background: "linear-gradient(135deg, #faf6f0, #f5e8e6)",
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                      fontSize: "1.6rem",
                    }}
                  >
                    {item.thumbnail ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img
                        src={item.thumbnail}
                        alt={item.name}
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
                </Link>

                <div style={{ flex: 1, minWidth: 0 }}>
                  <Link
                    href={`/shop/${item.slug}`}
                    style={{ textDecoration: "none" }}
                  >
                    <p
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontWeight: 600,
                        fontSize: "14px",
                        color: "#0A0A3E",
                        overflow: "hidden",
                        textOverflow: "ellipsis",
                        whiteSpace: "nowrap",
                      }}
                    >
                      {item.name}
                    </p>
                  </Link>
                  <p
                    style={{
                      fontFamily: "var(--font-playfair)",
                      fontWeight: 700,
                      fontSize: "15px",
                      color: "#E8470A",
                      marginTop: "2px",
                    }}
                  >
                    {taka(item.effective_price)}
                    {item.special_price && (
                      <span
                        style={{
                          fontFamily: "var(--font-dm-sans)",
                          fontSize: "11px",
                          color: "#bbb",
                          textDecoration: "line-through",
                          marginLeft: "8px",
                        }}
                      >
                        {taka(item.price)}
                      </span>
                    )}
                  </p>
                  {item.stock_status !== "in_stock" && (
                    <span
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "11px",
                        color: "#c23a08",
                      }}
                    >
                      Out of stock
                    </span>
                  )}
                </div>

                <button
                  onClick={() => remove(item.product_id)}
                  disabled={busy === item.product_id}
                  title="Remove"
                  style={{
                    background: "transparent",
                    border: "1px solid #e8ddd5",
                    borderRadius: "10px",
                    width: "40px",
                    height: "40px",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    cursor: busy === item.product_id ? "wait" : "pointer",
                    color: "#c23a08",
                    flexShrink: 0,
                  }}
                >
                  <Trash2 size={16} strokeWidth={1.5} />
                </button>
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
