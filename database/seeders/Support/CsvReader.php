<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use League\Csv\Reader;

/** читает csv из docs/, разделитель «;», utf-8, первая строка заголовок */
final class CsvReader
{
    /** @return iterable<array<string,string|null>> */
    public static function rows(string $file): iterable
    {
        $reader = Reader::createFromPath(base_path('docs/'.$file));
        $reader->setDelimiter(';');
        $reader->setHeaderOffset(0);

        return $reader->getRecords();
    }

    public static function bool(?string $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'да', 'yes'], true);
    }

    public static function intOrNull(?string $value): ?int
    {
        $v = trim((string) $value);

        return $v === '' ? null : (int) $v;
    }

    public static function strOrNull(?string $value): ?string
    {
        $v = trim((string) $value);

        return $v === '' ? null : $v;
    }
}
