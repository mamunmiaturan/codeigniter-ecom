"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Eye, EyeOff, Mail, Lock, User, Phone } from "lucide-react";
import { useAuthStore } from "@/store/authStore";

export default function RegisterPage() {
  const router = useRouter();
  const { register, loading, error, clearError, token } = useAuthStore();
  const [form, setForm] = useState({
    name: "",
    phone: "",
    email: "",
    password: "",
  });
  const [showPassword, setShowPassword] = useState(false);

  useEffect(() => {
    if (token) router.replace("/account");
  }, [token, router]);

  useEffect(() => clearError, [clearError]);

  const handleSubmit = async () => {
    if (!form.name || !form.email || !form.password) return;
    const ok = await register({
      name: form.name,
      email: form.email,
      phone: form.phone,
      password: form.password,
    });
    if (ok) router.replace("/account");
  };

  return (
    <div
      style={{
        minHeight: "100vh",
        background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "40px 20px",
      }}
    >
      <motion.div
        initial={{ opacity: 0, y: 30 }}
        animate={{ opacity: 1, y: 0 }}
        style={{
          width: "100%",
          maxWidth: "440px",
        }}
      >
        {/* Logo */}
        <div style={{ textAlign: "center", marginBottom: "40px" }}>
          <Link href="/" style={{ textDecoration: "none" }}>
            <div
              style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                gap: "2px",
              }}
            >
              <span
                style={{
                  fontFamily: "var(--font-playfair)",
                  fontSize: "2rem",
                  fontWeight: "700",
                  color: "#0A0A3E",
                  letterSpacing: "-0.5px",
                }}
              >
                Babu
              </span>
              <span
                style={{
                  fontFamily: "var(--font-playfair)",
                  fontSize: "2rem",
                  fontWeight: "700",
                  color: "#0A0A3E",
                  fontStyle: "italic",
                  letterSpacing: "-0.5px",
                }}
              >
                Sona
              </span>
              <span
                style={{
                  color: "#E8470A",
                  fontSize: "1rem",
                  marginLeft: "3px",
                }}
              >
                ♡
              </span>
            </div>
            <p
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "9px",
                color: "#E8470A",
                letterSpacing: "2.5px",
                textTransform: "uppercase",
                marginTop: "4px",
                fontWeight: "600",
              }}
            >
              LOVE. CARE. EVERYDAY.
            </p>
          </Link>
        </div>

        {/* Card */}
        <div
          style={{
            background: "white",
            borderRadius: "24px",
            padding: "40px",
            border: "1px solid #e8ddd5",
            boxShadow: "0 20px 60px rgba(10,10,62,0.08)",
          }}
        >
          <h1
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "1.8rem",
              fontWeight: "700",
              color: "#0A0A3E",
              marginBottom: "4px",
              letterSpacing: "-0.5px",
            }}
          >
            Join BabuSona
          </h1>
          <p
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "13px",
              color: "#c9a84c",
              marginBottom: "32px",
            }}
          >
            বাবুসোনা পরিবারে যোগ দিন ❤️
          </p>

          {/* Fields */}
          {[
            {
              key: "name",
              label: "Full Name",
              labelBn: "পুরো নাম",
              icon: <User size={16} color="#c9a84c" strokeWidth={1.5} />,
              placeholder: "Your name",
              type: "text",
            },
            {
              key: "phone",
              label: "Phone Number",
              labelBn: "ফোন নম্বর",
              icon: <Phone size={16} color="#c9a84c" strokeWidth={1.5} />,
              placeholder: "01XXXXXXXXX",
              type: "tel",
            },
            {
              key: "email",
              label: "Email Address",
              labelBn: "ইমেইল",
              icon: <Mail size={16} color="#c9a84c" strokeWidth={1.5} />,
              placeholder: "your@email.com",
              type: "email",
            },
          ].map((field) => (
            <div key={field.key} style={{ marginBottom: "20px" }}>
              <label
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: "700",
                  color: "#7a6e65",
                  letterSpacing: "1.5px",
                  textTransform: "uppercase",
                  display: "block",
                  marginBottom: "8px",
                }}
              >
                {field.label}
                <span
                  style={{
                    fontFamily: "var(--font-hind)",
                    fontSize: "10px",
                    color: "#c9a84c",
                    marginLeft: "8px",
                    textTransform: "none",
                    letterSpacing: "0",
                  }}
                >
                  {field.labelBn}
                </span>
              </label>
              <div
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: "12px",
                  background: "#faf6f0",
                  borderRadius: "12px",
                  border: "1.5px solid #e8ddd5",
                  padding: "14px 16px",
                }}
              >
                {field.icon}
                <input
                  type={field.type}
                  placeholder={field.placeholder}
                  value={form[field.key as keyof typeof form]}
                  onChange={(e) =>
                    setForm({ ...form, [field.key]: e.target.value })
                  }
                  style={{
                    flex: 1,
                    border: "none",
                    outline: "none",
                    background: "transparent",
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "14px",
                    color: "#0A0A3E",
                  }}
                />
              </div>
            </div>
          ))}

          {/* Password */}
          <div style={{ marginBottom: "28px" }}>
            <label
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "11px",
                fontWeight: "700",
                color: "#7a6e65",
                letterSpacing: "1.5px",
                textTransform: "uppercase",
                display: "block",
                marginBottom: "8px",
              }}
            >
              Password{" "}
              <span
                style={{
                  fontFamily: "var(--font-hind)",
                  fontSize: "10px",
                  color: "#c9a84c",
                  textTransform: "none",
                  letterSpacing: "0",
                }}
              >
                পাসওয়ার্ড
              </span>
            </label>
            <div
              style={{
                display: "flex",
                alignItems: "center",
                gap: "12px",
                background: "#faf6f0",
                borderRadius: "12px",
                border: "1.5px solid #e8ddd5",
                padding: "14px 16px",
              }}
            >
              <Lock size={16} color="#c9a84c" strokeWidth={1.5} />
              <input
                type={showPassword ? "text" : "password"}
                placeholder="Min. 8 characters"
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
                onKeyDown={(e) => e.key === "Enter" && handleSubmit()}
                style={{
                  flex: 1,
                  border: "none",
                  outline: "none",
                  background: "transparent",
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "14px",
                  color: "#0A0A3E",
                }}
              />
              <button
                onClick={() => setShowPassword(!showPassword)}
                style={{
                  background: "transparent",
                  border: "none",
                  cursor: "pointer",
                  display: "flex",
                }}
              >
                {showPassword ? (
                  <EyeOff size={16} color="#7a6e65" strokeWidth={1.5} />
                ) : (
                  <Eye size={16} color="#7a6e65" strokeWidth={1.5} />
                )}
              </button>
            </div>
          </div>

          {/* Error */}
          {error && (
            <div
              style={{
                background: "rgba(232,71,10,0.08)",
                border: "1px solid rgba(232,71,10,0.3)",
                borderRadius: "12px",
                padding: "12px 16px",
                marginBottom: "20px",
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                color: "#c23a08",
              }}
            >
              {error}
            </div>
          )}

          {/* Submit */}
          <motion.button
            whileTap={{ scale: 0.98 }}
            onClick={handleSubmit}
            disabled={loading}
            style={{
              width: "100%",
              background: loading ? "#7a6e65" : "#E8470A",
              color: "white",
              border: "none",
              borderRadius: "14px",
              padding: "16px",
              fontSize: "12px",
              fontWeight: "700",
              cursor: loading ? "not-allowed" : "pointer",
              fontFamily: "var(--font-dm-sans)",
              letterSpacing: "2px",
              textTransform: "uppercase",
              boxShadow: "0 8px 24px rgba(232,71,10,0.3)",
              transition: "all 0.3s ease",
              marginBottom: "20px",
            }}
            onMouseEnter={(e) => {
              if (!loading) e.currentTarget.style.background = "#0A0A3E";
            }}
            onMouseLeave={(e) => {
              if (!loading) e.currentTarget.style.background = "#E8470A";
            }}
          >
            {loading ? "Creating Account..." : "Create Account"}
          </motion.button>

          {/* Login Link */}
          <p
            style={{
              textAlign: "center",
              fontFamily: "var(--font-dm-sans)",
              fontSize: "13px",
              color: "#7a6e65",
            }}
          >
            Already have an account?{" "}
            <Link
              href="/account/login"
              style={{
                color: "#E8470A",
                fontWeight: "700",
                textDecoration: "none",
              }}
            >
              Sign in
            </Link>
          </p>
        </div>

        <p
          style={{
            textAlign: "center",
            marginTop: "24px",
            fontFamily: "var(--font-hind)",
            fontSize: "12px",
            color: "#7a6e65",
          }}
        >
          ভালোবাসা, খুনসুটি আর যতু — সবই বাবুসোনা। ♡
        </p>
      </motion.div>
    </div>
  );
}
