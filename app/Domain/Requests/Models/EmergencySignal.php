<?php

declare(strict_types=1);

namespace App\Domain\Requests\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

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

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'house_id' => 'integer',
            'organization_id' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
