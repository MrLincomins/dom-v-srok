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
        Schema::create('access_codes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('code_hash', 64)->unique();          // sha256(код)
            $t->string('role', 16)->default('dispatcher');
            $t->integer('max_uses')->nullable();
            $t->integer('used_count')->default(0);
            $t->timestampTz('expires_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();
        });
        Constraints::enum('access_codes', 'role', ['dispatcher', 'admin']);
    }

    public function down(): void
    {
        Schema::dropIfExists('access_codes');
    }
};
