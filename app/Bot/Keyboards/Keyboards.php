<?php

declare(strict_types=1);

namespace App\Bot\Keyboards;

/**
 * inline клава в махе.
 */
final class Keyboards
{
    /** @param list<list<array{label:string,action:string}>> $rows action: callback-payload, "url:https://…", "app" (кнопка кабинета) или "tel:+7…" */
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
            // чекнуть работает ли, если что использовать просто текстом ввод.
            return ['type' => 'link', 'text' => $label, 'url' => $action];
        }
        if (str_starts_with($action, 'copy:')) {
            return ['type' => 'clipboard', 'text' => $label, 'payload' => substr($action, 5)];
        }
        if ($action === 'cab' || $action === 'app') {
            // открыть миниапп.
            return ['type' => 'open_app', 'text' => $label, 'web_app' => (string) config('max.miniapp_url')];
        }

        return ['type' => 'callback', 'text' => $label, 'payload' => $action];
    }
}
