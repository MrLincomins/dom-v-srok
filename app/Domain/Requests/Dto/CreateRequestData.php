<?php

declare(strict_types=1);

namespace App\Domain\Requests\Dto;

use App\Domain\Requests\Enums\RequestOrigin;

final readonly class CreateRequestData
{
    /** @param list<PhotoDraft> $photos */
    public function __construct(
        public int $houseId,
        public int $residentUserId,
        public int $categoryId,
        public string $description = '',
        public ?int $entrance = null,
        public ?string $flat = null,
        public array $photos = [],
        public RequestOrigin $origin = RequestOrigin::Direct,
        public ?int $sourceChatId = null,
        public bool $residentUnsure = false,
        public ?int $repeatOfId = null,
    ) {}
}
