<?php

declare(strict_types=1);

namespace App\Bot\Callbacks;

final readonly class ParsedCallback
{
    /** @param list<string> $args */
    public function __construct(public CallbackAction $action, public array $args = []) {}

    public function arg(int $index, ?string $default = null): ?string
    {
        return $this->args[$index] ?? $default;
    }

    public function intArg(int $index): ?int
    {
        $value = $this->arg($index);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }
}
