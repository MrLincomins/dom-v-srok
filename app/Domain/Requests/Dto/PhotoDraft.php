<?php

declare(strict_types=1);

namespace App\Domain\Requests\Dto;

/** фотка из черновика: токен маха и ссылка на скачивание */
final readonly class PhotoDraft
{
    public function __construct(public ?string $maxToken = null, public ?string $url = null) {}
}
