"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";

export function ExpenseActions({ id }: { id: number }) {
  const router = useRouter();
  const [busy, setBusy] = useState<"reeval" | "delete" | null>(null);
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  const reevaluate = () => {
    setError(null);
    setBusy("reeval");
    api
      .reevaluateExpense(id)
      .then(() => startTransition(() => router.refresh()))
      .catch((e: Error) => setError(e.message))
      .finally(() => setBusy(null));
  };

  const remove = () => {
    if (!confirm("Delete this expense?")) return;
    setError(null);
    setBusy("delete");
    api
      .deleteExpense(id)
      .then(() =>
        startTransition(() => {
          router.refresh();
          router.push("/");
        }),
      )
      .catch((e: Error) => setError(e.message))
      .finally(() => setBusy(null));
  };

  const loading = busy !== null || pending;

  return (
    <div className="flex flex-wrap items-center gap-2">
      <button
        onClick={reevaluate}
        disabled={loading}
        className="rounded-md border border-foreground/20 px-3 py-1.5 text-sm hover:bg-foreground/5 disabled:opacity-50"
      >
        {busy === "reeval" ? "Re-evaluating…" : "Re-run policies"}
      </button>
      <button
        onClick={remove}
        disabled={loading}
        className="rounded-md border border-rose-500/30 px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50 disabled:opacity-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
      >
        {busy === "delete" ? "Deleting…" : "Delete"}
      </button>
      {error && (
        <span className="text-sm text-rose-700 dark:text-rose-300">{error}</span>
      )}
    </div>
  );
}
