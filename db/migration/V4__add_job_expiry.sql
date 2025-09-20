-- Add expiry date field to jobs table for admin-posted jobs
ALTER TABLE jobs ADD COLUMN expires_at DATETIME DEFAULT NULL AFTER created_at;

-- Add index for better performance when filtering expired jobs
CREATE INDEX idx_jobs_expires_at ON jobs(expires_at);
