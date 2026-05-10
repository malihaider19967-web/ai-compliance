import { api } from "@/lib/api";
import type { Policy } from "@/lib/types";
import { PolicyManager } from "@/components/policy-manager";

export default async function PoliciesPage() {
  let policies: Policy[] = [];
  let loadError: string | null = null;
  try {
    policies = await api.listPolicies();
  } catch (e) {
    loadError = (e as Error).message;
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold mb-1">Expense policies</h1>
        <p className="text-sm text-foreground/60">
          Write rules in plain English. Mistral interprets each one against every
          uploaded expense and decides{" "}
          <span className="font-medium">pass</span>,{" "}
          <span className="font-medium">fail</span>, or{" "}
          <span className="font-medium">needs approval</span>.
        </p>
      </header>

      {loadError ? (
        <div className="rounded-md bg-rose-50 p-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
          Could not load policies: {loadError}
        </div>
      ) : (
        <PolicyManager initial={policies} />
      )}
    </div>
  );
}
