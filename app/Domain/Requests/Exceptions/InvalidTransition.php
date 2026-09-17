<?php

declare(strict_types=1);

namespace App\Domain\Requests\Exceptions;

use App\Domain\Requests\Enums\RequestStatus;

final class InvalidTransition extends DomainException
{
    public static function between(RequestStatus $from, RequestStatus $to): self
    {
        return new self(
            sprintf('Заявку в статусе «%s» нельзя перевести в «%s»', $from->label(), $to->label()),
            ['from' => $from->value, 'to' => $to->value],
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function code(): string
    {
        return 'invalid_transition';
    }
}
