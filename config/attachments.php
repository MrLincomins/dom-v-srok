<?php

declare(strict_types=1);

return [
    'max_mb' => (int) env('ATTACHMENT_MAX_MB', 10),
    'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'disk' => 'private',
    // сколько минут живёт подписанная ссылка на файл
    'url_ttl_minutes' => 15,
];
