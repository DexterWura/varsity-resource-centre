<?php
declare(strict_types=1);

namespace Content;

class SemanticScholar
{
    private const BASE_URL = 'https://api.semanticscholar.org/graph/v1';
    private const DEFAULT_LIMIT = 20;
    
    private ?string $apiKey;
    
    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Search for papers using the Semantic Scholar API
     */
    public function searchPapers(string $query, int $limit = self::DEFAULT_LIMIT, int $offset = 0): array
    {
        $params = [
            'query' => $query,
            'limit' => min($limit, 100), // API max is 100
            'offset' => $offset,
            'fields' => 'paperId,title,abstract,authors,venue,year,citationCount,referenceCount,isOpenAccess,openAccessPdf,url,publicationDate'
        ];
        
        $response = $this->makeRequest('/paper/search', $params);
        
        if (!$response || !isset($response['data'])) {
            return [];
        }
        
        return array_map([$this, 'formatPaper'], $response['data']);
    }
    
    /**
     * Get paper details by ID
     */
    public function getPaper(string $paperId): ?array
    {
        $params = [
            'fields' => 'paperId,title,abstract,authors,venue,year,citationCount,referenceCount,isOpenAccess,openAccessPdf,url,publicationDate,publicationTypes,fieldsOfStudy'
        ];
        
        $response = $this->makeRequest("/paper/{$paperId}", $params);
        
        if (!$response) {
            return null;
        }
        
        return $this->formatPaper($response);
    }
    
    /**
     * Get author details
     */
    public function getAuthor(string $authorId): ?array
    {
        $params = [
            'fields' => 'authorId,name,papers,aliases,url,affiliations'
        ];
        
        $response = $this->makeRequest("/author/{$authorId}", $params);
        
        if (!$response) {
            return null;
        }
        
        return $this->formatAuthor($response);
    }
    
    /**
     * Get recommended papers for a given paper
     */
    public function getRecommendations(string $paperId, int $limit = 10): array
    {
        $params = [
            'limit' => min($limit, 100),
            'fields' => 'paperId,title,abstract,authors,venue,year,citationCount,referenceCount,isOpenAccess,openAccessPdf,url'
        ];
        
        $response = $this->makeRequest("/paper/{$paperId}/recommendations", $params);
        
        if (!$response || !isset($response['data'])) {
            return [];
        }
        
        return array_map([$this, 'formatPaper'], $response['data']);
    }
    
    /**
     * Make HTTP request to Semantic Scholar API
     */
    private function makeRequest(string $endpoint, array $params = []): ?array
    {
        $url = self::BASE_URL . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = [
            'User-Agent: VarsityResourceCentre/1.0',
            'Accept: application/json'
        ];
        
        if ($this->apiKey) {
            $headers[] = 'x-api-key: ' . $this->apiKey;
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 30
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            error_log("Semantic Scholar API request failed: {$url}");
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Semantic Scholar API JSON decode error: " . json_last_error_msg());
            return null;
        }
        
        return $data;
    }
    
    /**
     * Format paper data for our application
     */
    private function formatPaper(array $paper): array
    {
        $authors = [];
        if (isset($paper['authors']) && is_array($paper['authors'])) {
            foreach ($paper['authors'] as $author) {
                $authors[] = $author['name'] ?? 'Unknown Author';
            }
        }
        
        return [
            'id' => $paper['paperId'] ?? '',
            'title' => $paper['title'] ?? 'Untitled',
            'abstract' => $paper['abstract'] ?? '',
            'authors' => $authors,
            'author_names' => implode(', ', $authors),
            'venue' => $paper['venue'] ?? '',
            'year' => $paper['year'] ?? null,
            'citation_count' => $paper['citationCount'] ?? 0,
            'reference_count' => $paper['referenceCount'] ?? 0,
            'is_open_access' => $paper['isOpenAccess'] ?? false,
            'pdf_url' => $paper['openAccessPdf']['url'] ?? null,
            'url' => $paper['url'] ?? null,
            'publication_date' => $paper['publicationDate'] ?? null,
            'fields_of_study' => $paper['fieldsOfStudy'] ?? [],
            'publication_types' => $paper['publicationTypes'] ?? []
        ];
    }
    
    /**
     * Format author data for our application
     */
    private function formatAuthor(array $author): array
    {
        return [
            'id' => $author['authorId'] ?? '',
            'name' => $author['name'] ?? 'Unknown Author',
            'aliases' => $author['aliases'] ?? [],
            'url' => $author['url'] ?? null,
            'affiliations' => $author['affiliations'] ?? [],
            'paper_count' => count($author['papers'] ?? [])
        ];
    }
    
    /**
     * Convert Semantic Scholar paper to Article format
     */
    public function paperToArticle(array $paper, int $authorId): array
    {
        $content = $paper['abstract'] ?? '';
        
        // Add metadata to content
        if (!empty($paper['authors'])) {
            $content .= "\n\n**Authors:** " . implode(', ', $paper['authors']);
        }
        
        if (!empty($paper['venue'])) {
            $content .= "\n\n**Published in:** " . $paper['venue'];
        }
        
        if (!empty($paper['year'])) {
            $content .= "\n\n**Year:** " . $paper['year'];
        }
        
        if (!empty($paper['citation_count'])) {
            $content .= "\n\n**Citations:** " . $paper['citation_count'];
        }
        
        if (!empty($paper['url'])) {
            $content .= "\n\n**Original URL:** " . $paper['url'];
        }
        
        if (!empty($paper['pdf_url'])) {
            $content .= "\n\n**PDF:** " . $paper['pdf_url'];
        }
        
        return [
            'title' => $paper['title'],
            'content' => $content,
            'excerpt' => $this->createExcerpt($paper['abstract'] ?? ''),
            'author_id' => $authorId,
            'status' => 'draft',
            'metadata' => json_encode([
                'semantic_scholar_id' => $paper['id'],
                'original_authors' => $paper['authors'],
                'venue' => $paper['venue'],
                'year' => $paper['year'],
                'citation_count' => $paper['citation_count'],
                'is_open_access' => $paper['is_open_access'],
                'pdf_url' => $paper['pdf_url'],
                'original_url' => $paper['url'],
                'fields_of_study' => $paper['fields_of_study']
            ])
        ];
    }
    
    /**
     * Create excerpt from abstract
     */
    private function createExcerpt(string $abstract, int $maxLength = 200): string
    {
        if (empty($abstract)) {
            return '';
        }
        
        $abstract = strip_tags($abstract);
        $abstract = preg_replace('/\s+/', ' ', $abstract);
        $abstract = trim($abstract);
        
        if (strlen($abstract) <= $maxLength) {
            return $abstract;
        }
        
        return substr($abstract, 0, $maxLength) . '...';
    }
}
