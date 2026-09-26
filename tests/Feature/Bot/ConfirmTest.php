<?php

declare(strict_types=1);

use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->max = fakeMax();
    runUpdate(startUpdate(555));
    runUpdate(startUpdate(888));
    $this->author = User::query()->where('max_user_id', 555)->firstOrFail();
    $this->neighbour = User::query()->where('max_user_id', 888)->firstOrFail();
    $this->request = ServiceRequest::query()->where('status', RequestStatus::Done->value)->firstOrFail();
    $this->request->forceFill(['resident_user_id' => $this->author->id])->save();
    app(RequestService::class)->join($this->request, $this->neighbour);
    $this->max->sent = [];
});

it('shows the confirmation buttons only to the author', function () {
    runUpdate(callbackUpdate(555, 'st:'.$this->request->id));
    expect(lastButtons($this->max))->toContain('ok:'.$this->request->id);

    runUpdate(callbackUpdate(888, 'st:'.$this->request->id));
    expect(lastButtons($this->max))->not->toContain('ok:'.$this->request->id)
        ->and(lastText($this->max))->toContain('Заявка № '.$this->request->id);
});

it('lets only the author confirm, and explains when the request is already closed', function () {
    runUpdate(callbackUpdate(888, 'ok:'.$this->request->id));
    expect(lastText($this->max))->toBe(botText('request.author_only', ['number' => $this->request->id]))
        ->and($this->request->refresh()->status)->toBe(RequestStatus::Done);

    runUpdate(callbackUpdate(555, 'ok:'.$this->request->id));
    expect($this->request->refresh()->status)->toBe(RequestStatus::Confirmed);

    runUpdate(callbackUpdate(555, 'no:'.$this->request->id));
    expect(lastText($this->max))->toBe(botText('request.already_confirmed', ['number' => $this->request->id]))
        ->and(lastButtons($this->max))->toContain('again:'.$this->request->id)
        ->and($this->request->refresh()->status)->toBe(RequestStatus::Confirmed);
});
