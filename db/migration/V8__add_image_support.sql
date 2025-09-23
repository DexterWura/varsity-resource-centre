-- Add image support for articles, houses, and businesses
-- This migration adds image-related columns and creates an images table

-- Add image columns to articles table (if not already exists)
ALTER TABLE articles 
ADD COLUMN IF NOT EXISTS featured_image VARCHAR(500) NULL COMMENT 'Featured image URL for the article',
ADD COLUMN IF NOT EXISTS image_gallery JSON NULL COMMENT 'Array of image URLs for article gallery';

-- Add image columns to houses table (if not already exists)
ALTER TABLE houses 
ADD COLUMN IF NOT EXISTS featured_image VARCHAR(500) NULL COMMENT 'Featured image URL for the house',
ADD COLUMN IF NOT EXISTS image_gallery JSON NULL COMMENT 'Array of image URLs for house gallery';

-- Add image columns to businesses table (if not already exists)
ALTER TABLE businesses 
ADD COLUMN IF NOT EXISTS featured_image VARCHAR(500) NULL COMMENT 'Featured image URL for the business',
ADD COLUMN IF NOT EXISTS image_gallery JSON NULL COMMENT 'Array of image URLs for business gallery';

-- Create images table for centralized image management
CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('article', 'house', 'business') NOT NULL COMMENT 'Type of entity this image belongs to',
    entity_id INT NOT NULL COMMENT 'ID of the entity this image belongs to',
    image_url VARCHAR(500) NOT NULL COMMENT 'URL or path to the image file',
    image_type ENUM('featured', 'gallery', 'thumbnail') DEFAULT 'gallery' COMMENT 'Type of image',
    alt_text VARCHAR(255) NULL COMMENT 'Alt text for accessibility',
    caption VARCHAR(500) NULL COMMENT 'Image caption',
    sort_order INT DEFAULT 0 COMMENT 'Order for displaying images',
    file_size INT NULL COMMENT 'File size in bytes',
    mime_type VARCHAR(100) NULL COMMENT 'MIME type of the image',
    width INT NULL COMMENT 'Image width in pixels',
    height INT NULL COMMENT 'Image height in pixels',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_type (image_type),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Centralized image management for articles, houses, and businesses';

-- Create image_uploads table for tracking uploads
CREATE TABLE IF NOT EXISTS image_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_filename VARCHAR(255) NOT NULL COMMENT 'Original filename of uploaded image',
    stored_filename VARCHAR(255) NOT NULL COMMENT 'Filename as stored on server',
    file_path VARCHAR(500) NOT NULL COMMENT 'Full path to stored image',
    file_size INT NOT NULL COMMENT 'File size in bytes',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIME type of the image',
    width INT NULL COMMENT 'Image width in pixels',
    height INT NULL COMMENT 'Image height in pixels',
    uploaded_by INT NULL COMMENT 'User ID who uploaded the image',
    entity_type ENUM('article', 'house', 'business') NULL COMMENT 'Type of entity this image is for',
    entity_id INT NULL COMMENT 'ID of the entity this image is for',
    status ENUM('pending', 'processed', 'failed') DEFAULT 'pending' COMMENT 'Processing status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track image uploads and processing';

-- Migrate existing image data from JSON columns to new images table
-- This handles existing data in the 'images' JSON columns

-- Migrate house images
INSERT INTO images (entity_type, entity_id, image_url, image_type, sort_order, created_at)
SELECT 
    'house' as entity_type,
    id as entity_id,
    JSON_UNQUOTE(JSON_EXTRACT(images, CONCAT('$[', numbers.n, ']'))) as image_url,
    CASE 
        WHEN numbers.n = 0 THEN 'featured'
        ELSE 'gallery'
    END as image_type,
    numbers.n as sort_order,
    created_at
FROM houses 
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION 
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
) numbers
WHERE JSON_VALID(images) 
AND JSON_LENGTH(images) > numbers.n
AND JSON_UNQUOTE(JSON_EXTRACT(images, CONCAT('$[', numbers.n, ']'))) IS NOT NULL
AND JSON_UNQUOTE(JSON_EXTRACT(images, CONCAT('$[', numbers.n, ']'))) != '';

-- Migrate business images
INSERT INTO images (entity_type, entity_id, image_url, image_type, sort_order, created_at)
SELECT 
    'business' as entity_type,
    id as entity_id,
    JSON_UNQUOTE(JSON_EXTRACT(images, CONCAT('$[', numbers.n, ']'))) as image_url,
    CASE 
        WHEN numbers.n = 0 THEN 'featured'
        ELSE 'gallery'
    END as image_type,
    numbers.n as sort_order,
    created_at
FROM businesses 
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION 
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
) numbers
WHERE JSON_VALID(images) 
AND JSON_LENGTH(images) > numbers.n
AND JSON_UNQUOTE(JSON_EXTRACT(images, CONCAT('$[', numbers.n, ']'))) IS NOT NULL
AND JSON_UNQUOTE(JSON_EXTRACT(images, CONCAT('$[', numbers.n, ']'))) != '';

-- Update featured_image columns with first image from gallery
UPDATE houses 
SET featured_image = (
    SELECT image_url 
    FROM images 
    WHERE entity_type = 'house' 
    AND entity_id = houses.id 
    AND image_type = 'featured'
    LIMIT 1
)
WHERE featured_image IS NULL;

UPDATE businesses 
SET featured_image = (
    SELECT image_url 
    FROM images 
    WHERE entity_type = 'business' 
    AND entity_id = businesses.id 
    AND image_type = 'featured'
    LIMIT 1
)
WHERE featured_image IS NULL;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_images_entity_type_id ON images(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_images_type ON images(image_type);
CREATE INDEX IF NOT EXISTS idx_images_sort ON images(sort_order);

-- Add foreign key constraints (optional, can be enabled if needed)
-- ALTER TABLE images ADD CONSTRAINT fk_images_articles FOREIGN KEY (entity_id) REFERENCES articles(id) ON DELETE CASCADE;
-- ALTER TABLE images ADD CONSTRAINT fk_images_houses FOREIGN KEY (entity_id) REFERENCES houses(id) ON DELETE CASCADE;
-- ALTER TABLE images ADD CONSTRAINT fk_images_businesses FOREIGN KEY (entity_id) REFERENCES businesses(id) ON DELETE CASCADE;
