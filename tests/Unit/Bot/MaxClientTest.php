<?php

declare(strict_types=1);

use App\Bot\Client\MaxClient;
use App\Bot\Keyboards\Keyboards;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('keeps the keyboard when editing a message and clears it when the new screen has no buttons', function () {
    Http::fake(['*/messages*' => Http::response(['success' => true])]);
    $keyboard = Keyboards::fromRows([[['label' => 'Меню', 'action' => 'menu']]]);
    $client = new MaxClient;

    $client->editMessage('mid.menu', ['text' => 'Что нужно сделать?', 'keyboard' => $keyboard]);
    $client->editMessage('mid.plain', ['text' => 'Черновик заявки удалён.']);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
        && str_contains($request->url(), 'message_id=mid.menu')
        && $request['text'] === 'Что нужно сделать?'
        && $request['attachments'] === $keyboard);
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'message_id=mid.plain')
        && $request['attachments'] === []);
});

it('sends the keyboard as attachments in a new message', function () {
    Http::fake(['*/messages*' => Http::response(['message' => ['body' => ['mid' => 'mid.new']]])]);
    $keyboard = Keyboards::fromRows([[['label' => 'Сообщить о проблеме', 'action' => 'report']]]);

    (new MaxClient)->sendToUser(555, ['text' => 'Меню', 'keyboard' => $keyboard]);

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_contains($request->url(), 'user_id=555')
        && $request['attachments'] === $keyboard);
});

it('sends bot commands with a patch', function () {
    Http::fake(['*/me/commands' => Http::response(['commands' => []])]);

    (new MaxClient)->setCommands([['name' => 'start', 'description' => 'Начать заново']]);

    Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
        && str_ends_with($request->url(), '/me/commands')
        && $request['commands'] === [['name' => 'start', 'description' => 'Начать заново']]);
});
