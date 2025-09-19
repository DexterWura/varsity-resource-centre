<?php include __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/lib/http.php'; ?>

<?php
// Crossref works API: query for student-related topics
$query = isset($_GET['q']) ? trim($_GET['q']) : 'student university Zimbabwe';
$rows = 20;
$apiUrl = 'https://api.crossref.org/works?query=' . urlencode($query) . '&rows=' . $rows;
$data = fetch_json($apiUrl);
$items = $data['message']['items'] ?? [];
?>

    <h1 class="h4 mb-3">Articles</h1>
    <form class="mb-3" method="get">
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" class="form-control" placeholder="Search topics (e.g., scholarships, research methods)">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <div class="list-group">
        <?php foreach ($items as $it): ?>
            <?php
                $title = is_array($it['title'] ?? null) ? ($it['title'][0] ?? 'Untitled') : ($it['title'] ?? 'Untitled');
                $doi = $it['DOI'] ?? '';
                $url = !empty($it['URL']) ? $it['URL'] : (!empty($doi) ? 'https://doi.org/' . rawurlencode($doi) : '#');
                $container = $it['container-title'][0] ?? '';
                $year = $it['issued']['"date-parts"'][0][0] ?? ($it['issued']['date-parts'][0][0] ?? '');
            ?>
            <a class="list-group-item list-group-item-action" target="_blank" rel="noopener" href="<?= htmlspecialchars($url) ?>">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($title) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($year) ?></small>
                </div>
                <small class="text-muted"><?= htmlspecialchars($container) ?></small>
            </a>
        <?php endforeach; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>


