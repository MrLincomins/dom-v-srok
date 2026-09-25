<?php

declare(strict_types=1);

use App\Bot\Models\OutboxMessage;
use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Dto\ClosingPhoto;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Events\RequestCreated;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

function newDemoRequest(): ServiceRequest
{
    return app(RequestService::class)->create(new CreateRequestData(
        houseId: (int) House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->value('id'),
        residentUserId: User::query()->where('login', 'demo_resident')->firstOrFail()->id,
        categoryId: (int) Category::query()->where('slug', 'roof.leak')->value('id'),
        description: 'Течёт крыша',
    ));
}

it('keeps the request when a listener fails', function () {
    Event::listen(RequestCreated::class, fn () => throw new RuntimeException('Уведомление не ушло'));

    $request = newDemoRequest();

    expect($request->exists)->toBeTrue();
    $this->assertDatabaseHas('requests', ['id' => $request->id, 'description' => 'Течёт крыша']);
});

it('records the resident rating in the request feed', function () {
    $request = ServiceRequest::query()->where('status', RequestStatus::Confirmed->value)->firstOrFail();

    app(RequestService::class)->rate($request, $request->resident, 4);

    expect($request->refresh()->rating)->toBe(4);
    $event = $request->events()->where('type', EventType::Comment->value)->latest('id')->firstOrFail();
    expect($event->comment)->toBe('Оценка жителя: 4')
        ->and($event->payload)->toBe(['rating' => 4]);
});

it('removes stored closing photos when closing fails', function () {
    $disk = (string) config('attachments.disk');
    Storage::fake($disk);
    $request = ServiceRequest::query()->where('status', RequestStatus::InProgress->value)->firstOrFail();
    $dispatcher = User::query()->where('login', 'demo_dispatcher')->firstOrFail();
    $photos = [
        new ClosingPhoto(UploadedFile::fake()->image('done.jpg'), 'image/jpeg'),
        new ClosingPhoto(new SplFileInfo('/nonexistent/photo.jpg'), 'image/jpeg'),
    ];

    expect(fn () => app(RequestService::class)->close($request, Actor::dispatcher($dispatcher), 'Готово', $photos))
        ->toThrow(ErrorException::class);

    expect(Storage::disk($disk)->allFiles())->toBe([])
        ->and($request->refresh()->status)->toBe(RequestStatus::InProgress);
});

it('tells a joined neighbour about every completion, not just the first', function () {
    $service = app(RequestService::class);
    $neighbour = User::query()->create(['name' => 'Сосед', 'role' => Role::Resident, 'max_user_id' => 700200, 'bot_started_at' => now()]);
    $dispatcher = Actor::dispatcher(User::query()->where('login', 'demo_dispatcher')->firstOrFail());
    $request = ServiceRequest::query()->where('status', RequestStatus::InProgress->value)->firstOrFail();
    $service->join($request, $neighbour);

    $service->close($request, $dispatcher, 'Готово');
    $service->returnToWork($request, Actor::resident($request->resident), 'Не решено');
    $service->close($request, $dispatcher, 'Теперь готово');

    expect(OutboxMessage::query()->where('target_id', 700200)->where('request_id', $request->id)->count())->toBe(2);
});
