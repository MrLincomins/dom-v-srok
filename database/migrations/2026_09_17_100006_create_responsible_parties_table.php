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
        Schema::create('responsible_parties', function (Blueprint $t) {
            $t->id();
            $t->string('region_code', 8);
            $t->foreign('region_code')->references('code')->on('regions');
            $t->string('type', 16);
            $t->text('name');
            $t->string('phone', 32)->nullable();
            $t->text('url')->nullable();
            $t->text('note')->nullable();
            $t->string('source', 16)->default('manual');
            $t->date('source_date')->nullable();
            $t->timestampsTz();

            $t->unique(['region_code', 'type', 'name']);
        });
        Constraints::enum('responsible_parties', 'type', ['rso_cold', 'rso_hot', 'rso_heat', 'rso_power', 'lift', 'tko', 'intercom', 'municipal', 'capital_repair']);
    }

    public function down(): void
    {
        Schema::dropIfExists('responsible_parties');
    }
};
