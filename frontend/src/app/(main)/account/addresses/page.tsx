"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { motion } from "framer-motion";
import { ChevronLeft, MapPin, Plus, Star, Trash2, Pencil } from "lucide-react";
import { useRequireAuth } from "@/lib/useRequireAuth";
import {
  fetchAddresses,
  createAddress,
  updateAddress,
  deleteAddress,
  setDefaultAddress,
  type Address,
  type AddressInput,
} from "@/lib/api";

const EMPTY: AddressInput = {
  label: "",
  name: "",
  phone: "",
  division: "",
  district: "",
  area: "",
  address: "",
  landmark: "",
  postcode: "",
  is_default: false,
};

const FIELDS: {
  key: keyof AddressInput;
  label: string;
  required?: boolean;
  full?: boolean;
}[] = [
  { key: "label", label: "Label (e.g. Home, Office)" },
  { key: "name", label: "Full Name", required: true },
  { key: "phone", label: "Phone", required: true },
  { key: "division", label: "Division" },
  { key: "district", label: "District" },
  { key: "area", label: "Area" },
  { key: "address", label: "Full Address", required: true, full: true },
  { key: "landmark", label: "Landmark" },
  { key: "postcode", label: "Postcode" },
];

export default function AddressesPage() {
  const { token, ready } = useRequireAuth();
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState<AddressInput>(EMPTY);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) return;
    setLoading(true);
    fetchAddresses(token)
      .then(setAddresses)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [token]);

  const openAdd = () => {
    setForm(EMPTY);
    setEditingId(null);
    setError(null);
    setShowForm(true);
  };

  const openEdit = (a: Address) => {
    setForm({
      label: a.label ?? "",
      name: a.name,
      phone: a.phone,
      division: a.division ?? "",
      district: a.district ?? "",
      area: a.area ?? "",
      address: a.address,
      landmark: a.landmark ?? "",
      postcode: a.postcode ?? "",
      is_default: a.is_default,
    });
    setEditingId(a.id);
    setError(null);
    setShowForm(true);
  };

  const save = async () => {
    if (!token) return;
    if (!form.name || !form.phone || !form.address) {
      setError("Name, phone and address are required.");
      return;
    }
    setSaving(true);
    setError(null);
    try {
      const list =
        editingId === null
          ? await createAddress(token, form)
          : await updateAddress(token, editingId, form);
      setAddresses(list);
      setShowForm(false);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Could not save address");
    } finally {
      setSaving(false);
    }
  };

  const onDelete = async (id: number) => {
    if (!token) return;
    try {
      setAddresses(await deleteAddress(token, id));
    } catch (e) {
      console.error(e);
    }
  };

  const onSetDefault = async (id: number) => {
    if (!token) return;
    try {
      setAddresses(await setDefaultAddress(token, id));
    } catch (e) {
      console.error(e);
    }
  };

  if (!ready) return null;

  const inputStyle: React.CSSProperties = {
    width: "100%",
    padding: "12px 14px",
    borderRadius: "10px",
    border: "1.5px solid #e8ddd5",
    outline: "none",
    fontFamily: "var(--font-dm-sans)",
    fontSize: "13px",
    color: "#0A0A3E",
    background: "#faf6f0",
  };

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

        <div
          style={{
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            marginBottom: "24px",
            gap: "16px",
            flexWrap: "wrap",
          }}
        >
          <h1
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "1.8rem",
              fontWeight: 700,
              color: "#0A0A3E",
            }}
          >
            Saved Addresses
          </h1>
          {!showForm && (
            <button
              onClick={openAdd}
              style={{
                display: "flex",
                alignItems: "center",
                gap: "6px",
                background: "#0A0A3E",
                color: "white",
                border: "none",
                borderRadius: "25px",
                padding: "10px 20px",
                fontFamily: "var(--font-dm-sans)",
                fontSize: "11px",
                fontWeight: 700,
                letterSpacing: "1px",
                textTransform: "uppercase",
                cursor: "pointer",
              }}
            >
              <Plus size={14} /> Add Address
            </button>
          )}
        </div>

        {/* Form */}
        {showForm && (
          <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            style={{
              background: "white",
              borderRadius: "20px",
              border: "1px solid #e8ddd5",
              padding: "24px",
              marginBottom: "24px",
            }}
          >
            <h2
              style={{
                fontFamily: "var(--font-playfair)",
                fontSize: "1.1rem",
                fontWeight: 700,
                color: "#0A0A3E",
                marginBottom: "16px",
              }}
            >
              {editingId === null ? "New Address" : "Edit Address"}
            </h2>

            <div
              style={{
                display: "grid",
                gridTemplateColumns: "1fr 1fr",
                gap: "12px",
              }}
            >
              {FIELDS.map((f) => (
                <div
                  key={f.key}
                  style={{ gridColumn: f.full ? "1 / -1" : undefined }}
                >
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
                    {f.label}
                    {f.required && <span style={{ color: "#E8470A" }}> *</span>}
                  </label>
                  <input
                    value={(form[f.key] as string) ?? ""}
                    onChange={(e) =>
                      setForm({ ...form, [f.key]: e.target.value })
                    }
                    style={inputStyle}
                  />
                </div>
              ))}
            </div>

            <label
              style={{
                display: "flex",
                alignItems: "center",
                gap: "8px",
                marginTop: "16px",
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                color: "#0A0A3E",
                cursor: "pointer",
              }}
            >
              <input
                type="checkbox"
                checked={Boolean(form.is_default)}
                onChange={(e) =>
                  setForm({ ...form, is_default: e.target.checked })
                }
                style={{ accentColor: "#E8470A" }}
              />
              Set as default address
            </label>

            {error && (
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "13px",
                  color: "#c23a08",
                  marginTop: "12px",
                }}
              >
                {error}
              </p>
            )}

            <div style={{ display: "flex", gap: "12px", marginTop: "20px" }}>
              <button
                onClick={save}
                disabled={saving}
                style={{
                  background: saving ? "#7a6e65" : "#E8470A",
                  color: "white",
                  border: "none",
                  borderRadius: "12px",
                  padding: "12px 28px",
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: 700,
                  letterSpacing: "1px",
                  textTransform: "uppercase",
                  cursor: saving ? "not-allowed" : "pointer",
                }}
              >
                {saving ? "Saving…" : "Save Address"}
              </button>
              <button
                onClick={() => setShowForm(false)}
                style={{
                  background: "transparent",
                  color: "#7a6e65",
                  border: "1.5px solid #e8ddd5",
                  borderRadius: "12px",
                  padding: "12px 28px",
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: 700,
                  letterSpacing: "1px",
                  textTransform: "uppercase",
                  cursor: "pointer",
                }}
              >
                Cancel
              </button>
            </div>
          </motion.div>
        )}

        {/* List */}
        {loading ? (
          <p style={{ fontFamily: "var(--font-dm-sans)", color: "#aaa" }}>
            Loading addresses…
          </p>
        ) : addresses.length === 0 && !showForm ? (
          <div
            style={{
              background: "white",
              borderRadius: "20px",
              border: "1px solid #e8ddd5",
              padding: "64px 24px",
              textAlign: "center",
            }}
          >
            <MapPin
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
              No saved addresses
            </p>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                color: "#7a6e65",
              }}
            >
              Add a delivery address to speed up checkout.
            </p>
          </div>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: "12px" }}>
            {addresses.map((a) => (
              <div
                key={a.id}
                style={{
                  background: "white",
                  borderRadius: "16px",
                  border: a.is_default
                    ? "1.5px solid #E8470A"
                    : "1px solid #e8ddd5",
                  padding: "20px 24px",
                }}
              >
                <div
                  style={{
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "space-between",
                    marginBottom: "8px",
                    gap: "8px",
                  }}
                >
                  <p
                    style={{
                      fontFamily: "var(--font-dm-sans)",
                      fontWeight: 700,
                      fontSize: "14px",
                      color: "#0A0A3E",
                    }}
                  >
                    {a.name}
                    {a.label && (
                      <span
                        style={{
                          fontWeight: 600,
                          fontSize: "11px",
                          color: "#7a6e65",
                          marginLeft: "8px",
                        }}
                      >
                        · {a.label}
                      </span>
                    )}
                  </p>
                  {a.is_default && (
                    <span
                      style={{
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "10px",
                        fontWeight: 700,
                        letterSpacing: "0.5px",
                        textTransform: "uppercase",
                        color: "#E8470A",
                        background: "rgba(232,71,10,0.08)",
                        borderRadius: "20px",
                        padding: "3px 10px",
                      }}
                    >
                      Default
                    </span>
                  )}
                </div>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "13px",
                    color: "#7a6e65",
                    lineHeight: 1.6,
                  }}
                >
                  {a.phone}
                  <br />
                  {[a.address, a.area, a.district, a.division, a.postcode]
                    .filter(Boolean)
                    .join(", ")}
                </p>

                <div
                  style={{
                    display: "flex",
                    gap: "8px",
                    marginTop: "14px",
                    flexWrap: "wrap",
                  }}
                >
                  {!a.is_default && (
                    <button
                      onClick={() => onSetDefault(a.id)}
                      style={pillBtn}
                    >
                      <Star size={13} /> Set default
                    </button>
                  )}
                  <button onClick={() => openEdit(a)} style={pillBtn}>
                    <Pencil size={13} /> Edit
                  </button>
                  <button
                    onClick={() => onDelete(a.id)}
                    style={{ ...pillBtn, color: "#c23a08" }}
                  >
                    <Trash2 size={13} /> Delete
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

const pillBtn: React.CSSProperties = {
  display: "inline-flex",
  alignItems: "center",
  gap: "5px",
  background: "transparent",
  border: "1px solid #e8ddd5",
  borderRadius: "20px",
  padding: "7px 14px",
  fontFamily: "var(--font-dm-sans)",
  fontSize: "11px",
  fontWeight: 600,
  color: "#7a6e65",
  cursor: "pointer",
};
