"use client";

import { cn, HeroUIProvider, ToastProvider } from "@heroui/react";
import { Plus_Jakarta_Sans } from "next/font/google";

const geistSans = Plus_Jakarta_Sans({
  subsets: ["latin"],
  display: "swap",
});

export default function Body(props: { children: React.ReactNode }) {
  return (
    <body
      className={cn(
        `${geistSans.className} min-h-dvh antialiased`,
        "text-foreground",
        "bg-[#f0f1f5] dark:bg-[#111111]"
      )}
    >
      <HeroUIProvider>
        <ToastProvider placement="top-right" toastOffset={60} />
        <div className="flex flex-col w-full min-h-dvh">{props.children}</div>
      </HeroUIProvider>
    </body>
  );
}
