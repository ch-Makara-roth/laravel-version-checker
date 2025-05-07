<?php

return [
    'telegram' => [
        'chat_id' => env('VERSION_CHECKER_TELEGRAM_CHAT_ID'),
    ],
    'github' => [
        'api_url' => env('VERSION_CHECKER_GITHUB_API_URL', 'https://api.github.com/repos/laravel/framework/releases/latest'),
        'token' => env('VERSION_CHECKER_GITHUB_TOKEN'),
    ],
    'schedule' => [
        'enabled' => env('VERSION_CHECKER_SCHEDULE_ENABLED', true),
        'cron' => env('VERSION_CHECKER_SCHEDULE_CRON', '0 0 * * *'), // Daily at midnight
    ],
    'requirements' => [
        '11.*' => [
            'php' => '8.1.0',
            'extensions' => ['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'],
        ],
        '10.*' => [
            'php' => '8.0.0',
            'extensions' => ['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'],
        ],
        '9.*' => [
            'php' => '8.0.0',
            'extensions' => ['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'],
        ],
        '8.*' => [
            'php' => '7.3.0',
            'extensions' => ['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'],
        ],
    ],
];