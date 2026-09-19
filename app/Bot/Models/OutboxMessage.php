<?php

declare(strict_types=1);

namespace App\Bot\Models;

use App\Bot\Outbox\OutboxStatus;
use App\Bot\Outbox\OutboxTarget;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * возврат в мах - лимиты, ретраи и тд.
 *
 * @property int $id
 * @property OutboxTarget $target_type
 * @property int $target_id
 * @property string $kind
 * @property array<string,mixed> $body
 * @property string|null $dedupe_key
 * @property OutboxStatus $status
 * @property int $attempts
 * @property int|null $request_id
 * @property CarbonImmutable $available_at
 * @property CarbonImmutable|null $sent_at
 * @property string|null $max_message_id
 * @property string|null $edit_message_id
 * @property string|null $last_error
 */
class OutboxMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'target_type' => OutboxTarget::class,
            'target_id' => 'integer',
            'request_id' => 'integer',
            'body' => 'array',
            'status' => OutboxStatus::class,
            'attempts' => 'integer',
            'available_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }

    public function targetKey(): string
    {
        return $this->target_type->value.':'.$this->target_id;
    }
}
