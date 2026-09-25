<?php

declare(strict_types=1);

namespace App\Support\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property array<string,mixed> $value
 * @property CarbonImmutable $updated_at
 */
class AppSetting extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'array', 'updated_at' => 'immutable_datetime'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->find($key);

        return $row === null ? $default : $row->value['v'] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => ['v' => $value], 'updated_at' => now()]);
    }
}
