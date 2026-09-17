<?php

declare(strict_types=1);

use App\Support\Migration\Constraints;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $t) {
            $t->id();
            $t->string('target_type', 8);                              // user | chat
            $t->bigInteger('target_id');                               // max_user_id или max_chat_id
            $t->string('kind', 32);                                    // request.created, request.status, reminder, digest
            $t->jsonb('body');                                         // {text, keyboard, attachments, format}
            $t->foreignId('request_id')->nullable()->constrained()->nullOnDelete();
            $t->string('dedupe_key', 120)->nullable()->unique();
            $t->string('status', 8)->default('pending');
            $t->smallInteger('attempts')->default(0);
            $t->timestampTz('available_at')->useCurrent();
            $t->timestampTz('sent_at')->nullable();
            $t->string('max_message_id', 64)->nullable();
            $t->text('last_error')->nullable();
            $t->timestampsTz();
        });
        Constraints::enum('outbox_messages', 'target_type', ['user', 'chat']);
        Constraints::enum('outbox_messages', 'status', ['pending', 'sent', 'failed', 'skipped']);
        DB::statement("CREATE INDEX outbox_pending_idx ON outbox_messages (available_at) WHERE status = 'pending'");
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
