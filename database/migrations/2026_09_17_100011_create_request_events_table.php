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
        Schema::create('request_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained()->cascadeOnDelete();
            $t->string('type', 24);
            $t->string('from_status', 16)->nullable();
            $t->string('to_status', 16)->nullable();
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();   // null = система
            $t->string('actor_role', 16)->default('system');
            $t->text('comment')->nullable();
            $t->jsonb('payload')->default('{}');
            $t->timestampTz('created_at')->useCurrent();

            $t->index(['request_id', 'id']);
        });
        Constraints::enum('request_events', 'type', ['created', 'assigned', 'status_changed', 'redirected', 'returned', 'confirmed', 'comment', 'photo_added', 'participant_joined', 'notification', 'reminder']);
        Constraints::enum('request_events', 'actor_role', ['resident', 'dispatcher', 'system']);
    }

    public function down(): void
    {
        Schema::dropIfExists('request_events');
    }
};
