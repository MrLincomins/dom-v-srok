<?php

declare(strict_types=1);

namespace App\Bot\Callbacks;

enum CallbackAction: string
{
    case Emergency = 'em';        // em:yes | em:no
    case Category = 'cat';        // cat:<id>
    case Subcategory = 'sub';     // sub:<id>
    case Address = 'addr';        // addr:same | addr:new
    case Send = 'send';
    case Unsure = 'unsure';       // «не уверен - отправить в организацию»
    case Cancel = 'cancel';
    case Menu = 'menu';
    case Report = 'report';       // «Сообщить о проблеме»
    case My = 'my';               // «Мои заявки»
    case Contacts = 'contacts';   // «Контакты организации»
    case Status = 'st';           // st:<id>
    case Resolved = 'ok';         // ok:<id>
    case NotResolved = 'no';      // no:<id>
    case Join = 'join';           // join:<id>
    case Rate = 'rate';           // rate:<id>:<1..5>
    case Again = 'again';         // again:<id>
    case Cabinet = 'cab';

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
