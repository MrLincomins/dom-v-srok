<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\DeadlineUnit;
use App\Domain\Catalog\Enums\ResponsibleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $slug
 * @property string $name
 * @property bool $is_emergency
 * @property ResponsibleType|null $responsible_type
 * @property int|null $deadline_fix_value
 * @property DeadlineUnit|null $deadline_fix_unit
 * @property int|null $deadline_reply_value
 * @property DeadlineUnit|null $deadline_reply_unit
 * @property string|null $basis
 * @property string|null $advice_text
 * @property list<string> $synonyms
 * @property bool $verify
 * @property int $sort_order
 * @property bool $is_active
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 */
class Category extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_emergency' => 'boolean',
            'responsible_type' => ResponsibleType::class,
            'deadline_fix_unit' => DeadlineUnit::class,
            'deadline_reply_unit' => DeadlineUnit::class,
            'synonyms' => 'array',
            'verify' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    public function isLeaf(): bool
    {
        return $this->parent_id !== null;
    }

    public function hasFixDeadline(): bool
    {
        return $this->deadline_fix_value !== null && $this->deadline_fix_unit !== null;
    }

    public function hasReplyDeadline(): bool
    {
        return $this->deadline_reply_value !== null && $this->deadline_reply_unit !== null;
    }
}
