"use client";

import { useState } from "react";
import { ChevronDown, ChevronUp } from "lucide-react";
import { type CategoryNode, type BrandOption } from "@/lib/api";

const priceRanges = [
  { id: "all", label: "All Prices" },
  { id: "under-1000", label: "Under ৳1,000" },
  { id: "1000-2000", label: "৳1,000 – ৳2,000" },
  { id: "2000-3000", label: "৳2,000 – ৳3,000" },
  { id: "above-3000", label: "Above ৳3,000" },
];

const sortOptions = [
  { id: "bestselling", label: "Best Selling" },
  { id: "newest", label: "Newest First" },
  { id: "price-low", label: "Price: Low to High" },
  { id: "price-high", label: "Price: High to Low" },
  { id: "rating", label: "Top Rated" },
];

function Section({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  const [open, setOpen] = useState(true);

  return (
    <div
      style={{
        borderBottom: "1px solid #e8ddd5",
        paddingBottom: "16px",
        marginBottom: "16px",
      }}
    >
      <button
        onClick={() => setOpen(!open)}
        style={{
          width: "100%",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          background: "transparent",
          border: "none",
          cursor: "pointer",
          padding: "0 0 12px",
          fontFamily: "var(--font-dm-sans)",
          fontWeight: "700",
          fontSize: "11px",
          letterSpacing: "1.5px",
          textTransform: "uppercase",
          color: "#0A0A3E",
        }}
      >
        {title}
        {open ? (
          <ChevronUp size={14} color="#7a6e65" />
        ) : (
          <ChevronDown size={14} color="#7a6e65" />
        )}
      </button>
      {open && children}
    </div>
  );
}

function Radio({
  name,
  checked,
  onChange,
  label,
  indent,
}: {
  name: string;
  checked: boolean;
  onChange: () => void;
  label: string;
  indent?: boolean;
}) {
  return (
    <label
      style={{
        display: "flex",
        alignItems: "center",
        gap: "10px",
        cursor: "pointer",
        fontFamily: "var(--font-dm-sans)",
        fontSize: "13px",
        color: checked ? "#E8470A" : "#7a6e65",
        fontWeight: checked ? "600" : "400",
        paddingLeft: indent ? "16px" : 0,
      }}
    >
      <input
        type="radio"
        name={name}
        checked={checked}
        onChange={onChange}
        style={{ accentColor: "#E8470A" }}
      />
      <span>{label}</span>
    </label>
  );
}

interface FilterSidebarProps {
  categories: CategoryNode[];
  brands: BrandOption[];
  selectedCategory: string;
  selectedBrand: string;
  selectedPrice: string;
  selectedSort: string;
  onCategoryChange: (id: string) => void;
  onBrandChange: (id: string) => void;
  onPriceChange: (id: string) => void;
  onSortChange: (id: string) => void;
  /** Drop the card chrome + heading (for use inside the mobile modal). */
  bare?: boolean;
}

export function FilterSidebar({
  categories,
  brands,
  selectedCategory,
  selectedBrand,
  selectedPrice,
  selectedSort,
  onCategoryChange,
  onBrandChange,
  onPriceChange,
  onSortChange,
  bare = false,
}: FilterSidebarProps) {
  const cardStyle: React.CSSProperties = bare
    ? {}
    : {
        background: "white",
        borderRadius: "20px",
        padding: "24px",
        border: "1px solid #e8ddd5",
        position: "sticky",
        top: "100px",
        boxShadow: "0 4px 24px rgba(10,10,62,0.06)",
      };

  return (
    <div style={cardStyle}>
      {!bare && (
        <h3
          style={{
            fontFamily: "var(--font-playfair)",
            fontSize: "1.1rem",
            fontWeight: "700",
            color: "#0A0A3E",
            marginBottom: "20px",
          }}
        >
          Filter & Sort
        </h3>
      )}

      {/* Sort */}
      <Section title="Sort By">
        <div style={{ display: "flex", flexDirection: "column", gap: "8px" }}>
          {sortOptions.map((opt) => (
            <Radio
              key={opt.id}
              name="sort"
              checked={selectedSort === opt.id}
              onChange={() => onSortChange(opt.id)}
              label={opt.label}
            />
          ))}
        </div>
      </Section>

      {/* Category + subcategory tree */}
      <Section title="Category">
        <div
          style={{
            display: "flex",
            flexDirection: "column",
            gap: "8px",
            maxHeight: "300px",
            overflowY: "auto",
          }}
        >
          <Radio
            name="category"
            checked={selectedCategory === "all"}
            onChange={() => onCategoryChange("all")}
            label="All Products"
          />
          {categories.map((parent) => (
            <div
              key={parent.id}
              style={{ display: "flex", flexDirection: "column", gap: "8px" }}
            >
              {parent.children.length > 0 ? (
                // Parent has subcategories → it's a group header (parents hold
                // no products directly), only the subcategories are selectable.
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    fontWeight: 700,
                    letterSpacing: "0.5px",
                    color: "#0A0A3E",
                    marginTop: "4px",
                  }}
                >
                  {parent.label}
                </p>
              ) : (
                <Radio
                  name="category"
                  checked={selectedCategory === parent.id}
                  onChange={() => onCategoryChange(parent.id)}
                  label={parent.label}
                />
              )}
              {parent.children.map((child) => (
                <Radio
                  key={child.id}
                  name="category"
                  checked={selectedCategory === child.id}
                  onChange={() => onCategoryChange(child.id)}
                  label={child.label}
                  indent
                />
              ))}
            </div>
          ))}
        </div>
      </Section>

      {/* Brand */}
      {brands.length > 0 && (
        <Section title="Brand">
          <div
            style={{
              display: "flex",
              flexDirection: "column",
              gap: "8px",
              maxHeight: "220px",
              overflowY: "auto",
            }}
          >
            <Radio
              name="brand"
              checked={selectedBrand === "all"}
              onChange={() => onBrandChange("all")}
              label="All Brands"
            />
            {brands.map((b) => (
              <Radio
                key={b.id}
                name="brand"
                checked={selectedBrand === b.id}
                onChange={() => onBrandChange(b.id)}
                label={b.label}
              />
            ))}
          </div>
        </Section>
      )}

      {/* Price */}
      <Section title="Price Range">
        <div style={{ display: "flex", flexDirection: "column", gap: "8px" }}>
          {priceRanges.map((range) => (
            <Radio
              key={range.id}
              name="price"
              checked={selectedPrice === range.id}
              onChange={() => onPriceChange(range.id)}
              label={range.label}
            />
          ))}
        </div>
      </Section>
    </div>
  );
}
