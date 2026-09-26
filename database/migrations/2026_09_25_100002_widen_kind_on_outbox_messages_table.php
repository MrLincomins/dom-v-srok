<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbox_messages', function (Blueprint $t) {
            $t->string('kind', 64)->change();
        });
    }

    public function down(): void
    {
        Schema::table('outbox_messages', function (Blueprint $t) {
            $t->string('kind', 32)->change();
        });
    }
};
