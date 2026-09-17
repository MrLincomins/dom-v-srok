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
        Schema::create('contractors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('type', 16);
            $t->text('name');
            $t->string('phone', 32)->nullable();
            $t->timestampsTz();

            $t->unique(['organization_id', 'type', 'name']);
        });
        Constraints::enum('contractors', 'type', ['lift', 'intercom', 'tko', 'other']);
    }

    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
