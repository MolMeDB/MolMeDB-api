import type { Metadata } from "next";
import "./globals.css";
import Body from "@/components/_core/Body";
import FeedbackWidget from "@/components/feedback/FeedbackWidget";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import { Cookie } from "@/lib/api/cookies";
import { Suspense } from "react";

export const metadata: Metadata = {
  title: "MolMeDB",
  description: "Molecules on Membranes Database",
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const user: UserSession | undefined =
    (await Cookie.getUserData()) as UserSession;

  return (
    <html lang="en">
      <Body>
        {children}
        <Suspense fallback={null}>
          <FeedbackWidget user={user} />
        </Suspense>
      </Body>
    </html>
  );
}
