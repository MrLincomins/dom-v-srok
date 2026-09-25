<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Enums\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int|null $max_user_id
 * @property string $name
 * @property Role $role
 * @property int|null $organization_id
 * @property int|null $house_id
 * @property int|null $entrance
 * @property string|null $flat
 * @property bool $is_demo
 * @property CarbonImmutable|null $bot_started_at
 * @property string|null $username
 * @property string|null $phone
 * @property string|null $login
 * @property string|null $password
 * @property CarbonImmutable|null $anonymized_at
 * @property-read Organization|null $organization
 * @property-read House|null $house
 * @property-read Collection<int, ServiceRequest> $requests
 */
class User extends Authenticatable
{
    use HasApiTokens;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'max_user_id' => 'integer',
            'organization_id' => 'integer',
            'house_id' => 'integer',
            'role' => Role::class,
            'entrance' => 'integer',
            'bot_started_at' => 'immutable_datetime',
            'anonymized_at' => 'immutable_datetime',
            'is_demo' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<House, $this> */
    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    /** @return HasMany<ServiceRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'resident_user_id');
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function canBeMessaged(): bool
    {
        return $this->max_user_id !== null && $this->bot_started_at !== null;
    }
}
