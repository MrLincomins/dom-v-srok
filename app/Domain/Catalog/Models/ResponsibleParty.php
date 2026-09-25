<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\ResponsibleType;
use App\Domain\Organizations\Models\Region;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $region_code
 * @property ResponsibleType $type
 * @property string $name
 * @property string|null $phone
 * @property string|null $url
 * @property string|null $note
 * @property CarbonImmutable|null $source_date
 */
class ResponsibleParty extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => ResponsibleType::class,
            'source_date' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_code', 'code');
    }
}
