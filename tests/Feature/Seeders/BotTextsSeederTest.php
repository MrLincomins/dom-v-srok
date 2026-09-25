<?php

declare(strict_types=1);

use App\Bot\Models\BotText;
use App\Bot\Models\OutboxMessage;
use App\Bot\Outbox\OutboxStatus;
use App\Bot\Outbox\OutboxTarget;
use Database\Seeders\BotTextsSeeder;

it('loads a valid row', function () {
    (new BotTextsSeeder)->seedRows([1 => [
        'key' => 'test.valid',
        'text' => 'Заявка № {number}: {status}',
        'buttons' => '[[{"label":"Статус","action":"st:{number}"},{"label":"Кабинет","action":"cab"}],[{"label":"Позвонить","action":"tel:+78430000001"}]]',
    ]]);

    expect(BotText::query()->where('key', 'test.valid')->value('text'))->toBe('Заявка № {number}: {status}');
});

it('rejects a broken row with its key and line', function (array $row, string $reason) {
    $row = ['key' => 'test.bad', 'text' => 'Текст', 'buttons' => '', ...$row];

    expect(fn () => (new BotTextsSeeder)->seedRows([4 => $row]))
        ->toThrow(RuntimeException::class, "texts.csv, строка 5, ключ «{$row['key']}»: {$reason}");
})->with([
    'длинный ключ' => [['key' => str_repeat('k', 61)], 'ключ пустой или длиннее 60 символов'],
    'не json' => [['buttons' => '[[{"label":"А"'], 'кнопки не JSON'],
    'без action' => [['buttons' => '[[{"label":"А"}]]'], 'у кнопки нет label или action'],
    'чужое действие' => [['buttons' => '[[{"label":"А","action":"unknown:1"}]]'], 'неизвестное действие кнопки «unknown:1»'],
    'подстановка' => [['text' => 'Заявка {Number}'], 'подстановки только вида'],
]);

it('stores a long outbox kind', function () {
    $kind = 'bot.'.str_repeat('k', BotTextsSeeder::MAX_KEY_LENGTH);

    $message = OutboxMessage::query()->create(['target_type' => OutboxTarget::User, 'target_id' => 1, 'kind' => $kind, 'body' => ['text' => 'а'], 'status' => OutboxStatus::Pending]);

    expect($message->refresh()->kind)->toBe($kind);
});
