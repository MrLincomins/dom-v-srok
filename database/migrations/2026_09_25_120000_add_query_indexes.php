<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDEXES = [
        'users_organization_idx' => 'users (organization_id) WHERE organization_id IS NOT NULL',
        'requests_open_deadline_idx' => "requests (deadline_fix_at) WHERE status IN ('new', 'assigned', 'in_progress', 'returned')",
        'requests_done_at_idx' => "requests (done_at) WHERE status = 'done'",
        'requests_organization_created_idx' => 'requests (organization_id, created_at)',
        'outbox_pending_stale_idx' => "outbox_messages (available_at, updated_at) WHERE status = 'pending'",
        'outbox_request_idx' => 'outbox_messages (request_id)',
        'request_participants_user_idx' => 'request_participants (user_id)',
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $name => $definition) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$name} ON {$definition}");
        }
        DB::statement('DROP INDEX IF EXISTS outbox_pending_idx');
    }

    public function down(): void
    {
        DB::statement("CREATE INDEX IF NOT EXISTS outbox_pending_idx ON outbox_messages (available_at) WHERE status = 'pending'");
        foreach (array_keys(self::INDEXES) as $name) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }
    }
};
