<?php
declare(strict_types=1);

namespace Http;

class HttpClient {
    private int $timeoutSeconds;

    public function __construct(int $timeoutSeconds = 10) {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function getJson(string $url, array $headers = []): ?array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => array_merge([
                'Accept: application/json',
                'User-Agent: VarsityResourceCentre/1.0'
            ], $headers),
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return null;
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status < 200 || $status >= 300) {
            return null;
        }
        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
}


