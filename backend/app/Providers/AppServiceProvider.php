<?php

namespace App\Providers;

use App\Services\Mistral\MistralClient;
use App\Services\Ocr\MistralOcrService;
use App\Services\Ocr\OcrServiceInterface;
use App\Services\PolicyEngine\MistralPolicyEvaluator;
use App\Services\PolicyEngine\PolicyEvaluatorInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MistralClient::class, function (): MistralClient {
            return new MistralClient(
                apiKey: (string) config('mistral.api_key'),
                baseUrl: (string) config('mistral.base_url'),
                timeout: (int) config('mistral.timeout'),
            );
        });

        $this->app->bind(OcrServiceInterface::class, function ($app): OcrServiceInterface {
            return new MistralOcrService(
                client: $app->make(MistralClient::class),
                visionModel: (string) config('mistral.vision_model'),
            );
        });

        $this->app->bind(PolicyEvaluatorInterface::class, function ($app): PolicyEvaluatorInterface {
            return new MistralPolicyEvaluator(
                client: $app->make(MistralClient::class),
                chatModel: (string) config('mistral.chat_model'),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
