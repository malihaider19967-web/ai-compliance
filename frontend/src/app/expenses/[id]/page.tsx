import Link from "next/link";
import { notFound } from "next/navigation";
import { api } from "@/lib/api";
import { formatDate, formatMoney } from "@/lib/format";
import { StatusBadge } from "@/components/status-badge";
import { ExpenseActions } from "@/components/expense-actions";

const API_URL =
  process.env.NEXT_PUBLIC_API_URL?.replace(/\/$/, "") ?? "http://localhost:8000";

function absoluteReceiptUrl(url: string | null): string | null {
  if (!url) return null;
  if (url.startsWith("http")) return url;
  return `${API_URL}${url}`;
}

export default async function ExpenseDetailPage(
  props: PageProps<"/expenses/[id]">,
) {
  const { id } = await props.params;
  const numericId = Number(id);
  if (Number.isNaN(numericId)) notFound();

  let expense;
  try {
    expense = await api.getExpense(numericId);
  } catch {
    notFound();
  }

  return (
    <div className="space-y-8">
      <div>
        <Link href="/" className="text-sm text-foreground/60 hover:underline">
          ← Back to expenses
        </Link>
      </div>

      <div className="grid gap-8 md:grid-cols-[1fr_300px]">
        <div className="space-y-6">
          <header>
            <div className="flex flex-wrap items-center gap-3 mb-2">
              <h1 className="text-2xl font-semibold">
                {expense.merchant ?? "Unknown merchant"}
              </h1>
              <StatusBadge status={expense.status} />
            </div>
            <div className="text-sm text-foreground/60">
              {formatDate(expense.transaction_date)}
              {expense.category && ` · ${expense.category}`}
              {expense.payment_method && ` · ${expense.payment_method}`}
            </div>
          </header>

          <section className="rounded-lg border border-foreground/10 p-5 space-y-3">
            <h2 className="text-sm uppercase tracking-wide text-foreground/50">
              Totals
            </h2>
            <dl className="grid grid-cols-2 gap-y-2 text-sm">
              <dt className="text-foreground/60">Total</dt>
              <dd className="font-mono font-medium text-right">
                {formatMoney(expense.total, expense.currency)}
              </dd>
              <dt className="text-foreground/60">Tax</dt>
              <dd className="font-mono text-right">
                {formatMoney(expense.tax, expense.currency)}
              </dd>
              <dt className="text-foreground/60">Currency</dt>
              <dd className="text-right">{expense.currency ?? "—"}</dd>
            </dl>
          </section>

          {expense.line_items.length > 0 && (
            <section className="rounded-lg border border-foreground/10 p-5">
              <h2 className="text-sm uppercase tracking-wide text-foreground/50 mb-3">
                Line items
              </h2>
              <ul className="divide-y divide-foreground/10 text-sm">
                {expense.line_items.map((item, i) => (
                  <li key={i} className="flex justify-between gap-4 py-2">
                    <span>
                      {item.description}
                      {item.quantity ? (
                        <span className="text-foreground/50">
                          {" "}
                          × {item.quantity}
                        </span>
                      ) : null}
                    </span>
                    <span className="font-mono">
                      {formatMoney(item.amount, expense.currency)}
                    </span>
                  </li>
                ))}
              </ul>
            </section>
          )}

          <section className="rounded-lg border border-foreground/10 p-5">
            <h2 className="text-sm uppercase tracking-wide text-foreground/50 mb-3">
              Policy results
            </h2>
            {expense.policy_results.length === 0 ? (
              <p className="text-sm text-foreground/60">
                No active policies. Define one in{" "}
                <Link href="/policies" className="underline">
                  Policies
                </Link>
                , then re-run.
              </p>
            ) : (
              <ul className="space-y-3">
                {expense.policy_results.map((r) => (
                  <li
                    key={r.policy_id}
                    className="flex flex-col gap-1 rounded-md bg-foreground/[0.03] p-3 sm:flex-row sm:items-start sm:justify-between"
                  >
                    <div className="min-w-0">
                      <div className="font-medium text-sm">{r.policy_name}</div>
                      <div className="text-sm text-foreground/70">
                        {r.reason || "—"}
                      </div>
                    </div>
                    <StatusBadge status={r.status} />
                  </li>
                ))}
              </ul>
            )}
          </section>

          <ExpenseActions id={expense.id} />
        </div>

        <aside className="space-y-3">
          <h2 className="text-sm uppercase tracking-wide text-foreground/50">
            Receipt
          </h2>
          {expense.receipt_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={absoluteReceiptUrl(expense.receipt_url) ?? ""}
              alt="Receipt"
              className="w-full rounded-md border border-foreground/10"
            />
          ) : (
            <div className="text-sm text-foreground/60">No image stored.</div>
          )}
        </aside>
      </div>
    </div>
  );
}
