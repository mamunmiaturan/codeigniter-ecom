import { ProductDetailView } from "@/components/shop/ProductDetailView";

export default async function ProductPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;

  return (
    <div style={{ background: "#ffffff", minHeight: "100vh" }}>
      <div
        style={{
          maxWidth: "1300px",
          margin: "0 auto",
          padding: "48px clamp(1rem, 4vw, 4rem)",
        }}
      >
        <ProductDetailView slug={slug} />
      </div>
    </div>
  );
}
