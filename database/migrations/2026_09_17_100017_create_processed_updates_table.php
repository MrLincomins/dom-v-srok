<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // идемпотентность вебхука, повтор обновления - 200 без обработки. чистится через 7 дней
        Schema::create('processed_updates', function (Blueprint $t) {
            $t->string('update_key', 120)->primary();
            $t->timestampTz('received_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_updates');
    }
};
