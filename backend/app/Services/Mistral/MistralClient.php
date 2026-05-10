<?php

namespace App\Services\Mistral;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MistralClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout,
    ) {
    }

    /**
     * Send a chat-completions request and return the parsed response payload.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function chat(string $model, array $messages, array $extra = []): array
    {
        if ($this->apiKey === '' || $this->apiKey === null) {
            throw new RuntimeException('MISTRAL_API_KEY is not configured.');
        }

        $payload = array_merge([
            'model' => $model,
            'messages' => $messages,
        ], $extra);

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->post(rtrim($this->baseUrl, '/').'/chat/completions', $payload);

        $this->ensureOk($response);

        return $response->json();
    }

    /**
     * Convenience: extract the first assistant message's text content.
     *
     * @param  array<string, mixed>  $response
     */
    public function firstMessageContent(array $response): string
    {
        $content = $response['choices'][0]['message']['content'] ?? null;

        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            // Mistral may return content as an array of parts; concatenate text parts.
            $text = '';
            foreach ($content as $part) {
                if (is_array($part) && isset($part['text'])) {
                    $text .= $part['text'];
                }
            }

            return $text;
        }

        throw new RuntimeException('Mistral response did not contain assistant text content.');
    }

    private function ensureOk(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Mistral request failed (%d): %s',
            $response->status(),
            $response->body(),
        ));
    }
}
