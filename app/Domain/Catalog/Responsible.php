<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Requests\Enums\ResponsibleKind;

/** кто отвечает - результат резолвера, копируется в заявку */
final readonly class Responsible
{
    public function __construct(
        public ResponsibleKind $kind,
        public string $name,
        public ?string $phone,
        public bool $isSure = true,
        public ?string $hint = null,
        public ?int $partyId = null,
    ) {}
}
