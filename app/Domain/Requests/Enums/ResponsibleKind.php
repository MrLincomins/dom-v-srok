<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

enum ResponsibleKind: string
{
    case Organization = 'organization';
    case Party = 'party';
    case Unknown = 'unknown';
}
