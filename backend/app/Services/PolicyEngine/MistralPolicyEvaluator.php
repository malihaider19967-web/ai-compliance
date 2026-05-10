<?php

namespace App\Services\PolicyEngine;

use App\Models\Expense;
use App\Models\Policy;
use App\Services\Mistral\MistralClient;
use RuntimeException;

class MistralPolicyEvaluator implements PolicyEvaluatorInterface
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an expense-policy compliance engine.

You will receive:
1. A POLICY written in plain English.
2. An EXPENSE described as a JSON object.

Decide whether the expense complies with the policy and return ONLY a JSON object:

{
  "status": "pass" | "fail" | "needs_approval" | "not_applicable",
  "reason": string   // one short sentence the user can read
}

Rules:
- "pass"            — expense clearly satisfies the policy.
- "fail"            — expense clearly violates the policy.
- "needs_approval"  — policy requires approval/manager sign-off for this case.
- "not_applicable"  — the policy does not concern this expense (different category, etc.).

Be strict but fair. If the expense is missing data needed to judge, lean to "needs_approval"
and say what is missing. Do not invent facts.
PROMPT;

    public function __construct(
        private readonly MistralClient $client,
        private readonly string $chatModel,
    ) {
    }

    public function evaluate(Expense $expense, Policy $policy): array
    {
        $expenseJson = json_encode($this->expensePayload($expense), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $userMessage = "POLICY:\n{$policy->rule_text}\n\nEXPENSE:\n{$expenseJson}";

        $response = $this->client->chat(
            $this->chatModel,
            [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $userMessage],
            ],
            [
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
            ],
        );

        $parsed = $this->parseJson($this->client->firstMessageContent($response));

        $status = $parsed['status'] ?? 'needs_approval';
        if (! in_array($status, ['pass', 'fail', 'needs_approval', 'not_applicable'], true)) {
            $status = 'needs_approval';
        }

        return [
            'policy_id' => $policy->id,
            'policy_name' => $policy->name,
            'status' => $status,
            'reason' => (string) ($parsed['reason'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function expensePayload(Expense $expense): array
    {
        return [
            'merchant' => $expense->merchant,
            'transaction_date' => optional($expense->transaction_date)->toIso8601String(),
            'total' => $expense->total !== null ? (float) $expense->total : null,
            'tax' => $expense->tax !== null ? (float) $expense->tax : null,
            'currency' => $expense->currency,
            'category' => $expense->category,
            'payment_method' => $expense->payment_method,
            'line_items' => $expense->line_items ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJson(string $content): array
    {
        $trimmed = trim($content);
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $trimmed) ?? $trimmed;
        }
        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Mistral policy response was not JSON: '.$content);
        }

        return $decoded;
    }
}
