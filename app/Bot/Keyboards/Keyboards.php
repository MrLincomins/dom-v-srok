<?php

declare(strict_types=1);

namespace App\Bot\Keyboards;

use App\Bot\BotIdentity;

final class Keyboards
{
    /** @param list<list<array{label:string,action:string}>> $rows */
    public static function fromRows(array $rows): array
    {
        $buttons = [];
        foreach ($rows as $row) {
            $line = [];
            foreach ($row as $button) {
                $line[] = self::button($button['label'], $button['action']);
            }
            if ($line !== []) {
                $buttons[] = $line;
            }
        }

        return $buttons === [] ? [] : [self::attachment($buttons)];
    }

    /** @param list<list<array<string,mixed>>> $buttons */
    public static function attachment(array $buttons): array
    {
        return ['type' => 'inline_keyboard', 'payload' => ['buttons' => $buttons]];
    }

    /** @return array<string,mixed> */
    public static function button(string $label, string $action): array
    {
        if (str_starts_with($action, 'url:')) {
            return ['type' => 'link', 'text' => $label, 'url' => substr($action, 4)];
        }
        if (str_starts_with($action, 'tel:')) {
            return ['type' => 'link', 'text' => $label, 'url' => $action];
        }
        if (str_starts_with($action, 'copy:')) {
            return ['type' => 'clipboard', 'text' => $label, 'payload' => substr($action, 5)];
        }
        if ($action === 'cab' || $action === 'app') {
            return self::cabinet($label);
        }

        return ['type' => 'callback', 'text' => $label, 'payload' => $action];
    }

    /** @return array<string,mixed> */
    private static function cabinet(string $label): array
    {
        $identity = app(BotIdentity::class);
        if ($identity->username() === '') {
            return ['type' => 'link', 'text' => $label, 'url' => (string) config('max.miniapp_url')];
        }
        $button = ['type' => 'open_app', 'text' => $label, 'web_app' => $identity->username()];
        $id = $identity->id();
        if ($id !== null) {
            $button['contact_id'] = $id;
        }

        return $button;
    }
}
