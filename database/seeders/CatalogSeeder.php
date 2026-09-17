<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use Database\Seeders\Support\CsvReader;
use Illuminate\Database\Seeder;
use RuntimeException;

/** справочник из docs/catalog.csv, можно гонять сколько угодно, строки обновляются по slug */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $bySlug = [];

        foreach (CsvReader::rows('catalog.csv') as $row) {
            $slug = trim((string) $row['slug']);
            $parentSlug = CsvReader::strOrNull($row['parent_slug']);
            $parentId = null;
            if ($parentSlug !== null) {
                $parentId = $bySlug[$parentSlug] ?? Category::query()->where('slug', $parentSlug)->value('id');
                if ($parentId === null) {
                    throw new RuntimeException("catalog.csv: родитель {$parentSlug} для {$slug} должен идти выше по файлу");
                }
            }

            $synonyms = array_values(array_filter(array_map('trim', explode('|', (string) $row['synonyms']))));

            $category = Category::query()->updateOrCreate(['slug' => $slug], [
                'parent_id' => $parentId,
                'name' => trim((string) $row['name']),
                'is_emergency' => CsvReader::bool($row['is_emergency']),
                'responsible_type' => CsvReader::strOrNull($row['responsible_type']),
                'deadline_fix_value' => CsvReader::intOrNull($row['fix_value']),
                'deadline_fix_unit' => CsvReader::strOrNull($row['fix_unit']),
                'deadline_reply_value' => CsvReader::intOrNull($row['reply_value']),
                'deadline_reply_unit' => CsvReader::strOrNull($row['reply_unit']),
                'basis' => CsvReader::strOrNull($row['basis']),
                'advice_text' => CsvReader::strOrNull($row['advice']),
                'synonyms' => $synonyms,
                'verify' => CsvReader::bool($row['verify']),
                'sort_order' => CsvReader::intOrNull($row['sort']) ?? 0,
                'is_active' => true,
            ]);

            $bySlug[$slug] = $category->id;
        }

        $this->assertIntegrity();
    }

    /** у каждой листовой неаварийной категории есть ответственный и хотя бы один срок */
    private function assertIntegrity(): void
    {
        $broken = Category::query()->whereNotNull('parent_id')->where('is_emergency', false)->get()
            ->filter(fn (Category $c) => $c->responsible_type === null || (! $c->hasFixDeadline() && ! $c->hasReplyDeadline()))
            ->pluck('slug');

        if ($broken->isNotEmpty()) {
            throw new RuntimeException('catalog.csv: без ответственного или срока: '.$broken->implode(', '));
        }
    }
}
