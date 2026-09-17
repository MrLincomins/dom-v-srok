<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_participants', function (Blueprint $t) {
            $t->foreignId('request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestampTz('joined_at')->useCurrent();

            $t->primary(['request_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_participants');
    }
};
