<?php

declare(strict_types=1);

namespace App\Bot\Texts;

use App\Bot\Models\BotText;
use Illuminate\Support\Facades\Cache;

/**
 * тексты бота из bot_texts (пока в docs/texts.csv).
 */
final class TextRepository
{
    private const CACHE_KEY = 'bot_texts:ru';

    /** @var array<string,array{text:string,buttons:list<list<array{label:string,action:string}>>|null}>|null */
    private ?array $texts = null;

    /** @param array<string,string|int|null> $vars */
    public function text(string $key, array $vars = []): string
    {
        $entry = $this->all()[$key] ?? null;
        $template = $entry['text'] ?? '['.$key.']';

        return $this->substitute(str_replace('\n', "\n", $template), $vars);
    }

    /**
     * @param  array<string,string|int|null>  $vars
     * @return list<list<array{label:string,action:string}>>
     */
    public function buttons(string $key, array $vars = []): array
    {
        $buttons = $this->all()[$key]['buttons'] ?? null;
        if ($buttons === null) {
            return [];
        }

        return array_map(
            fn (array $row) => array_map(fn (array $b) => [
                'label' => $this->substitute($b['label'], $vars),
                'action' => $this->substitute($b['action'], $vars),
            ], $row),
            $buttons,
        );
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    public function forget(): void
    {
        $this->texts = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string,array{text:string,buttons:list<list<array{label:string,action:string}>>|null}> */
    private function all(): array
    {
        return $this->texts ??= Cache::remember(self::CACHE_KEY, 300, fn () => BotText::query()
            ->where('locale', 'ru')
            ->get()
            ->mapWithKeys(fn (BotText $t) => [$t->key => ['text' => $t->text, 'buttons' => $t->buttons]])
            ->all());
    }

    /** @param array<string,string|int|null> $vars */
    private function substitute(string $template, array $vars): string
    {
        foreach ($vars as $name => $value) {
            $template = str_replace('{'.$name.'}', (string) ($value ?? ''), $template);
        }

        return preg_replace('/\{[a-z_]+\}/', '', $template) ?? $template;
    }
}
