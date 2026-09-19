<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Dto;

use App\Domain\Organizations\Models\Executor;

final readonly class ExecutorData
{
    public function __construct(public string $name, public ?string $phone = null, public ?string $specialty = null) {}

    /** @param array<string,mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(trim((string) ($input['name'] ?? '')), self::nullable($input['phone'] ?? null), self::nullable($input['specialty'] ?? null));
    }

    /** @param array<string,mixed> $changes */
    public static function fromPatch(Executor $executor, array $changes): self
    {
        return new self(
            array_key_exists('name', $changes) ? trim((string) $changes['name']) : $executor->name,
            array_key_exists('phone', $changes) ? self::nullable($changes['phone']) : $executor->phone,
            array_key_exists('specialty', $changes) ? self::nullable($changes['specialty']) : $executor->specialty,
        );
    }

    private static function nullable(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
