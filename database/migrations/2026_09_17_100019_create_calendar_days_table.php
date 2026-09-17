<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // производственный календарь, только исключения из правила «пн-пт рабочие»
        Schema::create('calendar_days', function (Blueprint $t) {
            $t->date('day')->primary();
            $t->boolean('is_working');
            $t->text('note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
    }
};
