<?php

namespace Properties;

use Database\DB;

class House
{
    private array $house;
    private $pdo;

    public function __construct(array $house = null)
    {
        $this->house = $house ?: [];
        $this->pdo = DB::pdo();
    }

    public function getId(): ?int
    {
        return $this->house['id'] ?? null;
    }

    public function getTitle(): string
    {
        return $this->house['title'] ?? '';
    }

    public function getDescription(): string
    {
        return $this->house['description'] ?? '';
    }

    public function getLocation(): string
    {
        return $this->house['location'] ?? '';
    }

    public function getPrice(): ?float
    {
        return $this->house['price'] ?? null;
    }

    public function getFormattedPrice(): string
    {
        $price = $this->getPrice();
        if (!$price) return 'Price on request';
        
        return '$' . number_format($price, 2);
    }

    public function getOwnerPhone(): ?string
    {
        return $this->house['owner_phone'] ?? null;
    }

    public function getOwnerWhatsapp(): ?string
    {
        return $this->house['owner_whatsapp'] ?? null;
    }

    public function getOwnerWebsite(): ?string
    {
        return $this->house['owner_website'] ?? null;
    }

    public function getContactMethod(): string
    {
        return $this->house['contact_method'] ?? 'phone';
    }

    public function getContactUrl(): string
    {
        switch ($this->getContactMethod()) {
            case 'whatsapp':
                $whatsapp = $this->getOwnerWhatsapp();
                return $whatsapp ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $whatsapp) : '#';
            case 'website':
                return $this->getOwnerWebsite() ?: '#';
            case 'phone':
            default:
                $phone = $this->getOwnerPhone();
                return $phone ? "tel:$phone" : '#';
        }
    }

    public function getContactText(): string
    {
        switch ($this->getContactMethod()) {
            case 'whatsapp':
                return 'Contact on WhatsApp';
            case 'website':
                return 'Book Online';
            case 'phone':
            default:
                return 'Call Owner';
        }
    }

    public function getContactIcon(): string
    {
        switch ($this->getContactMethod()) {
            case 'whatsapp':
                return 'fa-brands fa-whatsapp';
            case 'website':
                return 'fa-solid fa-globe';
            case 'phone':
            default:
                return 'fa-solid fa-phone';
        }
    }

    public function getContactClass(): string
    {
        switch ($this->getContactMethod()) {
            case 'whatsapp':
                return 'btn-success';
            case 'website':
                return 'btn-info';
            case 'phone':
            default:
                return 'btn-primary';
        }
    }

    public function getFeaturedImage(): ?string
    {
        return $this->house['featured_image'] ?? null;
    }

    public function getImages(): array
    {
        $images = $this->house['images'] ?? null;
        if (!$images) return [];
        
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($images) ? $images : [];
    }

    public function getCreatedAt(): string
    {
        return $this->house['created_at'] ?? '';
    }

    public function toArray(): array
    {
        return $this->house;
    }

    public static function findById(int $id): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT * FROM houses WHERE id = ?');
            $stmt->execute([$id]);
            $house = $stmt->fetch();
            return $house ? new self($house) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT * FROM houses 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$limit, $offset]);
            $houses = $stmt->fetchAll();
            
            return array_map(fn($house) => new self($house), $houses);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function save(): bool
    {
        try {
            if ($this->getId()) {
                // Update existing house
                $stmt = $this->pdo->prepare('
                    UPDATE houses SET 
                        title = ?, description = ?, location = ?, price = ?,
                        owner_phone = ?, owner_whatsapp = ?, owner_website = ?,
                        contact_method = ?, featured_image = ?, images = ?, updated_at = NOW()
                    WHERE id = ?
                ');
                $stmt->execute([
                    $this->house['title'],
                    $this->house['description'],
                    $this->house['location'],
                    $this->house['price'],
                    $this->house['owner_phone'],
                    $this->house['owner_whatsapp'],
                    $this->house['owner_website'],
                    $this->house['contact_method'],
                    $this->house['featured_image'],
                    is_array($this->house['images']) ? json_encode($this->house['images']) : $this->house['images'],
                    $this->getId()
                ]);
            } else {
                // Insert new house
                $stmt = $this->pdo->prepare('
                    INSERT INTO houses 
                    (title, description, location, price, owner_phone, owner_whatsapp, 
                     owner_website, contact_method, featured_image, images, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ');
                $stmt->execute([
                    $this->house['title'],
                    $this->house['description'],
                    $this->house['location'],
                    $this->house['price'],
                    $this->house['owner_phone'],
                    $this->house['owner_whatsapp'],
                    $this->house['owner_website'],
                    $this->house['contact_method'],
                    $this->house['featured_image'],
                    is_array($this->house['images']) ? json_encode($this->house['images']) : $this->house['images']
                ]);
                $this->house['id'] = $this->pdo->lastInsertId();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(): bool
    {
        if (!$this->getId()) return false;
        
        try {
            $stmt = $this->pdo->prepare('DELETE FROM houses WHERE id = ?');
            $stmt->execute([$this->getId()]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}