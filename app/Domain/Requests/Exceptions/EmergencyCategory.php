<?php

declare(strict_types=1);

namespace App\Domain\Requests\Exceptions;

final class EmergencyCategory extends DomainException
{
    public function status(): int
    {
        return 422;
    }

    public function code(): string
    {
        return 'emergency_category';
    }
}
