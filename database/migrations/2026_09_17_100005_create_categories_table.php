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
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $t->string('slug', 64)->unique();              // 'entrance.light'
            $t->text('name');
            $t->boolean('is_emergency')->default(false);
            $t->string('responsible_type', 16)->nullable();
            $t->integer('deadline_fix_value')->nullable();
            $t->string('deadline_fix_unit', 16)->nullable();
            $t->integer('deadline_reply_value')->nullable();
            $t->string('deadline_reply_unit', 16)->nullable();
            $t->text('basis')->nullable();                 // 'ПП 416 п. 13'
            $t->text('advice_text')->nullable();
            $t->jsonb('synonyms')->default('[]');          // ["темно в подъезде", ...]
            $t->boolean('verify')->default(false);         // строка помечена «сверить с первоисточником»
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();

            $t->index(['parent_id', 'sort_order']);
        });
        $types = ['uk', 'rso_cold', 'rso_hot', 'rso_heat', 'rso_power', 'lift', 'tko', 'intercom', 'municipal', 'capital_repair', 'ads'];
        Constraints::enum('categories', 'responsible_type', $types);
        Constraints::enum('categories', 'deadline_fix_unit', ['hours', 'days', 'working_days']);
        Constraints::enum('categories', 'deadline_reply_unit', ['hours', 'days', 'working_days']);
        Constraints::check('categories', 'fix_pair', '(deadline_fix_value IS NULL) = (deadline_fix_unit IS NULL)');
        Constraints::check('categories', 'reply_pair', '(deadline_reply_value IS NULL) = (deadline_reply_unit IS NULL)');
        Constraints::check('categories', 'fix_positive', 'deadline_fix_value IS NULL OR deadline_fix_value > 0');
        Constraints::check('categories', 'reply_positive', 'deadline_reply_value IS NULL OR deadline_reply_value > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
