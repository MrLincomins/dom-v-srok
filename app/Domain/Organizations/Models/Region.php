<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $name
 * @property string $timezone
 * @property string|null $gzhi_name
 * @property string|null $gzhi_url
 * @property string|null $pos_url
 * @property string|null $control_url
 * @property string|null $escalation_text
 */
class Region extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    /** @return HasMany<Organization, $this> */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'region_code', 'code');
    }
}
