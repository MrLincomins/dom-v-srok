<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Callbacks\ParsedCallback;
use App\Bot\Cards\RequestCard;
use App\Bot\Client\MaxApiException;
use App\Bot\Client\MaxClient;
use App\Bot\Keyboards\Keyboards;
use App\Bot\Updates\Update;
use App\Domain\Catalog\CatalogService;
use App\Domain\Organizations\DeepLinks;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * групповой чат дома.
 * «статус N» (карточка без личных данных), «Присоединиться», подсказка по словам если включена у дома.
 */
final class ChatHandler
{
    public function __construct(
        private readonly BotContext $ctx,
        private readonly MaxClient $client,
        private readonly DeepLinks $links,
        private readonly RequestCard $card,
        private readonly RequestService $requests,
        private readonly CatalogService $catalog,
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

            return;
        }

        if (! str_starts_with($text, '/')) {
            $this->offerByKeywords($chatId, $text);
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

        $this->ctx->outbox()->toChat($chatId, 'chat.status', $this->statusBody($request), null, $request->id);
    }

    private function offerByKeywords(int $chatId, string $text): void
    {
        $house = House::query()->where('max_chat_id', $chatId)->first();
        if ($house === null || ! $house->chat_keywords_enabled) {
            return;
        }
        $categoryIds = $this->catalog->suggest($text)->pluck('id')->all();
        if ($categoryIds === []) {
            return;
        }
        $request = ServiceRequest::query()->with(['category', 'house.region'])
            ->where('house_id', $house->id)
            ->whereIn('category_id', $categoryIds)
            ->open()
            ->latest('id')
            ->first();
        if ($request === null) {
            return;
        }

        $this->ctx->outbox()->toChat($chatId, 'chat.join_offer', [
            'text' => $this->card->chatOffer($request),
            'keyboard' => Keyboards::fromRows($this->joinRows($request)),
        ], 'chat.join_offer:'.$request->id.':'.$chatId.':'.CarbonImmutable::now()->format('YmdH'), $request->id);
    }

    public function join(Update $update, ParsedCallback $callback, User $user): void
    {
        $id = $callback->intArg(0);
        $request = $id === null ? null : ServiceRequest::query()->with(['category', 'house.region'])->find($id);
        $houseId = $update->isPrivate()
            ? $user->house_id
            : House::query()->where('max_chat_id', $update->chatId())->first()?->id;
        $vars = ['number' => $id];

        if ($request === null || $houseId === null || $request->house_id !== $houseId) {
            $this->answer($update, 'chat.join_missing', $vars);

            return;
        }
        if (! $request->status->isOpen()) {
            $this->answer($update, 'chat.join_closed', $vars);

            return;
        }
        if ($request->resident_user_id === $user->id) {
            $this->answer($update, 'chat.join_own', $vars);

            return;
        }
        if (! $this->requests->join($request, $user)) {
            $this->answer($update, 'chat.join_already', $vars);

            return;
        }

        $request->refresh();
        $started = $user->canBeMessaged();
        $this->answer($update, $started ? 'chat.joined' : 'chat.join_start', $vars, $this->statusBody($request));
        Log::info('chat.joined', ['request_id' => $request->id, 'chat_id' => $update->chatId(), 'started' => $started]);

        if ($started) {
            $this->ctx->reply($user, 'request.joined', [
                'number' => $request->id,
                'what' => $this->card->what($request, true),
                'deadline' => $this->card->deadline($request),
            ], 'request.joined:'.$request->id.':'.$user->id);
        }
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

    /**
     * @param  array<string,string|int|null>  $vars
     * @param  array<string,mixed>|null  $message
     */
    private function answer(Update $update, string $key, array $vars, ?array $message = null): void
    {
        $callbackId = $update->callbackId();
        if ($callbackId === null || ! $this->client->isConfigured()) {
            return;
        }
        try {
            $this->client->answerCallback($callbackId, $this->ctx->texts()->text($key, $vars), $message);
        } catch (MaxApiException $e) {
            Log::info('bot.answer_callback_failed', ['error' => $e->getMessage()]);
        }
    }

    /** @return array{text:string,keyboard:array<int,mixed>} */
    private function statusBody(ServiceRequest $request): array
    {
        return ['text' => $this->card->chatStatus($request), 'keyboard' => Keyboards::fromRows($this->joinRows($request))];
    }

    /** @return list<list<array{label:string,action:string}>> */
    private function joinRows(ServiceRequest $request): array
    {
        return $request->status->isOpen() ? $this->ctx->texts()->buttons('chat.join_offer', ['number' => $request->id]) : [];
    }
}
