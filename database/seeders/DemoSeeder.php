<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\AccessCode;
use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Dto\Actor;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Dto\RedirectData;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\RequestOrigin;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Enums\Role;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** демо организация для проверки: тсж, дом с qr, диспетчер, житель, тестовые учётки, код доступа и 8 заявок в разных статусах (одна просрочена). всё с is_demo, пароли и код из окружения */
class DemoSeeder extends Seeder
{
    public const ORGANIZATION_NAME = 'ТСЖ «Демо»';

    public const HOUSE_ADDRESS = 'Казань, ул. Демонстрационная, д. 1';

    public const HOUSE_QR_TOKEN = 'demohouse001';

    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(['inn' => '1600000001'], [
            'region_code' => 'RU-TA',
            'type' => 'tsj',
            'name' => self::ORGANIZATION_NAME,
            'phone_ads' => '+7 843 000-00-01',
            'phone_dispatch' => '+7 843 000-00-02',
            'email' => 'demo@example.ru',
            'reception_hours' => 'пн–пт 9:00–18:00',
            'reception_address' => self::HOUSE_ADDRESS.', офис ТСЖ',
            'direct_cold_water' => false,
            'direct_tko' => true,
            'is_demo' => true,
        ]);

        $house = House::query()->updateOrCreate(['qr_token' => self::HOUSE_QR_TOKEN], [
            'organization_id' => $organization->id,
            'region_code' => 'RU-TA',
            'address' => self::HOUSE_ADDRESS,
            'entrances' => 3,
            'is_demo' => true,
        ]);

        $organization->executors()->updateOrCreate(['name' => 'Сантехник Иванов'], ['specialty' => 'сантехник', 'phone' => '+7 900 000-00-11', 'is_active' => true]);
        $electrician = $organization->executors()->updateOrCreate(['name' => 'Электрик Петров'], ['specialty' => 'электрик', 'phone' => '+7 900 000-00-12', 'is_active' => true]);
        $organization->contractors()->firstOrCreate(['type' => 'intercom', 'name' => 'ООО «Домофон-Сервис»'], ['phone' => '+7 843 000-00-03']);

        $accessCode = (string) config('demo.access_code');
        if ($accessCode !== '') {
            AccessCode::query()->updateOrCreate(['code_hash' => AccessCode::hash($accessCode)], [
                'organization_id' => $organization->id,
                'role' => 'dispatcher',
                'is_active' => true,
            ]);
        }

        $dispatcher = User::query()->updateOrCreate(['login' => 'demo_dispatcher'], [
            'name' => 'Диспетчер Демо',
            'role' => Role::Dispatcher,
            'organization_id' => $organization->id,
            'password' => Hash::make((string) (config('demo.dispatcher_password') ?: 'change-me')),
            'is_demo' => true,
        ]);

        $resident = User::query()->updateOrCreate(['login' => 'demo_resident'], [
            'name' => 'Житель Демо',
            'role' => Role::Resident,
            'house_id' => $house->id,
            'entrance' => 2,
            'flat' => '45',
            'password' => Hash::make((string) (config('demo.resident_password') ?: 'change-me')),
            'is_demo' => true,
        ]);

        if (ServiceRequest::query()->where('organization_id', $organization->id)->exists()) {
            return; // заявки уже есть, сброс через demo:reset
        }

        /** @var RequestService $service */
        $service = app(RequestService::class);
        $by = Actor::dispatcher($dispatcher);
        $residentActor = Actor::resident($resident);
        $cat = fn (string $slug): int => (int) Category::query()->where('slug', $slug)->value('id');
        $make = fn (string $slug, string $description, int $entrance = 2, ?string $flat = '45') => $service->create(new CreateRequestData(
            houseId: $house->id, residentUserId: $resident->id, categoryId: $cat($slug),
            description: $description, entrance: $entrance, flat: $flat, origin: RequestOrigin::Qr,
        ));

        // 1-2 новые
        $make('entrance.light', 'На третьем этаже не горит свет уже два дня');
        $make('intercom.broken', 'Домофон не открывает дверь с квартиры', 2, '45');

        // 3 назначена
        $r3 = $make('water.leak', 'Подтекает труба в подвале у второго подъезда');
        $service->assign($r3, $organization->executors()->where('name', 'Сантехник Иванов')->firstOrFail(), $by, 'Посмотреть сегодня');

        // 4 в работе
        $r4 = $make('power.floor', 'Нет света у лифта на первом этаже', 1, null);
        $service->assign($r4, $electrician, $by);
        $service->start($r4, $by, 'Выехал');

        // 5 просрочена, срок вышел вчера
        $r5 = $make('roof.leak', 'Течёт потолок в квартире на последнем этаже после дождя', 3, '120');
        $r5->forceFill([
            'created_at' => CarbonImmutable::now()->subDays(3),
            'deadline_fix_at' => CarbonImmutable::now()->subDay(),
        ])->save();

        // 6 выполнена, ждёт подтверждения жителя
        $r6 = $make('entrance.door', 'Не закрывается дверь подъезда, сломан доводчик', 2, null);
        $service->assign($r6, $organization->executors()->where('name', 'Сантехник Иванов')->firstOrFail(), $by);
        $service->start($r6, $by);
        $service->close($r6, $by, 'Доводчик заменён');

        // 7 подтверждена жителем
        $r7 = $make('heat.leak', 'Капает батарея в комнате', 2, '45');
        $service->start($r7, $by);
        $service->close($r7, $by, 'Подтянули соединение');
        $service->confirm($r7, $residentActor, ConfirmedBy::Resident);

        // 8 переадресована регоператору тко
        $r8 = $make('waste.not_removed', 'Контейнеры не вывозили с понедельника', 1, null);
        $service->redirect($r8, $by, new RedirectData(name: 'Региональный оператор ТКО', phone: '+7 843 000-00-04', note: 'Передано регоператору, заявка № ТКО-1234'));
    }
}
