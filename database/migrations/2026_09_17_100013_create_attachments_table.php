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
        Schema::create('attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('event_id')->nullable()->constrained('request_events')->nullOnDelete();
            $t->string('kind', 16);                                    // resident | closing
            $t->string('disk', 32)->default('private');
            $t->text('path');
            $t->string('mime', 64);
            $t->integer('size_bytes');
            $t->text('max_token')->nullable();                         // токен вложения маха, чтобы не грузить фотку заново
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('created_at')->useCurrent();

            $t->index('request_id');
        });
        Constraints::enum('attachments', 'kind', ['resident', 'closing']);
        Constraints::check('attachments', 'size_positive', 'size_bytes > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
