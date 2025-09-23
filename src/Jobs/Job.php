<?php

namespace Jobs;

use Database\DB;

class Job
{
    private array $job;
    private $pdo;

    public function __construct(array $job = null)
    {
        $this->job = $job ?: [];
        $this->pdo = DB::pdo();
    }

    public function getId(): ?int
    {
        return $this->job['id'] ?? null;
    }

    public function getTitle(): string
    {
        return $this->job['title'] ?? '';
    }

    public function getCompany(): string
    {
        return $this->job['company'] ?? '';
    }

    public function getLocation(): string
    {
        return $this->job['location'] ?? '';
    }

    public function getDescription(): string
    {
        return $this->job['description'] ?? '';
    }

    public function getJobUrl(): ?string
    {
        return $this->job['job_url'] ?? null;
    }

    public function getWhatsappContact(): ?string
    {
        return $this->job['whatsapp_contact'] ?? null;
    }

    public function getEmailContact(): ?string
    {
        return $this->job['email_contact'] ?? null;
    }

    public function getContactMethod(): string
    {
        return $this->job['contact_method'] ?? 'url';
    }

    public function getApplicationUrl(): string
    {
        switch ($this->getContactMethod()) {
            case 'whatsapp':
                $whatsapp = $this->getWhatsappContact();
                return $whatsapp ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $whatsapp) : '#';
            case 'email':
                $email = $this->getEmailContact();
                return $email ? "mailto:$email" : '#';
            case 'url':
            default:
                return $this->getJobUrl() ?: '#';
        }
    }

    public function getApplicationText(): string
    {
        switch ($this->getContactMethod()) {
            case 'whatsapp':
                return 'Contact on WhatsApp';
            case 'email':
                return 'Send Email';
            case 'url':
            default:
                return 'Apply Now';
        }
    }

    public function getApplicationIcon(): string
    {
        switch ($this->getContactMethod()) {
            case 'whatsapp':
                return 'fa-brands fa-whatsapp';
            case 'email':
                return 'fa-solid fa-envelope';
            case 'url':
            default:
                return 'fa-solid fa-external-link-alt';
        }
    }

    public function getApplicationClass(): string
    {
        switch ($this->getContactMethod()) {
            case 'whatsapp':
                return 'btn-success';
            case 'email':
                return 'btn-primary';
            case 'url':
            default:
                return 'btn-primary';
        }
    }

    public function getExpiresAt(): ?string
    {
        return $this->job['expires_at'] ?? null;
    }

    public function getCreatedAt(): string
    {
        return $this->job['created_at'] ?? '';
    }

    public function isExpired(): bool
    {
        $expiresAt = $this->getExpiresAt();
        if (!$expiresAt) return false;
        
        return strtotime($expiresAt) < time();
    }

    public function getDaysUntilExpiry(): ?int
    {
        $expiresAt = $this->getExpiresAt();
        if (!$expiresAt) return null;
        
        $days = (strtotime($expiresAt) - time()) / (24 * 60 * 60);
        return max(0, (int)ceil($days));
    }

    public function toArray(): array
    {
        return $this->job;
    }

    public static function findById(int $id): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
            $stmt->execute([$id]);
            $job = $stmt->fetch();
            return $job ? new self($job) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getAll(int $limit = 50, int $offset = 0): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT * FROM jobs 
                WHERE (expires_at IS NULL OR expires_at > NOW()) 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$limit, $offset]);
            $jobs = $stmt->fetchAll();
            
            return array_map(fn($job) => new self($job), $jobs);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function save(): bool
    {
        try {
            if ($this->getId()) {
                // Update existing job
                $stmt = $this->pdo->prepare('
                    UPDATE jobs SET 
                        title = ?, company = ?, location = ?, description = ?,
                        job_url = ?, whatsapp_contact = ?, email_contact = ?,
                        contact_method = ?, expires_at = ?, updated_at = NOW()
                    WHERE id = ?
                ');
                $stmt->execute([
                    $this->job['title'],
                    $this->job['company'],
                    $this->job['location'],
                    $this->job['description'],
                    $this->job['job_url'],
                    $this->job['whatsapp_contact'],
                    $this->job['email_contact'],
                    $this->job['contact_method'],
                    $this->job['expires_at'],
                    $this->getId()
                ]);
            } else {
                // Insert new job
                $stmt = $this->pdo->prepare('
                    INSERT INTO jobs 
                    (title, company, location, description, job_url, whatsapp_contact, 
                     email_contact, contact_method, expires_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ');
                $stmt->execute([
                    $this->job['title'],
                    $this->job['company'],
                    $this->job['location'],
                    $this->job['description'],
                    $this->job['job_url'],
                    $this->job['whatsapp_contact'],
                    $this->job['email_contact'],
                    $this->job['contact_method'],
                    $this->job['expires_at']
                ]);
                $this->job['id'] = $this->pdo->lastInsertId();
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
            $stmt = $this->pdo->prepare('DELETE FROM jobs WHERE id = ?');
            $stmt->execute([$this->getId()]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
