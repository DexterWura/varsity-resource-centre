<?php
declare(strict_types=1);

namespace Database;

use Database\DB;
use PDO;

class MigrationRunner
{
    private PDO $pdo;
    private string $migrationPath;
    private string $migrationTable = 'flyway_schema_history';

    public function __construct()
    {
        $this->pdo = DB::pdo();
        $this->migrationPath = __DIR__ . '/../../db/migration';
        $this->ensureMigrationTable();
    }

    private function ensureMigrationTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationTable} (
            installed_rank INT NOT NULL,
            version VARCHAR(50),
            description VARCHAR(200) NOT NULL,
            type VARCHAR(20) NOT NULL,
            script VARCHAR(1000) NOT NULL,
            checksum INT,
            installed_by VARCHAR(100) NOT NULL,
            installed_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            execution_time INT NOT NULL,
            success TINYINT(1) NOT NULL,
            PRIMARY KEY (installed_rank)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->pdo->exec($sql);
    }

    public function getMigrationFiles(): array
    {
        $files = glob($this->migrationPath . '/V*.sql');
        sort($files);
        return $files;
    }

    public function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->prepare("SELECT version FROM {$this->migrationTable} WHERE success = 1 ORDER BY installed_rank");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getPendingMigrations(): array
    {
        $allFiles = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        
        $pending = [];
        foreach ($allFiles as $file) {
            $version = $this->extractVersionFromFilename(basename($file));
            if (!in_array($version, $executed)) {
                $pending[] = [
                    'file' => $file,
                    'version' => $version,
                    'description' => $this->extractDescriptionFromFilename(basename($file))
                ];
            }
        }
        
        return $pending;
    }

    private function extractVersionFromFilename(string $filename): string
    {
        if (preg_match('/V(\d+)__/', $filename, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function extractDescriptionFromFilename(string $filename): string
    {
        if (preg_match('/V\d+__(.+)\.sql/', $filename, $matches)) {
            return str_replace('_', ' ', $matches[1]);
        }
        return 'Unknown migration';
    }

    public function runMigration(string $file): array
    {
        $version = $this->extractVersionFromFilename(basename($file));
        $description = $this->extractDescriptionFromFilename(basename($file));
        
        $startTime = microtime(true);
        $success = false;
        $error = null;
        
        try {
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Read and execute SQL
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \Exception("Could not read migration file: {$file}");
            }
            
            // Split SQL into individual statements
            $statements = $this->splitSqlStatements($sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $this->pdo->exec($statement);
                }
            }
            
            $this->pdo->commit();
            $success = true;
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $error = $e->getMessage();
        }
        
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        
        // Record migration in history
        $this->recordMigration($version, $description, $file, $executionTime, $success, $error);
        
        return [
            'success' => $success,
            'error' => $error,
            'execution_time' => $executionTime,
            'version' => $version,
            'description' => $description
        ];
    }

    private function splitSqlStatements(string $sql): array
    {
        // Remove comments and split by semicolon
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar) {
                $inString = false;
            } elseif (!$inString && $char === ';') {
                $statements[] = $current;
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $statements[] = $current;
        }
        
        return $statements;
    }

    private function recordMigration(string $version, string $description, string $script, int $executionTime, bool $success, ?string $error): void
    {
        $installedRank = $this->getNextInstalledRank();
        $checksum = crc32(file_get_contents($script));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->migrationTable} 
            (installed_rank, version, description, type, script, checksum, installed_by, execution_time, success) 
            VALUES (?, ?, ?, 'SQL', ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $installedRank,
            $version,
            $description,
            basename($script),
            $checksum,
            'admin',
            $executionTime,
            $success ? 1 : 0
        ]);
    }

    private function getNextInstalledRank(): int
    {
        $stmt = $this->pdo->prepare("SELECT MAX(installed_rank) FROM {$this->migrationTable}");
        $stmt->execute();
        $max = $stmt->fetchColumn();
        return ($max ?: 0) + 1;
    }

    public function runAllPendingMigrations(): array
    {
        $pending = $this->getPendingMigrations();
        $results = [];
        
        foreach ($pending as $migration) {
            $result = $this->runMigration($migration['file']);
            $results[] = [
                'version' => $migration['version'],
                'description' => $migration['description'],
                'file' => basename($migration['file']),
                'success' => $result['success'],
                'error' => $result['error'],
                'execution_time' => $result['execution_time']
            ];
            
            // Stop on first failure
            if (!$result['success']) {
                break;
            }
        }
        
        return $results;
    }

    public function getMigrationHistory(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT version, description, script, installed_on, execution_time, success 
            FROM {$this->migrationTable} 
            ORDER BY installed_rank DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validateMigrations(): array
    {
        $issues = [];
        $executed = $this->getExecutedMigrations();
        
        foreach ($executed as $version) {
            $file = $this->migrationPath . "/V{$version}__*.sql";
            $files = glob($file);
            
            if (empty($files)) {
                $issues[] = "Migration file for version {$version} not found";
            } else {
                $file = $files[0];
                $currentChecksum = crc32(file_get_contents($file));
                
                $stmt = $this->pdo->prepare("SELECT checksum FROM {$this->migrationTable} WHERE version = ?");
                $stmt->execute([$version]);
                $storedChecksum = $stmt->fetchColumn();
                
                if ($currentChecksum !== $storedChecksum) {
                    $issues[] = "Checksum mismatch for version {$version}";
                }
            }
        }
        
        return $issues;
    }
}
