export default function SiteContent({
  children,
  className = "",
  classNameChildren = "",
}: {
  children: React.ReactNode;
  className?: string;
  classNameChildren?: string;
}) {
  return (
    <main
      className={`isolate flex-1 w-full h-full mx-auto p-6 sm:p-8 md:p-16 lg:p-16 bg-white dark:bg-background-dark ${className}`}
    >
      <div
        className={`w-full h-full max-w-screen-lg mx-auto ${classNameChildren}`}
      >
        {children}
      </div>
    </main>
  );
}
