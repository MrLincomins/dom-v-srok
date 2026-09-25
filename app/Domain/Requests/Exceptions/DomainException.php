<?php

declare(strict_types=1);

namespace App\Domain\Requests\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    /** @param array<string,mixed> $details */
    public function __construct(string $message, private readonly array $details = [])
    {
        parent::__construct($message);
    }

    abstract public function status(): int;

    abstract public function code(): string;

    /** @return array<string,mixed> */
    public function details(): array
    {
        return $this->details;
    }
}
