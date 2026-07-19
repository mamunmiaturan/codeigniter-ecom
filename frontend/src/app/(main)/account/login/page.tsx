"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Eye, EyeOff, Mail, Lock } from "lucide-react";
import { useAuthStore } from "@/store/authStore";

export default function LoginPage() {
  const router = useRouter();
  const { login, loading, error, clearError, token } = useAuthStore();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);

  // Already signed in → skip the form.
  useEffect(() => {
    if (token) router.replace("/account");
  }, [token, router]);

  useEffect(() => clearError, [clearError]);

  const handleSubmit = async () => {
    if (!email || !password) return;
    const ok = await login(email, password);
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
            Welcome Back
          </h1>
          <p
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "13px",
              color: "#c9a84c",
              marginBottom: "32px",
            }}
          >
            আবার স্বাগতম ❤️
          </p>

          {/* Email */}
          <div style={{ marginBottom: "20px" }}>
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
              Email Address
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
                transition: "border 0.2s ease",
              }}
              onFocus={() => {}}
            >
              <Mail size={16} color="#c9a84c" strokeWidth={1.5} />
              <input
                type="email"
                placeholder="your@email.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
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

          {/* Password */}
          <div style={{ marginBottom: "28px" }}>
            <div
              style={{
                display: "flex",
                justifyContent: "space-between",
                alignItems: "center",
                marginBottom: "8px",
              }}
            >
              <label
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  fontWeight: "700",
                  color: "#7a6e65",
                  letterSpacing: "1.5px",
                  textTransform: "uppercase",
                }}
              >
                Password
              </label>
              <Link
                href="#"
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "11px",
                  color: "#E8470A",
                  textDecoration: "none",
                  fontWeight: "600",
                }}
              >
                Forgot Password?
              </Link>
            </div>
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
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
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
              background: loading ? "#7a6e65" : "#0A0A3E",
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
              boxShadow: "0 8px 24px rgba(10,10,62,0.2)",
              transition: "all 0.3s ease",
              marginBottom: "20px",
            }}
            onMouseEnter={(e) => {
              if (!loading) e.currentTarget.style.background = "#E8470A";
            }}
            onMouseLeave={(e) => {
              if (!loading) e.currentTarget.style.background = "#0A0A3E";
            }}
          >
            {loading ? "Signing in..." : "Sign In"}
          </motion.button>

          {/* Divider */}
          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: "12px",
              marginBottom: "20px",
            }}
          >
            <div style={{ flex: 1, height: "1px", background: "#e8ddd5" }} />
            <span
              style={{
                fontFamily: "var(--font-dm-sans)",
                fontSize: "11px",
                color: "#7a6e65",
                letterSpacing: "1px",
              }}
            >
              OR
            </span>
            <div style={{ flex: 1, height: "1px", background: "#e8ddd5" }} />
          </div>

          {/* Register Link */}
          <p
            style={{
              textAlign: "center",
              fontFamily: "var(--font-dm-sans)",
              fontSize: "13px",
              color: "#7a6e65",
            }}
          >
            Don&apos;t have an account?{" "}
            <Link
              href="/account/register"
              style={{
                color: "#E8470A",
                fontWeight: "700",
                textDecoration: "none",
              }}
            >
              Register now
            </Link>
          </p>
        </div>

        {/* Back */}
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
