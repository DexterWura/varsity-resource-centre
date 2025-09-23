<?php
declare(strict_types=1);

namespace Properties;

use Database\DB;
use Media\ImageManager;

class Business
{
    private array $business;

    public function __construct(array $business = null)
    {
        if ($business) {
            $this->business = $business;
        }
    }

    public static function findById(int $id): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT * FROM businesses WHERE id = ? AND is_active = 1');
            $stmt->execute([$id]);
            $business = $stmt->fetch();
            return $business ? new self($business) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT * FROM businesses WHERE is_active = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?');
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getId(): int
    {
        return (int)$this->business['id'];
    }

    public function getName(): string
    {
        return $this->business['name'] ?? '';
    }

    public function getDescription(): string
    {
        return $this->business['description'] ?? '';
    }

    public function getCategory(): string
    {
        return $this->business['category'] ?? '';
    }

    public function getCity(): string
    {
        return $this->business['city'] ?? '';
    }

    public function getAddress(): string
    {
        return $this->business['address'] ?? '';
    }

    public function getPhone(): string
    {
        return $this->business['phone'] ?? '';
    }

    public function getEmail(): string
    {
        return $this->business['email'] ?? '';
    }

    public function getWebsite(): string
    {
        return $this->business['website'] ?? '';
    }

    public function getServices(): string
    {
        return $this->business['services'] ?? '';
    }

    public function getHours(): string
    {
        return $this->business['hours'] ?? '';
    }

    public function isActive(): bool
    {
        return (bool)$this->business['is_active'];
    }

    public function getCreatedAt(): string
    {
        return $this->business['created_at'] ?? '';
    }

    public function getUpdatedAt(): string
    {
        return $this->business['updated_at'] ?? '';
    }

    // Image-related methods
    public function getFeaturedImage(): ?array
    {
        $imageManager = new ImageManager();
        return $imageManager->getFeaturedImage('business', $this->getId());
    }

    public function getGalleryImages(): array
    {
        $imageManager = new ImageManager();
        return $imageManager->getGalleryImages('business', $this->getId());
    }

    public function getAllImages(): array
    {
        $imageManager = new ImageManager();
        return $imageManager->getImages('business', $this->getId());
    }

    public function addImage(array $file, string $imageType = 'gallery'): ?array
    {
        $imageManager = new ImageManager();
        return $imageManager->uploadImage($file, 'business', $this->getId(), $imageType);
    }

    public function setFeaturedImage(int $imageId): bool
    {
        $imageManager = new ImageManager();
        return $imageManager->setFeaturedImage('business', $this->getId(), $imageId);
    }

    public function deleteImage(int $imageId): bool
    {
        $imageManager = new ImageManager();
        return $imageManager->deleteImage($imageId);
    }

    public function getFeaturedImageUrl(): ?string
    {
        $featuredImage = $this->getFeaturedImage();
        return $featuredImage ? $featuredImage['image_url'] : null;
    }

    public function hasImages(): bool
    {
        return !empty($this->getAllImages());
    }

    public function toArray(): array
    {
        return $this->business;
    }
}
