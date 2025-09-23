<?php
declare(strict_types=1);

namespace Content;

use Database\DB;
use Media\ImageManager;

class Article
{
    private array $article;

    public function __construct(array $article = null)
    {
        if ($article) {
            $this->article = $article;
        }
    }

    public static function findById(int $id): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT a.*, u.full_name as author_name 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.id = ?
            ');
            $stmt->execute([$id]);
            $article = $stmt->fetch();
            return $article ? new self($article) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function findBySlug(string $slug): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT a.*, u.full_name as author_name 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.slug = ?
            ');
            $stmt->execute([$slug]);
            $article = $stmt->fetch();
            return $article ? new self($article) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function create(array $data): ?self
    {
        try {
            $pdo = DB::pdo();
            $slug = self::generateSlug($data['title']);
            $safeContent = self::sanitizeHtml((string)($data['content'] ?? ''));
            $safeExcerpt = trim((string)($data['excerpt'] ?? ''));
            
            $stmt = $pdo->prepare('
                INSERT INTO articles (title, slug, content, excerpt, featured_image, 
                                    meta_title, meta_description, meta_keywords, author_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $data['title'],
                $slug,
                $safeContent,
                $safeExcerpt,
                $data['featured_image'] ?? '',
                $data['meta_title'] ?? $data['title'],
                $data['meta_description'] ?? '',
                $data['meta_keywords'] ?? '',
                $data['author_id'],
                $data['status'] ?? 'draft'
            ]);
            
            $articleId = $pdo->lastInsertId();
            return self::findById((int)$articleId);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function update(array $data): bool
    {
        try {
            $pdo = DB::pdo();
            $slug = isset($data['title']) ? self::generateSlug($data['title'], $this->getId()) : $this->getSlug();
            
            $stmt = $pdo->prepare('
                UPDATE articles SET 
                    title = COALESCE(?, title),
                    slug = ?,
                    content = COALESCE(?, content),
                    excerpt = COALESCE(?, excerpt),
                    featured_image = COALESCE(?, featured_image),
                    meta_title = COALESCE(?, meta_title),
                    meta_description = COALESCE(?, meta_description),
                    meta_keywords = COALESCE(?, meta_keywords),
                    status = COALESCE(?, status),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute([
                $data['title'] ?? null,
                $slug,
                isset($data['content']) ? self::sanitizeHtml((string)$data['content']) : null,
                isset($data['excerpt']) ? trim((string)$data['excerpt']) : null,
                $data['featured_image'] ?? null,
                $data['meta_title'] ?? null,
                $data['meta_description'] ?? null,
                $data['meta_keywords'] ?? null,
                $data['status'] ?? null,
                $this->getId()
            ]);
            
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function submitForReview(): bool
    {
        return $this->update(['status' => 'submitted']);
    }

    public function approve(int $reviewerId, string $notes = ''): bool
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                UPDATE articles SET 
                    status = "approved", 
                    reviewer_id = ?, 
                    review_notes = ?,
                    published_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute([$reviewerId, $notes, $this->getId()]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function reject(int $reviewerId, string $notes): bool
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                UPDATE articles SET 
                    status = "rejected", 
                    reviewer_id = ?, 
                    review_notes = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute([$reviewerId, $notes, $this->getId()]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function publish(): bool
    {
        return $this->update(['status' => 'published']);
    }

    public static function getByAuthor(int $authorId, int $limit = 10, int $offset = 0): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT a.*, u.full_name as author_name 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.author_id = ? 
                ORDER BY a.created_at DESC 
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$authorId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getPublished(int $limit = 10, int $offset = 0): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT a.*, u.full_name as author_name 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.status = "published" 
                ORDER BY a.published_at DESC 
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getForReview(int $limit = 10, int $offset = 0): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT a.*, u.full_name as author_name 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.status = "submitted" 
                ORDER BY a.created_at ASC 
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function assignToReviewer(int $reviewerId): bool
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                UPDATE articles SET 
                    status = "under_review", 
                    reviewer_id = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND status = "submitted"
            ');
            $stmt->execute([$reviewerId, $this->getId()]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getAssignedToReviewer(int $reviewerId, int $limit = 10, int $offset = 0): array
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT a.*, u.full_name as author_name 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.reviewer_id = ? AND a.status = "under_review"
                ORDER BY a.updated_at ASC 
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$reviewerId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function generateSlug(string $title, int $excludeId = 0): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        try {
            $pdo = DB::pdo();
            do {
                $stmt = $pdo->prepare('SELECT id FROM articles WHERE slug = ? AND id != ?');
                $stmt->execute([$slug, $excludeId]);
                if ($stmt->fetch()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                } else {
                    break;
                }
            } while (true);
        } catch (\Throwable $e) {
            // Fallback to timestamp if database error
            $slug = $originalSlug . '-' . time();
        }
        
        return $slug;
    }

    private static function sanitizeHtml(string $html): string
    {
        // Allow a safe subset of tags and basic attributes
        $allowedTags = '<p><br><strong><b><em><i><u><ol><ul><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><pre><code><hr>';
        $clean = strip_tags($html, $allowedTags);
        // Remove event handlers and javascript: URLs
        $clean = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace("/on\w+\s*='[^']*'/i", '', $clean);
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        // Restrict href/src to http(s) data images only
        $clean = preg_replace_callback('/\s(href|src)\s*=\s*([\"\"][^\"\"]*[\"\"]|[^\s>]+)/i', function($m){
            $attr = strtolower($m[1]);
            $val = trim($m[2], " \"'\t\n\r");
            if (preg_match('/^(https?:|data:image\/(png|jpeg|gif);base64,)/i', $val)) {
                return ' ' . $attr . '="' . htmlspecialchars($val, ENT_QUOTES) . '"';
            }
            return '';
        }, $clean);
        return $clean;
    }

    public function getId(): int
    {
        return (int)$this->article['id'];
    }

    public function getTitle(): string
    {
        return $this->article['title'];
    }

    public function getSlug(): string
    {
        return $this->article['slug'];
    }

    public function getContent(): string
    {
        return $this->article['content'];
    }

    public function getExcerpt(): string
    {
        return $this->article['excerpt'];
    }

    public function getFeaturedImageUrl(): string
    {
        return $this->article['featured_image'];
    }

    public function getMetaTitle(): string
    {
        return $this->article['meta_title'];
    }

    public function getMetaDescription(): string
    {
        return $this->article['meta_description'];
    }

    public function getMetaKeywords(): string
    {
        return $this->article['meta_keywords'];
    }

    public function getAuthorId(): int
    {
        return (int)$this->article['author_id'];
    }

    public function getAuthorName(): string
    {
        return $this->article['author_name'] ?? '';
    }

    public function getStatus(): string
    {
        return $this->article['status'];
    }

    public function getReviewerId(): ?int
    {
        return $this->article['reviewer_id'] ? (int)$this->article['reviewer_id'] : null;
    }

    public function getReviewNotes(): string
    {
        return $this->article['review_notes'] ?? '';
    }

    public function getPublishedAt(): ?string
    {
        return $this->article['published_at'];
    }

    public function getCreatedAt(): string
    {
        return $this->article['created_at'];
    }

    public function getUpdatedAt(): string
    {
        return $this->article['updated_at'];
    }

    public static function deleteById(int $id, int $authorId): bool
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('DELETE FROM articles WHERE id = ? AND author_id = ?');
            return $stmt->execute([$id, $authorId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isPublished(): bool
    {
        return $this->getStatus() === 'published';
    }

    public function isDraft(): bool
    {
        return $this->getStatus() === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->getStatus() === 'submitted';
    }

    public function isUnderReview(): bool
    {
        return $this->getStatus() === 'under_review';
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->getStatus() === 'rejected';
    }

    public function toArray(): array
    {
        return $this->article;
    }

    // Image-related methods
    public function getFeaturedImage(): ?array
    {
        $imageManager = new ImageManager();
        return $imageManager->getFeaturedImage('article', $this->getId());
    }

    public function getGalleryImages(): array
    {
        $imageManager = new ImageManager();
        return $imageManager->getGalleryImages('article', $this->getId());
    }

    public function getAllImages(): array
    {
        $imageManager = new ImageManager();
        return $imageManager->getImages('article', $this->getId());
    }

    public function addImage(array $file, string $imageType = 'gallery'): ?array
    {
        $imageManager = new ImageManager();
        return $imageManager->uploadImage($file, 'article', $this->getId(), $imageType);
    }

    public function setFeaturedImage(int $imageId): bool
    {
        $imageManager = new ImageManager();
        return $imageManager->setFeaturedImage('article', $this->getId(), $imageId);
    }

    public function deleteImage(int $imageId): bool
    {
        $imageManager = new ImageManager();
        return $imageManager->deleteImage($imageId);
    }


    public function hasImages(): bool
    {
        return !empty($this->getAllImages());
    }
}
