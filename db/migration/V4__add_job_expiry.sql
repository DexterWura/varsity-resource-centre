-- Add expiry date field to jobs table for admin-posted jobs (idempotent)
ALTER TABLE jobs ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL AFTER created_at;

-- Add index for better performance when filtering expired jobs (idempotent)
CREATE INDEX IF NOT EXISTS idx_jobs_expires_at ON jobs(expires_at);
