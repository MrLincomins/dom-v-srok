<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Enums\AttachmentKind;
use App\Domain\Requests\Models\Attachment;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use App\Domain\Users\UserService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

function userServiceRequest(User $resident): ServiceRequest
{
    return app(RequestService::class)->create(new CreateRequestData(
        houseId: (int) House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->value('id'),
        residentUserId: $resident->id,
        categoryId: (int) Category::query()->where('slug', 'roof.leak')->value('id'),
        description: 'Течёт крыша',
    ));
}

function userServiceAttachment(ServiceRequest $request, AttachmentKind $kind, string $path): Attachment
{
    Storage::disk('private')->put($path, 'photo');

    return Attachment::query()->create([
        'request_id' => $request->id,
        'kind' => $kind,
        'disk' => 'private',
        'path' => $path,
        'mime' => 'image/png',
        'size_bytes' => 5,
    ]);
}

it('removes resident photos, tokens and bot access but keeps closing photos', function () {
    Storage::fake('private');
    $resident = User::query()->where('login', 'demo_resident')->firstOrFail();
    $resident->forceFill(['bot_started_at' => now()])->save();
    $resident->createToken('miniapp');
    $request = userServiceRequest($resident);
    $residentPhoto = userServiceAttachment($request, AttachmentKind::Resident, 'attachments/resident.png');
    $closingPhoto = userServiceAttachment($request, AttachmentKind::Closing, 'attachments/closing.png');

    app(UserService::class)->anonymize($resident);

    $resident->refresh();
    expect(Attachment::query()->whereKey($residentPhoto->id)->exists())->toBeFalse()
        ->and(Attachment::query()->whereKey($closingPhoto->id)->exists())->toBeTrue()
        ->and($resident->tokens()->count())->toBe(0)
        ->and($resident->bot_started_at)->toBeNull()
        ->and($resident->anonymized_at)->not->toBeNull()
        ->and($resident->name)->toBe('Житель (удалён)')
        ->and($resident->username)->toBeNull();
    Storage::disk('private')->assertMissing('attachments/resident.png');
    Storage::disk('private')->assertExists('attachments/closing.png');
});

it('does not restore name and username after anonymization', function () {
    $service = app(UserService::class);
    $user = $service->upsertFromMax(['user_id' => 777001, 'first_name' => 'Анна', 'username' => 'anna']);

    $service->anonymize($user);
    $again = $service->upsertFromMax(['user_id' => 777001, 'first_name' => 'Анна', 'username' => 'anna']);

    expect($again->id)->toBe($user->id)
        ->and($again->username)->toBeNull()
        ->and($again->name)->toBe('Житель (удалён)');
});

it('names a user without a name from MAX as a resident', function () {
    $user = app(UserService::class)->upsertFromMax(['user_id' => 777002, 'first_name' => '', 'last_name' => '']);

    expect($user->name)->toBe('Житель');
});

it('stops bot notifications for a user', function () {
    $service = app(UserService::class);
    $user = $service->upsertFromMax(['user_id' => 777003, 'first_name' => 'Анна'], startedBot: true);

    $service->stopBot(777003);

    expect($user->refresh()->bot_started_at)->toBeNull();
});
