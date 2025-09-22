<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
<?php if (!(($siteConfig['features']['articles'] ?? true))): include __DIR__ . '/errors/404.php'; exit; endif; ?>
<?php use Content\Article; ?>

<?php
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$article = $slug !== '' ? Article::findBySlug($slug) : null;
if (!$article) {
	header('Location: /404.php');
	exit;
}
// Set page meta
$pageTitle = $article->getMetaTitle() ?: $article->getTitle();
$metaDescription = $article->getMetaDescription() ?: ($article->getExcerpt() ?: '');
$metaImage = $article->getFeaturedImage() ?: '';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/article.php?slug=' . urlencode($article->getSlug());
$structuredDataJson = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article->getTitle(),
    'author' => [ '@type' => 'Person', 'name' => $article->getAuthorName() ?: ('Author #' . $article->getAuthorId()) ],
    'datePublished' => $article->getPublishedAt() ?: $article->getCreatedAt(),
    'dateModified' => $article->getUpdatedAt(),
    'image' => $article->getFeaturedImage() ?: null,
]);
?>
<?php include __DIR__ . '/includes/header.php'; ?>

    <article class="mx-auto" style="max-width: 860px;">
        <header class="mb-3">
            <h1 class="h3 mb-1"><?= htmlspecialchars($article->getTitle()) ?></h1>
            <div class="text-muted small d-flex align-items-center gap-2">
                <span><i class="fa-regular fa-user me-1"></i> <?= htmlspecialchars($article->getAuthorName() ?: ('Author #' . $article->getAuthorId())) ?></span>
                <?php if ($article->getPublishedAt()): ?>
                    <span>·</span>
                    <time datetime="<?= htmlspecialchars($article->getPublishedAt()) ?>"><?= date('M j, Y', strtotime($article->getPublishedAt())) ?></time>
                <?php endif; ?>
            </div>
        </header>
        <?php if ($article->getFeaturedImage()): ?>
            <img src="<?= htmlspecialchars($article->getFeaturedImage()) ?>" alt="<?= htmlspecialchars($article->getTitle()) ?>" class="img-fluid rounded mb-3">
        <?php endif; ?>
        <div class="border rounded p-3" style="white-space: pre-wrap;">
            <?= nl2br(htmlspecialchars($article->getContent())) ?>
        </div>
    </article>

    <?php
    // Related articles: latest 4 excluding current
    $related = [];
    try {
		$pdo = \Database\DB::pdo();
		$stmt = $pdo->prepare('SELECT title, slug, published_at FROM articles WHERE status = "published" AND id != ? ORDER BY published_at DESC LIMIT 4');
		$stmt->execute([$article->getId()]);
		$related = $stmt->fetchAll();
    } catch (\Throwable $e) { $related = []; }
    ?>
    <?php if (!empty($related)): ?>
    <section class="mx-auto mt-4" style="max-width: 860px;">
        <h2 class="h6 mb-3">Related articles</h2>
        <div class="list-group">
            <?php foreach ($related as $r): ?>
                <a class="list-group-item list-group-item-action" href="article.php?slug=<?= urlencode($r['slug']) ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <span><?= htmlspecialchars($r['title']) ?></span>
                        <small class="text-muted"><?= !empty($r['published_at']) ? date('M j, Y', strtotime($r['published_at'])) : '' ?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>



