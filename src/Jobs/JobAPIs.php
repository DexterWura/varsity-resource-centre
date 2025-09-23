<?php
declare(strict_types=1);

namespace Jobs;

class JobAPIs
{
    private const OPEN_SKILLS_API = 'https://api.open-skills.org/jobs';
    private const DEVITJOBS_API = 'https://api.devitjobs.uk/jobs';
    private const ARBEITNOW_API = 'https://api.arbeitnow.com/jobs';
    
    private array $activeAPIs;
    private int $timeout = 30;
    
    public function __construct(array $activeAPIs = [])
    {
        $this->activeAPIs = $activeAPIs;
    }
    
    /**
     * Fetch jobs from all active APIs and shuffle them
     */
    public function fetchShuffledJobs(int $limit = 50): array
    {
        $allJobs = [];
        
        foreach ($this->activeAPIs as $apiName => $isActive) {
            if (!$isActive) continue;
            
            try {
                $jobs = $this->fetchFromAPI($apiName, $limit);
                if (!empty($jobs)) {
                    // Add source information to each job
                    foreach ($jobs as &$job) {
                        $job['source'] = $apiName;
                        $job['source_display'] = $this->getSourceDisplayName($apiName);
                    }
                    $allJobs = array_merge($allJobs, $jobs);
                }
            } catch (\Throwable $e) {
                error_log("Failed to fetch jobs from {$apiName}: " . $e->getMessage());
                continue;
            }
        }
        
        // Shuffle the combined results
        shuffle($allJobs);
        
        // Limit the final results
        return array_slice($allJobs, 0, $limit);
    }
    
    /**
     * Fetch jobs from a specific API
     */
    private function fetchFromAPI(string $apiName, int $limit): array
    {
        switch ($apiName) {
            case 'open_skills':
                return $this->fetchOpenSkillsJobs($limit);
            case 'devitjobs':
                return $this->fetchDevITJobs($limit);
            case 'arbeitnow':
                return $this->fetchArbeitnowJobs($limit);
            default:
                return [];
        }
    }
    
    /**
     * Fetch jobs from Open Skills API
     */
    private function fetchOpenSkillsJobs(int $limit): array
    {
        $url = self::OPEN_SKILLS_API . '?limit=' . $limit;
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['jobs'])) {
            return [];
        }
        
        return array_map([$this, 'formatOpenSkillsJob'], $response['jobs']);
    }
    
    /**
     * Fetch jobs from DevITjobs UK API
     */
    private function fetchDevITJobs(int $limit): array
    {
        $url = self::DEVITJOBS_API . '?limit=' . $limit;
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['data'])) {
            return [];
        }
        
        return array_map([$this, 'formatDevITJob'], $response['data']);
    }
    
    /**
     * Fetch jobs from Arbeitnow API
     */
    private function fetchArbeitnowJobs(int $limit): array
    {
        $url = self::ARBEITNOW_API . '?limit=' . $limit;
        $response = $this->makeRequest($url);
        
        if (!$response || !isset($response['data'])) {
            return [];
        }
        
        return array_map([$this, 'formatArbeitnowJob'], $response['data']);
    }
    
    /**
     * Make HTTP request to API
     */
    private function makeRequest(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: VarsityResourceCentre/1.0',
                    'Accept: application/json',
                    'Content-Type: application/json'
                ],
                'timeout' => $this->timeout
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error for {$url}: " . json_last_error_msg());
            return null;
        }
        
        return $data;
    }
    
    /**
     * Format Open Skills job data
     */
    private function formatOpenSkillsJob(array $job): array
    {
        return [
            'id' => 'open_skills_' . ($job['id'] ?? uniqid()),
            'title' => $job['title'] ?? 'Untitled Position',
            'company' => $job['company'] ?? 'Unknown Company',
            'location' => $job['location'] ?? 'Remote',
            'description' => $job['description'] ?? '',
            'salary' => $job['salary'] ?? null,
            'type' => $job['type'] ?? 'Full-time',
            'remote' => $job['remote'] ?? false,
            'url' => $job['url'] ?? null,
            'posted_date' => $job['posted_date'] ?? date('Y-m-d'),
            'skills' => $job['skills'] ?? [],
            'experience_level' => $job['experience_level'] ?? 'Mid-level'
        ];
    }
    
    /**
     * Format DevITjobs UK job data
     */
    private function formatDevITJob(array $job): array
    {
        return [
            'id' => 'devitjobs_' . ($job['id'] ?? uniqid()),
            'title' => $job['title'] ?? 'Untitled Position',
            'company' => $job['company'] ?? 'Unknown Company',
            'location' => $job['location'] ?? 'UK',
            'description' => $job['description'] ?? '',
            'salary' => $job['salary'] ?? null,
            'type' => $job['employment_type'] ?? 'Full-time',
            'remote' => $job['remote_work'] ?? false,
            'url' => $job['apply_url'] ?? null,
            'posted_date' => $job['date_posted'] ?? date('Y-m-d'),
            'skills' => $job['required_skills'] ?? [],
            'experience_level' => $job['seniority_level'] ?? 'Mid-level'
        ];
    }
    
    /**
     * Format Arbeitnow job data
     */
    private function formatArbeitnowJob(array $job): array
    {
        return [
            'id' => 'arbeitnow_' . ($job['id'] ?? uniqid()),
            'title' => $job['title'] ?? 'Untitled Position',
            'company' => $job['company_name'] ?? 'Unknown Company',
            'location' => $job['location'] ?? 'Europe',
            'description' => $job['description'] ?? '',
            'salary' => $job['salary'] ?? null,
            'type' => $job['job_type'] ?? 'Full-time',
            'remote' => $job['remote'] ?? false,
            'url' => $job['url'] ?? null,
            'posted_date' => $job['created_at'] ?? date('Y-m-d'),
            'skills' => $job['tags'] ?? [],
            'experience_level' => $job['seniority'] ?? 'Mid-level'
        ];
    }
    
    /**
     * Get display name for API source
     */
    private function getSourceDisplayName(string $apiName): string
    {
        $names = [
            'open_skills' => 'Open Skills',
            'devitjobs' => 'DevITjobs UK',
            'arbeitnow' => 'Arbeitnow'
        ];
        
        return $names[$apiName] ?? ucfirst($apiName);
    }
    
    /**
     * Get available API sources
     */
    public static function getAvailableAPIs(): array
    {
        return [
            'open_skills' => [
                'name' => 'Open Skills',
                'description' => 'Open source job board with tech positions',
                'website' => 'https://open-skills.org',
                'features' => ['Remote jobs', 'Tech focus', 'Open source']
            ],
            'devitjobs' => [
                'name' => 'DevITjobs UK',
                'description' => 'UK-focused IT and development jobs',
                'website' => 'https://devitjobs.uk',
                'features' => ['UK jobs', 'IT focus', 'Development roles']
            ],
            'arbeitnow' => [
                'name' => 'Arbeitnow',
                'description' => 'European job board with diverse positions',
                'website' => 'https://arbeitnow.com',
                'features' => ['European jobs', 'Diverse roles', 'Remote options']
            ]
        ];
    }
    
    /**
     * Test API connectivity
     */
    public function testAPI(string $apiName): array
    {
        try {
            $jobs = $this->fetchFromAPI($apiName, 1);
            return [
                'success' => true,
                'message' => 'API is working correctly',
                'sample_job' => $jobs[0] ?? null
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'API test failed: ' . $e->getMessage(),
                'sample_job' => null
            ];
        }
    }
    
    /**
     * Get API statistics
     */
    public function getAPIStats(): array
    {
        $stats = [];
        
        foreach ($this->activeAPIs as $apiName => $isActive) {
            if (!$isActive) continue;
            
            try {
                $jobs = $this->fetchFromAPI($apiName, 100);
                $stats[$apiName] = [
                    'name' => $this->getSourceDisplayName($apiName),
                    'active' => true,
                    'job_count' => count($jobs),
                    'last_check' => date('Y-m-d H:i:s'),
                    'status' => 'online'
                ];
            } catch (\Throwable $e) {
                $stats[$apiName] = [
                    'name' => $this->getSourceDisplayName($apiName),
                    'active' => true,
                    'job_count' => 0,
                    'last_check' => date('Y-m-d H:i:s'),
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $stats;
    }
}
