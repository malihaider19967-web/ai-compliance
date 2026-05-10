<?php

namespace App\Services\PolicyEngine;

use App\Models\Expense;
use App\Repositories\ExpenseRepository;
use App\Repositories\PolicyRepository;
use Throwable;

class PolicyEvaluationService
{
    public function __construct(
        private readonly PolicyEvaluatorInterface $evaluator,
        private readonly PolicyRepository $policies,
        private readonly ExpenseRepository $expenses,
    ) {
    }

    /**
     * Run every active policy against the expense, persist the results, and return them.
     *
     * @return array<int, array<string, mixed>>
     */
    public function evaluateExpense(Expense $expense): array
    {
        $results = [];

        foreach ($this->policies->active() as $policy) {
            try {
                $results[] = $this->evaluator->evaluate($expense, $policy);
            } catch (Throwable $e) {
                $results[] = [
                    'policy_id' => $policy->id,
                    'policy_name' => $policy->name,
                    'status' => 'error',
                    'reason' => 'Evaluator failed: '.$e->getMessage(),
                ];
            }
        }

        $this->expenses->update($expense, [
            'policy_results' => $results,
            'status' => $this->summarize($results),
        ]);

        return $results;
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function summarize(array $results): string
    {
        $statuses = array_column($results, 'status');
        if (in_array('fail', $statuses, true)) {
            return 'rejected';
        }
        if (in_array('needs_approval', $statuses, true)) {
            return 'needs_approval';
        }
        if ($statuses === [] || in_array('error', $statuses, true)) {
            return 'pending';
        }

        return 'approved';
    }
}
