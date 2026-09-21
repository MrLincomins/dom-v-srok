<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Bot\Handlers\ChatHandler;
use App\Bot\Outbox\Events\OutboxMessageSent;
use App\Bot\Outbox\OutboxTarget;
use App\Domain\Organizations\Models\House;

final class PinHouseCardInChat
{
    public function __construct(private readonly ChatHandler $chat) {}

    public function handleSent(OutboxMessageSent $event): void
    {
        $message = $event->message;
        if ($message->kind !== 'chat.card' || $message->target_type !== OutboxTarget::Chat || $message->max_message_id === null) {
            return;
        }
        $house = House::query()->where('max_chat_id', $message->target_id)->first();
        if ($house === null) {
            return;
        }
        $this->chat->pin($house, $message->max_message_id);
    }
}
