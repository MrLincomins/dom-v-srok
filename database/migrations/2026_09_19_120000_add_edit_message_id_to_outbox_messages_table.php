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
            $t->string('edit_message_id', 64)->nullable()->after('max_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('outbox_messages', function (Blueprint $t) {
            $t->dropColumn('edit_message_id');
        });
    }
};
