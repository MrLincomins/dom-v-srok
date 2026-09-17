<?php

declare(strict_types=1);

namespace App\Domain\Requests\Dto;

use App\Domain\Requests\Enums\ActorRole;
use App\Domain\Users\Models\User;

/** кто делает действие: житель, диспетчер или система */
final readonly class Actor
{
    public function __construct(public ActorRole $role, public ?int $userId = null) {}

    public static function resident(User $user): self
    {
        return new self(ActorRole::Resident, $user->id);
    }

    public static function dispatcher(User $user): self
    {
        return new self(ActorRole::Dispatcher, $user->id);
    }

    public static function system(): self
    {
        return new self(ActorRole::System);
    }

    public static function fromUser(User $user): self
    {
        return $user->isStaff() ? self::dispatcher($user) : self::resident($user);
    }

    public function isDispatcher(): bool
    {
        return $this->role === ActorRole::Dispatcher;
    }
}
