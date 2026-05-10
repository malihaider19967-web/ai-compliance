<?php

return [
    'api_key' => env('MISTRAL_API_KEY'),
    'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
    'vision_model' => env('MISTRAL_VISION_MODEL', 'pixtral-12b-2409'),
    'chat_model' => env('MISTRAL_CHAT_MODEL', 'mistral-small-latest'),
    'timeout' => (int) env('MISTRAL_TIMEOUT', 60),
];
