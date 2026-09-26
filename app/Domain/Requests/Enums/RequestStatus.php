<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

enum RequestStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Confirmed = 'confirmed';
    case Returned = 'returned';
    case Redirected = 'redirected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Принято',
            self::Assigned => 'Назначено',
            self::InProgress => 'В работе',
            self::Done => 'Выполнено',
            self::Confirmed => 'Подтверждено',
            self::Returned => 'Возвращено',
            self::Redirected => 'Переадресовано',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Assigned, self::InProgress, self::Done, self::Redirected],
            self::Assigned => [self::InProgress, self::Done, self::Redirected],
            self::InProgress => [self::Done, self::Redirected],
            self::Done => [self::Confirmed, self::Returned],
            self::Returned => [self::Assigned, self::InProgress, self::Done],
            self::Confirmed, self::Redirected => [],
        };
    }

    /** @return list<self> */
    public function allowedTransitionsFor(ActorRole $role): array
    {
        return array_values(array_filter($this->allowedTransitions(), static fn (self $to): bool => $to->canBeSetBy($role)));
    }

    public function canBeSetBy(ActorRole $role): bool
    {
        return match ($role) {
            ActorRole::Resident => in_array($this, [self::Confirmed, self::Returned], true),
            ActorRole::Dispatcher, ActorRole::System => $this !== self::Returned,
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Assigned, self::InProgress, self::Returned], true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return array_values(array_map(static fn (self $s) => $s->value, array_filter(self::cases(), static fn (self $s) => $s->isOpen())));
    }

    /** @return list<string> */
    public static function activeValues(): array
    {
        return [self::Assigned->value, self::InProgress->value, self::Returned->value, self::Done->value];
    }

    /** @return list<string> */
    public static function closedValues(): array
    {
        return [self::Confirmed->value, self::Redirected->value];
    }
}
