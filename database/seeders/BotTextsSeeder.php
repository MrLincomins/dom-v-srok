<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Bot\Models\BotText;
use App\Bot\Texts\TextRepository;
use Database\Seeders\Support\CsvReader;
use Illuminate\Database\Seeder;
use JsonException;
use RuntimeException;

/** тексты бота из docs/texts.csv, кнопки json в третьей колонке */
class BotTextsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CsvReader::rows('texts.csv') as $row) {
            $buttons = CsvReader::strOrNull($row['buttons']);
            try {
                $decoded = $buttons === null ? null : json_decode($buttons, true, 8, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException("texts.csv: кнопки у {$row['key']} не JSON: ".$e->getMessage());
            }

            BotText::query()->updateOrCreate(
                ['key' => trim((string) $row['key']), 'locale' => 'ru'],
                ['text' => (string) $row['text'], 'buttons' => $decoded, 'updated_at' => now()],
            );
        }

        app(TextRepository::class)->forget();
    }
}
