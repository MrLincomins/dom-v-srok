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
        Schema::create('requests', function (Blueprint $t) {
            $t->id();                                                   // это и есть номер заявки
            $t->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete(); // null = без организации
            $t->foreignId('house_id')->constrained()->restrictOnDelete();
            $t->foreignId('resident_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->text('description')->default('');
            $t->smallInteger('entrance')->nullable();
            $t->string('flat', 10)->nullable();
            $t->string('responsible_kind', 16);                        // organization | party | unknown
            $t->foreignId('responsible_party_id')->nullable()->constrained('responsible_parties')->nullOnDelete();
            $t->text('responsible_name');                              // снимок
            $t->string('responsible_phone', 32)->nullable();           // снимок
            $t->boolean('is_sure')->default(true);                     // false = «не уверен, пусть организация уточнит»
            $t->timestampTz('deadline_fix_at')->nullable();            // срок устранения (основной)
            $t->timestampTz('deadline_reply_at')->nullable();          // срок ответа (отдельно)
            $t->text('basis')->default('');                            // снимок основания
            $t->string('status', 16)->default('new');
            $t->foreignId('executor_id')->nullable()->constrained()->nullOnDelete();
            $t->timestampTz('first_reaction_at')->nullable();          // первое действие диспетчера
            $t->timestampTz('assigned_at')->nullable();
            $t->timestampTz('in_progress_at')->nullable();
            $t->timestampTz('done_at')->nullable();
            $t->timestampTz('confirmed_at')->nullable();
            $t->timestampTz('closed_at')->nullable();                  // confirmed или redirected
            $t->string('confirmed_by', 16)->nullable();
            $t->smallInteger('returned_count')->default(0);
            $t->foreignId('redirected_party_id')->nullable()->constrained('responsible_parties')->nullOnDelete();
            $t->text('redirect_note')->nullable();
            $t->integer('participants_count')->default(0);
            $t->string('origin', 8)->default('direct');
            $t->bigInteger('source_chat_id')->nullable();
            $t->smallInteger('rating')->nullable();
            $t->text('rating_comment')->nullable();
            $t->foreignId('repeat_of_id')->nullable()->constrained('requests')->nullOnDelete(); // «это снова случилось»
            $t->timestampsTz();

            $t->index(['organization_id', 'status', 'deadline_fix_at'], 'requests_queue_idx');
            $t->index(['house_id', 'status'], 'requests_house_idx');
            $t->index(['resident_user_id', 'created_at'], 'requests_resident_idx');
            $t->index('category_id');
        });
        Constraints::enum('requests', 'responsible_kind', ['organization', 'party', 'unknown']);
        Constraints::enum('requests', 'status', ['new', 'assigned', 'in_progress', 'done', 'confirmed', 'returned', 'redirected']);
        Constraints::enum('requests', 'confirmed_by', ['resident', 'auto', 'dispatcher']);
        Constraints::enum('requests', 'origin', ['qr', 'chat', 'direct', 'api']);
        Constraints::check('requests', 'rating_range', 'rating IS NULL OR rating BETWEEN 1 AND 5');
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
