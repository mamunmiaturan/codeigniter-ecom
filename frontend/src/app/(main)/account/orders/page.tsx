"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { motion } from "framer-motion";
import { ChevronLeft, Package } from "lucide-react";
import { useRequireAuth } from "@/lib/useRequireAuth";
import { fetchOrders, type OrderSummary } from "@/lib/api";

function taka(v: string) {
  return `৳ ${parseFloat(v).toLocaleString()}`;
}

const STATUS_COLORS: Record<string, string> = {
  pending: "#c9a84c",
  processing: "#0A0A3E",
  shipped: "#0A0A3E",
  delivered: "#22c55e",
  cancelled: "#c23a08",
};

export default function OrdersPage() {
  const { token, ready } = useRequireAuth();
  const [orders, setOrders] = useState<OrderSummary[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchOrders(token)
      .then(setOrders)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [token]);

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
          My Orders
        </h1>

        {loading ? (
          <p style={{ fontFamily: "var(--font-dm-sans)", color: "#aaa" }}>
            Loading orders…
          </p>
        ) : orders.length === 0 ? (
          <div
            style={{
              background: "white",
              borderRadius: "20px",
              border: "1px solid #e8ddd5",
              padding: "64px 24px",
              textAlign: "center",
            }}
          >
            <Package
              size={40}
              color="#c9a84c"
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
              No orders yet
            </p>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                color: "#7a6e65",
                marginBottom: "20px",
              }}
            >
              Once you place an order it will show up here.
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
                Start Shopping
              </span>
            </Link>
          </div>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: "12px" }}>
            {orders.map((o, i) => (
              <motion.div
                key={o.order_number}
                initial={{ opacity: 0, y: 16 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: i * 0.05 }}
                style={{
                  background: "white",
                  borderRadius: "16px",
                  border: "1px solid #e8ddd5",
                  padding: "20px 24px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "space-between",
                  gap: "16px",
                  flexWrap: "wrap",
                }}
              >
                <div>
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontWeight: 700,
                      fontSize: "14px",
                      color: "#0A0A3E",
                    }}
                  >
                    #{o.order_number}
                  </p>
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "12px",
                      color: "#7a6e65",
                      marginTop: "2px",
                    }}
                  >
                    {o.item_count} item{o.item_count === 1 ? "" : "s"} ·{" "}
                    {(o.placed_at ?? "").slice(0, 10)}
                  </p>
                </div>
                <div style={{ textAlign: "right" }}>
                  <span
                    style={{
                      display: "inline-block",
                      fontFamily: "var(--font-dm-sans)",
                      fontSize: "10px",
                      fontWeight: 700,
                      letterSpacing: "0.5px",
                      textTransform: "uppercase",
                      color: "white",
                      background: STATUS_COLORS[o.status] ?? "#7a6e65",
                      borderRadius: "20px",
                      padding: "3px 12px",
                      marginBottom: "6px",
                    }}
                  >
                    {o.status}
                  </span>
                  <p
                    style={{
                      fontFamily: "var(--font-playfair)",
                      fontWeight: 700,
                      fontSize: "16px",
                      color: "#E8470A",
                    }}
                  >
                    {taka(o.total)}
                  </p>
                </div>
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
