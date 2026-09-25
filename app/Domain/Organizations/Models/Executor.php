<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $specialty
 * @property bool $is_active
 * @property-read Organization $organization
 */
class Executor extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['organization_id' => 'integer', 'user_id' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
