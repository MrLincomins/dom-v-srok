<?php

declare(strict_types=1);

namespace App\Domain\Requests\Models;

use App\Domain\Requests\Enums\ActorRole;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $request_id
 * @property EventType $type
 * @property string|null $from_status
 * @property string|null $to_status
 * @property int|null $actor_user_id
 * @property ActorRole $actor_role
 * @property string|null $comment
 * @property array<string,mixed> $payload
 * @property CarbonImmutable $created_at
 * @property-read User|null $actor
 */
class RequestEvent extends Model
{
    public const UPDATED_AT = null;

    public const OVERDUE_KIND = 'overdue';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'request_id' => 'integer',
            'actor_user_id' => 'integer',
            'type' => EventType::class,
            'actor_role' => ActorRole::class,
            'payload' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ServiceRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
