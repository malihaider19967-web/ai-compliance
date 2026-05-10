<?php

namespace App\Services\Ocr;

interface OcrServiceInterface
{
    /**
     * Extract structured receipt data from an image.
     *
     * @return array{
     *     merchant: ?string,
     *     transaction_date: ?string,
     *     total: ?float,
     *     tax: ?float,
     *     currency: ?string,
     *     category: ?string,
     *     payment_method: ?string,
     *     line_items: array<int, array{description: string, amount: ?float, quantity: ?float}>,
     *     raw: array<string, mixed>
     * }
     */
    public function extractFromImage(string $absolutePath, string $mimeType): array;
}
