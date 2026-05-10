"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import type { Policy } from "@/lib/types";

const SAMPLES = [
  "Meals over $75 per person require manager approval.",
  "All travel expenses must include the city and dates of travel; otherwise, fail.",
  "Alcohol purchases are not reimbursable.",
  "Reimbursable lodging is capped at $250 per night.",
];

export function PolicyManager({ initial }: { initial: Policy[] }) {
  const router = useRouter();
  const [pending, startTransition] = useTransition();
  const [policies, setPolicies] = useState<Policy[]>(initial);
  const [name, setName] = useState("");
  const [ruleText, setRuleText] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function refresh() {
    const list = await api.listPolicies();
    setPolicies(list);
    startTransition(() => router.refresh());
  }

  async function onCreate(e: React.FormEvent) {
    e.preventDefault();
    if (!name.trim() || !ruleText.trim()) return;
    setBusy(true);
    setError(null);
    try {
      await api.createPolicy({ name: name.trim(), rule_text: ruleText.trim() });
      setName("");
      setRuleText("");
      await refresh();
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setBusy(false);
    }
  }

  async function toggleActive(p: Policy) {
    try {
      await api.updatePolicy(p.id, { active: !p.active });
      await refresh();
    } catch (err) {
      setError((err as Error).message);
    }
  }

  async function remove(p: Policy) {
    if (!confirm(`Delete policy "${p.name}"?`)) return;
    try {
      await api.deletePolicy(p.id);
      await refresh();
    } catch (err) {
      setError((err as Error).message);
    }
  }

  return (
    <div className="space-y-8">
      <form
        onSubmit={onCreate}
        className="rounded-xl border border-foreground/10 p-5 space-y-3"
      >
        <h2 className="font-medium">Add policy</h2>
        <div>
          <label className="block text-xs text-foreground/60 mb-1">Name</label>
          <input
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Meals cap"
            className="w-full rounded-md border border-foreground/15 bg-background px-3 py-2 text-sm focus:outline-none focus:border-foreground/40"
          />
        </div>
        <div>
          <label className="block text-xs text-foreground/60 mb-1">
            Rule (plain English)
          </label>
          <textarea
            value={ruleText}
            onChange={(e) => setRuleText(e.target.value)}
            rows={3}
            placeholder="Meals over $75 per person require manager approval."
            className="w-full rounded-md border border-foreground/15 bg-background px-3 py-2 text-sm focus:outline-none focus:border-foreground/40 font-mono"
          />
          <div className="mt-2 flex flex-wrap gap-2">
            {SAMPLES.map((s) => (
              <button
                key={s}
                type="button"
                onClick={() => setRuleText(s)}
                className="text-xs rounded-full border border-foreground/15 px-2.5 py-1 text-foreground/70 hover:bg-foreground/5"
              >
                {s.length > 40 ? s.slice(0, 40) + "…" : s}
              </button>
            ))}
          </div>
        </div>
        <div className="flex items-center gap-3">
          <button
            type="submit"
            disabled={busy || pending || !name.trim() || !ruleText.trim()}
            className="rounded-md bg-foreground px-4 py-2 text-sm font-medium text-background hover:opacity-90 disabled:opacity-50"
          >
            {busy ? "Saving…" : "Add policy"}
          </button>
          {error && (
            <span className="text-sm text-rose-700 dark:text-rose-300">
              {error}
            </span>
          )}
        </div>
      </form>

      <section>
        <div className="flex items-baseline justify-between mb-3">
          <h2 className="text-xl font-semibold">Active policies</h2>
          <span className="text-sm text-foreground/50">
            {policies.length} total
          </span>
        </div>
        {policies.length === 0 ? (
          <div className="text-sm text-foreground/60 rounded-lg border border-foreground/10 p-6 text-center">
            No policies yet. Add one above.
          </div>
        ) : (
          <ul className="space-y-2">
            {policies.map((p) => (
              <li
                key={p.id}
                className="rounded-lg border border-foreground/10 p-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
              >
                <div className="min-w-0">
                  <div className="font-medium">
                    {p.name}{" "}
                    {!p.active && (
                      <span className="ml-1 rounded bg-foreground/10 px-1.5 py-0.5 text-xs text-foreground/60">
                        disabled
                      </span>
                    )}
                  </div>
                  <p className="text-sm text-foreground/70 whitespace-pre-wrap mt-0.5">
                    {p.rule_text}
                  </p>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                  <button
                    onClick={() => toggleActive(p)}
                    className="rounded-md border border-foreground/20 px-2.5 py-1 text-xs hover:bg-foreground/5"
                  >
                    {p.active ? "Disable" : "Enable"}
                  </button>
                  <button
                    onClick={() => remove(p)}
                    className="rounded-md border border-rose-500/30 px-2.5 py-1 text-xs text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                  >
                    Delete
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
