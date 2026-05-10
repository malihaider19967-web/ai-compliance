import Link from "next/link";
import type { Expense } from "@/lib/types";
import { formatDate, formatMoney } from "@/lib/format";
import { StatusBadge } from "./status-badge";

export function ExpenseRow({ expense }: { expense: Expense }) {
  return (
    <Link
      href={`/expenses/${expense.id}`}
      className="block rounded-lg border border-foreground/10 p-4 hover:border-foreground/30 transition-colors"
    >
      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <h3 className="font-medium truncate">
              {expense.merchant ?? "Unknown merchant"}
            </h3>
            <StatusBadge status={expense.status} />
          </div>
          <div className="text-sm text-foreground/60 flex flex-wrap gap-x-3 gap-y-1">
            <span>{formatDate(expense.transaction_date)}</span>
            {expense.category && <span>· {expense.category}</span>}
            {expense.payment_method && <span>· {expense.payment_method}</span>}
          </div>
        </div>
        <div className="text-right shrink-0">
          <div className="font-mono font-medium">
            {formatMoney(expense.total, expense.currency)}
          </div>
          {expense.policy_results.length > 0 && (
            <div className="text-xs text-foreground/50 mt-1">
              {expense.policy_results.length} polic
              {expense.policy_results.length === 1 ? "y" : "ies"} checked
            </div>
          )}
        </div>
      </div>
    </Link>
  );
}
