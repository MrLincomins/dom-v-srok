<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Models;

use App\Domain\Organizations\Enums\OrganizationType;
use App\Domain\Requests\Models\ServiceRequest;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $region_code
 * @property OrganizationType $type
 * @property string $name
 * @property string $phone_ads
 * @property array<string,mixed> $settings
 * @property bool $is_demo
 * @property string|null $inn
 * @property string|null $phone_dispatch
 * @property string|null $email
 * @property string|null $reception_hours
 * @property string|null $reception_address
 * @property bool $direct_cold_water
 * @property bool $direct_hot_water
 * @property bool $direct_heat
 * @property bool $direct_power
 * @property bool $direct_tko
 * @property-read Region $region
 * @property-read Collection<int, House> $houses
 * @property-read Collection<int, Executor> $executors
 * @property-read Collection<int, Contractor> $contractors
 */
class Organization extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'direct_cold_water' => 'boolean',
            'direct_hot_water' => 'boolean',
            'direct_heat' => 'boolean',
            'direct_power' => 'boolean',
            'direct_tko' => 'boolean',
            'settings' => 'array',
            'is_demo' => 'boolean',
            'source_date' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_code', 'code');
    }

    /** @return HasMany<House, $this> */
    public function houses(): HasMany
    {
        return $this->hasMany(House::class);
    }

    /** @return HasMany<Executor, $this> */
    public function executors(): HasMany
    {
        return $this->hasMany(Executor::class);
    }

    /** @return HasMany<Contractor, $this> */
    public function contractors(): HasMany
    {
        return $this->hasMany(Contractor::class);
    }

    /** @return HasMany<AccessCode, $this> */
    public function accessCodes(): HasMany
    {
        return $this->hasMany(AccessCode::class);
    }

    /** @return HasMany<User, $this> */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<ServiceRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /** есть ли прямой договор по колонке direct_* */
    public function hasDirectContract(?string $column): bool
    {
        return $column !== null && (bool) $this->getAttribute($column);
    }
}
