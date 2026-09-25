<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use League\Csv\Reader;
use RuntimeException;

final class CsvReader
{
    /** @return iterable<array<string,string|null>> */
    public static function rows(string $file): iterable
    {
        $reader = Reader::createFromPath(base_path('docs/'.$file));
        $reader->setDelimiter(';');
        self::assertColumns($reader, $file);
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

    private static function assertColumns(Reader $reader, string $file): void
    {
        $expected = null;
        foreach ($reader->getRecords() as $index => $record) {
            $expected ??= count($record);
            if (count($record) !== $expected) {
                throw new RuntimeException(sprintf('%s: строка %d содержит %d полей вместо %d, поле с «;» нужно взять в кавычки', $file, $index + 1, count($record), $expected));
            }
        }
    }
}
