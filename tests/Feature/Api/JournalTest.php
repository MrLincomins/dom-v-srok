<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Enums\RequestOrigin;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\URL;
use Spectator\Spectator;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');
    $this->dispatcher = $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'dispatcher-pass'])->json('data.token');
    $this->resident = $this->postJson('/api/v1/auth/login', ['login' => 'demo_resident', 'password' => 'resident-pass'])->json('data.token');
    $this->from = CarbonImmutable::now('Europe/Moscow')->subDays(10)->toDateString();
    $this->to = CarbonImmutable::now('Europe/Moscow')->toDateString();
});

it('lists journal rows for the period with a summary and matches the contract', function () {
    $response = asToken($this->dispatcher)->getJson("/api/v1/journal?from={$this->from}&to={$this->to}")
        ->assertValidRequest()->assertValidResponse(200)
        ->assertJsonCount(8, 'data')
        ->assertJsonPath('meta.period.from', $this->from)
        ->assertJsonPath('meta.period.to', $this->to)
        ->assertJsonPath('meta.summary.total', 8)
        ->assertJsonPath('meta.summary.open', 5)
        ->assertJsonPath('meta.summary.overdue', 1)
        ->assertJsonPath('meta.summary.closed', 2)
        ->assertJsonPath('meta.summary.on_time', 2)
        ->assertJsonPath('meta.summary.late', 0)
        ->assertJsonPath('meta.summary.returned', 0)
        ->assertJsonPath('meta.summary.redirected', 1)
        ->assertJsonPath('meta.summary.first_reaction_minutes', 0);

    $rows = collect($response->json('data'));
    expect($rows->pluck('id')->all())->toBe($rows->pluck('id')->sort()->values()->all())
        ->and($rows->firstWhere('status', 'redirected')['redirected_to'])->toBe('Региональный оператор ТКО')
        ->and($rows->firstWhere('status', 'assigned')['executor_name'])->toBe('Сантехник Иванов')
        ->and($rows->firstWhere('is_overdue', true)['status'])->toBe('new')
        ->and($rows->firstWhere('status', 'confirmed')['confirmed_by'])->toBe('resident')
        ->and($rows->first()['resident_name'])->toBe('Житель Демо');
});

it('defaults the period to the current month and paginates', function () {
    $monthStart = CarbonImmutable::now('Europe/Moscow')->startOfMonth()->toDateString();

    asToken($this->dispatcher)->getJson('/api/v1/journal?per_page=3')
        ->assertValidResponse(200)
        ->assertJsonPath('meta.period.from', $monthStart)
        ->assertJsonPath('meta.period.to', $this->to)
        ->assertJsonPath('meta.per_page', 3)
        ->assertJsonCount(3, 'data');
});

it('exports the journal as CSV with a BOM, semicolons and one line per request', function () {
    $response = asToken($this->dispatcher)->get("/api/v1/journal.csv?from={$this->from}&to={$this->to}");

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="zhurnal-zayavok-'.$this->from.'-'.$this->to.'.csv"');

    $body = $response->getContent();
    $lines = explode("\n", trim(substr($body, 3)));
    $firstId = ServiceRequest::query()->min('id');
    expect(substr($body, 0, 3))->toBe("\xEF\xBB\xBF")
        ->and($lines)->toHaveCount(9)
        ->and($lines[0])->toStartWith('"№ заявки";"Дата и время приёма";Адрес;')
        ->and($lines[0])->toContain('"Срок по нормативу"')
        ->and($lines[1])->toStartWith($firstId.';')
        ->and($lines[1])->toContain('"Казань, ул. Демонстрационная, д. 1"')
        ->and($lines[1])->toContain(';Принято;')
        ->and($lines[5])->toContain(';просрочена;')
        ->and($lines[7])->toContain(';да;житель;')
        ->and($lines[8])->toContain('"Региональный оператор ТКО"');
});

it('keeps other organisations and houses without an organisation out of the journal', function () {
    $house = House::query()->create(['region_code' => 'RU-TA', 'address' => 'Казань, ул. Чужая, д. 2']);
    $resident = User::query()->where('login', 'demo_resident')->firstOrFail();
    app(RequestService::class)->create(new CreateRequestData(
        houseId: $house->id,
        residentUserId: $resident->id,
        categoryId: (int) Category::query()->where('slug', 'entrance.light')->value('id'),
        description: 'Свет в чужом доме',
        origin: RequestOrigin::Direct,
    ));

    asToken($this->dispatcher)->getJson("/api/v1/journal?from={$this->from}&to={$this->to}")
        ->assertOk()
        ->assertJsonPath('meta.summary.total', 8);
});

it('rejects a broken period and hides the journal from residents', function () {
    asToken($this->dispatcher)->getJson('/api/v1/journal?from=2026-09-10&to=2026-09-01')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');
    asToken($this->dispatcher)->getJson('/api/v1/journal?from=2025-01-01&to=2026-06-01')
        ->assertStatus(422)
        ->assertJsonPath('error.details.fields.to.0', 'Период журнала не длиннее года');
    asToken($this->dispatcher)->getJson('/api/v1/journal?from=вчера')
        ->assertStatus(422);

    asToken($this->resident)->getJson('/api/v1/journal')->assertStatus(403);
    asToken($this->resident)->get('/api/v1/journal.csv')->assertStatus(403);
});

it('escapes cells that look like spreadsheet formulas', function () {
    $resident = User::query()->where('login', 'demo_resident')->firstOrFail();
    $request = app(RequestService::class)->create(new CreateRequestData(
        houseId: (int) $resident->house_id,
        residentUserId: $resident->id,
        categoryId: (int) Category::query()->where('slug', 'entrance.light')->value('id'),
        description: '=HYPERLINK("http://evil.example","нажми")',
    ));

    $body = asToken($this->dispatcher)->get("/api/v1/journal.csv?from={$this->from}&to={$this->to}")->assertOk()->getContent();

    $line = collect(explode("\n", $body))->first(fn (string $line) => str_starts_with($line, $request->id.';'));
    expect($line)->toContain(';"\'=HYPERLINK(')
        ->and($line)->not->toContain(';"=HYPERLINK(');
});

it('limits the period to a year when only the start date is given', function () {
    $from = CarbonImmutable::now('Europe/Moscow')->subYears(2)->toDateString();

    asToken($this->dispatcher)->getJson("/api/v1/journal?from={$from}")
        ->assertStatus(422)
        ->assertJsonPath('error.details.fields.from.0', 'Период журнала не длиннее года');
    asToken($this->dispatcher)->getJson("/api/v1/journal/csv-link?from={$from}")
        ->assertStatus(422);
    asToken($this->dispatcher)->getJson('/api/v1/journal?from='.CarbonImmutable::now('Europe/Moscow')->addDays(3)->toDateString())
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');
});

it('gives a three-minute signed link to the same CSV that opens without a token', function () {
    $response = asToken($this->dispatcher)->getJson("/api/v1/journal/csv-link?from={$this->from}&to={$this->to}")
        ->assertValidRequest()->assertValidResponse(200);
    $link = $response->json('data.url');
    $expected = asToken($this->dispatcher)->get("/api/v1/journal.csv?from={$this->from}&to={$this->to}")->getContent();

    app('auth')->forgetGuards();
    $file = $this->withoutToken()->get($link)
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="zhurnal-zayavok-'.$this->from.'-'.$this->to.'.csv"');
    expect($file->getContent())->toBe($expected)
        ->and(now()->diffInMinutes(CarbonImmutable::parse($response->json('data.expires_at'))))->toBeGreaterThan(2.0)->toBeLessThanOrEqual(3.0);

    $this->get(str_replace('organization=', 'organization=9', $link))->assertStatus(403)->assertJsonPath('error.code', 'forbidden');
    $this->get("/api/v1/journal/export.csv?from={$this->from}&to={$this->to}")->assertStatus(403);

    $this->travel(4)->minutes();
    $this->get($link)->assertStatus(403);
});

it('does not give the CSV link to residents or for a broken period', function () {
    asToken($this->resident)->getJson('/api/v1/journal/csv-link')->assertStatus(403);
    asToken($this->dispatcher)->getJson('/api/v1/journal/csv-link?from=2026-09-10&to=2026-09-01')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');
});

it('binds the CSV link to the dispatcher who asked for it', function () {
    $link = asToken($this->dispatcher)->getJson("/api/v1/journal/csv-link?from={$this->from}&to={$this->to}")->json('data.url');
    $dispatcherId = User::query()->where('login', 'demo_dispatcher')->value('id');
    $residentId = User::query()->where('login', 'demo_resident')->value('id');
    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
    expect((int) $query['user'])->toBe($dispatcherId);

    app('auth')->forgetGuards();
    $this->withoutToken()->get(str_replace('user='.$dispatcherId, 'user='.$residentId, $link))
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'forbidden');

    $residentLink = URL::temporarySignedRoute('api.journal.export', now()->addMinutes(3), [
        'organization' => $query['organization'], 'user' => $residentId, 'from' => $this->from, 'to' => $this->to,
    ]);
    $this->get($residentLink)->assertStatus(403);
});
