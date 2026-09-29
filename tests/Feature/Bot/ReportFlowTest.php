<?php

declare(strict_types=1);

use App\Bot\Client\MaxApiException;
use App\Bot\Fsm\DialogState;
use App\Bot\Models\BotSession;
use App\Bot\Models\OutboxMessage;
use App\Bot\Outbox\OutboxStatus;
use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use App\Jobs\DownloadMaxAttachment;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Queue;

function flowCategory(string $slug): int
{
    return (int) Category::query()->where('slug', $slug)->value('id');
}

function flowResident(): User
{
    return User::query()->where('max_user_id', 555)->firstOrFail();
}

function flowState(): ?DialogState
{
    return BotSession::query()->find(555)?->state;
}

function flowLastRequest(): ServiceRequest
{
    return ServiceRequest::query()->where('resident_user_id', flowResident()->id)->latest('id')->firstOrFail();
}

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->max = fakeMax();
    runUpdate(startUpdate(555, 'h_'.DemoSeeder::HOUSE_QR_TOKEN.'_2'));
    $this->max->sent = [];
});

it('files a request in four steps: emergency question, category, details, address', function () {
    runUpdate(callbackUpdate(555, 'report'));
    expect(lastText($this->max))->toBe(botText('emergency.question'))
        ->and(flowState())->toBe(DialogState::AskEmergency);

    runUpdate(callbackUpdate(555, 'em:no'));
    expect(lastText($this->max))->toBe(botText('report.category'))
        ->and(lastButtons($this->max))->toContain('cat:'.flowCategory('entrance'))
        ->not->toContain('cat:'.flowCategory('emergency'));

    runUpdate(callbackUpdate(555, 'cat:'.flowCategory('entrance')));
    expect(lastButtons($this->max))->toContain('sub:'.flowCategory('entrance.light'));

    runUpdate(callbackUpdate(555, 'sub:'.flowCategory('entrance.light')));
    expect(lastText($this->max))->toBe(botText('report.details'));

    runUpdate(messageUpdate(555, 'На третьем этаже не горит свет', 'mid.10'));
    expect(lastText($this->max))->toBe(botText('report.address'));

    runUpdate(messageUpdate(555, '2, 45', 'mid.11'));
    expect(lastText($this->max))->toContain('Проверьте заявку:')
        ->toContain('Что: Не горит свет в подъезде: На третьем этаже не горит свет, подъезд 2')
        ->toContain('Кто отвечает: '.DemoSeeder::ORGANIZATION_NAME)
        ->toContain('Срок: ')->toContain(', до ')
        ->and(flowState())->toBe(DialogState::Preview);

    runUpdate(callbackUpdate(555, 'send'));
    $request = flowLastRequest();
    expect($request->category_id)->toBe(flowCategory('entrance.light'))
        ->and($request->entrance)->toBe(2)
        ->and($request->flat)->toBe('45')
        ->and($request->status)->toBe(RequestStatus::New)
        ->and($request->responsible_name)->toBe(DemoSeeder::ORGANIZATION_NAME)
        ->and($request->is_sure)->toBeTrue()
        ->and($request->deadline_fix_at)->not->toBeNull();
    expect(lastText($this->max))->toContain('Заявка № '.$request->id.' принята')
        ->and(flowState())->toBe(DialogState::Idle);
    $this->assertDatabaseHas('users', ['max_user_id' => 555, 'entrance' => 2, 'flat' => '45']);
});

it('answers an emergency with the phone and records the signal without a request', function () {
    runUpdate(callbackUpdate(555, 'report'));
    runUpdate(callbackUpdate(555, 'em:yes'));

    expect(lastText($this->max))->toContain('+7 843 000-00-01')
        ->and(lastButtons($this->max))->toContain('+7 843 000-00-01')
        ->and(flowState())->toBe(DialogState::Idle);
    $this->assertDatabaseHas('emergency_signals', ['user_id' => flowResident()->id, 'phone_shown' => '+7 843 000-00-01']);
    expect(ServiceRequest::query()->where('resident_user_id', flowResident()->id)->count())->toBe(0);
});

it('suggests a category for free text and files an unsure request', function () {
    runUpdate(messageUpdate(555, 'у нас не горит свет на этаже', 'mid.20'));
    $light = 'sub:'.flowCategory('entrance.light');
    expect(lastText($this->max))->toBe(botText('report.suggest'))
        ->and(lastButtons($this->max))->toContain($light);

    runUpdate(callbackUpdate(555, $light));
    expect(lastText($this->max))->toBe(botText('report.details'));
    runUpdate(callbackUpdate(555, 'send'));
    expect(lastText($this->max))->toBe(botText('report.address'));
    runUpdate(messageUpdate(555, 'кв 45', 'mid.21'));
    runUpdate(callbackUpdate(555, 'unsure'));

    $request = flowLastRequest();
    expect($request->is_sure)->toBeFalse()
        ->and($request->entrance)->toBe(2)
        ->and($request->flat)->toBe('45')
        ->and($request->description)->toBe('');
    expect(lastText($this->max))->toContain(botText('request.created.unsure'));
});

it('keeps the photo token in the draft and queues the download after sending', function () {
    Queue::fake([DownloadMaxAttachment::class]);
    runUpdate(callbackUpdate(555, 'sub:'.flowCategory('water.leak')));
    $photo = ['type' => 'image', 'payload' => ['photo_id' => 1, 'token' => 'tok-1', 'url' => 'https://cdn.example/1.jpg']];
    runUpdate(messageUpdate(555, null, 'mid.30', [$photo]));
    expect(lastText($this->max))->toBe(botText('report.address'));
    runUpdate(messageUpdate(555, '1, 7', 'mid.31'));
    runUpdate(callbackUpdate(555, 'send'));

    $request = flowLastRequest();
    $this->assertDatabaseHas('attachments', ['request_id' => $request->id, 'max_token' => 'tok-1', 'path' => '']);
    Queue::assertPushed(DownloadMaxAttachment::class, fn (DownloadMaxAttachment $job) => $job->url === 'https://cdn.example/1.jpg');
});

it('offers the previous address and reuses it', function () {
    User::query()->where('max_user_id', 555)->update(['entrance' => 3, 'flat' => '120']);
    runUpdate(callbackUpdate(555, 'sub:'.flowCategory('roof.leak')));
    runUpdate(messageUpdate(555, 'Течёт потолок', 'mid.40'));
    expect(lastText($this->max))->toBe(botText('report.address_same', ['entrance' => 3, 'flat' => '120']));

    runUpdate(callbackUpdate(555, 'addr:same'));
    expect(lastText($this->max))->toContain('подъезд 3');
    runUpdate(callbackUpdate(555, 'send'));

    $request = flowLastRequest();
    expect($request->entrance)->toBe(3)->and($request->flat)->toBe('120');
});

it('re-asks the current step for unexpected text and keeps the draft', function () {
    runUpdate(callbackUpdate(555, 'report'));
    runUpdate(messageUpdate(555, 'привет', 'mid.50'));
    expect(lastText($this->max))->toBe(botText('emergency.question'))
        ->and(flowState())->toBe(DialogState::AskEmergency);

    runUpdate(callbackUpdate(555, 'em:no'));
    runUpdate(messageUpdate(555, 'ну и что мне нажать', 'mid.51'));
    expect(lastText($this->max))->toBe(botText('report.category'))
        ->and(flowState())->toBe(DialogState::AskCategory);
});

it('drops the draft on cancel', function () {
    runUpdate(callbackUpdate(555, 'report'));
    runUpdate(callbackUpdate(555, 'em:no'));
    runUpdate(callbackUpdate(555, 'cancel'));

    expect(lastText($this->max))->toBe(botText('report.cancelled'))
        ->and(flowState())->toBe(DialogState::Idle);
});

it('asks for the house when the resident came without a QR code', function () {
    runUpdate(messageUpdate(777, '/start', 'mid.60'));
    $this->max->sent = [];
    runUpdate(callbackUpdate(777, 'report'));
    expect(lastText($this->max))->toBe(botText('report.house'));

    runUpdate(messageUpdate(777, 'Демонстрационная', 'mid.61'));
    $house = House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->firstOrFail();
    expect(lastButtons($this->max))->toContain('house:'.$house->id);

    runUpdate(callbackUpdate(777, 'house:'.$house->id));
    expect(lastText($this->max))->toBe(botText('emergency.question'));
    $this->assertDatabaseHas('users', ['max_user_id' => 777, 'house_id' => $house->id]);
});

it('forgets a draft older than a day', function () {
    runUpdate(callbackUpdate(555, 'report'));
    BotSession::query()->whereKey(555)->update(['updated_at' => CarbonImmutable::now()->subHours(25)]);

    runUpdate(messageUpdate(555, 'привет', 'mid.70'));
    expect(lastText($this->max))->toBe(botText('fallback.hint'))
        ->and(flowState())->toBe(DialogState::Idle);
});

it('starts a repeat request from the «again» button with the same category and address', function () {
    $first = app(RequestService::class)->create(new CreateRequestData(
        houseId: (int) House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->value('id'),
        residentUserId: flowResident()->id,
        categoryId: flowCategory('intercom.broken'),
        description: 'Не открывает',
        entrance: 2,
        flat: '45',
    ));
    User::query()->where('max_user_id', 555)->update(['flat' => '45']);
    $this->max->sent = [];

    runUpdate(callbackUpdate(555, 'again:'.$first->id));
    expect(lastText($this->max))->toBe(botText('report.details'));
    runUpdate(messageUpdate(555, 'Опять не открывает', 'mid.80'));
    runUpdate(callbackUpdate(555, 'addr:same'));
    runUpdate(callbackUpdate(555, 'send'));

    $repeat = flowLastRequest();
    expect($repeat->id)->not->toBe($first->id)
        ->and($repeat->repeat_of_id)->toBe($first->id)
        ->and($repeat->category_id)->toBe(flowCategory('intercom.broken'))
        ->and($repeat->flat)->toBe('45');
});

it('schedules a reminder for a house without an organization', function () {
    $house = House::query()->create(['region_code' => 'RU-TA', 'address' => 'Казань, ул. Тестовая, д. 5', 'entrances' => 2]);
    runUpdate(startUpdate(555, 'h_'.$house->qr_token.'_1'));
    $this->max->sent = [];

    runUpdate(callbackUpdate(555, 'sub:'.flowCategory('entrance.light')));
    runUpdate(messageUpdate(555, 'Темно', 'mid.90'));
    runUpdate(messageUpdate(555, '1, 3', 'mid.91'));
    expect(lastText($this->max))->toContain('Кто отвечает: Управляющая организация вашего дома');
    runUpdate(callbackUpdate(555, 'send'));

    $request = ServiceRequest::query()->where('house_id', $house->id)->firstOrFail();
    $reminder = OutboxMessage::query()->where('dedupe_key', 'request.reminder:'.$request->id)->firstOrFail();
    expect($request->organization_id)->toBeNull()
        ->and($reminder->available_at->toIso8601String())->toBe($request->deadline_fix_at?->toIso8601String())
        ->and($reminder->body['text'])->toContain('Срок по заявке № '.$request->id);
});

it('answers a button press with the next screen and writes a new message after the resident types', function () {
    runUpdate(callbackUpdate(555, 'report', 'cb.7'));
    $answer = lastAnswer($this->max);
    $row = OutboxMessage::query()->latest('id')->firstOrFail();
    expect($answer['id'])->toBe('cb.7')
        ->and($answer['message']['text'] ?? '')->toBe(botText('emergency.question'))
        ->and($row->status)->toBe(OutboxStatus::Sent)
        ->and($row->max_message_id)->toBe('mid.bot.cb.7')
        ->and($this->max->messages())->toBe([]);

    runUpdate(callbackUpdate(555, 'em:no', 'cb.8'));
    expect(lastAnswer($this->max)['id'])->toBe('cb.8')
        ->and(lastText($this->max))->toBe(botText('report.category'));

    runUpdate(callbackUpdate(555, 'sub:'.flowCategory('entrance.light'), 'cb.9'));
    runUpdate(messageUpdate(555, 'Темно на этаже', 'mid.20'));
    $fresh = end($this->max->sent);
    expect($fresh['target'])->toBe('user')
        ->and($fresh['id'])->toBe(555)
        ->and(lastText($this->max))->toBe(botText('report.address'));

    runUpdate(messageUpdate(555, '1, 7', 'mid.21'));
    runUpdate(callbackUpdate(555, 'send', 'cb.10'));
    expect(lastAnswer($this->max)['id'])->toBe('cb.10')
        ->and(end($this->max->sent)['target'])->toBe('answer')
        ->and(lastText($this->max))->toContain('Заявка № '.flowLastRequest()->id.' принята');
});

it('edits the pressed message when the answer is rejected, and writes a new one when the edit fails too', function () {
    $this->max->answerFailure = new MaxApiException('POST /answers → 400: callback expired', 400, 'POST /answers');

    runUpdate(callbackUpdate(555, 'report', 'cb.11'));
    $edited = end($this->max->sent);
    expect($edited['target'])->toBe('edit')
        ->and($edited['mid'])->toBe('mid.bot.cb.11')
        ->and(lastText($this->max))->toBe(botText('emergency.question'));

    $this->max->failEdits = true;
    runUpdate(callbackUpdate(555, 'em:no', 'cb.12'));
    $sent = end($this->max->sent);
    expect($sent['target'])->toBe('user')
        ->and($sent['id'])->toBe(555)
        ->and(lastText($this->max))->toBe(botText('report.category'))
        ->and(OutboxMessage::query()->latest('id')->value('edit_message_id'))->toBe('mid.bot.cb.12');
});

it('does not edit a message from the house chat when answering in the dialog', function () {
    $update = callbackUpdate(555, 'my', 'cb.13');
    $update['message']['recipient'] = ['chat_type' => 'chat', 'chat_id' => 9001];

    runUpdate($update);

    expect(end($this->max->sent)['target'])->toBe('user')
        ->and(lastText($this->max))->toBe(botText('my.empty'));
});
