<?php

declare(strict_types=1);

use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxRateLimited;
use App\Bot\Models\OutboxMessage;
use App\Bot\Outbox\OutboxService;
use App\Bot\Outbox\OutboxStatus;
use App\Bot\Outbox\OutboxTarget;
use App\Jobs\SendOutboxMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;

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
