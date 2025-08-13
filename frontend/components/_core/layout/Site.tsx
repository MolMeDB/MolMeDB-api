export default function Site({
  children,
  className = "",
}: {
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <div
      className={`mx-auto h-full w-full max-w-[2000px] bg-white ${className}`}
    >
      {children}
    </div>
  );
}
