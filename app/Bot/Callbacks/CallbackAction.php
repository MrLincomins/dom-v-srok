<?php

declare(strict_types=1);

namespace App\Bot\Callbacks;

enum CallbackAction: string
{
    case Emergency = 'em';
    case Category = 'cat';
    case Subcategory = 'sub';
    case Address = 'addr';
    case House = 'house';
    case Send = 'send';
    case Unsure = 'unsure';
    case Cancel = 'cancel';
    case Menu = 'menu';
    case Report = 'report';
    case My = 'my';
    case Contacts = 'contacts';
    case Status = 'st';
    case Resolved = 'ok';
    case NotResolved = 'no';
    case Join = 'join';
    case Rate = 'rate';
    case Again = 'again';
    case Cabinet = 'cab';
    case Who = 'who';

    public function payload(string|int ...$args): string
    {
        return implode(':', [$this->value, ...array_map('strval', $args)]);
    }

    public static function parse(string $payload): ?ParsedCallback
    {
        $parts = explode(':', trim($payload));
        $action = self::tryFrom((string) array_shift($parts));

        return $action === null ? null : new ParsedCallback($action, $parts);
    }
}
