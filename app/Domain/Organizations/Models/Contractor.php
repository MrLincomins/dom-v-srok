<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Models;

use App\Domain\Organizations\Enums\ContractorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** подрядчик организации (лифтовая, домофонная), важнее региональных */
/**
 * @property int $id
 * @property int $organization_id
 * @property ContractorType $type
 * @property string $name
 * @property string|null $phone
 */
class Contractor extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['organization_id' => 'integer', 'type' => ContractorType::class];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
