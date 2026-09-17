<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // состояние диалога в постгресе, а не в редисе: переживает перезапуски и видно при отладке
        Schema::create('bot_sessions', function (Blueprint $t) {
            $t->bigInteger('max_user_id')->primary();
            $t->string('state', 32)->default('idle');
            $t->jsonb('payload')->default('{}');                       // черновик заявки
            $t->timestampTz('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_sessions');
    }
};
