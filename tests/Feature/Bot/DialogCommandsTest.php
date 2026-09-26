<?php

declare(strict_types=1);

use App\Bot\Fsm\DialogState;
use App\Bot\Models\BotSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->max = fakeMax();
    runUpdate(startUpdate(555, 'h_'.DemoSeeder::HOUSE_QR_TOKEN.'_1'));
    $this->max->sent = [];
});

it('returns to the menu by command or by the word and drops the draft', function () {
    runUpdate(callbackUpdate(555, 'report'));
    expect(BotSession::query()->find(555)?->state)->toBe(DialogState::AskEmergency);

    runUpdate(messageUpdate(555, '/menu', 'mid.m1'));
    expect(lastText($this->max))->toBe(botText('menu.main'))
        ->and(lastButtons($this->max))->toContain('report')
        ->and(BotSession::query()->find(555)?->state)->toBe(DialogState::Idle);

    runUpdate(messageUpdate(555, 'Меню', 'mid.m2'));
    expect(lastText($this->max))->toBe(botText('menu.main'));
});

it('greets staff with the cabinet only and keeps them out of the report flow', function () {
    $organizationId = Organization::query()->where('name', DemoSeeder::ORGANIZATION_NAME)->value('id');
    User::query()->where('max_user_id', 555)->update(['role' => Role::Dispatcher->value, 'organization_id' => $organizationId]);

    runUpdate([...startUpdate(555, 'h_'.DemoSeeder::HOUSE_QR_TOKEN.'_1'), 'timestamp' => 1758100001000]);
    expect($this->max->sent)->toHaveCount(1)
        ->and(lastText($this->max))->toBe(botText('start.staff', ['name' => DemoSeeder::ORGANIZATION_NAME]))
        ->and(lastButtons($this->max))->toContain((string) config('max.bot_username'));

    runUpdate(callbackUpdate(555, 'menu'));
    expect(lastText($this->max))->toBe(botText('menu.cabinet'))
        ->and(lastButtons($this->max))->toContain((string) config('max.bot_username'));

    runUpdate(callbackUpdate(555, 'report'));
    expect(lastText($this->max))->toBe(botText('menu.cabinet'))
        ->and(BotSession::query()->find(555)?->state)->not->toBe(DialogState::AskEmergency);
});

it('rejects a wrong dispatcher code and refuses the demo reset to residents', function () {
    runUpdate(messageUpdate(555, '/dispatcher WRONG', 'mid.d1'));
    expect(lastText($this->max))->toBe(botText('dispatcher.invalid_code'));
    $this->assertDatabaseHas('users', ['max_user_id' => 555, 'role' => Role::Resident->value]);

    runUpdate(messageUpdate(555, '/demo_reset', 'mid.d2'));
    expect(lastText($this->max))->toBe(botText('fallback.hint'));
});

it('resets the demo data for a staff member of the demo organisation', function () {
    runUpdate(messageUpdate(555, '/dispatcher TEST-CODE', 'mid.d3'));
    $first = ServiceRequest::query()->orderBy('id')->firstOrFail();
    $first->forceFill(['status' => RequestStatus::InProgress])->save();

    runUpdate(messageUpdate(555, '/demo_reset', 'mid.d4'));

    expect(lastText($this->max))->toBe(botText('demo.reset_done'))
        ->and(ServiceRequest::query()->orderBy('id')->firstOrFail()->status)->toBe(RequestStatus::New);
});

it('answers an unknown button with a hint and stops writing after the dialog is removed', function () {
    runUpdate(callbackUpdate(555, 'what:1'));
    expect(lastText($this->max))->toBe(botText('fallback.hint'));

    runUpdate(['update_type' => 'dialog_removed', 'timestamp' => 1758100003000, 'user' => ['user_id' => 555, 'first_name' => 'Анна']]);
    $this->assertDatabaseHas('users', ['max_user_id' => 555, 'bot_started_at' => null]);
});
