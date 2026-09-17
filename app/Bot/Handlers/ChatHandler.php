<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Cards\RequestCard;
use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxClient;
use App\Bot\Keyboards\Keyboards;
use App\Bot\Updates\Update;
use App\Domain\Organizations\DeepLinks;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * групповой чат дома.
 * «статус N» (карточка без личных данных).
 * потом сделать «Присоединиться к заявке соседа» и подсказки.
 */
final class ChatHandler
{
    public function __construct(
        private readonly BotContext $ctx,
        private readonly MaxClient $client,
        private readonly DeepLinks $links,
        private readonly RequestCard $card,
    ) {}

    public function handle(Update $update, ?User $user): void
    {
        $chatId = $update->chatId();
        $text = $update->text();
        if ($chatId === null || $text === null || $text === '') {
            return;
        }

        if (preg_match('/^\/(дом|house)\s+([a-z0-9]{6,16})$/iu', $text, $m) === 1) {
            $this->bind($chatId, strtolower($m[2]), $user);

            return;
        }

        if (preg_match('/^(статус|status)\s*№?\s*(\d+)$/iu', $text, $m) === 1) {
            $this->status($chatId, (int) $m[2]);
        }
    }

    private function bind(int $chatId, string $token, ?User $user): void
    {
        $house = House::query()->where('qr_token', $token)->first();
        if ($house === null || $user === null || ! $user->isStaff() || $user->organization_id !== $house->organization_id) {
            $this->ctx->outbox()->toChat($chatId, 'chat.bind_denied', ['text' => 'Привязать чат может сотрудник организации дома командой «/дом <код дома>».']);

            return;
        }

        $house->max_chat_id = $chatId;
        $house->save();

        $rows = [[['label' => 'Сообщить о проблеме', 'action' => 'url:'.$this->links->houseStartUrl($house)]]];
        $this->ctx->outbox()->toChat($chatId, 'chat.card', [
            'text' => $this->ctx->texts()->text('chat.card', ['address' => $house->address]),
            'keyboard' => Keyboards::fromRows($rows),
        ], 'chat.card:'.$house->id.':'.$chatId);
        // закреп карточки после отправки, когда известен message_id: команда bot:pin-house-card позже
        Log::info('chat.bound', ['house_id' => $house->id, 'chat_id' => $chatId]);
    }

    private function status(int $chatId, int $id): void
    {
        $request = ServiceRequest::query()->with(['category', 'house.region'])->find($id);
        $house = House::query()->where('max_chat_id', $chatId)->first();
        if ($request === null || $house === null || $request->house_id !== $house->id) {
            return; // если чужая или несуществующая заявка
        }

        $this->ctx->outbox()->toChat($chatId, 'chat.status', [
            'text' => $this->ctx->texts()->text('chat.status', [
                'number' => $request->id,
                'status' => $request->status->label(),
                'what' => $this->card->what($request, true),
                'deadline' => $this->card->deadline($request),
            ]),
        ]);
    }

    /** закреп карточки, когда бот админ потом */
    public function pin(int $chatId, string $messageId): void
    {
        try {
            $this->client->pinMessage($chatId, $messageId);
        } catch (MaxApiException $e) {
            Log::info('chat.pin_failed', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
        }
    }
}
