<?php

declare(strict_types=1);

namespace App\Domain\Requests\Dto;

final readonly class RedirectData
{
    public function __construct(
        public ?int $partyId = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $note = null,
        public ?int $contractorId = null,
    ) {}
}
