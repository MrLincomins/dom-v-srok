<?php

declare(strict_types=1);

namespace App\Bot\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string $locale
 * @property string $text
 * @property list<list<array{label:string,action:string}>>|null $buttons
 * @property CarbonImmutable $updated_at
 */
class BotText extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['buttons' => 'array', 'updated_at' => 'immutable_datetime'];
    }
}
