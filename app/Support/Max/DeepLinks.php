<?php

declare(strict_types=1);

namespace App\Support\Max;

use App\Domain\Organizations\Models\House;

final class DeepLinks
{
    public function housePayload(House $house, ?int $entrance = null): string
    {
        return 'h_'.$house->qr_token.($entrance !== null ? '_'.$entrance : '');
    }

    public function houseStartUrl(House $house, ?int $entrance = null): string
    {
        return sprintf('https://max.ru/%s?start=%s', (string) config('max.bot_username'), $this->housePayload($house, $entrance));
    }

    /** @return array{token:string,entrance:int|null}|null */
    public function parseHousePayload(?string $payload): ?array
    {
        if ($payload === null || preg_match('/^h_([a-z0-9]{6,16})(?:_(\d{1,2}))?$/i', trim($payload), $m) !== 1) {
            return null;
        }

        return ['token' => strtolower($m[1]), 'entrance' => isset($m[2]) ? (int) $m[2] : null];
    }
}
