"use client";

import { motion } from "framer-motion";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Heart, ShoppingBag, Star, ChevronRight } from "lucide-react";
import { fetchFeaturedProducts } from "@/lib/api";
import { type Product } from "@/types";
import { useCartStore } from "@/store/cartStore";
import { useWishlistStore } from "@/store/wishlistStore";
import { useEffect, useState } from "react";

function formatPrice(price: number) {
  return `৳ ${price.toLocaleString()}`;
}

export function BestsellersSection() {
  const [products, setProducts] = useState<Product[]>([]);
  const { addItem } = useCartStore();
  const router = useRouter();
  const wishedIds = useWishlistStore((s) => s.ids);
  const toggleWish = useWishlistStore((s) => s.toggle);
  const [added, setAdded] = useState<string | null>(null);

  useEffect(() => {
    const controller = new AbortController();
    fetchFeaturedProducts(controller.signal)
      .then(setProducts)
      .catch((err) => {
        if (err?.name !== "AbortError") console.error(err);
      });
    return () => controller.abort();
  }, []);

  const handleWish = async (id: string) => {
    const res = await toggleWish(Number(id));
    if (res === "noauth") router.push("/account/login");
  };

  const handleAdd = (product: Product) => {
    addItem(product);
    setAdded(product.id);
    setTimeout(() => setAdded(null), 1500);
  };

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
            display: "flex",
            alignItems: "flex-end",
            justifyContent: "space-between",
            marginBottom: "48px",
            flexWrap: "wrap",
            gap: "16px",
          }}
        >
          <div>
            <motion.p
              initial={{ opacity: 0, y: 10 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "10px",
                fontWeight: "700",
                letterSpacing: "3px",
                textTransform: "uppercase",
                color: "#E8470A",
                marginBottom: "8px",
              }}
            >
              Most Loved
            </motion.p>
            <motion.h2
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: 0.1 }}
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "clamp(1.6rem, 3vw, 2.2rem)",
                fontWeight: "700",
                color: "#0A0A3E",
                letterSpacing: "-0.5px",
              }}
            >
              Bestsellers Loved By Families
            </motion.h2>
          </div>
          <Link
            href="/shop"
            style={{
              textDecoration: "none",
              display: "flex",
              alignItems: "center",
              gap: "4px",
              fontFamily: "var(--font-dm-sans)",
              fontSize: "11px",
              fontWeight: "600",
              letterSpacing: "1px",
              color: "#7a6e65",
            }}
          >
            View all <ChevronRight size={14} />
          </Link>
        </div>

        {/* Products Grid */}
        <div
          style={{
            display: "grid",
            gridTemplateColumns: "repeat(auto-fill, minmax(220px, 1fr))",
            gap: "20px",
          }}
        >
          {products.map((product, i) => (
            <motion.div
              key={product.id}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.08 }}
              style={{
                background: "white",
                borderRadius: "20px",
                overflow: "hidden",
                border: "1px solid #e8ddd5",
                position: "relative",
                boxShadow: "0 4px 24px rgba(10,10,62,0.06)",
              }}
              whileHover={{
                y: -6,
                boxShadow: "0 20px 48px rgba(232,71,10,0.1)",
              }}
            >
              {/* Badges */}
              <div
                style={{
                  position: "absolute",
                  top: "12px",
                  left: "12px",
                  display: "flex",
                  flexDirection: "column",
                  gap: "4px",
                  zIndex: 2,
                }}
              >
                {product.isBestseller && (
                  <span
                    style={{
                      background: "#0A0A3E",
                      color: "white",
                      fontSize: "9px",
                      fontWeight: "700",
                      padding: "3px 10px",
                      borderRadius: "20px",
                      fontFamily: "var(--font-dm-sans)",
                      letterSpacing: "0.5px",
                    }}
                  >
                    BESTSELLER
                  </span>
                )}
                {product.isNew && (
                  <span
                    style={{
                      background: "#E8470A",
                      color: "white",
                      fontSize: "9px",
                      fontWeight: "700",
                      padding: "3px 10px",
                      borderRadius: "20px",
                      fontFamily: "var(--font-dm-sans)",
                    }}
                  >
                    NEW
                  </span>
                )}
              </div>

              {/* Wishlist */}
              <button
                onClick={() => handleWish(product.id)}
                style={{
                  position: "absolute",
                  top: "12px",
                  right: "12px",
                  background: "white",
                  border: "none",
                  borderRadius: "50%",
                  width: "34px",
                  height: "34px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  cursor: "pointer",
                  boxShadow: "0 2px 8px rgba(0,0,0,0.08)",
                  zIndex: 2,
                }}
              >
                <Heart
                  size={14}
                  color="#E8470A"
                  fill={
                    wishedIds.includes(Number(product.id))
                      ? "#E8470A"
                      : "transparent"
                  }
                  strokeWidth={1.5}
                />
              </button>

              {/* Image */}
              <Link
                href={`/shop/${product.slug}`}
                style={{ textDecoration: "none" }}
              >
                <div
                  style={{
                    height: "220px",
                    background:
                      "linear-gradient(135deg, #faf6f0 0%, #f5e8e6 100%)",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    fontSize: "5rem",
                    overflow: "hidden",
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
              </Link>

              {/* Color dots */}
              {product.colors.length > 0 && (
                <div
                  style={{
                    display: "flex",
                    gap: "4px",
                    padding: "10px 16px 0",
                  }}
                >
                  {product.colors.map((color, j) => (
                    <div
                      key={j}
                      title={color.name}
                      style={{
                        width: "10px",
                        height: "10px",
                        borderRadius: "50%",
                        background: color.hex,
                        border: "1px solid rgba(0,0,0,0.1)",
                        opacity: color.inStock ? 1 : 0.3,
                      }}
                    />
                  ))}
                </div>
              )}

              {/* Info */}
              <div style={{ padding: "10px 16px 16px" }}>
                <Link
                  href={`/shop/${product.slug}`}
                  style={{ textDecoration: "none" }}
                >
                  <p
                    style={{
                      fontFamily: "var(--font-playfair)",
                      fontWeight: "700",
                      fontSize: "14px",
                      color: "#0A0A3E",
                      marginBottom: "2px",
                      overflow: "hidden",
                      textOverflow: "ellipsis",
                      whiteSpace: "nowrap",
                    }}
                  >
                    {product.name}
                  </p>
                  <p
                    style={{
                      fontFamily: "var(--font-hind)",
                      fontSize: "11px",
                      color: "#c9a84c",
                      marginBottom: "8px",
                      overflow: "hidden",
                      textOverflow: "ellipsis",
                      whiteSpace: "nowrap",
                    }}
                  >
                    {product.nameBn}
                  </p>
                </Link>

                {/* Stars */}
                {product.reviewCount > 0 && (
                  <div
                    style={{
                      display: "flex",
                      alignItems: "center",
                      gap: "4px",
                      marginBottom: "12px",
                    }}
                  >
                    <div style={{ display: "flex", gap: "1px" }}>
                      {Array.from({ length: 5 }).map((_, i) => (
                        <Star
                          key={i}
                          size={11}
                          fill={
                            i < Math.floor(product.rating)
                              ? "#c9a84c"
                              : "transparent"
                          }
                          color="#c9a84c"
                          strokeWidth={1.5}
                        />
                      ))}
                    </div>
                    <span
                      style={{
                        fontSize: "11px",
                        color: "#7a6e65",
                        fontFamily: "var(--font-dm-sans)",
                      }}
                    >
                      {product.rating} ({product.reviewCount})
                    </span>
                  </div>
                )}

                {/* Price + Add */}
                <div
                  style={{
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "space-between",
                  }}
                >
                  <div>
                    <span
                      style={{
                        fontFamily: "var(--font-playfair)",
                        fontWeight: "700",
                        fontSize: "17px",
                        color: "#0A0A3E",
                      }}
                    >
                      {formatPrice(product.price)}
                    </span>
                    {product.originalPrice && (
                      <span
                        style={{
                          fontSize: "11px",
                          color: "#c9a84c",
                          textDecoration: "line-through",
                          marginLeft: "6px",
                          fontFamily: "var(--font-dm-sans)",
                        }}
                      >
                        {formatPrice(product.originalPrice)}
                      </span>
                    )}
                  </div>

                  <motion.button
                    whileTap={{ scale: 0.9 }}
                    onClick={() => handleAdd(product)}
                    style={{
                      background: added === product.id ? "#22c55e" : "#E8470A",
                      border: "none",
                      borderRadius: "50%",
                      width: "36px",
                      height: "36px",
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                      cursor: "pointer",
                      transition: "background 0.3s ease",
                      boxShadow: "0 4px 16px rgba(232,71,10,0.3)",
                    }}
                  >
                    {added === product.id ? (
                      <span style={{ color: "white", fontSize: "14px" }}>
                        ✓
                      </span>
                    ) : (
                      <ShoppingBag size={15} color="white" strokeWidth={1.5} />
                    )}
                  </motion.button>
                </div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Bottom CTA */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          style={{ textAlign: "center", marginTop: "56px" }}
        >
          <Link href="/shop" style={{ textDecoration: "none" }}>
            <button
              style={{
                background: "transparent",
                border: "1.5px solid #E8470A",
                borderRadius: "30px",
                padding: "14px 48px",
                fontSize: "11px",
                fontWeight: "700",
                color: "#E8470A",
                cursor: "pointer",
                fontFamily: "var(--font-dm-sans)",
                letterSpacing: "2px",
                textTransform: "uppercase",
                transition: "all 0.3s ease",
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.background = "#E8470A";
                e.currentTarget.style.color = "white";
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.background = "transparent";
                e.currentTarget.style.color = "#E8470A";
              }}
            >
              View All Products
            </button>
          </Link>
        </motion.div>
      </div>

      <style>{`
        @media (max-width: 640px) {
          div[style*="repeat(auto-fill, minmax(220px, 1fr))"] {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px !important;
          }
        }
      `}</style>
    </section>
  );
}
