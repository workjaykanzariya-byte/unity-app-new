-- Requirements timeline + interests schema changes (PostgreSQL)
ALTER TABLE requirements
    ADD COLUMN IF NOT EXISTS timeline_post_id UUID NULL,
    ADD COLUMN IF NOT EXISTS closed_at TIMESTAMPTZ NULL,
    ADD COLUMN IF NOT EXISTS completed_at TIMESTAMPTZ NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'requirements_timeline_post_id_foreign'
    ) THEN
        ALTER TABLE requirements
            ADD CONSTRAINT requirements_timeline_post_id_foreign
            FOREIGN KEY (timeline_post_id) REFERENCES posts(id) ON DELETE SET NULL;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_requirements_status_created_at
    ON requirements(status, created_at DESC);

CREATE TABLE IF NOT EXISTS requirement_interests (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    requirement_id UUID NOT NULL REFERENCES requirements(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    source VARCHAR(50) NULL,
    comment TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_requirement_interests_requirement_user UNIQUE (requirement_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_requirement_interests_requirement_id
    ON requirement_interests(requirement_id);

-- Optional notification enum extension if your environment enforces specific enum values
-- and does not use `activity_update` as wrapper type:
-- ALTER TYPE notification_type_enum ADD VALUE IF NOT EXISTS 'requirement_created';
-- ALTER TYPE notification_type_enum ADD VALUE IF NOT EXISTS 'requirement_interest';
