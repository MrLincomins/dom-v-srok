<?php

declare(strict_types=1);

namespace App\Bot\Client;

use RuntimeException;

class MaxApiException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 0, private readonly ?string $method = null)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function method(): ?string
    {
        return $this->method;
    }

    public function isPermanent(): bool
    {
        return $this->status >= 400 && $this->status < 500 && $this->status !== 429;
    }
}
