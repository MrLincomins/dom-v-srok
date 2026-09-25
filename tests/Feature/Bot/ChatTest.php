<?php

declare(strict_types=1);

use App\Bot\Cards\RequestCard;
use App\Bot\Models\OutboxMessage;
use App\Domain\Organizations\DeepLinks;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

function chatHouse(): House
{
    return House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->firstOrFail();
}

function chatRequest(string $slug): ServiceRequest
{
    return ServiceRequest::query()->whereHas('category', fn ($q) => $q->where('slug', $slug))->firstOrFail();
}

function chatUser(int $maxUserId): User
{
    return User::query()->where('max_user_id', $maxUserId)->firstOrFail();
}

function bindDemoChat(int $chatId): void
{
    runUpdate(chatMessageUpdate(777, $chatId, '/дом '.DemoSeeder::HOUSE_QR_TOKEN, 'mid.bind'));
}

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->max = fakeMax();
    $this->chatId = 9001;
    User::query()->create([
        'max_user_id' => 777,
        'name' => 'Диспетчер Чата',
        'role' => Role::Dispatcher,
        'organization_id' => chatHouse()->organization_id,
    ]);
});

it('binds the chat to the house by a staff command and refuses everyone else', function () {
    runUpdate(startUpdate(555));
    $this->max->sent = [];

    runUpdate(chatMessageUpdate(555, $this->chatId, '/дом '.DemoSeeder::HOUSE_QR_TOKEN, 'mid.deny'));
    expect(chatHouse()->max_chat_id)->toBeNull()
        ->and(lastText($this->max))->toContain('сотрудник организации');

    bindDemoChat($this->chatId);
    $last = end($this->max->sent);
    expect(chatHouse()->max_chat_id)->toBe($this->chatId)
        ->and($last['target'])->toBe('chat')
        ->and($last['id'])->toBe($this->chatId)
        ->and(lastText($this->max))->toContain(DemoSeeder::HOUSE_ADDRESS)
        ->and(lastButtons($this->max))->toContain(app(DeepLinks::class)->houseStartUrl(chatHouse()));
});

it('pins the house card right after it is sent and remembers the pinned message', function () {
    bindDemoChat($this->chatId);

    $card = OutboxMessage::query()->where('kind', 'chat.card')->firstOrFail();
    expect($this->max->pinned)->toBe([['chat' => $this->chatId, 'mid' => $card->max_message_id]])
        ->and(chatHouse()->chat_pinned_message_id)->toBe($card->max_message_id);
});

it('keeps the card when the bot is not an admin and cannot pin', function () {
    $this->max->failPins = true;

    bindDemoChat($this->chatId);

    expect(lastText($this->max))->toContain(DemoSeeder::HOUSE_ADDRESS)
        ->and($this->max->pinned)->toBe([])
        ->and(chatHouse()->chat_pinned_message_id)->toBeNull()
        ->and(chatHouse()->max_chat_id)->toBe($this->chatId);
});

it('answers «статус N» with a card without personal data and a join button only for open requests', function () {
    bindDemoChat($this->chatId);
    $open = chatRequest('entrance.light');
    $closed = chatRequest('heat.leak');
    $this->max->sent = [];

    runUpdate(chatMessageUpdate(555, $this->chatId, 'статус '.$open->id, 'mid.s1'));
    expect(lastText($this->max))->toBe(botText('chat.status', [
        'number' => $open->id,
        'status' => 'Принято',
        'what' => 'Не горит свет в подъезде',
        'deadline' => app(RequestCard::class)->deadline($open),
    ]))
        ->not->toContain('третьем')
        ->and(lastButtons($this->max))->toBe(['join:'.$open->id]);

    runUpdate(chatMessageUpdate(555, $this->chatId, 'Статус №'.$closed->id, 'mid.s7'));
    expect(lastText($this->max))->toContain('Заявка № '.$closed->id.': Подтверждено')
        ->and(lastButtons($this->max))->toBe([]);

    $count = count($this->max->sent);
    runUpdate(chatMessageUpdate(555, 9002, 'статус '.$open->id, 'mid.s1b'));
    expect($this->max->sent)->toHaveCount($count);
});

it('lets a neighbour join from the chat and asks them to start the bot', function () {
    bindDemoChat($this->chatId);
    $request = chatRequest('entrance.light');
    $this->max->sent = [];

    runUpdate(chatCallbackUpdate(888, $this->chatId, 'join:'.$request->id, 'cb.join.1'));

    $neighbour = chatUser(888);
    $answer = lastAnswer($this->max);
    expect($neighbour->bot_started_at)->toBeNull()
        ->and($request->refresh()->participants_count)->toBe(1)
        ->and($request->participants()->where('user_id', $neighbour->id)->exists())->toBeTrue()
        ->and($request->events()->where('type', EventType::ParticipantJoined->value)->count())->toBe(1)
        ->and($answer['id'])->toBe('cb.join.1')
        ->and($answer['notification'])->toBe(botText('chat.join_start', ['number' => $request->id]))
        ->and($answer['message']['text'] ?? '')->toContain(botText('chat.neighbours', ['count' => 1]))
        ->and($this->max->messages())->toBe([])
        ->and(OutboxMessage::query()->where('target_id', 888)->exists())->toBeFalse();
});

it('sends a private card to a neighbour who started the bot and ignores a repeated press', function () {
    bindDemoChat($this->chatId);
    runUpdate(startUpdate(555));
    $request = chatRequest('entrance.light');
    $this->max->sent = [];

    runUpdate(chatCallbackUpdate(555, $this->chatId, 'join:'.$request->id, 'cb.join.2'));
    $last = end($this->max->sent);
    expect(lastAnswer($this->max)['notification'])->toBe(botText('chat.joined', ['number' => $request->id]))
        ->and($last['target'])->toBe('user')
        ->and($last['id'])->toBe(555)
        ->and(lastText($this->max))->toContain('Вы присоединились к заявке № '.$request->id)
        ->and(lastButtons($this->max))->toContain('st:'.$request->id);

    runUpdate(chatCallbackUpdate(555, $this->chatId, 'join:'.$request->id, 'cb.join.3'));
    expect(lastAnswer($this->max)['notification'])->toBe(botText('chat.join_already', ['number' => $request->id]))
        ->and($request->refresh()->participants_count)->toBe(1);
});

it('refuses to join an own, closed or foreign request', function () {
    bindDemoChat($this->chatId);
    runUpdate(startUpdate(555));

    $own = chatRequest('entrance.light');
    $own->forceFill(['resident_user_id' => chatUser(555)->id])->save();
    runUpdate(chatCallbackUpdate(555, $this->chatId, 'join:'.$own->id, 'cb.own'));
    expect(lastAnswer($this->max)['notification'])->toBe(botText('chat.join_own', ['number' => $own->id]));

    $closed = chatRequest('heat.leak');
    runUpdate(chatCallbackUpdate(555, $this->chatId, 'join:'.$closed->id, 'cb.closed'));
    expect(lastAnswer($this->max)['notification'])->toBe(botText('chat.join_closed', ['number' => $closed->id]));

    $other = chatRequest('intercom.broken');
    runUpdate(chatCallbackUpdate(555, 9002, 'join:'.$other->id, 'cb.foreign'));
    expect(lastAnswer($this->max)['notification'])->toBe(botText('chat.join_missing', ['number' => $other->id]))
        ->and($other->refresh()->participants_count)->toBe(0);
});

it('offers to join an open request when a chat message matches a category and the option is on', function () {
    bindDemoChat($this->chatId);
    $request = chatRequest('entrance.light');
    $this->max->sent = [];

    runUpdate(chatMessageUpdate(555, $this->chatId, 'Опять темно в подъезде', 'mid.k1'));
    expect($this->max->sent)->toBe([]);

    chatHouse()->forceFill(['chat_keywords_enabled' => true])->save();
    runUpdate(chatMessageUpdate(555, $this->chatId, 'Опять темно в подъезде', 'mid.k2'));
    expect(lastText($this->max))->toBe(app(RequestCard::class)->chatOffer($request))
        ->and(lastButtons($this->max))->toBe(['join:'.$request->id]);

    runUpdate(chatMessageUpdate(556, $this->chatId, 'темно в подъезде', 'mid.k3'));
    runUpdate(chatMessageUpdate(556, $this->chatId, 'Кто знает телефон управляющей?', 'mid.k4'));
    expect($this->max->sent)->toHaveCount(1);
});
