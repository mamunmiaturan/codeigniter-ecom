"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { fetchProduct } from "@/lib/api";
import { type Product } from "@/types";
import { ProductDetail } from "./ProductDetail";

type State =
  | { status: "loading" }
  | { status: "error" }
  | { status: "notfound" }
  | { status: "ready"; product: Product };

export function ProductDetailView({ slug }: { slug: string }) {
  const [state, setState] = useState<State>({ status: "loading" });

  useEffect(() => {
    const controller = new AbortController();
    setState({ status: "loading" });
    fetchProduct(slug, controller.signal)
      .then((product) => {
        if (!product) setState({ status: "notfound" });
        else setState({ status: "ready", product });
      })
      .catch((err) => {
        if (err?.name !== "AbortError") setState({ status: "error" });
      });
    return () => controller.abort();
  }, [slug]);

  if (state.status === "ready") {
    return <ProductDetail product={state.product} />;
  }

  const message =
    state.status === "loading"
      ? "Loading product…"
      : state.status === "notfound"
        ? "Product not found."
        : "Something went wrong loading this product.";

  return (
    <div
      style={{
        textAlign: "center",
        padding: "120px 20px",
        fontFamily: "var(--font-dm-sans)",
      }}
    >
      <p style={{ fontSize: "15px", color: "#7a6e65", marginBottom: "16px" }}>
        {message}
      </p>
      {state.status !== "loading" && (
        <Link
          href="/shop"
          style={{
            fontSize: "12px",
            fontWeight: 700,
            letterSpacing: "1px",
            color: "#E8470A",
            textDecoration: "none",
          }}
        >
          ← Back to Shop
        </Link>
      )}
    </div>
  );
}
