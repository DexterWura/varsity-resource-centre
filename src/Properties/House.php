<?php
declare(strict_types=1);

namespace Properties;

use Database\DB;
use Media\ImageManager;

class House
{
    private array $house;

    public function __construct(array $house = null)
    {
        if ($house) {
            $this->house = $house;
        }
    }

    public static function findById(int $id): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT * FROM houses WHERE id = ? AND is_active = 1');
            $stmt->execute([$id]);
            $house = $stmt->fetch();
            return $house ? new self($house) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT * FROM houses WHERE is_active = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?');
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getId(): int
    {
        return (int)$this->house['id'];
    }

    public function getTitle(): string
    {
        return $this->house['title'] ?? '';
    }

    public function getCity(): string
    {
        return $this->house['city'] ?? '';
    }

    public function getPrice(): float
    {
        return (float)$this->house['price'];
    }

    public function getPriceType(): string
    {
        return $this->house['price_type'] ?? 'per_month';
    }

    public function getDescription(): string
    {
        return $this->house['description'] ?? '';
    }

    public function getAddress(): string
    {
        return $this->house['address'] ?? '';
    }

    public function getContactName(): string
    {
        return $this->house['contact_name'] ?? '';
    }

    public function getContactPhone(): string
    {
        return $this->house['contact_phone'] ?? '';
    }

    public function getContactEmail(): string
    {
        return $this->house['contact_email'] ?? '';
    }

    public function getBedrooms(): ?int
    {
        return $this->house['bedrooms'] ? (int)$this->house['bedrooms'] : null;
    }

    public function getBathrooms(): ?int
    {
        return $this->house['bathrooms'] ? (int)$this->house['bathrooms'] : null;
    }

    public function getPropertyType(): string
    {
        return $this->house['property_type'] ?? '';
    }

    public function getAmenities(): string
    {
        return $this->house['amenities'] ?? '';
    }

    public function getRules(): string
    {
        return $this->house['rules'] ?? '';
    }

    public function getDeposit(): ?float
    {
        return $this->house['deposit'] ? (float)$this->house['deposit'] : null;
    }

    public function isActive(): bool
    {
        return (bool)$this->house['is_active'];
    }

    public function getCreatedAt(): string
    {
        return $this->house['created_at'] ?? '';
    }

    public function getUpdatedAt(): string
    {
        return $this->house['updated_at'] ?? '';
    }

    // Image-related methods
    public function getFeaturedImage(): ?array
    {
        $imageManager = new ImageManager();
        return $imageManager->getFeaturedImage('house', $this->getId());
    }

    public function getGalleryImages(): array
    {
        $imageManager = new ImageManager();
        return $imageManager->getGalleryImages('house', $this->getId());
    }

    public function getAllImages(): array
    {
        $imageManager = new ImageManager();
        return $imageManager->getImages('house', $this->getId());
    }

    public function addImage(array $file, string $imageType = 'gallery'): ?array
    {
        $imageManager = new ImageManager();
        return $imageManager->uploadImage($file, 'house', $this->getId(), $imageType);
    }

    public function setFeaturedImage(int $imageId): bool
    {
        $imageManager = new ImageManager();
        return $imageManager->setFeaturedImage('house', $this->getId(), $imageId);
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
        return $this->house;
    }
}
