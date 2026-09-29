<?php

declare(strict_types=1);

return [
    // сид демо организации при первом старте контейнера
    'seed' => (bool) env('DEMO_SEED', false),

    // логин по паролю для тестовых учёток, только демо организация
    'accounts_enabled' => (bool) env('DEMO_ACCOUNTS_ENABLED', false),
    'access_code' => env('DEMO_ACCESS_CODE'),
    'dispatcher_password' => env('DEMO_DISPATCHER_PASSWORD'),
    'resident_password' => env('DEMO_RESIDENT_PASSWORD'),
    'reset_per_minute' => 10,
];
