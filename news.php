<?php include __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php use Http\HttpClient; ?>

<?php
// HN Algolia API as a free source for tech/student-relevant news
$query = isset($_GET['q']) ? trim($_GET['q']) : 'student university scholarship internship campus';
$apiUrl = 'https://hn.algolia.com/api/v1/search?query=' . urlencode($query) . '&tags=story';
$client = new HttpClient();
$data = $client->getJson($apiUrl);
$hits = $data['hits'] ?? [];
?>

    <h1 class="h4 mb-3">Student News</h1>
    <form class="mb-3" method="get">
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" class="form-control" placeholder="Search student news (e.g., scholarships, internships)">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <div class="list-group">
        <?php foreach ($hits as $hit): ?>
            <?php $url = !empty($hit['url']) ? $hit['url'] : ('https://news.ycombinator.com/item?id=' . $hit['objectID']); ?>
            <a class="list-group-item list-group-item-action" target="_blank" rel="noopener" href="<?= htmlspecialchars($url) ?>">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($hit['title'] ?? 'Untitled') ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($hit['author'] ?? '') ?></small>
                </div>
                <small class="text-muted"><?= htmlspecialchars(parse_url($url, PHP_URL_HOST) ?: '') ?></small>
            </a>
        <?php endforeach; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>


