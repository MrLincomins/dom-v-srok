<?php

declare(strict_types=1);

use App\Bot\Fsm\DialogState;
use App\Bot\Models\BotSession;
use App\Domain\Catalog\Models\Category;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

function whoCategory(string $slug): int
{
    return (int) Category::query()->where('slug', $slug)->value('id');
}

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->max = fakeMax();
    runUpdate(startUpdate(555, 'h_'.DemoSeeder::HOUSE_QR_TOKEN.'_2'));
    $this->max->sent = [];
});

it('tells who is responsible and the deadline without filing a request', function () {
    runUpdate(callbackUpdate(555, 'who'));
    expect(lastText($this->max))->toBe(botText('who.category'))
        ->and(lastButtons($this->max))->toContain('who:'.whoCategory('entrance'))
        ->not->toContain('who:'.whoCategory('emergency'))
        ->toContain('menu');

    runUpdate(callbackUpdate(555, 'who:'.whoCategory('entrance')));
    expect(lastText($this->max))->toBe(botText('who.subcategory'))
        ->and(lastButtons($this->max))->toContain('who:'.whoCategory('entrance.light'));

    runUpdate(callbackUpdate(555, 'who:'.whoCategory('entrance.light')));
    expect(lastText($this->max))->toStartWith('Не горит свет в подъезде')
        ->toContain('Кто отвечает: '.DemoSeeder::ORGANIZATION_NAME)
        ->toContain('Срок: ')->toContain(', до ')
        ->and(lastButtons($this->max))->toContain('report')
        ->and(BotSession::query()->find(555)?->state ?? DialogState::Idle)->toBe(DialogState::Idle);
    expect(User::query()->where('max_user_id', 555)->firstOrFail()->requests()->count())->toBe(0);
});

it('sends emergencies to the emergency line', function () {
    runUpdate(callbackUpdate(555, 'who:'.whoCategory('emergency.pipe')));

    expect(lastText($this->max))->toBe(botText('who.emergency'))
        ->and(lastButtons($this->max))->toContain('contacts');
});

it('asks for the house when the resident has none', function () {
    User::query()->where('max_user_id', 555)->update(['house_id' => null]);

    runUpdate(callbackUpdate(555, 'who'));

    expect(lastText($this->max))->toBe(botText('who.house'));
});

it('keeps the request draft while the resident looks up who is responsible', function () {
    runUpdate(callbackUpdate(555, 'report'));
    runUpdate(callbackUpdate(555, 'who:'.whoCategory('entrance.light')));

    expect(BotSession::query()->find(555)?->state)->toBe(DialogState::AskEmergency);
});
