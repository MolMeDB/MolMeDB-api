import { cn } from "@heroui/react";

export default function SimpleSiteHeader(props: {
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "w-full h-44 lg:h-52 px-8 md:px-16 xl:px-64 py-8 bg-gradient-to-br from-intro-blue via-intro-purple to-intro-pink text-zinc-200",
        props.className
      )}
    >
      {props.children}
    </div>
  );
}
