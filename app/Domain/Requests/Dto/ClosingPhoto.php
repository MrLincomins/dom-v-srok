<?php

declare(strict_types=1);

namespace App\Domain\Requests\Dto;

use SplFileInfo;

final readonly class ClosingPhoto
{
    public function __construct(public SplFileInfo $file, public string $mime) {}
}
