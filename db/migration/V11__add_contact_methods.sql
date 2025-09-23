-- Add contact methods for jobs, houses, and businesses
-- This migration adds contact fields and application options

-- Add contact methods to jobs table
ALTER TABLE jobs 
ADD COLUMN IF NOT EXISTS job_url VARCHAR(500) NULL COMMENT 'Direct URL to apply for the job',
ADD COLUMN IF NOT EXISTS whatsapp_contact VARCHAR(20) NULL COMMENT 'WhatsApp number for job contact',
ADD COLUMN IF NOT EXISTS email_contact VARCHAR(255) NULL COMMENT 'Email address for job applications',
ADD COLUMN IF NOT EXISTS contact_method ENUM('url', 'whatsapp', 'email') DEFAULT 'url' COMMENT 'Preferred contact method for applications';

-- Add contact methods to houses table
ALTER TABLE houses 
ADD COLUMN IF NOT EXISTS owner_phone VARCHAR(20) NULL COMMENT 'Owner phone number',
ADD COLUMN IF NOT EXISTS owner_whatsapp VARCHAR(20) NULL COMMENT 'Owner WhatsApp number',
ADD COLUMN IF NOT EXISTS owner_website VARCHAR(500) NULL COMMENT 'Owner website URL',
ADD COLUMN IF NOT EXISTS contact_method ENUM('phone', 'whatsapp', 'website') DEFAULT 'phone' COMMENT 'Preferred contact method for inquiries';

-- Add contact methods to businesses table
ALTER TABLE businesses 
ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(20) NULL COMMENT 'Business phone number',
ADD COLUMN IF NOT EXISTS contact_whatsapp VARCHAR(20) NULL COMMENT 'Business WhatsApp number',
ADD COLUMN IF NOT EXISTS contact_email VARCHAR(255) NULL COMMENT 'Business email address',
ADD COLUMN IF NOT EXISTS website_url VARCHAR(500) NULL COMMENT 'Business website URL',
ADD COLUMN IF NOT EXISTS contact_method ENUM('phone', 'whatsapp', 'email', 'website') DEFAULT 'phone' COMMENT 'Preferred contact method for inquiries';

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_jobs_contact_method ON jobs(contact_method);
CREATE INDEX IF NOT EXISTS idx_houses_contact_method ON houses(contact_method);
CREATE INDEX IF NOT EXISTS idx_businesses_contact_method ON businesses(contact_method);
