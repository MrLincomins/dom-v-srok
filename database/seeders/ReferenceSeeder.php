<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            CalendarSeeder::class,
            CatalogSeeder::class,
            ResponsiblePartiesSeeder::class,
            BotTextsSeeder::class,
        ]);
    }
}
