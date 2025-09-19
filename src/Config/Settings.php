<?php
declare(strict_types=1);

namespace Config;

class Settings {
    private string $file;
    private array $cache = [];

    public function __construct(string $file) {
        $this->file = $file;
        $dir = dirname($this->file);
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        if (!is_file($this->file)) { @file_put_contents($this->file, json_encode($this->defaultSettings(), JSON_PRETTY_PRINT)); }
        $this->cache = $this->load();
    }

    public function get(string $key, $default = null) {
        return $this->cache[$key] ?? $default;
    }

    public function all(): array {
        return $this->cache;
    }

    public function setMany(array $values): void {
        $this->cache = array_merge($this->cache, $values);
        @file_put_contents($this->file, json_encode($this->cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function load(): array {
        $raw = @file_get_contents($this->file);
        $data = $raw ? json_decode($raw, true) : [];
        return is_array($data) ? $data : $this->defaultSettings();
    }

    private function defaultSettings(): array {
        return [
            'adsense_client' => '',
            'adsense_slot_header' => '',
            'adsense_slot_sidebar' => '',
            'notifications' => [
                ['message' => 'Welcome to Varsity Resource Centre!', 'type' => 'info']
            ],
            'theme' => [
                'primary' => '#0d6efd',
                'secondary' => '#6c757d',
                'background' => '#f8f9fa',
            ],
        ];
    }
}


