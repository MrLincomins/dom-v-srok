<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Dto;

final readonly class HouseData
{
    public function __construct(public string $address, public int $entrances, public bool $chatKeywordsEnabled = false) {}

    /** @param array<string,mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            address: self::normalizeAddress((string) ($input['address'] ?? '')),
            entrances: (int) ($input['entrances'] ?? 1),
            chatKeywordsEnabled: (bool) ($input['chat_keywords_enabled'] ?? false),
        );
    }

    public static function normalizeAddress(string $address): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $address));
    }
}
