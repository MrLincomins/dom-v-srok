<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Models;

use App\Domain\Users\Enums\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $code_hash
 * @property Role $role
 * @property int|null $max_uses
 * @property int $used_count
 * @property CarbonImmutable|null $expires_at
 * @property bool $is_active
 * @property-read Organization $organization
 */
class AccessCode extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'role' => Role::class,
            'expires_at' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    public static function hash(string $code): string
    {
        return hash('sha256', mb_strtoupper(trim($code)));
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }
}
