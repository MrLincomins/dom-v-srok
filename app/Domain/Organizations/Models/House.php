<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Models;

use App\Domain\Requests\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $region_code
 * @property string $address
 * @property int $entrances
 * @property string $qr_token
 * @property int|null $max_chat_id
 * @property bool $chat_keywords_enabled
 * @property string|null $fias_guid
 * @property string|null $chat_pinned_message_id
 * @property bool $is_demo
 * @property-read Organization|null $organization
 * @property-read Region $region
 */
class House extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'entrances' => 'integer',
            'max_chat_id' => 'integer',
            'chat_keywords_enabled' => 'boolean',
            'is_demo' => 'boolean',
            'source_date' => 'immutable_date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (House $house): void {
            $house->qr_token ??= self::newQrToken();
        });
    }

    public static function newQrToken(): string
    {
        return Str::lower(Str::random(12));
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_code', 'code');
    }

    /** @return HasMany<ServiceRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }
}
