<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Repositories\ExpenseRepository;
use App\Services\PolicyEngine\PolicyEvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly PolicyEvaluationService $policyEvaluation,
    ) {
    }

    public function index(): JsonResponse
    {
        $items = $this->expenses->all()->map(fn (Expense $e) => $this->present($e));

        return response()->json(['expenses' => $items->all()]);
    }

    public function show(int $id): JsonResponse
    {
        $expense = $this->expenses->find($id);
        if (! $expense) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json(['expense' => $this->present($expense)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $expense = $this->expenses->find($id);
        if (! $expense) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $data = $request->validate([
            'merchant' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
            'total' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:8'],
            'category' => ['nullable', 'string', 'max:64'],
            'payment_method' => ['nullable', 'string', 'max:64'],
        ]);

        $this->expenses->update($expense, $data);

        return response()->json(['expense' => $this->present($expense->fresh())]);
    }

    public function reevaluate(int $id): JsonResponse
    {
        $expense = $this->expenses->find($id);
        if (! $expense) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $this->policyEvaluation->evaluateExpense($expense);

        return response()->json(['expense' => $this->present($expense->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $expense = $this->expenses->find($id);
        if (! $expense) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }
        $this->expenses->delete($expense);

        return response()->json(['deleted' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'merchant' => $expense->merchant,
            'transaction_date' => optional($expense->transaction_date)->toIso8601String(),
            'total' => $expense->total !== null ? (float) $expense->total : null,
            'tax' => $expense->tax !== null ? (float) $expense->tax : null,
            'currency' => $expense->currency,
            'category' => $expense->category,
            'payment_method' => $expense->payment_method,
            'line_items' => $expense->line_items ?? [],
            'status' => $expense->status,
            'policy_results' => $expense->policy_results ?? [],
            'receipt_url' => $expense->receipt_path
                ? Storage::disk('public')->url($expense->receipt_path)
                : null,
            'created_at' => $expense->created_at?->toIso8601String(),
        ];
    }
}
