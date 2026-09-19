<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Dto;

use App\Domain\Organizations\Models\House;

final readonly class HouseSettingsData
{
    public function __construct(public int $entrances, public bool $chatKeywordsEnabled) {}

    /** @param array<string,mixed> $changes */
    public static function fromPatch(House $house, array $changes): self
    {
        return new self(
            entrances: (int) ($changes['entrances'] ?? $house->entrances),
            chatKeywordsEnabled: (bool) ($changes['chat_keywords_enabled'] ?? $house->chat_keywords_enabled),
        );
    }
}
