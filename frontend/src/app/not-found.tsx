import Link from "next/link";

export default function NotFound() {
  return (
    <div
      style={{
        minHeight: "100vh",
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        textAlign: "center",
        padding: "40px",
        background:
          "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 50%, #f5ede6 100%)",
      }}
    >
      <div style={{ maxWidth: "500px" }}>
        {/* Logo */}
        <div style={{ marginBottom: "32px" }}>
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
            style={{ color: "#E8470A", fontSize: "1rem", marginLeft: "3px" }}
          >
            ♡
          </span>
        </div>

        {/* 404 */}
        <h1
          style={{
            fontFamily: "var(--font-playfair)",
            fontSize: "6rem",
            fontWeight: "700",
            color: "#E8470A",
            lineHeight: 1,
            marginBottom: "16px",
            letterSpacing: "-2px",
          }}
        >
          404
        </h1>

        <h2
          style={{
            fontFamily: "var(--font-playfair)",
            fontSize: "1.6rem",
            fontWeight: "700",
            color: "#0A0A3E",
            marginBottom: "12px",
          }}
        >
          Page not found
        </h2>

        <p
          style={{
            fontFamily: "var(--font-hind)",
            fontSize: "14px",
            color: "#7a6e65",
            marginBottom: "8px",
            lineHeight: "1.7",
          }}
        >
          এই পেজটি খুঁজে পাওয়া যাচ্ছে না
        </p>

        <p
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "13px",
            color: "#7a6e65",
            marginBottom: "36px",
            lineHeight: "1.7",
          }}
        >
          Looks like this page wandered off like Babu went to get milk and never
          came back. 😅
        </p>

        <Link href="/" style={{ textDecoration: "none" }}>
          <button
            style={{
              background: "#0A0A3E",
              color: "white",
              border: "none",
              borderRadius: "30px",
              padding: "14px 36px",
              fontSize: "11px",
              fontWeight: "700",
              cursor: "pointer",
              fontFamily: "var(--font-dm-sans)",
              letterSpacing: "2px",
              textTransform: "uppercase",
              boxShadow: "0 8px 24px rgba(10,10,62,0.2)",
              transition: "all 0.3s ease",
            }}
          >
            Go Back Home
          </button>
        </Link>
      </div>
    </div>
  );
}
