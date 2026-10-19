<?php

/*
 * Merge these entries into the array returned by config/services.php.
 * Do not replace your existing services.php file with this snippet.
 */

return [
    'ai' => [
        // Choose: openai or anthropic.
        'provider' => env('AI_PROVIDER', 'openai'),
        'timeout' => (int) env('AI_TIMEOUT', 30),
        'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 5),
        'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 1200),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
    ],
];
