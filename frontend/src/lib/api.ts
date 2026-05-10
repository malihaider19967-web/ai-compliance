import type { Expense, Policy } from "./types";

const API_URL =
  process.env.NEXT_PUBLIC_API_URL?.replace(/\/$/, "") ?? "http://localhost:8000";

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${API_URL}${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      ...(init?.headers ?? {}),
    },
    cache: "no-store",
  });

  if (!res.ok) {
    let message = `${res.status} ${res.statusText}`;
    try {
      const body = await res.json();
      if (body?.message) message = body.message;
      if (body?.errors) message += ` — ${JSON.stringify(body.errors)}`;
      if (body?.error) message += ` — ${body.error}`;
    } catch {
      // ignore
    }
    throw new Error(message);
  }

  return res.json() as Promise<T>;
}

export const api = {
  uploadReceipt: async (file: File): Promise<Expense> => {
    const fd = new FormData();
    fd.append("receipt", file);
    const res = await fetch(`${API_URL}/api/receipts`, {
      method: "POST",
      body: fd,
      headers: { Accept: "application/json" },
    });
    if (!res.ok) {
      const body = await res.json().catch(() => ({}));
      throw new Error(body?.message || body?.error || `Upload failed (${res.status})`);
    }
    const data = (await res.json()) as { expense: Expense };
    return data.expense;
  },

  listExpenses: async (): Promise<Expense[]> => {
    const data = await request<{ expenses: Expense[] }>("/api/expenses");
    return data.expenses;
  },

  getExpense: async (id: number): Promise<Expense> => {
    const data = await request<{ expense: Expense }>(`/api/expenses/${id}`);
    return data.expense;
  },

  reevaluateExpense: async (id: number): Promise<Expense> => {
    const data = await request<{ expense: Expense }>(
      `/api/expenses/${id}/reevaluate`,
      { method: "POST" },
    );
    return data.expense;
  },

  deleteExpense: async (id: number): Promise<void> => {
    await request<{ deleted: boolean }>(`/api/expenses/${id}`, {
      method: "DELETE",
    });
  },

  listPolicies: async (): Promise<Policy[]> => {
    const data = await request<{ policies: Policy[] }>("/api/policies");
    return data.policies;
  },

  createPolicy: async (input: {
    name: string;
    rule_text: string;
    active?: boolean;
  }): Promise<Policy> => {
    const data = await request<{ policy: Policy }>("/api/policies", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(input),
    });
    return data.policy;
  },

  updatePolicy: async (
    id: number,
    input: Partial<{ name: string; rule_text: string; active: boolean }>,
  ): Promise<Policy> => {
    const data = await request<{ policy: Policy }>(`/api/policies/${id}`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(input),
    });
    return data.policy;
  },

  deletePolicy: async (id: number): Promise<void> => {
    await request<{ deleted: boolean }>(`/api/policies/${id}`, {
      method: "DELETE",
    });
  },
};
