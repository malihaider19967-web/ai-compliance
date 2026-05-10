import { api } from "@/lib/api";
import { UploadForm } from "@/components/upload-form";
import { ExpenseRow } from "@/components/expense-row";
import type { Expense } from "@/lib/types";

export default async function HomePage() {
  let expenses: Expense[] = [];
  let loadError: string | null = null;
  try {
    expenses = await api.listExpenses();
  } catch (e) {
    loadError = (e as Error).message;
  }

  return (
    <div className="space-y-8">
      <section>
        <h1 className="text-2xl font-semibold mb-1">New expense</h1>
        <p className="text-sm text-foreground/60 mb-4">
          Drop in a photo of a receipt — Mistral pulls out the merchant, total,
          line items and category, then your policies decide what to do with it.
        </p>
        <UploadForm />
      </section>

      <section>
        <div className="flex items-baseline justify-between mb-3">
          <h2 className="text-xl font-semibold">Recent expenses</h2>
          <span className="text-sm text-foreground/50">
            {expenses.length} total
          </span>
        </div>

        {loadError && (
          <div className="rounded-md bg-rose-50 p-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
            Could not load expenses: {loadError}. Is the Laravel API running on{" "}
            <code>localhost:8000</code>?
          </div>
        )}

        {!loadError && expenses.length === 0 && (
          <div className="text-sm text-foreground/60 rounded-lg border border-foreground/10 p-6 text-center">
            No expenses yet. Upload your first receipt above.
          </div>
        )}

        <div className="space-y-2">
          {expenses.map((e) => (
            <ExpenseRow key={e.id} expense={e} />
          ))}
        </div>
      </section>
    </div>
  );
}
