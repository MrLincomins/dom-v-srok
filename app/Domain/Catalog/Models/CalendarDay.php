<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** праздники и перенесённые рабочие дни */
/**
 * @property CarbonImmutable $day
 * @property bool $is_working
 * @property string|null $note
 */
class CalendarDay extends Model
{
    protected $primaryKey = 'day';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['day' => 'immutable_date', 'is_working' => 'boolean'];
    }
}
