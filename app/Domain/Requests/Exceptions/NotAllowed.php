<?php

declare(strict_types=1);

namespace App\Domain\Requests\Exceptions;

final class NotAllowed extends DomainException
{
    public function status(): int
    {
        return 403;
    }

    public function code(): string
    {
        return 'forbidden';
    }
}
