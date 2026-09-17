<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();  // на будущее для исполнителя в боте
            $t->text('name');
            $t->string('phone', 32)->nullable();
            $t->string('specialty', 64)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();
        });
        DB::statement('CREATE INDEX executors_organization_active_idx ON executors (organization_id) WHERE is_active');
    }

    public function down(): void
    {
        Schema::dropIfExists('executors');
    }
};
