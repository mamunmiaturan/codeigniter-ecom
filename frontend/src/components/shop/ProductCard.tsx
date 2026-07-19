"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { motion } from "framer-motion";
import { Heart, ShoppingBag, Star } from "lucide-react";
import { type Product } from "@/types";
import { useCartStore } from "@/store/cartStore";
import { useWishlistStore } from "@/store/wishlistStore";

function formatPrice(price: number) {
  return `৳ ${price.toLocaleString()}`;
}

export function ProductCard({
  product,
  index,
}: {
  product: Product;
  index: number;
}) {
  const [added, setAdded] = useState(false);
  const { addItem } = useCartStore();
  const router = useRouter();
  const wished = useWishlistStore((s) => s.ids.includes(Number(product.id)));
  const toggleWish = useWishlistStore((s) => s.toggle);

  const handleAddToCart = () => {
    addItem(product);
    setAdded(true);
    setTimeout(() => setAdded(false), 1500);
  };

  const handleWish = async () => {
    const res = await toggleWish(Number(product.id));
    if (res === "noauth") router.push("/account/login");
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 30 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      transition={{ delay: index * 0.06 }}
      style={{
        background: "white",
        borderRadius: "20px",
        overflow: "hidden",
        border: "1px solid #f0e0e8",
        position: "relative",
        transition: "transform 0.3s ease, box-shadow 0.3s ease",
      }}
      whileHover={{
        y: -4,
        boxShadow: "0 12px 40px rgba(233,30,140,0.12)",
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
              background: "#e91e8c",
              color: "white",
              fontSize: "10px",
              fontWeight: "700",
              padding: "3px 10px",
              borderRadius: "20px",
              fontFamily: "var(--font-dm-sans)",
            }}
          >
            Bestseller
          </span>
        )}
        {product.isNew && (
          <span
            style={{
              background: "#22c55e",
              color: "white",
              fontSize: "10px",
              fontWeight: "700",
              padding: "3px 10px",
              borderRadius: "20px",
              fontFamily: "var(--font-dm-sans)",
            }}
          >
            New
          </span>
        )}
        {product.discount && (
          <span
            style={{
              background: "#ff6b35",
              color: "white",
              fontSize: "10px",
              fontWeight: "700",
              padding: "3px 10px",
              borderRadius: "20px",
              fontFamily: "var(--font-dm-sans)",
            }}
          >
            -{product.discount}%
          </span>
        )}
      </div>

      {/* Wishlist */}
      <button
        onClick={handleWish}
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
          boxShadow: "0 2px 8px rgba(0,0,0,0.1)",
          zIndex: 2,
          transition: "transform 0.2s ease",
        }}
      >
        <Heart
          size={15}
          color="#e91e8c"
          fill={wished ? "#e91e8c" : "transparent"}
        />
      </button>

      {/* Image */}
      <Link href={`/shop/${product.slug}`} style={{ textDecoration: "none" }}>
        <div
          style={{
            height: "200px",
            background: "linear-gradient(135deg, #fff0f7, #ffe8f0)",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            fontSize: "4.5rem",
            cursor: "pointer",
            overflow: "hidden",
            transition: "transform 0.3s ease",
          }}
        >
          {product.images[0] ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={product.images[0]}
              alt={product.name}
              style={{ width: "100%", height: "100%", objectFit: "cover" }}
            />
          ) : (
            "🎁"
          )}
        </div>
      </Link>

      {/* Color Dots */}
      {product.colors.length > 0 && (
        <div
          style={{
            display: "flex",
            gap: "4px",
            padding: "8px 14px 0",
          }}
        >
          {product.colors.map((color, i) => (
            <div
              key={i}
              title={color.name}
              style={{
                width: "12px",
                height: "12px",
                borderRadius: "50%",
                background: color.hex,
                border: "1.5px solid #f0e0e8",
                opacity: color.inStock ? 1 : 0.4,
                cursor: "pointer",
              }}
            />
          ))}
        </div>
      )}

      {/* Info */}
      <div style={{ padding: "10px 14px 14px" }}>
        <Link href={`/shop/${product.slug}`} style={{ textDecoration: "none" }}>
          <p
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontWeight: "600",
              fontSize: "13px",
              color: "#1a1a2e",
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
              color: "#bbb",
              marginBottom: "6px",
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
              marginBottom: "10px",
            }}
          >
            <div style={{ display: "flex", gap: "1px" }}>
              {Array.from({ length: 5 }).map((_, i) => (
                <Star
                  key={i}
                  size={11}
                  fill={
                    i < Math.floor(product.rating) ? "#FFB800" : "transparent"
                  }
                  color="#FFB800"
                />
              ))}
            </div>
            <span
              style={{
                fontSize: "11px",
                color: "#999",
                fontFamily: "var(--font-dm-sans)",
              }}
            >
              {product.rating} ({product.reviewCount})
            </span>
          </div>
        )}

        {/* Price + Cart */}
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
                fontSize: "16px",
                color: "#e91e8c",
              }}
            >
              {formatPrice(product.price)}
            </span>
            {product.originalPrice && (
              <span
                style={{
                  fontSize: "11px",
                  color: "#bbb",
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
            onClick={handleAddToCart}
            style={{
              background: added ? "#22c55e" : "#e91e8c",
              border: "none",
              borderRadius: "50%",
              width: "36px",
              height: "36px",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              cursor: "pointer",
              boxShadow: "0 4px 12px rgba(233,30,140,0.3)",
              transition: "background 0.3s ease",
            }}
          >
            {added ? (
              <span style={{ fontSize: "14px" }}>✓</span>
            ) : (
              <ShoppingBag size={15} color="white" />
            )}
          </motion.button>
        </div>
      </div>
    </motion.div>
  );
}
