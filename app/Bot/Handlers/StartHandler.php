<?php

declare(strict_types=1);

namespace App\Bot\Handlers;

use App\Bot\Fsm\SessionStore;
use App\Bot\Updates\Update;
use App\Domain\Organizations\Models\House;
use App\Domain\Users\Models\User;
use App\Support\Max\DeepLinks;

final class StartHandler
{
    public function __construct(
        private readonly BotContext $ctx,
        private readonly DeepLinks $links,
        private readonly SessionStore $sessions,
    ) {}

    public function handle(Update $update, User $user, ?string $payload): void
    {
        $this->sessions->reset((int) $user->max_user_id);
        $house = $this->bindHouse($user, $payload);

        $this->ctx->reply($user, 'start.greeting');
        if ($house !== null) {
            $this->ctx->reply($user, 'start.house_known', ['address' => $house->address]);
        }
        if ($user->isStaff()) {
            $this->ctx->reply($user, 'menu.cabinet');
        }
    }

    private function bindHouse(User $user, ?string $payload): ?House
    {
        $parsed = $this->links->parseHousePayload($payload);
        if ($parsed === null) {
            return $user->house_id !== null ? $user->house : null;
        }

        $house = House::query()->where('qr_token', $parsed['token'])->first();
        if ($house === null) {
            return null;
        }

        $user->house_id = $house->id;
        if ($parsed['entrance'] !== null) {
            $user->entrance = $parsed['entrance'];
        }
        $user->save();
        $user->setRelation('house', $house);

        return $house;
    }
}
