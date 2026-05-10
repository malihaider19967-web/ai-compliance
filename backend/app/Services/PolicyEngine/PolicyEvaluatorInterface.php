<?php

namespace App\Services\PolicyEngine;

use App\Models\Expense;
use App\Models\Policy;

interface PolicyEvaluatorInterface
{
    /**
     * Evaluate a single expense against a single policy.
     *
     * @return array{
     *     policy_id: int,
     *     policy_name: string,
     *     status: 'pass'|'fail'|'needs_approval'|'not_applicable',
     *     reason: string
     * }
     */
    public function evaluate(Expense $expense, Policy $policy): array;
}
