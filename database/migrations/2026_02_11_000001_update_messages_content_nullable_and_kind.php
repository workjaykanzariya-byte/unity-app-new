<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'messages'
          AND column_name = 'content'
          AND is_nullable = 'NO'
    ) THEN
        ALTER TABLE messages ALTER COLUMN content DROP NOT NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'messages'
          AND column_name = 'kind'
    ) THEN
        ALTER TABLE messages ADD COLUMN kind VARCHAR(20);
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE messages DROP COLUMN IF EXISTS kind;");
    }
};
