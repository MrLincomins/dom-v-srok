<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Bot\Callbacks\CallbackAction;
use App\Bot\Models\BotText;
use App\Bot\Texts\TextRepository;
use Database\Seeders\Support\CsvReader;
use Illuminate\Database\Seeder;
use JsonException;
use RuntimeException;

class BotTextsSeeder extends Seeder
{
    public const MAX_KEY_LENGTH = 60;

    private const LINK_PREFIXES = ['url:', 'tel:', 'copy:'];

    private const APP_ACTIONS = ['cab', 'app'];

    public function run(): void
    {
        $this->seedRows(CsvReader::rows('texts.csv'));
    }

    /** @param iterable<int, array<string,string|null>> $rows */
    public function seedRows(iterable $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $key = trim((string) $row['key']);
            $text = (string) $row['text'];
            $buttons = $this->buttons(CsvReader::strOrNull($row['buttons'] ?? null), $key, $line);

            $this->assertKey($key, $line);
            $this->assertPlaceholders($text, $key, $line);

            BotText::query()->updateOrCreate(
                ['key' => $key, 'locale' => 'ru'],
                ['text' => $text, 'buttons' => $buttons, 'updated_at' => now()],
            );
        }

        app(TextRepository::class)->forget();
    }

    /** @return list<list<array{label:string,action:string}>>|null */
    private function buttons(?string $json, string $key, int $line): ?array
    {
        if ($json === null) {
            return null;
        }

        try {
            $rows = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw $this->invalid($key, $line, 'кнопки не JSON: '.$e->getMessage());
        }

        if (! is_array($rows) || ! array_is_list($rows)) {
            throw $this->invalid($key, $line, 'кнопки должны быть списком рядов');
        }

        foreach ($rows as $row) {
            if (! is_array($row) || ! array_is_list($row)) {
                throw $this->invalid($key, $line, 'ряд кнопок должен быть списком');
            }
            foreach ($row as $button) {
                $label = is_array($button) ? ($button['label'] ?? null) : null;
                $action = is_array($button) ? ($button['action'] ?? null) : null;
                if (! is_string($label) || trim($label) === '' || ! is_string($action) || $action === '') {
                    throw $this->invalid($key, $line, 'у кнопки нет label или action');
                }
                if (! $this->isKnownAction($action)) {
                    throw $this->invalid($key, $line, "неизвестное действие кнопки «{$action}»");
                }
                $this->assertPlaceholders($label, $key, $line);
                $this->assertPlaceholders($action, $key, $line);
            }
        }

        /** @var list<list<array{label:string,action:string}>> $rows */
        return $rows;
    }

    private function isKnownAction(string $action): bool
    {
        foreach (self::LINK_PREFIXES as $prefix) {
            if (str_starts_with($action, $prefix)) {
                return true;
            }
        }

        if (in_array($action, self::APP_ACTIONS, true)) {
            return true;
        }

        return CallbackAction::tryFrom(explode(':', $action, 2)[0]) !== null;
    }

    private function assertKey(string $key, int $line): void
    {
        if ($key === '' || mb_strlen($key) > self::MAX_KEY_LENGTH) {
            throw $this->invalid($key, $line, 'ключ пустой или длиннее '.self::MAX_KEY_LENGTH.' символов');
        }
    }

    private function assertPlaceholders(string $value, string $key, int $line): void
    {
        $rest = (string) preg_replace('/\{[a-z_]+\}/', '', $value);
        if (str_contains($rest, '{') || str_contains($rest, '}')) {
            throw $this->invalid($key, $line, 'подстановки только вида {name}: строчные латинские буквы и «_»');
        }
    }

    private function invalid(string $key, int $line, string $reason): RuntimeException
    {
        return new RuntimeException("texts.csv, строка {$line}, ключ «{$key}»: {$reason}");
    }
}
