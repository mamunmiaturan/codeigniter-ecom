"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import { SlidersHorizontal, X } from "lucide-react";
import {
  fetchProducts,
  fetchCategories,
  fetchBrands,
  type CategoryNode,
  type BrandOption,
  type ProductFilters,
} from "@/lib/api";
import { type Product } from "@/types";
import { ProductCard } from "./ProductCard";
import { FilterSidebar } from "./FilterSidebar";

interface ProductGridProps {
  activeZone: "all" | "babu" | "sona";
}

const SORT_MAP: Record<string, string> = {
  bestselling: "featured",
  newest: "newest",
  "price-low": "price_asc",
  "price-high": "price_desc",
  rating: "featured",
};

const PRICE_MAP: Record<string, { min_price?: number; max_price?: number }> = {
  "under-1000": { max_price: 999.99 },
  "1000-2000": { min_price: 1000, max_price: 2000 },
  "2000-3000": { min_price: 2000.01, max_price: 3000 },
  "above-3000": { min_price: 3000.01 },
};

export function ProductGrid({ activeZone }: ProductGridProps) {
  void activeZone; // Babu/Sona zones have no backend taxonomy yet.
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [selectedBrand, setSelectedBrand] = useState("all");
  const [selectedPrice, setSelectedPrice] = useState("all");
  const [selectedSort, setSelectedSort] = useState("bestselling");
  const [mobileFilterOpen, setMobileFilterOpen] = useState(false);

  const [filtered, setFiltered] = useState<Product[]>([]);
  const [categories, setCategories] = useState<CategoryNode[]>([]);
  const [brands, setBrands] = useState<BrandOption[]>([]);
  const [loading, setLoading] = useState(true);

  // Load the real category tree + brand list once for the sidebar.
  useEffect(() => {
    const controller = new AbortController();
    fetchCategories(controller.signal)
      .then(setCategories)
      .catch((err) => {
        if (err?.name !== "AbortError") console.error(err);
      });
    fetchBrands(controller.signal)
      .then(setBrands)
      .catch((err) => {
        if (err?.name !== "AbortError") console.error(err);
      });
    return () => controller.abort();
  }, []);

  // Re-query the backend whenever a filter changes (server-side filtering).
  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    const filters: ProductFilters = {
      category: selectedCategory,
      brand: selectedBrand,
      sort: SORT_MAP[selectedSort],
      ...PRICE_MAP[selectedPrice],
    };
    fetchProducts(filters, controller.signal)
      .then((items) => {
        setFiltered(items);
        setLoading(false);
      })
      .catch((err) => {
        if (err?.name !== "AbortError") {
          console.error(err);
          setLoading(false);
        }
      });
    return () => controller.abort();
  }, [selectedCategory, selectedBrand, selectedPrice, selectedSort]);

  return (
    <div>
      {/* Mobile Filter Toggle */}
      <div
        style={{
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          marginBottom: "24px",
        }}
      >
        <p
          style={{
            fontFamily: "var(--font-dm-sans)",
            fontSize: "13px",
            color: "#888",
          }}
        >
          Showing{" "}
          <strong style={{ color: "#1a1a1a" }}>
            {loading ? "…" : filtered.length}
          </strong>{" "}
          products
        </p>

        <button
          onClick={() => setMobileFilterOpen(true)}
          style={{
            display: "none",
            alignItems: "center",
            gap: "8px",
            background: "white",
            border: "1.5px solid #e8e8e8",
            borderRadius: "25px",
            padding: "10px 20px",
            fontSize: "13px",
            fontWeight: "600",
            color: "#1a1a1a",
            cursor: "pointer",
            fontFamily: "var(--font-dm-sans)",
          }}
          className="show-mobile-filter"
        >
          <SlidersHorizontal size={15} color="#e91e8c" />
          Filter & Sort
        </button>
      </div>

      <div
        style={{
          display: "grid",
          gridTemplateColumns: "240px 1fr",
          gap: "32px",
          alignItems: "start",
        }}
      >
        {/* Sidebar */}
        <div className="hide-filter-mobile">
          <FilterSidebar
            categories={categories}
            brands={brands}
            selectedCategory={selectedCategory}
            selectedBrand={selectedBrand}
            selectedPrice={selectedPrice}
            selectedSort={selectedSort}
            onCategoryChange={setSelectedCategory}
            onBrandChange={setSelectedBrand}
            onPriceChange={setSelectedPrice}
            onSortChange={setSelectedSort}
          />
        </div>

        {/* Products */}
        <div>
          {loading ? (
            <div
              style={{
                textAlign: "center",
                padding: "80px 20px",
                background: "#fafafa",
                borderRadius: "20px",
                border: "1px solid #f0f0f0",
                fontFamily: "var(--font-dm-sans)",
                fontSize: "13px",
                color: "#aaa",
              }}
            >
              Loading products…
            </div>
          ) : filtered.length === 0 ? (
            <div
              style={{
                textAlign: "center",
                padding: "80px 20px",
                background: "#fafafa",
                borderRadius: "20px",
                border: "1px solid #f0f0f0",
              }}
            >
              <div style={{ fontSize: "3rem", marginBottom: "16px" }}>🔍</div>
              <p
                style={{
                  fontFamily: "var(--font-playfair)",
                  fontSize: "1.2rem",
                  fontWeight: "600",
                  color: "#1a1a1a",
                  marginBottom: "8px",
                }}
              >
                No products found
              </p>
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "13px",
                  color: "#aaa",
                }}
              >
                Try adjusting your filters
              </p>
            </div>
          ) : (
            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(auto-fill, minmax(200px, 1fr))",
                gap: "20px",
              }}
            >
              {filtered.map((product, i) => (
                <ProductCard key={product.id} product={product} index={i} />
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Mobile Filter Drawer */}
      {mobileFilterOpen && (
        <>
          <div
            onClick={() => setMobileFilterOpen(false)}
            style={{
              position: "fixed",
              inset: 0,
              background: "rgba(0,0,0,0.4)",
              zIndex: 300,
            }}
          />
          <div
            style={{
              position: "fixed",
              bottom: 0,
              left: 0,
              right: 0,
              background: "white",
              borderRadius: "24px 24px 0 0",
              padding: "24px",
              zIndex: 301,
              maxHeight: "80vh",
              overflowY: "auto",
            }}
          >
            <div
              style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between",
                marginBottom: "20px",
              }}
            >
              <h3
                style={{
                  fontFamily: "var(--font-playfair)",
                  fontSize: "1.1rem",
                  fontWeight: "700",
                  color: "#1a1a1a",
                }}
              >
                Filter & Sort
              </h3>
              <button
                onClick={() => setMobileFilterOpen(false)}
                style={{
                  background: "transparent",
                  border: "none",
                  cursor: "pointer",
                }}
              >
                <X size={20} color="#666" />
              </button>
            </div>
            <FilterSidebar
              bare
              categories={categories}
              brands={brands}
              selectedCategory={selectedCategory}
              selectedBrand={selectedBrand}
              selectedPrice={selectedPrice}
              selectedSort={selectedSort}
              onCategoryChange={setSelectedCategory}
              onBrandChange={setSelectedBrand}
              onPriceChange={setSelectedPrice}
              onSortChange={setSelectedSort}
            />

            <button
              onClick={() => setMobileFilterOpen(false)}
              style={{
                width: "100%",
                marginTop: "8px",
                background: "#0A0A3E",
                color: "white",
                border: "none",
                borderRadius: "14px",
                padding: "16px",
                fontFamily: "var(--font-dm-sans)",
                fontSize: "12px",
                fontWeight: 700,
                letterSpacing: "1.5px",
                textTransform: "uppercase",
                cursor: "pointer",
              }}
            >
              Show Results
            </button>
          </div>
        </>
      )}

      <style>{`
        @media (max-width: 768px) {
          .hide-filter-mobile { display: none !important; }
          .show-mobile-filter { display: flex !important; }
          div[style*="grid-template-columns: 240px 1fr"] {
            grid-template-columns: 1fr !important;
          }
        }
      `}</style>
    </div>
  );
}
