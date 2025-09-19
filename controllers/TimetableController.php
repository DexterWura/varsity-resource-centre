<?php
class TimetableController {

    private $universities;

    public function __construct() {
        $this->universities = require __DIR__ . '/config/universities.php';
    }

    private function getUniversityConfig($universityKey) {
        if (!isset($this->universities[$universityKey])) {
            throw new InvalidArgumentException('Unknown university key');
        }
        return $this->universities[$universityKey];
    }

    private function fetchCsv(string $url): array {
        if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->info('Fetching CSV', ['url' => $url]); }
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "Accept: text/csv\r\n",
            ],
        ]);
        $csv = @file_get_contents($url, false, $context);
        if ($csv === false) {
            if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->error('CSV fetch failed', ['url' => $url]); }
            return [];
        }
        $rows = array_map('str_getcsv', preg_split('/\r\n|\n|\r/', trim($csv)));
        if (empty($rows)) {
            if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->warning('CSV empty', ['url' => $url]); }
            return [];
        }
        $headers = array_map('trim', array_shift($rows));
        $data = [];
        foreach ($rows as $row) {
            if (count($row) !== count($headers)) {
                if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->warning('CSV row skipped due to header mismatch', ['url' => $url]); }
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = isset($row[$i]) ? trim($row[$i]) : '';
            }
            $data[] = $assoc;
        }
        return $data;
    }

    public function getUniversities(): array {
        $list = [];
        foreach ($this->universities as $key => $config) {
            $list[] = ['key' => $key, 'name' => $config['name']];
        }
        return $list;
    }

    public function getFaculties(string $universityKey): array {
        $config = $this->getUniversityConfig($universityKey);
        return $this->fetchCsv($config['faculties_url']);
    }

    public function checkModuleExists(string $universityKey, string $moduleCode): bool {
        $config = $this->getUniversityConfig($universityKey);
        $modules = $this->fetchCsv($config['modules_url']);
        $needle = strtoupper(trim($moduleCode));
        foreach ($modules as $module) {
            if (strtoupper($module['module_code']) === $needle) {
                return true;
            }
        }
        return false;
    }

    public function getTimetable(string $universityKey, array $moduleCodes): array {
        $config = $this->getUniversityConfig($universityKey);
        $modules = $this->fetchCsv($config['modules_url']);
        $timetableRows = $this->fetchCsv($config['timetable_url']);

        $codeToName = [];
        foreach ($modules as $module) {
            $codeToName[strtoupper($module['module_code'])] = $module['module_name'];
        }

        $wanted = array_map(function ($c) { return strtoupper(trim($c)); }, $moduleCodes);
        $wanted = array_values(array_filter($wanted));
        $wantedSet = array_flip($wanted);

        $results = [];
        foreach ($timetableRows as $row) {
            $code = strtoupper($row['module_code'] ?? '');
            if (!isset($wantedSet[$code])) {
                continue;
            }
            $results[] = [
                'module_code' => $code,
                'module_name' => $codeToName[$code] ?? '',
                'day_of_week' => $row['day_of_week'] ?? '',
                'start_time' => $row['start_time'] ?? '',
                'end_time' => $row['end_time'] ?? '',
                'venue' => $row['venue'] ?? '',
            ];
        }
        return $results;
    }
}
