<?php

function fetch_json(string $url, array $headers = [], int $timeoutSeconds = 10) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_HTTPHEADER => array_merge([
            'Accept: application/json',
            'User-Agent: VarsityResourceCentre/1.0 (+https://dextersoft.com)'
        ], $headers),
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        curl_close($ch);
        return null;
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status < 200 || $status >= 300) {
        return null;
    }
    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}


