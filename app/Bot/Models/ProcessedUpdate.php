<?php

declare(strict_types=1);

namespace App\Bot\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $update_key
 * @property CarbonImmutable $received_at
 */
class ProcessedUpdate extends Model
{
    protected $primaryKey = 'update_key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
