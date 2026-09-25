<?php

declare(strict_types=1);

namespace App\Domain\Requests\Models;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\ResponsibleParty;
use App\Domain\Organizations\Models\Executor;
use App\Domain\Organizations\Models\House;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\EventType;
use App\Domain\Requests\Enums\RequestOrigin;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Enums\ResponsibleKind;
use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int $house_id
 * @property int $resident_user_id
 * @property int $category_id
 * @property string $description
 * @property int|null $entrance
 * @property string|null $flat
 * @property ResponsibleKind $responsible_kind
 * @property int|null $responsible_party_id
 * @property string $responsible_name
 * @property string|null $responsible_phone
 * @property bool $is_sure
 * @property CarbonImmutable|null $deadline_fix_at
 * @property CarbonImmutable|null $deadline_reply_at
 * @property string $basis
 * @property RequestStatus $status
 * @property int|null $executor_id
 * @property CarbonImmutable|null $first_reaction_at
 * @property CarbonImmutable|null $done_at
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $closed_at
 * @property ConfirmedBy|null $confirmed_by
 * @property int $returned_count
 * @property int $participants_count
 * @property RequestOrigin $origin
 * @property int|null $rating
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $assigned_at
 * @property CarbonImmutable|null $in_progress_at
 * @property int|null $redirected_party_id
 * @property string|null $redirect_name
 * @property string|null $redirect_phone
 * @property string|null $redirect_note
 * @property string|null $rating_comment
 * @property int|null $repeat_of_id
 * @property int|null $source_chat_id
 * @property CarbonImmutable $updated_at
 * @property-read Organization|null $organization
 * @property-read House $house
 * @property-read User $resident
 * @property-read Category $category
 * @property-read ResponsibleParty|null $responsibleParty
 * @property-read ResponsibleParty|null $redirectedParty
 * @property-read Executor|null $executor
 * @property-read Collection<int, RequestEvent> $events
 * @property-read Collection<int, Attachment> $attachments
 * @property-read Collection<int, User> $participants
 */
class ServiceRequest extends Model
{
    protected $table = 'requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'house_id' => 'integer',
            'resident_user_id' => 'integer',
            'category_id' => 'integer',
            'entrance' => 'integer',
            'responsible_party_id' => 'integer',
            'executor_id' => 'integer',
            'redirected_party_id' => 'integer',
            'repeat_of_id' => 'integer',
            'responsible_kind' => ResponsibleKind::class,
            'is_sure' => 'boolean',
            'deadline_fix_at' => 'immutable_datetime',
            'deadline_reply_at' => 'immutable_datetime',
            'status' => RequestStatus::class,
            'first_reaction_at' => 'immutable_datetime',
            'assigned_at' => 'immutable_datetime',
            'in_progress_at' => 'immutable_datetime',
            'done_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'confirmed_by' => ConfirmedBy::class,
            'returned_count' => 'integer',
            'participants_count' => 'integer',
            'origin' => RequestOrigin::class,
            'source_chat_id' => 'integer',
            'rating' => 'integer',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<House, $this> */
    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    /** @return BelongsTo<User, $this> */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_user_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<ResponsibleParty, $this> */
    public function responsibleParty(): BelongsTo
    {
        return $this->belongsTo(ResponsibleParty::class, 'responsible_party_id');
    }

    /** @return BelongsTo<ResponsibleParty, $this> */
    public function redirectedParty(): BelongsTo
    {
        return $this->belongsTo(ResponsibleParty::class, 'redirected_party_id');
    }

    /** @return BelongsTo<Executor, $this> */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(Executor::class);
    }

    /** @return HasMany<RequestEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(RequestEvent::class, 'request_id')->orderBy('id');
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'request_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'request_participants', 'request_id', 'user_id')->withPivot('joined_at');
    }

    /** @return BelongsTo<self, $this> */
    public function repeatOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'repeat_of_id');
    }

    public function isOverdue(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        return $this->status->isOpen() && $this->deadline_fix_at !== null && $this->deadline_fix_at->lessThan($now);
    }

    public function closedLate(): bool
    {
        return $this->done_at !== null && $this->deadline_fix_at !== null && $this->done_at->greaterThan($this->deadline_fix_at);
    }

    public function hasOverdueMark(): bool
    {
        return $this->events()->where('type', EventType::Reminder->value)->where('payload->kind', RequestEvent::OVERDUE_KIND)->exists();
    }

    public function redirectedToName(): ?string
    {
        if ($this->status !== RequestStatus::Redirected) {
            return null;
        }
        if ($this->redirect_name !== null && $this->redirect_name !== '') {
            return $this->redirect_name;
        }
        if ($this->redirectedParty !== null) {
            return $this->redirectedParty->name;
        }
        $to = $this->redirectEvent()?->payload['to'] ?? null;

        return is_string($to) && $to !== '' ? $to : null;
    }

    public function redirectedToPhone(): ?string
    {
        if ($this->status !== RequestStatus::Redirected) {
            return null;
        }
        if ($this->redirect_phone !== null && $this->redirect_phone !== '') {
            return $this->redirect_phone;
        }
        if ($this->redirect_name !== null) {
            return null;
        }
        if ($this->redirectedParty?->phone !== null) {
            return $this->redirectedParty->phone;
        }
        $phone = $this->redirectEvent()?->payload['phone'] ?? null;

        return is_string($phone) && $phone !== '' ? $phone : null;
    }

    private function redirectEvent(): ?RequestEvent
    {
        return $this->relationLoaded('events')
            ? $this->events->last(fn (RequestEvent $event) => $event->to_status === RequestStatus::Redirected->value)
            : $this->events()->where('to_status', RequestStatus::Redirected->value)->reorder('id', 'desc')->first();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', RequestStatus::openValues());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', RequestStatus::activeValues());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('status', RequestStatus::closedValues());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('deadline_fix_at')->where('deadline_fix_at', '<', now());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }
}
