<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

/** статусы заявки и разрешённые переходы, менять только здесь */
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

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** открытые статусы, по ним считаем просрочку */
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
    public static function closedValues(): array
    {
        return [self::Confirmed->value, self::Redirected->value];
    }
}
