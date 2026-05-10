export type LineItem = {
  description: string;
  amount: number | null;
  quantity: number | null;
};

export type PolicyResultStatus =
  | "pass"
  | "fail"
  | "needs_approval"
  | "not_applicable"
  | "error";

export type PolicyResult = {
  policy_id: number;
  policy_name: string;
  status: PolicyResultStatus;
  reason: string;
};

export type ExpenseStatus =
  | "pending"
  | "approved"
  | "rejected"
  | "needs_approval";

export type Expense = {
  id: number;
  merchant: string | null;
  transaction_date: string | null;
  total: number | null;
  tax: number | null;
  currency: string | null;
  category: string | null;
  payment_method: string | null;
  line_items: LineItem[];
  status: ExpenseStatus;
  policy_results: PolicyResult[];
  receipt_url: string | null;
  created_at: string | null;
};

export type Policy = {
  id: number;
  name: string;
  rule_text: string;
  active: boolean;
  created_at: string | null;
  updated_at: string | null;
};
