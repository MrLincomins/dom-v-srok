<?php

declare(strict_types=1);

namespace App\Domain\Requests\Models;

use App\Domain\Requests\Enums\AttachmentKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $request_id
 * @property int|null $event_id
 * @property AttachmentKind $kind
 * @property string $disk
 * @property string $path
 * @property string $mime
 * @property int $size_bytes
 * @property string|null $max_token
 * @property int|null $uploaded_by
 * @property CarbonImmutable $created_at
 */
class Attachment extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'request_id' => 'integer',
            'event_id' => 'integer',
            'uploaded_by' => 'integer',
            'kind' => AttachmentKind::class,
            'size_bytes' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ServiceRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }
}
