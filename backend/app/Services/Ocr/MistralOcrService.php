<?php

namespace App\Services\Ocr;

use App\Services\Mistral\MistralClient;
use RuntimeException;

class MistralOcrService implements OcrServiceInterface
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a receipt parser. The user will send a photo of a receipt.
Return ONLY a single JSON object — no markdown fences, no commentary — with this shape:

{
  "merchant": string | null,
  "transaction_date": string | null,   // ISO 8601 if possible (YYYY-MM-DD or full timestamp)
  "total": number | null,              // grand total, numeric (no currency symbol)
  "tax": number | null,                // tax amount if shown
  "currency": string | null,           // ISO 4217 code if inferable (USD, EUR, GBP, ...)
  "category": string | null,           // best guess: meals, travel, lodging, supplies, transport, fuel, other
  "payment_method": string | null,     // e.g. "Visa **** 1234", "cash"
  "line_items": [                      // omit or use [] if not visible
    { "description": string, "amount": number | null, "quantity": number | null }
  ]
}

If a field is not present on the receipt, set it to null. Do not invent values.
PROMPT;

    public function __construct(
        private readonly MistralClient $client,
        private readonly string $visionModel,
    ) {
    }

    public function extractFromImage(string $absolutePath, string $mimeType): array
    {
        $bytes = @file_get_contents($absolutePath);
        if ($bytes === false) {
            throw new RuntimeException("Unable to read receipt image at {$absolutePath}");
        }

        $dataUrl = 'data:'.$mimeType.';base64,'.base64_encode($bytes);

        $response = $this->client->chat(
            $this->visionModel,
            [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Extract the receipt data as JSON.'],
                        ['type' => 'image_url', 'image_url' => $dataUrl],
                    ],
                ],
            ],
            [
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
            ],
        );

        $content = $this->client->firstMessageContent($response);
        $parsed = $this->parseJson($content);

        return [
            'merchant' => $this->stringOrNull($parsed['merchant'] ?? null),
            'transaction_date' => $this->stringOrNull($parsed['transaction_date'] ?? null),
            'total' => $this->floatOrNull($parsed['total'] ?? null),
            'tax' => $this->floatOrNull($parsed['tax'] ?? null),
            'currency' => $this->stringOrNull($parsed['currency'] ?? null),
            'category' => $this->stringOrNull($parsed['category'] ?? null),
            'payment_method' => $this->stringOrNull($parsed['payment_method'] ?? null),
            'line_items' => $this->normalizeLineItems($parsed['line_items'] ?? []),
            'raw' => $parsed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJson(string $content): array
    {
        $trimmed = trim($content);

        // Strip ```json fences if the model added them despite instructions.
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $trimmed) ?? $trimmed;
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Mistral returned non-JSON content: '.$content);
        }

        return $decoded;
    }

    /**
     * @param  mixed  $items
     * @return array<int, array{description: string, amount: ?float, quantity: ?float}>
     */
    private function normalizeLineItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $description = $this->stringOrNull($item['description'] ?? null);
            if ($description === null) {
                continue;
            }
            $result[] = [
                'description' => $description,
                'amount' => $this->floatOrNull($item['amount'] ?? null),
                'quantity' => $this->floatOrNull($item['quantity'] ?? null),
            ];
        }

        return $result;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        // Strip currency symbols and commas.
        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if ($cleaned === '' || $cleaned === null) {
            return null;
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
