import type { Metadata } from "next";
import "./globals.css";
import Body from "@/components/_core/Body";

export const metadata: Metadata = {
  title: "MolMeDB",
  description: "Molecules on Membranes Database",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <Body>{children}</Body>
    </html>
  );
}
