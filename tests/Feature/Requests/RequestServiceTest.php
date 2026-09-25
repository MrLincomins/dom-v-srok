<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Events\RequestCreated;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Event;

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
