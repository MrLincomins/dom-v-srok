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

it('uploads an image in two steps and returns the token', function () {
    $file = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($file, 'jpeg-bytes');
    Http::fake([
        '*/uploads*' => Http::response(['url' => 'https://upload.example/put?sig=1']),
        'upload.example/*' => Http::response(['photos' => ['abc' => ['token' => 'tok-image']]]),
    ]);

    $token = (new MaxClient)->uploadImage($file);
    unlink($file);

    expect($token)->toBe('tok-image');
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_contains($request->url(), '/uploads?type=image'));
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_starts_with($request->url(), 'https://upload.example/put')
        && $request->isMultipart()
        && $request[0]['name'] === 'data'
        && $request[0]['contents'] === 'jpeg-bytes');
});

it('sends image attachments together with the keyboard and text', function () {
    Http::fake(['*/messages*' => Http::response(['message' => ['body' => ['mid' => 'mid.new']]])]);
    $keyboard = Keyboards::fromRows([[['label' => 'Да, решено', 'action' => 'ok:1']]]);
    $image = ['type' => 'image', 'payload' => ['token' => 'tok-image']];

    (new MaxClient)->sendToUser(555, ['text' => 'Заявка выполнена', 'keyboard' => $keyboard, 'attachments' => [$image]]);

    Http::assertSent(fn (Request $request) => $request['text'] === 'Заявка выполнена'
        && $request['attachments'] === [...$keyboard, $image]);
});
