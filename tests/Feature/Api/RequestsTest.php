<?php

declare(strict_types=1);

use App\Bot\Models\OutboxMessage;
use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Enums\ContractorType;
use App\Domain\Organizations\Models\Contractor;
use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Dto\PhotoDraft;
use App\Domain\Requests\Enums\AttachmentKind;
use App\Domain\Requests\Models\Attachment;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spectator\Spectator;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');
    $this->dispatcher = $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'dispatcher-pass'])->json('data.token');
    $this->resident = $this->postJson('/api/v1/auth/login', ['login' => 'demo_resident', 'password' => 'resident-pass'])->json('data.token');
});

it('lists the queue with counters and matches the contract', function () {
    $response = asToken($this->dispatcher)->getJson('/api/v1/requests?status=open');

    $response->assertValidRequest()->assertValidResponse(200)
        ->assertJsonPath('meta.counters.overdue', 1)
        ->assertJsonPath('meta.counters.closed', 2);

    expect($response->json('data'))->toHaveCount(5);
});

it('shows only overdue requests when asked', function () {
    asToken($this->dispatcher)->getJson('/api/v1/requests?overdue=1')
        ->assertValidResponse(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.is_overdue', true);
});

it('forbids the queue for a resident', function () {
    asToken($this->resident)->getJson('/api/v1/requests')
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'forbidden');
});

it('returns a request card that matches the contract', function () {
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');

    asToken($this->dispatcher)->getJson("/api/v1/requests/{$id}")
        ->assertValidResponse(200)
        ->assertJsonPath('data.status', 'new')
        ->assertJsonStructure(['data' => ['responsible' => ['name', 'is_sure'], 'events', 'allowed_transitions']]);
});

it('moves a request through the workflow and rejects a bad transition with 409', function () {
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');

    asToken($this->dispatcher)->patchJson("/api/v1/requests/{$id}/status", ['status' => 'in_progress', 'comment' => 'Выехал'])
        ->assertValidResponse(200)
        ->assertJsonPath('data.status', 'in_progress');

    asToken($this->dispatcher)->patchJson("/api/v1/requests/{$id}/status", ['status' => 'new'])
        ->assertValidResponse(409)
        ->assertJsonPath('error.code', 'invalid_transition');
});

it('lets the resident confirm a done request and return another one to work', function () {
    $done = asToken($this->dispatcher)->getJson('/api/v1/requests?status=done')->json('data.0.id');

    asToken($this->resident)->postJson("/api/v1/requests/{$done}/confirm", ['resolved' => false, 'comment' => 'Дверь снова не закрывается'])
        ->assertValidResponse(200)
        ->assertJsonPath('data.status', 'returned')
        ->assertJsonPath('data.returned_count', 1);

    asToken($this->dispatcher)->patchJson("/api/v1/requests/{$done}/status", ['status' => 'done'])->assertOk();
    asToken($this->resident)->postJson("/api/v1/requests/{$done}/confirm", ['resolved' => true])
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.confirmed_by', 'resident');
});

it('redirects a request with a contact and tells the resident the number', function () {
    User::query()->where('login', 'demo_resident')->update(['max_user_id' => 700100, 'bot_started_at' => now()]);
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');

    asToken($this->dispatcher)->postJson("/api/v1/requests/{$id}/redirect", ['name' => 'Водоканал', 'phone' => '+7 843 000-00-00', 'note' => 'Магистраль'])
        ->assertValidResponse(200)
        ->assertJsonPath('data.status', 'redirected')
        ->assertJsonPath('data.redirected_to.name', 'Водоканал')
        ->assertJsonPath('data.redirected_to.phone', '+7 843 000-00-00')
        ->assertJsonPath('data.redirected_to.note', 'Магистраль');

    $texts = OutboxMessage::query()->where('request_id', $id)->get()->map(fn (OutboxMessage $m) => (string) ($m->body['text'] ?? ''))->all();
    expect($texts)->toContain(botText('request.redirected', ['number' => $id, 'status' => 'Переадресовано', 'comment' => 'Магистраль', 'to' => 'Водоканал', 'phone' => '+7 843 000-00-00']));
});

it('redirects a request to a contractor of the organization', function () {
    $organizationId = User::query()->where('login', 'demo_dispatcher')->value('organization_id');
    $contractor = Contractor::query()->create(['organization_id' => $organizationId, 'type' => ContractorType::Lift, 'name' => 'Лифтсервис', 'phone' => '+7 843 111-22-33']);
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');

    asToken($this->dispatcher)->postJson("/api/v1/requests/{$id}/redirect", ['contractor_id' => $contractor->id])
        ->assertValidResponse(200)
        ->assertJsonPath('data.redirected_to.name', 'Лифтсервис')
        ->assertJsonPath('data.redirected_to.phone', '+7 843 111-22-33');
});

it('does not redirect to a contractor of another organization', function () {
    $other = Organization::query()->create(['name' => 'Другая УК', 'region_code' => Organization::query()->value('region_code'), 'type' => 'uk', 'phone_ads' => '+7 843 000-00-99']);
    $contractor = Contractor::query()->create(['organization_id' => $other->id, 'type' => ContractorType::Lift, 'name' => 'Чужой лифт', 'phone' => null]);
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');

    asToken($this->dispatcher)->postJson("/api/v1/requests/{$id}/redirect", ['contractor_id' => $contractor->id])
        ->assertStatus(404);
    asToken($this->dispatcher)->postJson("/api/v1/requests/{$id}/redirect", ['contractor_id' => $contractor->id, 'name' => 'Водоканал'])
        ->assertStatus(422);
});

it('lists the active group as one page', function () {
    asToken($this->dispatcher)->getJson('/api/v1/requests?status=active')
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.counters.in_progress', 3);
});

it('keeps no closing photos when the transition is rejected', function () {
    $confirmed = asToken($this->dispatcher)->getJson('/api/v1/requests?status=confirmed')->json('data.0.id');

    asToken($this->dispatcher)->post("/api/v1/requests/{$confirmed}/close", [
        'comment' => 'Готово',
        'photos' => [UploadedFile::fake()->image('done.jpg')],
    ], ['Accept' => 'application/json'])
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'invalid_transition');

    $this->assertDatabaseMissing('attachments', ['request_id' => $confirmed]);
});

it('shows requests where the resident joined as a participant', function () {
    $neighbour = User::query()->create(['name' => 'Сосед', 'role' => Role::Resident, 'login' => 'demo_neighbour', 'password' => 'neighbour-pass', 'is_demo' => true]);
    $token = $this->postJson('/api/v1/auth/login', ['login' => 'demo_neighbour', 'password' => 'neighbour-pass'])->json('data.token');
    asToken($token)->getJson('/api/v1/my/requests')->assertOk()->assertJsonCount(0, 'data');

    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=new')->json('data.0.id');
    app(RequestService::class)->join(ServiceRequest::query()->findOrFail($id), $neighbour);

    asToken($token)->getJson('/api/v1/my/requests')->assertValidResponse(200)->assertJsonCount(1, 'data');
});

it('resets the demo organisation on request', function () {
    asToken($this->dispatcher)->postJson('/api/v1/demo/reset')->assertOk();
    asToken($this->dispatcher)->getJson('/api/v1/requests')->assertOk()->assertJsonPath('meta.total', 8);
});

it('refuses to assign an executor to a closed request with 409', function () {
    $confirmed = asToken($this->dispatcher)->getJson('/api/v1/requests?status=confirmed')->json('data.0.id');
    $executor = asToken($this->dispatcher)->getJson('/api/v1/organization/executors')->json('data.0.id');

    asToken($this->dispatcher)->postJson("/api/v1/requests/{$confirmed}/assign", ['executor_id' => $executor])
        ->assertValidResponse(409)
        ->assertJsonPath('error.code', 'invalid_transition');

    $this->assertDatabaseMissing('request_events', ['request_id' => $confirmed, 'type' => 'assigned']);
});

it('closes a request with photos and asks the resident to confirm', function () {
    $disk = (string) config('attachments.disk');
    Storage::fake($disk);
    User::query()->where('login', 'demo_resident')->update(['max_user_id' => 700100, 'bot_started_at' => now()]);
    $id = asToken($this->dispatcher)->getJson('/api/v1/requests?status=in_progress')->json('data.0.id');

    asToken($this->dispatcher)->post("/api/v1/requests/{$id}/close", [
        'comment' => 'Заменили лампу',
        'photos' => [UploadedFile::fake()->image('before.jpg', 40, 30), UploadedFile::fake()->image('after.png', 20, 20)],
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.status', 'done')
        ->assertJsonCount(2, 'data.attachments');

    $event = ServiceRequest::query()->findOrFail($id)->events()->where('to_status', 'done')->firstOrFail();
    $photos = Attachment::query()->where('request_id', $id)->where('kind', AttachmentKind::Closing->value)->orderBy('id')->get();
    expect($photos)->toHaveCount(2)
        ->and($photos->pluck('event_id')->unique()->all())->toBe([$event->id])
        ->and($photos->pluck('mime')->all())->toBe(['image/jpeg', 'image/png']);
    foreach ($photos as $photo) {
        Storage::disk($disk)->assertExists($photo->path);
        expect($photo->size_bytes)->toBe(strlen((string) Storage::disk($disk)->get($photo->path)));
    }

    $texts = OutboxMessage::query()->where('request_id', $id)->get()->map(fn (OutboxMessage $m) => $m->body['text'] ?? '')->all();
    expect($texts)->toContain(botText('request.done_confirm', ['number' => $id, 'status' => 'Выполнено', 'comment' => 'Заменили лампу']));
});

it('shows a joined neighbour the request without the flat, description and resident photos', function () {
    $neighbour = User::query()->create(['name' => 'Сосед', 'role' => Role::Resident, 'login' => 'demo_neighbour', 'password' => 'neighbour-pass', 'is_demo' => true]);
    $token = $this->postJson('/api/v1/auth/login', ['login' => 'demo_neighbour', 'password' => 'neighbour-pass'])->json('data.token');
    $author = User::query()->where('login', 'demo_resident')->firstOrFail();
    $request = app(RequestService::class)->create(new CreateRequestData(
        houseId: (int) House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->value('id'),
        residentUserId: $author->id,
        categoryId: (int) Category::query()->where('slug', 'entrance.light')->value('id'),
        description: 'Не горит свет, квартира 45',
        entrance: 2,
        flat: '45',
        photos: [new PhotoDraft('tok-1', 'https://cdn.example/1.jpg')],
    ));
    app(RequestService::class)->join($request, $neighbour);

    asToken($token)->getJson("/api/v1/requests/{$request->id}")
        ->assertValidResponse(200)
        ->assertJsonPath('data.flat', null)
        ->assertJsonPath('data.entrance', null)
        ->assertJsonPath('data.description', null)
        ->assertJsonCount(0, 'data.attachments')
        ->assertJsonPath('data.events.0.type', 'created');
    asToken($token)->getJson('/api/v1/my/requests')
        ->assertValidResponse(200)
        ->assertJsonPath('data.0.flat', null)
        ->assertJsonPath('data.0.description', null);

    asToken($this->resident)->getJson("/api/v1/requests/{$request->id}")
        ->assertValidResponse(200)
        ->assertJsonPath('data.flat', '45')
        ->assertJsonPath('data.entrance', 2)
        ->assertJsonPath('data.description', 'Не горит свет, квартира 45')
        ->assertJsonCount(1, 'data.attachments');
});

it('hides the executor phone from the resident', function () {
    $assigned = asToken($this->dispatcher)->getJson('/api/v1/requests?status=assigned')->json('data.0');
    expect($assigned['executor']['phone'])->not->toBeNull();

    asToken($this->resident)->getJson("/api/v1/requests/{$assigned['id']}")
        ->assertValidResponse(200)
        ->assertJsonPath('data.executor.name', $assigned['executor']['name'])
        ->assertJsonPath('data.executor.phone', null);
});
