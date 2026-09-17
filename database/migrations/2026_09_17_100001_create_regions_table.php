<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $t) {
            $t->string('code', 8)->primary();              // 'RU-TA'
            $t->text('name');
            $t->string('timezone', 64)->default('Europe/Moscow');
            $t->text('gzhi_name')->nullable();
            $t->text('gzhi_url')->nullable();
            $t->text('pos_url')->nullable();               // пос «решаем вместе»
            $t->text('control_url')->nullable();           // «народный контроль»
            $t->text('escalation_text')->nullable();       // подсказка «срок вышел, что дальше»
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
