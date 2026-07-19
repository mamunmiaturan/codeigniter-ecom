"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { motion } from "framer-motion";
import { CheckCircle2, ShoppingBag } from "lucide-react";
import { useCartStore } from "@/store/cartStore";
import { useAuthStore } from "@/store/authStore";
import {
  checkout,
  fetchAddresses,
  type Address,
  type CheckoutInput,
  type PlacedOrder,
} from "@/lib/api";

function taka(v: string) {
  return `৳ ${parseFloat(v).toLocaleString()}`;
}

const FIELDS: { key: keyof CheckoutInput; label: string; full?: boolean }[] = [
  { key: "division", label: "Division" },
  { key: "district", label: "District" },
  { key: "area", label: "Area" },
  { key: "postcode", label: "Postcode" },
  { key: "address", label: "Full Address", full: true },
  { key: "landmark", label: "Landmark (optional)", full: true },
];

export default function CheckoutPage() {
  const { items, itemCount, subtotal, discount, total, cartToken, refresh, reset } =
    useCartStore();
  const { token, customer } = useAuthStore();
  const [mounted, setMounted] = useState(false);

  const [form, setForm] = useState<CheckoutInput>({
    name: "",
    phone: "",
    email: "",
    division: "",
    district: "",
    area: "",
    address: "",
    landmark: "",
    postcode: "",
    note: "",
  });
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [selectedAddress, setSelectedAddress] = useState<number | "new">("new");
  const [placing, setPlacing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [placed, setPlaced] = useState<PlacedOrder | null>(null);

  useEffect(() => setMounted(true), []);
  useEffect(() => {
    refresh();
  }, [refresh]);

  // Prefill contact details + load saved addresses for logged-in customers.
  useEffect(() => {
    if (customer) {
      setForm((f) => ({
        ...f,
        name: f.name || customer.name,
        phone: f.phone || customer.phone || "",
        email: f.email || customer.email,
      }));
    }
  }, [customer]);

  useEffect(() => {
    if (!token) return;
    fetchAddresses(token)
      .then((list) => {
        setAddresses(list);
        const def = list.find((a) => a.is_default) ?? list[0];
        if (def) setSelectedAddress(def.id);
      })
      .catch(console.error);
  }, [token]);

  const placeOrder = async () => {
    setError(null);
    const usingSaved = token && selectedAddress !== "new";
    const payload: CheckoutInput = usingSaved
      ? { address_id: selectedAddress as number, payment_method: "cod", note: form.note }
      : { ...form, payment_method: "cod" };

    if (!usingSaved && (!form.name || !form.phone || !form.address)) {
      setError("Name, phone and full address are required.");
      return;
    }

    setPlacing(true);
    try {
      const order = await checkout(payload, token, cartToken);
      setPlaced(order);
      reset(); // cart is closed server-side; clear locally too
    } catch (e) {
      setError(e instanceof Error ? e.message : "Could not place the order");
    } finally {
      setPlacing(false);
    }
  };

  if (!mounted) return null;

  // Order confirmation.
  if (placed) {
    return (
      <div
        style={{
          minHeight: "100vh",
          background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
          padding: "80px clamp(1.5rem, 4vw, 5rem)",
        }}
      >
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          style={{
            maxWidth: "520px",
            margin: "0 auto",
            background: "white",
            borderRadius: "24px",
            border: "1px solid #e8ddd5",
            padding: "48px 32px",
            textAlign: "center",
          }}
        >
          <CheckCircle2
            size={56}
            color="#22c55e"
            strokeWidth={1.5}
            style={{ marginBottom: "20px" }}
          />
          <h1
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "1.6rem",
              fontWeight: 700,
              color: "#0A0A3E",
              marginBottom: "8px",
            }}
          >
            Order Placed! 🎉
          </h1>
          <p
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "14px",
              color: "#7a6e65",
              marginBottom: "24px",
            }}
          >
            Order <strong>#{placed.order_number}</strong> · Total{" "}
            <strong>{taka(placed.totals.total)}</strong> · Cash on Delivery
          </p>
          <div style={{ display: "flex", gap: "12px", justifyContent: "center" }}>
            <Link href="/account/orders" style={{ textDecoration: "none" }}>
              <span style={primaryBtn}>View My Orders</span>
            </Link>
            <Link href="/shop" style={{ textDecoration: "none" }}>
              <span style={ghostBtn}>Continue Shopping</span>
            </Link>
          </div>
        </motion.div>
      </div>
    );
  }

  // Empty cart.
  if (itemCount === 0) {
    return (
      <div
        style={{
          minHeight: "100vh",
          background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
          padding: "100px clamp(1.5rem, 4vw, 5rem)",
          textAlign: "center",
        }}
      >
        <ShoppingBag
          size={44}
          color="#c9a84c"
          strokeWidth={1.3}
          style={{ marginBottom: "16px" }}
        />
        <p
          style={{
            fontFamily: "var(--font-playfair)",
            fontSize: "1.3rem",
            fontWeight: 700,
            color: "#0A0A3E",
            marginBottom: "16px",
          }}
        >
          Your cart is empty
        </p>
        <Link href="/shop" style={{ textDecoration: "none" }}>
          <span style={primaryBtn}>Browse Products</span>
        </Link>
      </div>
    );
  }

  return (
    <div
      style={{
        minHeight: "100vh",
        background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
        padding: "60px clamp(1.5rem, 4vw, 5rem)",
      }}
    >
      <div style={{ maxWidth: "1000px", margin: "0 auto" }}>
        <h1
          style={{
            fontFamily: "var(--font-playfair)",
            fontSize: "2rem",
            fontWeight: 700,
            color: "#0A0A3E",
            marginBottom: "24px",
          }}
        >
          Checkout
        </h1>

        <div
          style={{
            display: "grid",
            gridTemplateColumns: "1.4fr 1fr",
            gap: "24px",
            alignItems: "start",
          }}
          className="checkout-grid"
        >
          {/* Shipping */}
          <div
            style={{
              background: "white",
              borderRadius: "20px",
              border: "1px solid #e8ddd5",
              padding: "24px",
            }}
          >
            <h2 style={sectionTitle}>Shipping Details</h2>

            {/* Saved addresses */}
            {token && addresses.length > 0 && (
              <div style={{ marginBottom: "20px" }}>
                {addresses.map((a) => (
                  <label
                    key={a.id}
                    style={{
                      display: "flex",
                      gap: "10px",
                      alignItems: "flex-start",
                      padding: "12px",
                      border:
                        selectedAddress === a.id
                          ? "1.5px solid #E8470A"
                          : "1px solid #e8ddd5",
                      borderRadius: "12px",
                      marginBottom: "8px",
                      cursor: "pointer",
                    }}
                  >
                    <input
                      type="radio"
                      name="addr"
                      checked={selectedAddress === a.id}
                      onChange={() => setSelectedAddress(a.id)}
                      style={{ accentColor: "#E8470A", marginTop: "3px" }}
                    />
                    <span
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "13px",
                        color: "#0A0A3E",
                      }}
                    >
                      <strong>{a.name}</strong> · {a.phone}
                      <br />
                      <span style={{ color: "#7a6e65" }}>
                        {[a.address, a.area, a.district, a.division]
                          .filter(Boolean)
                          .join(", ")}
                      </span>
                    </span>
                  </label>
                ))}
                <label
                  style={{
                    display: "flex",
                    gap: "10px",
                    alignItems: "center",
                    padding: "12px",
                    border:
                      selectedAddress === "new"
                        ? "1.5px solid #E8470A"
                        : "1px solid #e8ddd5",
                    borderRadius: "12px",
                    cursor: "pointer",
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "13px",
                    color: "#0A0A3E",
                  }}
                >
                  <input
                    type="radio"
                    name="addr"
                    checked={selectedAddress === "new"}
                    onChange={() => setSelectedAddress("new")}
                    style={{ accentColor: "#E8470A" }}
                  />
                  Use a new address
                </label>
              </div>
            )}

            {/* Manual form (hidden when a saved address is chosen) */}
            {(!token || selectedAddress === "new") && (
              <>
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "12px" }}>
                  <Field
                    label="Full Name"
                    value={form.name ?? ""}
                    onChange={(v) => setForm({ ...form, name: v })}
                  />
                  <Field
                    label="Phone"
                    value={form.phone ?? ""}
                    onChange={(v) => setForm({ ...form, phone: v })}
                  />
                  {FIELDS.map((f) => (
                    <div
                      key={f.key}
                      style={{ gridColumn: f.full ? "1 / -1" : undefined }}
                    >
                      <Field
                        label={f.label}
                        value={(form[f.key] as string) ?? ""}
                        onChange={(v) => setForm({ ...form, [f.key]: v })}
                      />
                    </div>
                  ))}
                </div>
              </>
            )}

            <div style={{ marginTop: "12px" }}>
              <Field
                label="Order note (optional)"
                value={form.note ?? ""}
                onChange={(v) => setForm({ ...form, note: v })}
              />
            </div>

            <div
              style={{
                marginTop: "16px",
                padding: "12px 14px",
                background: "#faf6f0",
                borderRadius: "12px",
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                color: "#0A0A3E",
              }}
            >
              💵 Payment: <strong>Cash on Delivery</strong>
            </div>

            {!token && (
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "12px",
                  color: "#7a6e65",
                  marginTop: "12px",
                }}
              >
                Checking out as guest.{" "}
                <Link href="/account/login" style={{ color: "#E8470A" }}>
                  Log in
                </Link>{" "}
                to use saved addresses & track orders.
              </p>
            )}
          </div>

          {/* Summary */}
          <div
            style={{
              background: "white",
              borderRadius: "20px",
              border: "1px solid #e8ddd5",
              padding: "24px",
              position: "sticky",
              top: "100px",
            }}
          >
            <h2 style={sectionTitle}>Order Summary</h2>
            <div style={{ display: "flex", flexDirection: "column", gap: "10px" }}>
              {items.map((it) => (
                <div
                  key={it.id}
                  style={{
                    display: "flex",
                    justifyContent: "space-between",
                    gap: "12px",
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "13px",
                    color: "#0A0A3E",
                  }}
                >
                  <span style={{ color: "#7a6e65" }}>
                    {it.product.name} × {it.quantity}
                  </span>
                  <span>{taka(it.line_total)}</span>
                </div>
              ))}
            </div>

            <div
              style={{
                borderTop: "1px solid #e8ddd5",
                marginTop: "16px",
                paddingTop: "16px",
                display: "flex",
                flexDirection: "column",
                gap: "8px",
              }}
            >
              <Row label="Subtotal" value={taka(subtotal)} />
              {parseFloat(discount) > 0 && (
                <Row label="Discount" value={`− ${taka(discount)}`} />
              )}
              <Row label="Delivery" value="Free" />
              <div
                style={{
                  display: "flex",
                  justifyContent: "space-between",
                  borderTop: "1px solid #e8ddd5",
                  paddingTop: "12px",
                  marginTop: "4px",
                }}
              >
                <span
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontWeight: 700,
                    fontSize: "1.1rem",
                    color: "#0A0A3E",
                  }}
                >
                  Total
                </span>
                <span
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontWeight: 700,
                    fontSize: "1.1rem",
                    color: "#E8470A",
                  }}
                >
                  {taka(total)}
                </span>
              </div>
            </div>

            {error && (
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "13px",
                  color: "#c23a08",
                  marginTop: "14px",
                }}
              >
                {error}
              </p>
            )}

            <button
              onClick={placeOrder}
              disabled={placing}
              style={{
                width: "100%",
                marginTop: "16px",
                background: placing ? "#7a6e65" : "#0A0A3E",
                color: "white",
                border: "none",
                borderRadius: "14px",
                padding: "16px",
                fontFamily: "var(--font-dm-sans)",
                fontSize: "12px",
                fontWeight: 700,
                letterSpacing: "1.5px",
                textTransform: "uppercase",
                cursor: placing ? "not-allowed" : "pointer",
              }}
            >
              {placing ? "Placing Order…" : "Place Order"}
            </button>
          </div>
        </div>
      </div>

      <style>{`
        @media (max-width: 800px) {
          .checkout-grid { grid-template-columns: 1fr !important; }
        }
      `}</style>
    </div>
  );
}

function Field({
  label,
  value,
  onChange,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <div>
      <label
        style={{
          display: "block",
          fontFamily: "var(--font-dm-sans)",
          fontSize: "11px",
          fontWeight: 700,
          color: "#7a6e65",
          letterSpacing: "0.5px",
          marginBottom: "6px",
        }}
      >
        {label}
      </label>
      <input
        value={value}
        onChange={(e) => onChange(e.target.value)}
        style={{
          width: "100%",
          padding: "12px 14px",
          borderRadius: "10px",
          border: "1.5px solid #e8ddd5",
          outline: "none",
          fontFamily: "var(--font-dm-sans)",
          fontSize: "13px",
          color: "#0A0A3E",
          background: "#faf6f0",
        }}
      />
    </div>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div
      style={{
        display: "flex",
        justifyContent: "space-between",
        fontFamily: "var(--font-dm-sans)",
        fontSize: "13px",
        color: "#7a6e65",
      }}
    >
      <span>{label}</span>
      <span>{value}</span>
    </div>
  );
}

const sectionTitle: React.CSSProperties = {
  fontFamily: "var(--font-playfair)",
  fontSize: "1.15rem",
  fontWeight: 700,
  color: "#0A0A3E",
  marginBottom: "16px",
};

const primaryBtn: React.CSSProperties = {
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
};

const ghostBtn: React.CSSProperties = {
  display: "inline-block",
  background: "transparent",
  color: "#7a6e65",
  border: "1.5px solid #e8ddd5",
  borderRadius: "25px",
  padding: "12px 28px",
  fontFamily: "var(--font-dm-sans)",
  fontSize: "11px",
  fontWeight: 700,
  letterSpacing: "1.5px",
  textTransform: "uppercase",
};
