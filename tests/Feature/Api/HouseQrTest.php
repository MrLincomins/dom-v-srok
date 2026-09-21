<?php

declare(strict_types=1);

use App\Domain\Organizations\Models\House;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->dispatcher = $this->postJson('/api/v1/auth/login', ['login' => 'demo_dispatcher', 'password' => 'dispatcher-pass'])->json('data.token');
});

it('serves the house QR as PNG by the signed link only, with an optional entrance', function () {
    $house = House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->firstOrFail();
    $url = asToken($this->dispatcher)->getJson('/api/v1/organization/houses')->json('data.0.qr_url');

    $png = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    expect(substr($png->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n")
        ->and(imagecreatefromstring($png->getContent()))->not->toBeFalse();

    $this->get($url.'&entrance=2')->assertOk()->assertHeader('Content-Disposition', 'inline; filename="qr-'.DemoSeeder::HOUSE_QR_TOKEN.'-2.png"');
    $this->get($url.'&entrance=9')->assertStatus(404);

    $this->get(str_replace('signature=', 'signature=0', $url))->assertStatus(403)->assertJsonPath('error.code', 'forbidden');
    $this->get("/api/v1/organization/houses/{$house->id}/qr.png")->assertStatus(403);
    $this->get(URL::signedRoute('api.organization.houses.qr', ['house' => $house->id + 100]))->assertStatus(404);
});
