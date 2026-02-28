<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PgEnum
{
    /**
     * Get enum labels for a Postgres enum type name (e.g. 'business_stage_enum').
     */
    public static function values(string $enumTypeName): array
    {
        return Cache::remember("pg_enum_values:{$enumTypeName}", 3600, function () use ($enumTypeName) {
            // Works when enum is in public schema.
            // If your enums are in another schema, adjust n.nspname accordingly.
            $rows = DB::select("
                SELECT e.enumlabel
                FROM pg_type t
                JOIN pg_enum e ON t.oid = e.enumtypid
                JOIN pg_namespace n ON n.oid = t.typnamespace
                WHERE t.typname = :type_name
                  AND n.nspname = 'public'
                ORDER BY e.enumsortorder
            ", ['type_name' => $enumTypeName]);

            return array_map(fn($r) => $r->enumlabel, $rows);
        });
    }
}
