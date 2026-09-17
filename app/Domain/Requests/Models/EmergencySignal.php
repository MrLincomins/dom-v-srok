<?php

declare(strict_types=1);

namespace App\Domain\Requests\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** аварийный сигнал - фиксируем время без заявки */
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $house_id
 * @property int|null $organization_id
 * @property string $phone_shown
 * @property CarbonImmutable $created_at
 */
class EmergencySignal extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
