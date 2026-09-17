<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

enum RequestOrigin: string
{
    case Qr = 'qr';
    case Chat = 'chat';
    case Direct = 'direct';
    case Api = 'api';
}
