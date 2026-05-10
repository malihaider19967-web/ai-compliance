import Link from "next/link";

export function Nav() {
  return (
    <header className="border-b border-black/10 dark:border-white/10 bg-background/80 backdrop-blur sticky top-0 z-10">
      <div className="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <Link href="/" className="font-semibold text-lg tracking-tight">
          Expensa <span className="text-foreground/50 font-normal">· AI expenses</span>
        </Link>
        <nav className="flex gap-5 text-sm">
          <Link href="/" className="hover:underline">
            Expenses
          </Link>
          <Link href="/policies" className="hover:underline">
            Policies
          </Link>
        </nav>
      </div>
    </header>
  );
}
