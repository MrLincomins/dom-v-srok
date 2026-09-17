<?php

declare(strict_types=1);

namespace App\Support\Migration;

use Illuminate\Support\Facades\DB;

/** перечисления как varchar + check, а не enum постгреса, так проще менять */
final class Constraints
{
    /** @param list<string> $values */
    public static function enum(string $table, string $column, array $values): void
    {
        $list = implode(', ', array_map(static fn (string $v) => "'".str_replace("'", "''", $v)."'", $values));
        DB::statement(sprintf('ALTER TABLE %s ADD CONSTRAINT %s_%s_check CHECK (%s IS NULL OR %s IN (%s))', $table, $table, $column, $column, $column, $list));
    }

    public static function check(string $table, string $name, string $expression): void
    {
        DB::statement(sprintf('ALTER TABLE %s ADD CONSTRAINT %s_%s_check CHECK (%s)', $table, $table, $name, $expression));
    }
}
