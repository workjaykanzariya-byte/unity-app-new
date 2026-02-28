<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PgEnum
{
    private static array $memo = [];

    public static function values(string $enumTypeName): array
    {
        if (isset(self::$memo[$enumTypeName])) {
            return self::$memo[$enumTypeName];
        }

        $rows = DB::select("
            SELECT e.enumlabel
            FROM pg_type t
            JOIN pg_enum e ON t.oid = e.enumtypid
            JOIN pg_namespace n ON n.oid = t.typnamespace
            WHERE t.typname = :type_name
            ORDER BY e.enumsortorder
        ", ['type_name' => $enumTypeName]);

        self::$memo[$enumTypeName] = array_map(fn ($r) => $r->enumlabel, $rows);

        return self::$memo[$enumTypeName];
    }
}
