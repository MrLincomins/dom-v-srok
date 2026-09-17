<?php

declare(strict_types=1);

use App\Support\Migration\Constraints;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('region_code', 8);
            $t->foreign('region_code')->references('code')->on('regions');
            $t->string('type', 8);                         // uk | tsj | jsk
            $t->text('name');
            $t->string('inn', 12)->nullable()->unique();
            $t->string('phone_ads', 32);                   // аварийная служба, обязательна
            $t->string('phone_dispatch', 32)->nullable();
            $t->string('email', 254)->nullable();
            $t->text('reception_hours')->nullable();
            $t->text('reception_address')->nullable();
            $t->boolean('direct_cold_water')->default(false);   // прямые договоры с рсо
            $t->boolean('direct_hot_water')->default(false);
            $t->boolean('direct_heat')->default(false);
            $t->boolean('direct_power')->default(false);
            $t->boolean('direct_tko')->default(false);
            $t->jsonb('settings')->default('{}');          // флаги функций
            $t->boolean('is_demo')->default(false);
            $t->string('source', 16)->default('manual');
            $t->date('source_date')->nullable();
            $t->timestampsTz();
        });
        Constraints::enum('organizations', 'type', ['uk', 'tsj', 'jsk']);
        Constraints::enum('organizations', 'source', ['manual', 'frt']);
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
