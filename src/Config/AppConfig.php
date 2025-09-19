<?php
declare(strict_types=1);

namespace Config;

class AppConfig {
    private string $file;
    private array $data = [];

    public function __construct(string $file) {
        $this->file = $file;
        $dir = dirname($this->file);
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        if (is_file($this->file)) {
            $raw = @file_get_contents($this->file);
            $this->data = $raw ? (include $this->file) : [];
            if (!is_array($this->data)) { $this->data = []; }
        }
    }

    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    public function all(): array { return $this->data; }

    public function setMany(array $values): void {
        $this->data = array_merge($this->data, $values);
        $export = var_export($this->data, true);
        @file_put_contents($this->file, "<?php\nreturn " . $export . ";\n");
    }
}


