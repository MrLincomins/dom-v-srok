<?php

declare(strict_types=1);

use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxRateLimited;
use App\Bot\Keyboards\Keyboards;
use App\Bot\Models\OutboxMessage;
use App\Bot\Outbox\OutboxService;
use App\Bot\Outbox\OutboxStatus;
use App\Bot\Outbox\OutboxTarget;
use App\Jobs\SendOutboxMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function outboxRow(?CarbonImmutable $availableAt = null): OutboxMessage
{
    Queue::fake();
    $message = app(OutboxService::class)->toUser(555, 'bot.raw', ['text' => 'Проверка'], null, null, $availableAt);
    Queue::fake([]);

    return $message;
}

it('marks the row failed on a permanent error and keeps it pending on a rate limit', function () {
    $max = fakeMax();
    $row = outboxRow();

    $max->sendFailure = new MaxApiException('POST /messages → 400: bad request', 400, 'POST /messages');
    (new SendOutboxMessage($row->id))->handle($max);
    expect($row->refresh()->status)->toBe(OutboxStatus::Failed)
        ->and($row->last_error)->toContain('400');

    $row->update(['status' => OutboxStatus::Pending, 'last_error' => null]);
    $max->sendFailure = new MaxRateLimited('MAX: превышен лимит запросов', 429, 'POST /messages');
    (new SendOutboxMessage($row->id))->handle($max);
    expect($row->refresh()->status)->toBe(OutboxStatus::Pending)
        ->and($row->attempts)->toBe(2);
});

it('gives a delayed message an hour after it becomes available, not after it was queued', function () {
    $availableAt = CarbonImmutable::now()->addDays(2);
    $row = outboxRow($availableAt);

    expect((new SendOutboxMessage($row->id))->retryUntil()->getTimestamp())
        ->toBeGreaterThanOrEqual($availableAt->addHour()->getTimestamp() - 1);
    expect($row->target_type)->toBe(OutboxTarget::User);
});

function outboxRowWithPhotos(array $files, ?string $editMessageId = null): OutboxMessage
{
    $disk = (string) config('attachments.disk');
    $photos = [];
    foreach ($files as $file => $exists) {
        $path = 'attachments/1/'.$file;
        if ($exists) {
            Storage::disk($disk)->put($path, 'jpeg');
        }
        $photos[] = ['disk' => $disk, 'path' => $path];
    }

    return app(OutboxService::class)->enqueue(OutboxTarget::User, 555, 'request.status', [
        'text' => 'Заявка выполнена',
        'keyboard' => Keyboards::fromRows([[['label' => 'Да, решено', 'action' => 'ok:1']]]),
        'photos' => $photos,
    ], editMessageId: $editMessageId, dispatch: false);
}

it('uploads closing photos and sends them as image attachments, skipping a missing file', function () {
    $disk = (string) config('attachments.disk');
    Storage::fake($disk);
    $max = fakeMax();
    $row = outboxRowWithPhotos(['a.jpg' => true, 'gone.jpg' => false, 'b.jpg' => true]);

    (new SendOutboxMessage($row->id))->handle($max);

    $body = $max->messages()[0]['body'];
    expect($row->refresh()->status)->toBe(OutboxStatus::Sent)
        ->and($body)->not->toHaveKey('photos')
        ->and($body['text'])->toBe('Заявка выполнена')
        ->and($body['keyboard'])->toHaveCount(1)
        ->and($body['attachments'])->toBe([
            ['type' => 'image', 'payload' => ['token' => 'tok.1']],
            ['type' => 'image', 'payload' => ['token' => 'tok.2']],
        ])
        ->and($max->uploaded)->toBe([
            Storage::disk($disk)->path('attachments/1/a.jpg'),
            Storage::disk($disk)->path('attachments/1/b.jpg'),
        ]);
});

it('sends the message without a photo that failed to upload', function () {
    Storage::fake((string) config('attachments.disk'));
    $max = fakeMax();
    $max->uploadFailures['a.jpg'] = new MaxApiException('POST upload → 500: error', 500, 'POST upload');
    $row = outboxRowWithPhotos(['a.jpg' => true, 'b.jpg' => true]);

    (new SendOutboxMessage($row->id))->handle($max);

    expect($row->refresh()->status)->toBe(OutboxStatus::Sent)
        ->and($max->messages()[0]['body']['attachments'])->toBe([['type' => 'image', 'payload' => ['token' => 'tok.1']]]);
});

it('keeps the photos when the message edits an earlier one', function () {
    Storage::fake((string) config('attachments.disk'));
    $max = fakeMax();
    $row = outboxRowWithPhotos(['a.jpg' => true], 'mid.card');

    (new SendOutboxMessage($row->id))->handle($max);

    $sent = $max->messages()[0];
    expect($sent['target'])->toBe('edit')
        ->and($sent['mid'])->toBe('mid.card')
        ->and($sent['body'])->not->toHaveKey('photos')
        ->and($sent['body']['attachments'])->toBe([['type' => 'image', 'payload' => ['token' => 'tok.1']]]);
});
