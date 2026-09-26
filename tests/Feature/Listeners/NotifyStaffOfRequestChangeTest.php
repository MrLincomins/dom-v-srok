<?php

declare(strict_types=1);

use App\Bot\Cards\RequestCard;
use App\Bot\Models\OutboxMessage;
use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Events\RequestCreated;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    fakeMax();
    $this->house = House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->firstOrFail();
    $this->resident = User::query()->where('login', 'demo_resident')->firstOrFail();
    $this->resident->forceFill(['max_user_id' => 700100, 'bot_started_at' => CarbonImmutable::now()])->save();
    $this->started = User::query()->create(['max_user_id' => 7101, 'name' => 'Диспетчер Один', 'role' => Role::Dispatcher, 'organization_id' => $this->house->organization_id, 'bot_started_at' => CarbonImmutable::now()]);
    $this->silent = User::query()->create(['max_user_id' => 7102, 'name' => 'Диспетчер Два', 'role' => Role::Dispatcher, 'organization_id' => $this->house->organization_id]);
});

function createLightRequest(House $house, User $resident): ServiceRequest
{
    return app(RequestService::class)->create(new CreateRequestData(
        houseId: $house->id,
        residentUserId: $resident->id,
        categoryId: (int) Category::query()->where('slug', 'entrance.light')->value('id'),
        description: 'Не горит свет, квартира 45',
        entrance: 2,
        flat: '45',
    ));
}

/** @return list<OutboxMessage> */
function staffMessages(string $kind, int $maxUserId): array
{
    return OutboxMessage::query()->where('kind', $kind)->where('target_id', $maxUserId)->orderBy('id')->get()->all();
}

it('tells staff who started the bot about a new request without the description and flat', function () {
    $request = createLightRequest($this->house, $this->resident);

    $messages = staffMessages('request.new_staff', 7101);
    expect($messages)->toHaveCount(1)
        ->and($messages[0]->request_id)->toBe($request->id)
        ->and($messages[0]->body['text'])->toBe(botText('request.new_staff', [
            'number' => $request->id,
            'what' => $request->category->name,
            'address' => $this->house->address.', подъезд 2',
            'deadline' => app(RequestCard::class)->deadline($request),
        ]))
        ->and($messages[0]->body['text'])->not->toContain('квартира')
        ->and(staffMessages('request.new_staff', 7102))->toBeEmpty()
        ->and(staffMessages('request.new_staff', 700100))->toBeEmpty();
});

it('does not repeat the new request message when the event comes twice', function () {
    $request = createLightRequest($this->house, $this->resident);

    event(new RequestCreated($request));

    expect(staffMessages('request.new_staff', 7101))->toHaveCount(1);
});

it('tells staff when the resident returns a request to work', function () {
    $request = createLightRequest($this->house, $this->resident);
    $service = app(RequestService::class);
    $service->close($request, Actor::dispatcher($this->started), 'Заменили лампу');

    $service->returnToWork($request, Actor::resident($this->resident), 'Снова не горит');

    $messages = staffMessages('request.returned_staff', 7101);
    expect($messages)->toHaveCount(1)
        ->and($messages[0]->body['text'])->toBe(botText('request.returned_staff', [
            'number' => $request->id,
            'what' => $request->category->name,
            'address' => $this->house->address.', подъезд 2',
        ]))
        ->and(staffMessages('request.returned_staff', 7102))->toBeEmpty();
});

it('stays silent for a house without an organization', function () {
    $house = House::query()->create(['region_code' => 'RU-TA', 'address' => 'Казань, ул. Ничья, д. 1']);

    createLightRequest($house, $this->resident);

    expect(OutboxMessage::query()->where('kind', 'request.new_staff')->count())->toBe(0);
});
