<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\DeadlineCalculator;
use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Enums\ContractorType;
use App\Domain\Organizations\Enums\OrganizationType;
use App\Domain\Organizations\Models\AccessCode;
use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Dto\RedirectData;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestOrigin;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public const ORGANIZATION_NAME = 'ТСЖ «Демо»';

    public const HOUSE_ADDRESS = 'Казань, ул. Демонстрационная, д. 1';

    public const HOUSE_QR_TOKEN = 'demohouse001';

    public const EXECUTORS = [
        ['name' => 'Сантехник Иванов', 'specialty' => 'сантехник', 'phone' => '+7 900 000-00-11'],
        ['name' => 'Электрик Петров', 'specialty' => 'электрик', 'phone' => '+7 900 000-00-12'],
    ];

    public const CONTRACTORS = [
        ['type' => ContractorType::Intercom, 'name' => 'ООО «Домофон-Сервис»', 'phone' => '+7 843 000-00-03'],
    ];

    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(['inn' => '1600000001'], [
            'region_code' => 'RU-TA',
            'type' => OrganizationType::Tsj,
            'name' => self::ORGANIZATION_NAME,
            'phone_ads' => '+7 843 000-00-01',
            'phone_dispatch' => '+7 843 000-00-02',
            'email' => 'demo@example.ru',
            'reception_hours' => 'пн–пт 9:00–18:00',
            'reception_address' => self::HOUSE_ADDRESS.', офис ТСЖ',
            'direct_cold_water' => false,
            'direct_hot_water' => false,
            'direct_heat' => false,
            'direct_power' => false,
            'direct_tko' => false,
            'is_demo' => true,
        ]);

        $house = House::query()->updateOrCreate(['qr_token' => self::HOUSE_QR_TOKEN], [
            'organization_id' => $organization->id,
            'region_code' => 'RU-TA',
            'address' => self::HOUSE_ADDRESS,
            'entrances' => 3,
            'chat_keywords_enabled' => false,
            'is_demo' => true,
        ]);

        foreach (self::EXECUTORS as $executor) {
            $organization->executors()->updateOrCreate(['name' => $executor['name']], [...$executor, 'is_active' => true]);
        }
        foreach (self::CONTRACTORS as $contractor) {
            $organization->contractors()->updateOrCreate(['type' => $contractor['type'], 'name' => $contractor['name']], ['phone' => $contractor['phone']]);
        }

        $this->syncAccessCode($organization);

        $dispatcher = User::query()->updateOrCreate(['login' => 'demo_dispatcher'], [
            'name' => 'Диспетчер Демо',
            'role' => Role::Dispatcher,
            'organization_id' => $organization->id,
            'password' => $this->passwordHash(config('demo.dispatcher_password')),
            'is_demo' => true,
        ]);

        $resident = User::query()->updateOrCreate(['login' => 'demo_resident'], [
            'name' => 'Житель Демо',
            'role' => Role::Resident,
            'house_id' => $house->id,
            'entrance' => 2,
            'flat' => '45',
            'password' => $this->passwordHash(config('demo.resident_password')),
            'is_demo' => true,
        ]);

        if (ServiceRequest::query()->where('organization_id', $organization->id)->exists()) {
            return;
        }

        DB::transaction(fn () => $this->seedRequests($organization, $house, $dispatcher, $resident));
    }

    private function seedRequests(Organization $organization, House $house, User $dispatcher, User $resident): void
    {
        /** @var RequestService $service */
        $service = app(RequestService::class);
        $deadlines = app(DeadlineCalculator::class);
        $by = Actor::dispatcher($dispatcher);
        $residentActor = Actor::resident($resident);
        $cat = fn (string $slug): int => (int) Category::query()->where('slug', $slug)->value('id');
        $make = fn (string $slug, string $description, int $entrance = 2, ?string $flat = '45') => $service->create(new CreateRequestData(
            houseId: $house->id, residentUserId: $resident->id, categoryId: $cat($slug),
            description: $description, entrance: $entrance, flat: $flat, origin: RequestOrigin::Qr,
        ));

        $make('entrance.light', 'На третьем этаже не горит свет уже два дня');
        $make('intercom.broken', 'Домофон не открывает дверь с квартиры', 2, '45');

        $plumber = $organization->executors()->where('name', self::EXECUTORS[0]['name'])->firstOrFail();
        $electrician = $organization->executors()->where('name', self::EXECUTORS[1]['name'])->firstOrFail();

        $r3 = $make('water.leak', 'Подтекает труба в подвале у второго подъезда');
        $service->assign($r3, $plumber, $by, 'Посмотреть сегодня');

        $r4 = $make('power.floor', 'Нет света у лифта на первом этаже', 1, null);
        $service->assign($r4, $electrician, $by);
        $service->start($r4, $by, 'Выехал');

        $r5 = $make('roof.leak', 'Течёт потолок в квартире на последнем этаже после дождя', 3, '120');
        $createdAt = CarbonImmutable::now()->subDays(3);
        $timezone = $house->region->timezone;
        $r5->forceFill([
            'created_at' => $createdAt,
            'deadline_fix_at' => $deadlines->fixDeadline($r5->category, $createdAt, $timezone),
            'deadline_reply_at' => $deadlines->replyDeadline($r5->category, $createdAt, $timezone),
        ])->save();
        $r5->events()->where('type', EventType::Created->value)->update(['created_at' => $createdAt]);

        $r6 = $make('entrance.door', 'Не закрывается дверь подъезда, сломан доводчик', 2, null);
        $service->assign($r6, $plumber, $by);
        $service->start($r6, $by);
        $service->close($r6, $by, 'Доводчик заменён');

        $r7 = $make('heat.leak', 'Капает батарея в комнате', 2, '45');
        $service->start($r7, $by);
        $service->close($r7, $by, 'Подтянули соединение');
        $service->confirm($r7, $residentActor);

        $r8 = $make('waste.not_removed', 'Контейнеры не вывозили с понедельника', 1, null);
        $service->redirect($r8, $by, new RedirectData(name: 'Региональный оператор ТКО', phone: '+7 843 000-00-04', note: 'Передано регоператору, заявка № ТКО-1234'));
    }

    private function syncAccessCode(Organization $organization): void
    {
        $plain = trim((string) config('demo.access_code'));
        $keepId = null;
        if ($plain !== '') {
            $keepId = AccessCode::query()->updateOrCreate(['code_hash' => AccessCode::hash($plain)], [
                'organization_id' => $organization->id,
                'role' => Role::Dispatcher,
                'is_active' => true,
            ])->id;
        }

        AccessCode::query()
            ->where('organization_id', $organization->id)
            ->when($keepId !== null, fn ($q) => $q->whereKeyNot($keepId))
            ->update(['is_active' => false]);
    }

    private function passwordHash(mixed $plain): ?string
    {
        return is_string($plain) && $plain !== '' ? Hash::make($plain) : null;
    }
}
