import type { ExpenseStatus, PolicyResultStatus } from "@/lib/types";

const colors: Record<string, string> = {
  pass: "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300",
  approved: "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300",
  fail: "bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300",
  rejected: "bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300",
  needs_approval: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300",
  pending: "bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300",
  not_applicable: "bg-zinc-100 text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-400",
  error: "bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300",
};

const labels: Record<string, string> = {
  pass: "Pass",
  approved: "Approved",
  fail: "Fail",
  rejected: "Rejected",
  needs_approval: "Needs approval",
  pending: "Pending",
  not_applicable: "N/A",
  error: "Error",
};

export function StatusBadge({
  status,
}: {
  status: ExpenseStatus | PolicyResultStatus;
}) {
  const cls = colors[status] ?? colors.pending;
  const label = labels[status] ?? status;
  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${cls}`}
    >
      {label}
    </span>
  );
}
