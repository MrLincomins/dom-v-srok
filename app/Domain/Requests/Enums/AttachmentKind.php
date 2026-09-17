<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

enum AttachmentKind: string
{
    case Resident = 'resident';
    case Closing = 'closing';
}
