<?php

declare(strict_types=1);

return [
    // токен бота от организаторов, только из окружения
    'token' => env('MAX_BOT_TOKEN'),
    'bot_username' => env('MAX_BOT_USERNAME'),

    // запросы на platform-api2.max.ru, токен в заголовке Authorization без Bearer
    'api_base' => rtrim((string) env('MAX_API_BASE', 'https://platform-api2.max.ru'), '/'),
    'timeout' => 10,

    // polling для локалки (bot:poll), webhook для прода (bot:subscribe)
    'mode' => env('MAX_MODE', 'polling'),
    'webhook_path' => '/max/webhook',
    'webhook_secret' => env('MAX_WEBHOOK_SECRET'),
    'update_types' => [
        'bot_started', 'bot_stopped', 'message_created', 'message_callback',
        'bot_added', 'bot_removed', 'user_added', 'dialog_removed',
    ],

    'miniapp_url' => env('MAX_MINIAPP_URL'),

    'commands' => ['start', 'menu', 'dispatcher', 'delete_me'],

    // сколько секунд initData считается свежим
    'init_data_ttl' => (int) env('MAX_INIT_DATA_TTL', 86400),

    // сертификат минцифры для platform-api2.max.ru, в докере уже стоит, без докера указать путь к docker/certs/russian_trusted_root_ca.crt
    'ca_bundle' => env('MAX_CA_BUNDLE'),

    // лимиты маха: 2 сообщения в сек на диалог, 30 запросов в сек всего
    'rate_per_target' => 2,
    'rate_global' => 25,
];
