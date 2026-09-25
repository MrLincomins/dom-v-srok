<?php

declare(strict_types=1);

use App\Bot\Keyboards\Keyboards;
use App\Support\Models\AppSetting;

it('opens the cabinet through the bot name and id as the platform expects', function () {
    config(['max.bot_username' => 'test_bot', 'max.miniapp_url' => 'https://dom.example.ru/app']);

    $button = Keyboards::button('Открыть кабинет', 'cab');
    expect($button)->toBe(['type' => 'open_app', 'text' => 'Открыть кабинет', 'web_app' => 'test_bot']);

    AppSetting::put('bot', ['id' => 405671160, 'username' => 'test_bot']);
    expect(Keyboards::button('Открыть кабинет', 'cab')['contact_id'])->toBe(405671160);
});

it('falls back to a link when the bot name is unknown', function () {
    config(['max.bot_username' => '', 'max.miniapp_url' => 'https://dom.example.ru/app']);

    expect(Keyboards::button('Открыть кабинет', 'cab'))
        ->toBe(['type' => 'link', 'text' => 'Открыть кабинет', 'url' => 'https://dom.example.ru/app']);
});
