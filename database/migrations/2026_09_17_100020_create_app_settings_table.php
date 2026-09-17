<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // курсор поллинга, подписка на вебхук, отметка сида
        Schema::create('app_settings', function (Blueprint $t) {
            $t->string('key', 64)->primary();
            $t->jsonb('value');
            $t->timestampTz('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
