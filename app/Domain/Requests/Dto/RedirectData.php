<?php

declare(strict_types=1);

namespace App\Domain\Requests\Dto;

/** переадресация: либо сторона по id, либо контакт текстом */
final readonly class RedirectData
{
    public function __construct(
        public ?int $partyId = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $note = null,
    ) {}
}
