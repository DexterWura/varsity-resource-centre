<?php
declare(strict_types=1);

namespace Media;

use Database\DB;

class ImageManager
{
    private const UPLOAD_DIR = 'uploads/images/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const THUMBNAIL_SIZES = [
        'small' => [150, 150],
        'medium' => [300, 300],
        'large' => [600, 600]
    ];

    /**
     * Upload and process an image
     */
    public function uploadImage(array $file, string $entityType, int $entityId, string $imageType = 'gallery'): ?array
    {
        // Validate file
        if (!$this->validateFile($file)) {
            return null;
        }

        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../' . self::UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return null;
        }

        // Get image info
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            unlink($filePath);
            return null;
        }

        // Create thumbnails
        $thumbnails = $this->createThumbnails($filePath, $filename);

        // Store in database
        $imageData = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'image_url' => self::UPLOAD_DIR . $filename,
            'image_type' => $imageType,
            'alt_text' => pathinfo($file['name'], PATHINFO_FILENAME),
            'file_size' => filesize($filePath),
            'mime_type' => $imageInfo['mime'],
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'sort_order' => $this->getNextSortOrder($entityType, $entityId)
        ];

        $imageId = $this->saveImageToDatabase($imageData);
        if (!$imageId) {
            unlink($filePath);
            return null;
        }

        return [
            'id' => $imageId,
            'url' => $imageData['image_url'],
            'thumbnails' => $thumbnails,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'size' => $imageData['file_size']
        ];
    }

    /**
     * Get images for an entity
     */
    public function getImages(string $entityType, int $entityId, string $imageType = null): array
    {
        try {
            $pdo = DB::pdo();
            $sql = 'SELECT * FROM images WHERE entity_type = ? AND entity_id = ?';
            $params = [$entityType, $entityId];

            if ($imageType) {
                $sql .= ' AND image_type = ?';
                $params[] = $imageType;
            }

            $sql .= ' ORDER BY sort_order ASC, created_at ASC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get featured image for an entity
     */
    public function getFeaturedImage(string $entityType, int $entityId): ?array
    {
        $images = $this->getImages($entityType, $entityId, 'featured');
        return !empty($images) ? $images[0] : null;
    }

    /**
     * Get gallery images for an entity
     */
    public function getGalleryImages(string $entityType, int $entityId): array
    {
        return $this->getImages($entityType, $entityId, 'gallery');
    }

    /**
     * Delete an image
     */
    public function deleteImage(int $imageId): bool
    {
        try {
            $pdo = DB::pdo();
            
            // Get image info
            $stmt = $pdo->prepare('SELECT * FROM images WHERE id = ?');
            $stmt->execute([$imageId]);
            $image = $stmt->fetch();

            if (!$image) {
                return false;
            }

            // Delete file
            $filePath = __DIR__ . '/../../' . $image['image_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete thumbnails
            $this->deleteThumbnails($image['image_url']);

            // Delete from database
            $stmt = $pdo->prepare('DELETE FROM images WHERE id = ?');
            $stmt->execute([$imageId]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Update image order
     */
    public function updateImageOrder(int $imageId, int $sortOrder): bool
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('UPDATE images SET sort_order = ? WHERE id = ?');
            $stmt->execute([$sortOrder, $imageId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Set featured image
     */
    public function setFeaturedImage(string $entityType, int $entityId, int $imageId): bool
    {
        try {
            $pdo = DB::pdo();
            
            // Remove existing featured image
            $stmt = $pdo->prepare('UPDATE images SET image_type = "gallery" WHERE entity_type = ? AND entity_id = ? AND image_type = "featured"');
            $stmt->execute([$entityType, $entityId]);

            // Set new featured image
            $stmt = $pdo->prepare('UPDATE images SET image_type = "featured" WHERE id = ? AND entity_type = ? AND entity_id = ?');
            $stmt->execute([$imageId, $entityType, $entityId]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(array $file): bool
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            return false;
        }

        return true;
    }

    /**
     * Create thumbnails for an image
     */
    private function createThumbnails(string $filePath, string $filename): array
    {
        $thumbnails = [];
        $pathInfo = pathinfo($filePath);
        $uploadDir = dirname($filePath) . '/';

        foreach (self::THUMBNAIL_SIZES as $size => $dimensions) {
            $thumbPath = $uploadDir . $size . '_' . $filename;
            
            if ($this->createThumbnail($filePath, $thumbPath, $dimensions[0], $dimensions[1])) {
                $thumbnails[$size] = self::UPLOAD_DIR . $size . '_' . $filename;
            }
        }

        return $thumbnails;
    }

    /**
     * Create a single thumbnail
     */
    private function createThumbnail(string $sourcePath, string $thumbPath, int $width, int $height): bool
    {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $sourceImage = $this->createImageFromFile($sourcePath, $imageInfo[2]);
        if (!$sourceImage) {
            return false;
        }

        // Calculate thumbnail dimensions maintaining aspect ratio
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        
        $ratio = min($width / $sourceWidth, $height / $sourceHeight);
        $thumbWidth = (int)($sourceWidth * $ratio);
        $thumbHeight = (int)($sourceHeight * $ratio);

        // Create thumbnail
        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG and GIF
        if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
            $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
            imagefill($thumbImage, 0, 0, $transparent);
        }

        imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight);

        // Save thumbnail
        $result = $this->saveImageToFile($thumbImage, $thumbPath, $imageInfo[2]);

        imagedestroy($sourceImage);
        imagedestroy($thumbImage);

        return $result;
    }

    /**
     * Create image resource from file
     */
    private function createImageFromFile(string $filePath, int $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }

    /**
     * Save image resource to file
     */
    private function saveImageToFile($imageResource, string $filePath, int $imageType): bool
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagejpeg($imageResource, $filePath, 90);
            case IMAGETYPE_PNG:
                return imagepng($imageResource, $filePath, 9);
            case IMAGETYPE_GIF:
                return imagegif($imageResource, $filePath);
            case IMAGETYPE_WEBP:
                return imagewebp($imageResource, $filePath, 90);
            default:
                return false;
        }
    }

    /**
     * Save image data to database
     */
    private function saveImageToDatabase(array $imageData): ?int
    {
        try {
            $pdo = DB::pdo();
            $sql = 'INSERT INTO images (entity_type, entity_id, image_url, image_type, alt_text, file_size, mime_type, width, height, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $imageData['entity_type'],
                $imageData['entity_id'],
                $imageData['image_url'],
                $imageData['image_type'],
                $imageData['alt_text'],
                $imageData['file_size'],
                $imageData['mime_type'],
                $imageData['width'],
                $imageData['height'],
                $imageData['sort_order']
            ]);
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get next sort order for entity
     */
    private function getNextSortOrder(string $entityType, int $entityId): int
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT MAX(sort_order) as max_order FROM images WHERE entity_type = ? AND entity_id = ?');
            $stmt->execute([$entityType, $entityId]);
            $result = $stmt->fetch();
            return ($result['max_order'] ?? 0) + 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /**
     * Delete thumbnails for an image
     */
    private function deleteThumbnails(string $imageUrl): void
    {
        $filename = basename($imageUrl);
        $uploadDir = __DIR__ . '/../../' . self::UPLOAD_DIR;

        foreach (array_keys(self::THUMBNAIL_SIZES) as $size) {
            $thumbPath = $uploadDir . $size . '_' . $filename;
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
    }
}
