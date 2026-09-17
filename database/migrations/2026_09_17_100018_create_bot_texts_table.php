<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // тексты бота из docs/texts.csv, продакт правит без разработчика
        Schema::create('bot_texts', function (Blueprint $t) {
            $t->string('key', 64);
            $t->string('locale', 5)->default('ru');
            $t->text('text');
            $t->jsonb('buttons')->nullable();                          // [[{label, action}], ...]
            $t->timestampTz('updated_at')->useCurrent();

            $t->primary(['key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_texts');
    }
};
