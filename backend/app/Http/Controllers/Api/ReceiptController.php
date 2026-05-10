<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ExpenseRepository;
use App\Services\Ocr\OcrServiceInterface;
use App\Services\PolicyEngine\PolicyEvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly OcrServiceInterface $ocr,
        private readonly ExpenseRepository $expenses,
        private readonly PolicyEvaluationService $policyEvaluation,
    ) {
    }

    /**
     * Upload a receipt image, run OCR, persist the expense, and evaluate active policies.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,bmp', 'max:10240'],
        ]);

        $file = $request->file('receipt');
        $storedRelative = $file->store('receipts', 'public');
        $absolutePath = Storage::disk('public')->path($storedRelative);
        $mime = $file->getMimeType() ?? 'image/jpeg';

        try {
            $extracted = $this->ocr->extractFromImage($absolutePath, $mime);
        } catch (Throwable $e) {
            Storage::disk('public')->delete($storedRelative);

            return response()->json([
                'message' => 'OCR extraction failed.',
                'error' => $e->getMessage(),
            ], 502);
        }

        $expense = $this->expenses->create([
            'merchant' => $extracted['merchant'],
            'transaction_date' => $extracted['transaction_date'],
            'total' => $extracted['total'],
            'tax' => $extracted['tax'],
            'currency' => $extracted['currency'],
            'category' => $extracted['category'],
            'payment_method' => $extracted['payment_method'],
            'line_items' => $extracted['line_items'],
            'raw_extraction' => $extracted['raw'],
            'receipt_path' => $storedRelative,
            'status' => 'pending',
        ]);

        $this->policyEvaluation->evaluateExpense($expense);
        $expense->refresh();

        return response()->json([
            'expense' => $this->present($expense),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(\App\Models\Expense $expense): array
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
