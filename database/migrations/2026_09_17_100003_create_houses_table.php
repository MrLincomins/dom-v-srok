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
        Schema::create('houses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete(); // null = без организации
            $t->string('region_code', 8);
            $t->foreign('region_code')->references('code')->on('regions');
            $t->uuid('fias_guid')->nullable();
            $t->text('address');
            $t->smallInteger('entrances')->default(1);
            $t->string('qr_token', 16)->unique();          // случайная строка для диплинка, не id
            $t->bigInteger('max_chat_id')->nullable()->unique();
            $t->boolean('chat_keywords_enabled')->default(false);
            $t->string('chat_pinned_message_id', 64)->nullable();
            $t->boolean('is_demo')->default(false);
            $t->string('source', 16)->default('manual');
            $t->date('source_date')->nullable();
            $t->timestampsTz();

            $t->index('organization_id');
        });
        Constraints::check('houses', 'entrances', 'entrances BETWEEN 1 AND 50');
        Constraints::enum('houses', 'source', ['manual', 'frt']);
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
