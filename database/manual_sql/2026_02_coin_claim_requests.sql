-- 1) coin_claim_status enum (safe create)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'coin_claim_status_enum') THEN
        CREATE TYPE coin_claim_status_enum AS ENUM ('pending', 'approved', 'rejected');
    END IF;
END $$;

-- 2) create coin_claim_requests table
CREATE TABLE IF NOT EXISTS coin_claim_requests (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    activity_code TEXT NOT NULL,
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    status coin_claim_status_enum NOT NULL DEFAULT 'pending',
    coins_awarded INT,
    reviewed_by_admin_id UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    reviewed_at TIMESTAMPTZ,
    admin_note TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3) indexes
CREATE INDEX IF NOT EXISTS idx_coin_claim_requests_status_created_at
    ON coin_claim_requests(status, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_coin_claim_requests_user_created_at
    ON coin_claim_requests(user_id, created_at DESC);

CREATE UNIQUE INDEX IF NOT EXISTS idx_coins_ledger_claim_coin_reference
    ON coins_ledger(reference)
    WHERE reference LIKE 'claim_coin:%';

-- 4) notification enum adds (only if needed)
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'notification_type_enum') THEN
        IF NOT EXISTS (
            SELECT 1
            FROM pg_enum e
            JOIN pg_type t ON t.oid = e.enumtypid
            WHERE t.typname = 'notification_type_enum'
              AND e.enumlabel = 'coin_claim_approved'
        ) THEN
            ALTER TYPE notification_type_enum ADD VALUE 'coin_claim_approved';
        END IF;

        IF NOT EXISTS (
            SELECT 1
            FROM pg_enum e
            JOIN pg_type t ON t.oid = e.enumtypid
            WHERE t.typname = 'notification_type_enum'
              AND e.enumlabel = 'coin_claim_rejected'
        ) THEN
            ALTER TYPE notification_type_enum ADD VALUE 'coin_claim_rejected';
        END IF;
    END IF;
END $$;
