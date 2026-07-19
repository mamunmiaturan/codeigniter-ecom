"use client";

import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  ShoppingBag,
  Heart,
  Star,
  Shield,
  Truck,
  RotateCcw,
  ChevronLeft,
} from "lucide-react";
import { type Product } from "@/types";
import { useCartStore } from "@/store/cartStore";
import { useWishlistStore } from "@/store/wishlistStore";
import { useAuthStore } from "@/store/authStore";
import { submitReview } from "@/lib/api";

function formatPrice(price: number) {
  return `৳ ${price.toLocaleString()}`;
}

export function ProductDetail({ product }: { product: Product }) {
  const [selectedColor, setSelectedColor] = useState(product.colors[0]);
  const [selectedSize, setSelectedSize] = useState(
    product.sizes.find((s) => s.inStock) ?? product.sizes[0],
  );
  const [quantity, setQuantity] = useState(1);
  const [activeTab, setActiveTab] = useState("description");
  const [bespokeText, setBespokeText] = useState("");
  const [added, setAdded] = useState(false);
  const [activeImage, setActiveImage] = useState(0);
  const { addItem } = useCartStore();
  const router = useRouter();
  const wished = useWishlistStore((s) => s.ids.includes(Number(product.id)));
  const toggleWish = useWishlistStore((s) => s.toggle);
  const authToken = useAuthStore((s) => s.token);

  // Review form state
  const [rvRating, setRvRating] = useState(5);
  const [rvTitle, setRvTitle] = useState("");
  const [rvComment, setRvComment] = useState("");
  const [rvSubmitting, setRvSubmitting] = useState(false);
  const [rvDone, setRvDone] = useState(false);
  const [rvError, setRvError] = useState<string | null>(null);

  const submitReviewForm = async () => {
    if (!authToken) {
      router.push("/account/login");
      return;
    }
    setRvSubmitting(true);
    setRvError(null);
    try {
      await submitReview(authToken, product.slug, {
        rating: rvRating,
        title: rvTitle || undefined,
        comment: rvComment || undefined,
      });
      setRvDone(true);
      setRvTitle("");
      setRvComment("");
    } catch (e) {
      setRvError(e instanceof Error ? e.message : "Could not submit review");
    } finally {
      setRvSubmitting(false);
    }
  };

  const handleAddToCart = () => {
    addItem(product, quantity);
    setAdded(true);
    setTimeout(() => setAdded(false), 2000);
  };

  const handleWish = async () => {
    const res = await toggleWish(Number(product.id));
    if (res === "noauth") router.push("/account/login");
  };

  return (
    <div>
      {/* Breadcrumb */}
      <div
        style={{
          display: "flex",
          alignItems: "center",
          gap: "8px",
          marginBottom: "40px",
        }}
      >
        <Link
          href="/shop"
          style={{
            textDecoration: "none",
            display: "flex",
            alignItems: "center",
            gap: "4px",
            fontFamily: "var(--font-dm-sans)",
            fontSize: "12px",
            fontWeight: "600",
            letterSpacing: "1px",
            color: "#7a6e65",
            transition: "color 0.2s ease",
          }}
        >
          <ChevronLeft size={14} />
          Back to Shop
        </Link>
        <span style={{ color: "#e8ddd5" }}>·</span>
        <span
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "12px",
            color: "#c9a84c",
          }}
        >
          {product.name}
        </span>
      </div>

      {/* Main Grid */}
      <div
        style={{
          display: "grid",
          gridTemplateColumns: "1fr 1fr",
          gap: "80px",
          alignItems: "start",
        }}
      >
        {/* LEFT — Images */}
        <div style={{ position: "sticky", top: "100px" }}>
          {/* Main Image */}
          <motion.div
            key={activeImage}
            initial={{ opacity: 0, scale: 0.98 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.3 }}
            style={{
              width: "100%",
              aspectRatio: "1",
              background: "linear-gradient(135deg, #faf6f0 0%, #f5e8e6 100%)",
              borderRadius: "24px",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              fontSize: "10rem",
              marginBottom: "16px",
              border: "1px solid #e8ddd5",
              position: "relative",
              overflow: "hidden",
              boxShadow: "0 20px 60px rgba(10,10,62,0.08)",
            }}
          >
            {product.images[activeImage] ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={product.images[activeImage]}
                alt={product.name}
                style={{ width: "100%", height: "100%", objectFit: "cover" }}
              />
            ) : (
              "🎁"
            )}
            {product.originalPrice && (
              <div
                style={{
                  position: "absolute",
                  top: "20px",
                  left: "20px",
                  background: "#E8470A",
                  color: "white",
                  fontSize: "12px",
                  fontWeight: "700",
                  padding: "6px 14px",
                  borderRadius: "20px",
                  fontFamily: "var(--font-dm-sans)",
                  letterSpacing: "0.5px",
                }}
              >
                -{Math.round((1 - product.price / product.originalPrice) * 100)}
                % OFF
              </div>
            )}
          </motion.div>

          {/* Thumbnails */}
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "repeat(4, 1fr)",
              gap: "12px",
            }}
          >
            {(product.images.length > 0 ? product.images : [null]).map(
              (img, i) => (
                <button
                  key={i}
                  onClick={() => setActiveImage(i)}
                  style={{
                    aspectRatio: "1",
                    background: "linear-gradient(135deg, #faf6f0, #f5e8e6)",
                    borderRadius: "12px",
                    border:
                      activeImage === i
                        ? "2px solid #E8470A"
                        : "2px solid #e8ddd5",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    fontSize: "1.8rem",
                    cursor: "pointer",
                    overflow: "hidden",
                    transition: "all 0.2s ease",
                  }}
                >
                  {img ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={img}
                      alt={`${product.name} ${i + 1}`}
                      style={{
                        width: "100%",
                        height: "100%",
                        objectFit: "cover",
                      }}
                    />
                  ) : (
                    "🎁"
                  )}
                </button>
              ),
            )}
          </div>
        </div>

        {/* RIGHT — Info */}
        <div>
          {/* Badges */}
          <div
            style={{
              display: "flex",
              gap: "8px",
              marginBottom: "16px",
              flexWrap: "wrap",
            }}
          >
            {product.isBestseller && (
              <span
                style={{
                  background: "#0A0A3E",
                  color: "white",
                  fontSize: "10px",
                  fontWeight: "700",
                  padding: "4px 12px",
                  borderRadius: "20px",
                  fontFamily: "var(--font-dm-sans)",
                  letterSpacing: "1px",
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
                  fontSize: "10px",
                  fontWeight: "700",
                  padding: "4px 12px",
                  borderRadius: "20px",
                  fontFamily: "var(--font-dm-sans)",
                }}
              >
                NEW ARRIVAL
              </span>
            )}
            {product.isBespoke && (
              <span
                style={{
                  background: "#faf6f0",
                  color: "#E8470A",
                  fontSize: "10px",
                  fontWeight: "700",
                  padding: "4px 12px",
                  borderRadius: "20px",
                  border: "1px solid #e8ddd5",
                  fontFamily: "var(--font-dm-sans)",
                }}
              >
                ✨ PERSONALIZABLE
              </span>
            )}
          </div>

          {/* Name */}
          <h1
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "clamp(1.8rem, 3vw, 2.4rem)",
              fontWeight: "700",
              color: "#0A0A3E",
              lineHeight: "1.3",
              marginBottom: "4px",
              letterSpacing: "-0.5px",
            }}
          >
            {product.name}
          </h1>
          <p
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "14px",
              color: "#c9a84c",
              marginBottom: "20px",
            }}
          >
            {product.nameBn}
          </p>

          {/* Rating */}
          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: "8px",
              marginBottom: "24px",
              paddingBottom: "24px",
              borderBottom: "1px solid #e8ddd5",
            }}
          >
            <div style={{ display: "flex", gap: "2px" }}>
              {Array.from({ length: 5 }).map((_, i) => (
                <Star
                  key={i}
                  size={15}
                  fill={
                    i < Math.floor(product.rating) ? "#c9a84c" : "transparent"
                  }
                  color="#c9a84c"
                  strokeWidth={1.5}
                />
              ))}
            </div>
            <span
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                fontWeight: "600",
                color: "#0A0A3E",
              }}
            >
              {product.rating}
            </span>
            <span
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                color: "#7a6e65",
              }}
            >
              ({product.reviewCount} reviews)
            </span>
          </div>

          {/* Price */}
          <div
            style={{
              display: "flex",
              alignItems: "baseline",
              gap: "12px",
              marginBottom: "32px",
            }}
          >
            <span
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "2.4rem",
                fontWeight: "700",
                color: "#0A0A3E",
                letterSpacing: "-1px",
              }}
            >
              {formatPrice(product.price)}
            </span>
            {product.originalPrice && (
              <>
                <span
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "1.1rem",
                    color: "#c9a84c",
                    textDecoration: "line-through",
                  }}
                >
                  {formatPrice(product.originalPrice)}
                </span>
                <span
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    fontWeight: "700",
                    color: "#E8470A",
                    background: "rgba(232,71,10,0.08)",
                    padding: "3px 10px",
                    borderRadius: "20px",
                    letterSpacing: "0.5px",
                  }}
                >
                  Save {formatPrice(product.originalPrice - product.price)}
                </span>
              </>
            )}
          </div>

          {/* Color Selector */}
          {product.colors.length > 0 && (
            <div style={{ marginBottom: "28px" }}>
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: "700",
                  color: "#7a6e65",
                  textTransform: "uppercase",
                  letterSpacing: "1.5px",
                  marginBottom: "12px",
                }}
              >
                Color —{" "}
                <span style={{ color: "#0A0A3E" }}>{selectedColor?.name}</span>
              </p>
              <div style={{ display: "flex", gap: "10px", flexWrap: "wrap" }}>
                {product.colors.map((color, i) => (
                  <button
                    key={i}
                    onClick={() => color.inStock && setSelectedColor(color)}
                    title={color.name}
                    style={{
                      width: "32px",
                      height: "32px",
                      borderRadius: "50%",
                      background: color.hex,
                      border: "none",
                      cursor: color.inStock ? "pointer" : "not-allowed",
                      opacity: color.inStock ? 1 : 0.3,
                      outline:
                        selectedColor?.name === color.name
                          ? "3px solid #E8470A"
                          : "2px solid transparent",
                      outlineOffset: "3px",
                      transition: "all 0.2s ease",
                      boxShadow: "0 2px 8px rgba(0,0,0,0.15)",
                    }}
                  />
                ))}
              </div>
            </div>
          )}

          {/* Size Selector */}
          {product.sizes.length > 0 && (
            <div style={{ marginBottom: "28px" }}>
              <div
                style={{
                  display: "flex",
                  justifyContent: "space-between",
                  alignItems: "center",
                  marginBottom: "12px",
                }}
              >
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    fontWeight: "700",
                    color: "#7a6e65",
                    textTransform: "uppercase",
                    letterSpacing: "1.5px",
                  }}
                >
                  Size —{" "}
                  <span style={{ color: "#0A0A3E" }}>
                    {selectedSize?.label}
                  </span>
                </p>
                <button
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    color: "#E8470A",
                    background: "transparent",
                    border: "none",
                    cursor: "pointer",
                    letterSpacing: "0.5px",
                  }}
                >
                  Size Guide
                </button>
              </div>
              <div style={{ display: "flex", gap: "8px", flexWrap: "wrap" }}>
                {product.sizes.map((size, i) => (
                  <button
                    key={i}
                    onClick={() => size.inStock && setSelectedSize(size)}
                    style={{
                      minWidth: "52px",
                      padding: "11px 16px",
                      borderRadius: "10px",
                      border: "1.5px solid",
                      borderColor:
                        selectedSize?.label === size.label
                          ? "#0A0A3E"
                          : "#e8ddd5",
                      background:
                        selectedSize?.label === size.label
                          ? "#0A0A3E"
                          : "white",
                      color:
                        selectedSize?.label === size.label
                          ? "white"
                          : size.inStock
                            ? "#0A0A3E"
                            : "#c9a84c",
                      fontSize: "12px",
                      fontWeight: "700",
                      cursor: size.inStock ? "pointer" : "not-allowed",
                      opacity: size.inStock ? 1 : 0.4,
                      fontFamily: "var(--font-dm-sans)",
                      transition: "all 0.2s ease",
                      letterSpacing: "0.5px",
                    }}
                  >
                    {size.label}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Bespoke Input */}
          {product.isBespoke && (
            <div style={{ marginBottom: "28px" }}>
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: "700",
                  color: "#7a6e65",
                  textTransform: "uppercase",
                  letterSpacing: "1.5px",
                  marginBottom: "12px",
                }}
              >
                ✨ Personalize It{" "}
                <span
                  style={{
                    fontSize: "10px",
                    color: "#c9a84c",
                    fontWeight: "400",
                    textTransform: "none",
                    letterSpacing: "0",
                  }}
                >
                  (optional)
                </span>
              </p>
              <input
                type="text"
                placeholder="e.g. Arham's Baba, My Sona..."
                value={bespokeText}
                onChange={(e) => setBespokeText(e.target.value)}
                maxLength={30}
                style={{
                  width: "100%",
                  padding: "14px 18px",
                  borderRadius: "12px",
                  border: "1.5px solid #e8ddd5",
                  outline: "none",
                  fontSize: "14px",
                  fontFamily: "var(--font-dm-sans)",
                  color: "#0A0A3E",
                  background: "white",
                  transition: "border 0.2s ease",
                  boxShadow: "0 2px 8px rgba(10,10,62,0.04)",
                }}
                onFocus={(e) => (e.target.style.borderColor = "#E8470A")}
                onBlur={(e) => (e.target.style.borderColor = "#e8ddd5")}
              />
              <p
                style={{
                  fontFamily: "var(--font-hind)",
                  fontSize: "11px",
                  color: "#c9a84c",
                  marginTop: "6px",
                }}
              >
                আপনার বিশেষ বার্তা লিখুন
              </p>
            </div>
          )}

          {/* Quantity */}
          <div style={{ marginBottom: "28px" }}>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "11px",
                fontWeight: "700",
                color: "#7a6e65",
                textTransform: "uppercase",
                letterSpacing: "1.5px",
                marginBottom: "12px",
              }}
            >
              Quantity
            </p>
            <div
              style={{
                display: "inline-flex",
                alignItems: "center",
                border: "1.5px solid #e8ddd5",
                borderRadius: "12px",
                overflow: "hidden",
                background: "white",
              }}
            >
              <button
                onClick={() => setQuantity(Math.max(1, quantity - 1))}
                style={{
                  width: "44px",
                  height: "44px",
                  background: "transparent",
                  border: "none",
                  cursor: "pointer",
                  fontSize: "1.2rem",
                  color: "#0A0A3E",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  transition: "background 0.2s ease",
                }}
                onMouseEnter={(e) =>
                  (e.currentTarget.style.background = "#f7f0e6")
                }
                onMouseLeave={(e) =>
                  (e.currentTarget.style.background = "transparent")
                }
              >
                −
              </button>
              <span
                style={{
                  width: "52px",
                  textAlign: "center",
                  fontFamily: "var(--font-dm-sans)",
                  fontWeight: "700",
                  fontSize: "15px",
                  color: "#0A0A3E",
                  borderLeft: "1.5px solid #e8ddd5",
                  borderRight: "1.5px solid #e8ddd5",
                  height: "44px",
                  lineHeight: "44px",
                }}
              >
                {quantity}
              </span>
              <button
                onClick={() => setQuantity(quantity + 1)}
                style={{
                  width: "44px",
                  height: "44px",
                  background: "transparent",
                  border: "none",
                  cursor: "pointer",
                  fontSize: "1.2rem",
                  color: "#0A0A3E",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  transition: "background 0.2s ease",
                }}
                onMouseEnter={(e) =>
                  (e.currentTarget.style.background = "#f7f0e6")
                }
                onMouseLeave={(e) =>
                  (e.currentTarget.style.background = "transparent")
                }
              >
                +
              </button>
            </div>
          </div>

          {/* Add to Cart + Wishlist */}
          <div
            style={{
              display: "flex",
              gap: "12px",
              marginBottom: "28px",
            }}
          >
            <motion.button
              whileTap={{ scale: 0.98 }}
              onClick={handleAddToCart}
              style={{
                flex: 1,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                gap: "10px",
                background: added ? "#22c55e" : "#0A0A3E",
                color: "white",
                border: "none",
                borderRadius: "14px",
                padding: "18px",
                fontSize: "12px",
                fontWeight: "700",
                cursor: "pointer",
                fontFamily: "var(--font-dm-sans)",
                letterSpacing: "1.5px",
                textTransform: "uppercase",
                transition: "all 0.3s ease",
                boxShadow: added
                  ? "0 8px 24px rgba(34,197,94,0.3)"
                  : "0 8px 24px rgba(10,10,62,0.2)",
              }}
              onMouseEnter={(e) => {
                if (!added) {
                  e.currentTarget.style.background = "#E8470A";
                  e.currentTarget.style.boxShadow =
                    "0 8px 24px rgba(232,71,10,0.3)";
                }
              }}
              onMouseLeave={(e) => {
                if (!added) {
                  e.currentTarget.style.background = "#0A0A3E";
                  e.currentTarget.style.boxShadow =
                    "0 8px 24px rgba(10,10,62,0.2)";
                }
              }}
            >
              <ShoppingBag size={18} strokeWidth={1.5} />
              {added ? "Added to Cart ✓" : "Add to Cart"}
            </motion.button>

            <motion.button
              whileTap={{ scale: 0.95 }}
              onClick={handleWish}
              style={{
                width: "58px",
                height: "58px",
                background: wished ? "rgba(232,71,10,0.08)" : "white",
                border: "1.5px solid",
                borderColor: wished ? "#E8470A" : "#e8ddd5",
                borderRadius: "14px",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                cursor: "pointer",
                transition: "all 0.2s ease",
              }}
            >
              <Heart
                size={20}
                color="#E8470A"
                fill={wished ? "#E8470A" : "transparent"}
                strokeWidth={1.5}
              />
            </motion.button>
          </div>

          {/* Delivery Info */}
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "1fr 1fr 1fr",
              gap: "12px",
              marginBottom: "32px",
            }}
          >
            {[
              {
                icon: <Truck size={15} strokeWidth={1.5} />,
                label: "Free Delivery",
                sub: "All over Bangladesh",
              },
              {
                icon: <RotateCcw size={15} strokeWidth={1.5} />,
                label: "Easy Returns",
                sub: "7-day policy",
              },
              {
                icon: <Shield size={15} strokeWidth={1.5} />,
                label: "Secure Payment",
                sub: "100% protected",
              },
            ].map((item, i) => (
              <div
                key={i}
                style={{
                  background: "white",
                  borderRadius: "12px",
                  padding: "14px 12px",
                  textAlign: "center",
                  border: "1px solid #e8ddd5",
                  boxShadow: "0 2px 8px rgba(10,10,62,0.04)",
                }}
              >
                <div
                  style={{
                    display: "flex",
                    justifyContent: "center",
                    marginBottom: "6px",
                    color: "#E8470A",
                  }}
                >
                  {item.icon}
                </div>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "10px",
                    fontWeight: "700",
                    color: "#0A0A3E",
                    marginBottom: "2px",
                    letterSpacing: "0.3px",
                  }}
                >
                  {item.label}
                </p>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "9px",
                    color: "#7a6e65",
                  }}
                >
                  {item.sub}
                </p>
              </div>
            ))}
          </div>

          {/* Tabs */}
          <div>
            <div
              style={{
                display: "flex",
                borderBottom: "1px solid #e8ddd5",
                marginBottom: "24px",
              }}
            >
              {["description", "reviews"].map((tab) => (
                <button
                  key={tab}
                  onClick={() => setActiveTab(tab)}
                  style={{
                    padding: "12px 20px",
                    background: "transparent",
                    border: "none",
                    borderBottom:
                      activeTab === tab
                        ? "2px solid #E8470A"
                        : "2px solid transparent",
                    marginBottom: "-1px",
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    fontWeight: activeTab === tab ? "700" : "500",
                    color: activeTab === tab ? "#E8470A" : "#7a6e65",
                    cursor: "pointer",
                    textTransform: "uppercase",
                    letterSpacing: "1.5px",
                    transition: "all 0.2s ease",
                  }}
                >
                  {tab === "reviews"
                    ? `Reviews (${product.reviewCount})`
                    : "Description"}
                </button>
              ))}
            </div>

            <AnimatePresence mode="wait">
              {activeTab === "description" && (
                <motion.div
                  key="description"
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -10 }}
                >
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "14px",
                      color: "#7a6e65",
                      lineHeight: "1.9",
                      marginBottom: "12px",
                    }}
                  >
                    {product.description}
                  </p>
                  <p
                    style={{
                      fontFamily: "var(--font-hind)",
                      fontSize: "13px",
                      color: "#c9a84c",
                      lineHeight: "1.9",
                    }}
                  >
                    {product.descriptionBn}
                  </p>

                  {product.attributes && product.attributes.length > 0 && (
                    <div style={{ marginTop: "24px" }}>
                      <p
                        style={{
                          fontFamily: "var(--font-dm-sans)",
                          fontSize: "11px",
                          fontWeight: 700,
                          letterSpacing: "1.5px",
                          textTransform: "uppercase",
                          color: "#7a6e65",
                          marginBottom: "12px",
                        }}
                      >
                        Specifications
                      </p>
                      <div
                        style={{
                          border: "1px solid #e8ddd5",
                          borderRadius: "12px",
                          overflow: "hidden",
                        }}
                      >
                        {product.attributes.map((a, i) => (
                          <div
                            key={a.code}
                            style={{
                              display: "flex",
                              justifyContent: "space-between",
                              gap: "16px",
                              padding: "10px 16px",
                              background: i % 2 === 0 ? "#faf6f0" : "white",
                              fontFamily: "var(--font-dm-sans)",
                              fontSize: "13px",
                            }}
                          >
                            <span style={{ color: "#7a6e65" }}>{a.name}</span>
                            <span style={{ color: "#0A0A3E", fontWeight: 600 }}>
                              {a.value}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </motion.div>
              )}

              {activeTab === "reviews" && (
                <motion.div
                  key="reviews"
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -10 }}
                  style={{
                    display: "flex",
                    flexDirection: "column",
                    gap: "16px",
                  }}
                >
                  {/* Write a review */}
                  <div
                    style={{
                      background: "#faf6f0",
                      border: "1px solid #e8ddd5",
                      borderRadius: "16px",
                      padding: "20px",
                    }}
                  >
                    {rvDone ? (
                      <p
                        style={{
                          fontFamily: "var(--font-dm-sans)",
                          fontSize: "13px",
                          color: "#22c55e",
                          fontWeight: 600,
                        }}
                      >
                        ✓ Thanks! Your review was submitted and will appear once
                        approved.
                      </p>
                    ) : !authToken ? (
                      <p
                        style={{
                          fontFamily: "var(--font-dm-sans)",
                          fontSize: "13px",
                          color: "#7a6e65",
                        }}
                      >
                        <Link
                          href="/account/login"
                          style={{ color: "#E8470A", fontWeight: 700 }}
                        >
                          Log in
                        </Link>{" "}
                        to write a review.
                      </p>
                    ) : (
                      <>
                        <p
                          style={{
                            fontFamily: "var(--font-dm-sans)",
                            fontSize: "11px",
                            fontWeight: 700,
                            letterSpacing: "1px",
                            textTransform: "uppercase",
                            color: "#7a6e65",
                            marginBottom: "12px",
                          }}
                        >
                          Write a Review
                        </p>
                        <div
                          style={{
                            display: "flex",
                            gap: "4px",
                            marginBottom: "12px",
                          }}
                        >
                          {Array.from({ length: 5 }).map((_, i) => (
                            <button
                              key={i}
                              onClick={() => setRvRating(i + 1)}
                              style={{
                                background: "transparent",
                                border: "none",
                                cursor: "pointer",
                                padding: 0,
                                display: "flex",
                              }}
                            >
                              <Star
                                size={22}
                                fill={i < rvRating ? "#c9a84c" : "transparent"}
                                color="#c9a84c"
                                strokeWidth={1.5}
                              />
                            </button>
                          ))}
                        </div>
                        <input
                          type="text"
                          placeholder="Title (optional)"
                          value={rvTitle}
                          onChange={(e) => setRvTitle(e.target.value)}
                          style={{
                            width: "100%",
                            padding: "12px 14px",
                            borderRadius: "10px",
                            border: "1.5px solid #e8ddd5",
                            outline: "none",
                            fontFamily: "var(--font-dm-sans)",
                            fontSize: "13px",
                            color: "#0A0A3E",
                            background: "white",
                            marginBottom: "10px",
                          }}
                        />
                        <textarea
                          placeholder="Share your thoughts…"
                          value={rvComment}
                          onChange={(e) => setRvComment(e.target.value)}
                          rows={3}
                          style={{
                            width: "100%",
                            padding: "12px 14px",
                            borderRadius: "10px",
                            border: "1.5px solid #e8ddd5",
                            outline: "none",
                            fontFamily: "var(--font-dm-sans)",
                            fontSize: "13px",
                            color: "#0A0A3E",
                            background: "white",
                            resize: "vertical",
                          }}
                        />
                        {rvError && (
                          <p
                            style={{
                              fontFamily: "var(--font-dm-sans)",
                              fontSize: "12px",
                              color: "#c23a08",
                              marginTop: "8px",
                            }}
                          >
                            {rvError}
                          </p>
                        )}
                        <button
                          onClick={submitReviewForm}
                          disabled={rvSubmitting}
                          style={{
                            marginTop: "12px",
                            background: rvSubmitting ? "#7a6e65" : "#0A0A3E",
                            color: "white",
                            border: "none",
                            borderRadius: "12px",
                            padding: "12px 24px",
                            fontFamily: "var(--font-dm-sans)",
                            fontSize: "11px",
                            fontWeight: 700,
                            letterSpacing: "1px",
                            textTransform: "uppercase",
                            cursor: rvSubmitting ? "not-allowed" : "pointer",
                          }}
                        >
                          {rvSubmitting ? "Submitting…" : "Submit Review"}
                        </button>
                      </>
                    )}
                  </div>

                  {product.reviews.length === 0 ? (
                    <p
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "14px",
                        color: "#7a6e65",
                        textAlign: "center",
                        padding: "32px",
                      }}
                    >
                      No reviews yet. Be the first! 💕
                    </p>
                  ) : (
                    product.reviews.map((review) => (
                      <div
                        key={review.id}
                        style={{
                          padding: "20px",
                          background: "white",
                          borderRadius: "16px",
                          border: "1px solid #e8ddd5",
                          boxShadow: "0 2px 12px rgba(10,10,62,0.04)",
                        }}
                      >
                        <div
                          style={{
                            display: "flex",
                            alignItems: "center",
                            gap: "10px",
                            marginBottom: "12px",
                          }}
                        >
                          <div
                            style={{
                              width: "38px",
                              height: "38px",
                              borderRadius: "50%",
                              background:
                                "linear-gradient(135deg, #0A0A3E, #E8470A)",
                              display: "flex",
                              alignItems: "center",
                              justifyContent: "center",
                              fontSize: "12px",
                              fontWeight: "700",
                              color: "white",
                              flexShrink: 0,
                              fontFamily: "var(--font-dm-sans)",
                            }}
                          >
                            {review.avatar}
                          </div>
                          <div style={{ flex: 1 }}>
                            <div
                              style={{
                                display: "flex",
                                alignItems: "center",
                                justifyContent: "space-between",
                              }}
                            >
                              <p
                                style={{
                                  fontFamily: "var(--font-dm-sans)",
                                  fontWeight: "700",
                                  fontSize: "13px",
                                  color: "#0A0A3E",
                                }}
                              >
                                {review.name}
                                {review.verified && (
                                  <span
                                    style={{
                                      marginLeft: "6px",
                                      fontSize: "10px",
                                      color: "#22c55e",
                                      fontWeight: "500",
                                    }}
                                  >
                                    ✓ Verified
                                  </span>
                                )}
                              </p>
                              <span
                                style={{
                                  fontFamily: "var(--font-dm-sans)",
                                  fontSize: "11px",
                                  color: "#c9a84c",
                                }}
                              >
                                {review.date}
                              </span>
                            </div>
                            <div
                              style={{
                                display: "flex",
                                gap: "2px",
                                marginTop: "2px",
                              }}
                            >
                              {Array.from({ length: 5 }).map((_, i) => (
                                <Star
                                  key={i}
                                  size={11}
                                  fill={
                                    i < review.rating
                                      ? "#c9a84c"
                                      : "transparent"
                                  }
                                  color="#c9a84c"
                                  strokeWidth={1.5}
                                />
                              ))}
                            </div>
                          </div>
                        </div>
                        <p
                          style={{
                            fontFamily: "var(--font-dm-sans)",
                            fontSize: "13px",
                            color: "#7a6e65",
                            lineHeight: "1.7",
                            marginBottom: "6px",
                          }}
                        >
                          {review.comment}
                        </p>
                        {review.commentBn && (
                          <p
                            style={{
                              fontFamily: "var(--font-hind)",
                              fontSize: "12px",
                              color: "#c9a84c",
                              lineHeight: "1.7",
                            }}
                          >
                            {review.commentBn}
                          </p>
                        )}
                      </div>
                    ))
                  )}
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </div>
      </div>

      <style>{`
        @media (max-width: 768px) {
          div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
            gap: 32px !important;
          }
          div[style*="grid-template-columns: 1fr 1fr 1fr"] {
            grid-template-columns: 1fr !important;
          }
        }
      `}</style>
    </div>
  );
}
