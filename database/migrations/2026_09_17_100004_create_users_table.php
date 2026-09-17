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
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->bigInteger('max_user_id')->nullable()->unique();   // null только у тестовых учёток
            $t->text('name');
            $t->string('username', 64)->nullable();
            $t->string('phone', 32)->nullable();
            $t->string('role', 16)->default('resident');
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('house_id')->nullable()->constrained()->nullOnDelete();
            $t->smallInteger('entrance')->nullable();
            $t->string('flat', 10)->nullable();
            $t->timestampTz('bot_started_at')->nullable();          // null = боту нельзя писать первым
            $t->string('login', 64)->nullable()->unique();          // только тестовые учётки
            $t->string('password')->nullable();
            $t->boolean('is_demo')->default(false);
            $t->timestampTz('anonymized_at')->nullable();
            $t->rememberToken();
            $t->timestampsTz();
        });
        Constraints::enum('users', 'role', ['resident', 'dispatcher', 'admin']);
        Constraints::check('users', 'identity', 'max_user_id IS NOT NULL OR login IS NOT NULL');
        Constraints::check('users', 'staff_org', "role = 'resident' OR organization_id IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
