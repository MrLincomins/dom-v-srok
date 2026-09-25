<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $t) {
            $t->text('redirect_name')->nullable()->after('redirected_party_id');
            $t->string('redirect_phone', 32)->nullable()->after('redirect_name');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $t) {
            $t->dropColumn(['redirect_name', 'redirect_phone']);
        });
    }
};
