export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body
        suppressHydrationWarning
        style={{
          fontFamily: "var(--font-dm-sans, sans-serif)",
          margin: 0,
          background: "#f7f0e6",
          color: "#0a0a3e",
        }}
      >
        {children}
      </body>
    </html>
  );
}
