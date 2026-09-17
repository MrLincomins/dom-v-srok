<?php

declare(strict_types=1);

namespace App\Bot\Models;

use App\Bot\Fsm\DialogState;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * состояние диалога.
 *
 * @property int $max_user_id
 * @property DialogState $state
 * @property array<string,mixed> $payload
 * @property CarbonImmutable $updated_at
 */
class BotSession extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'max_user_id';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'max_user_id' => 'integer',
            'state' => DialogState::class,
            'payload' => 'array',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
