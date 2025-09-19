<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Database\DB;

header('Content-Type: text/plain');

$type = $_GET['type'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read form-encoded body (sendBeacon payload)
    $raw = file_get_contents('php://input') ?: '';
    parse_str($raw, $post);
} else {
    $post = $_POST;
}

try {
    $pdo = DB::pdo();
    if ($type === 'article') {
        $title = trim((string)($post['title'] ?? ''));
        $url = trim((string)($post['url'] ?? ''));
        if ($url !== '') {
            $stmt = $pdo->prepare('INSERT INTO popular_articles (title, source_url, clicks_count, last_clicked_at) VALUES (:t,:u,1,NOW()) ON DUPLICATE KEY UPDATE title = VALUES(title), clicks_count = clicks_count + 1, last_clicked_at = NOW()');
            $stmt->execute([':t' => $title, ':u' => $url]);
        }
    } elseif ($type === 'job') {
        $title = trim((string)($post['title'] ?? ''));
        $url = trim((string)($post['url'] ?? ''));
        $company = trim((string)($post['company'] ?? ''));
        if ($url !== '') {
            $stmt = $pdo->prepare('INSERT INTO popular_jobs (title, company_name, source_url, clicks_count, last_clicked_at) VALUES (:t,:c,:u,1,NOW()) ON DUPLICATE KEY UPDATE title = VALUES(title), company_name = VALUES(company_name), clicks_count = clicks_count + 1, last_clicked_at = NOW()');
            $stmt->execute([':t' => $title, ':c' => $company, ':u' => $url]);
        }
    }
    http_response_code(204);
    exit;
} catch (Throwable $e) {
    http_response_code(200);
    echo 'ok';
}


