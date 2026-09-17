<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Organizations\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        Region::query()->updateOrCreate(['code' => 'RU-TA'], [
            'name' => 'Республика Татарстан',
            'timezone' => 'Europe/Moscow',
            'gzhi_name' => 'Государственная жилищная инспекция Республики Татарстан',
            'gzhi_url' => 'https://gji.tatarstan.ru',
            'pos_url' => 'https://pos.gosuslugi.ru',
            'control_url' => 'https://uslugi.tatarstan.ru/open-gov',
            'escalation_text' => 'Срок по нормативу вышел. Что можно сделать: подать заявку повторно, обратиться в организацию по телефону, а если ответа нет — написать в жилищную инспекцию через ПОС «Решаем вместе» или «Народный контроль» РТ.',
        ]);
    }
}
