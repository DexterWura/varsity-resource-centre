<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');

$base = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : [];
$features = $siteConfig['features'] ?? [];

$urls = [
    $base . '/index.php',
    $base . '/timetable.php',
];
try {
    if (($features['articles'] ?? true)) {
        $urls[] = $base . '/articles.php';
        $pdo = \Database\DB::pdo();
        $rows = $pdo->query('SELECT slug FROM articles WHERE status = "published" ORDER BY published_at DESC LIMIT 1000')->fetchAll() ?: [];
        foreach ($rows as $r) { $urls[] = $base . '/article.php?slug=' . urlencode($r['slug']); }
    }
    if (($features['houses'] ?? true)) {
        $urls[] = $base . '/houses.php';
        $pdo = $pdo ?? \Database\DB::pdo();
        $rows = $pdo->query('SELECT id FROM houses WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1000')->fetchAll() ?: [];
        foreach ($rows as $r) { $urls[] = $base . '/house.php?id=' . (int)$r['id']; }
    }
    if (($features['businesses'] ?? true)) {
        $urls[] = $base . '/businesses.php';
        $pdo = $pdo ?? \Database\DB::pdo();
        $rows = $pdo->query('SELECT id FROM businesses WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1000')->fetchAll() ?: [];
        foreach ($rows as $r) { $urls[] = $base . '/business.php?id=' . (int)$r['id']; }
    }
} catch (\Throwable $e) {}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($urls as $u): ?>
    <url><loc><?= htmlspecialchars($u) ?></loc></url>
    <?php endforeach; ?>
</urlset>


