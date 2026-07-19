import { Navbar } from "@/components/layout/Navbar";
import { CartDrawer } from "@/components/layout/CartDrawer";
import { Footer } from "@/components/layout/Footer";
import { SmoothScroll } from "@/components/layout/SmoothScroll";

export default function MainLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div
      style={{
        minHeight: "100vh",
        display: "flex",
        flexDirection: "column",
      }}
    >
      <SmoothScroll />
      <Navbar />
      <CartDrawer />

      <main style={{ flex: 1 }}>{children}</main>

      <Footer />
    </div>
  );
}
