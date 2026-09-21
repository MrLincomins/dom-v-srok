<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Dto;

use App\Domain\Organizations\Enums\ContractorType;

final readonly class ContractorData
{
    public function __construct(public ContractorType $type, public string $name, public ?string $phone = null) {}

    /** @param array<string,mixed> $input */
    public static function fromArray(array $input): self
    {
        $phone = is_string($input['phone'] ?? null) ? trim($input['phone']) : null;

        return new self(
            ContractorType::from((string) ($input['type'] ?? '')),
            trim((string) ($input['name'] ?? '')),
            $phone === '' ? null : $phone,
        );
    }
}
