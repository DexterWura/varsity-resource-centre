<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$pageTitle = 'Settings';
include __DIR__ . '/_layout_start.php';

$storageFile = __DIR__ . '/../storage/app.php';
$siteConfig = is_file($storageFile) ? (include $storageFile) : [];

// Defaults
$siteConfig['site_name'] = $siteConfig['site_name'] ?? 'Varsity Resource Centre';
$siteConfig['theme'] = $siteConfig['theme'] ?? ['primary' => '#0d6efd'];
$siteConfig['features'] = $siteConfig['features'] ?? [
	'articles' => true,
	'houses' => true,
	'businesses' => true,
	'news' => true,
	'jobs' => true,
];

$saved = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		// Update values
		$siteConfig['site_name'] = trim($_POST['site_name'] ?? $siteConfig['site_name']);
		$siteConfig['theme']['primary'] = trim($_POST['theme_primary'] ?? $siteConfig['theme']['primary']);
		$siteConfig['features']['articles'] = isset($_POST['feat_articles']);
		$siteConfig['features']['houses'] = isset($_POST['feat_houses']);
		$siteConfig['features']['businesses'] = isset($_POST['feat_businesses']);
		$siteConfig['features']['news'] = isset($_POST['feat_news']);
		$siteConfig['features']['jobs'] = isset($_POST['feat_jobs']);

		// Ensure storage dir
		$dir = dirname($storageFile);
		if (!is_dir($dir)) { @mkdir($dir, 0777, true); }

		// Write PHP config file safely
		$content = "<?php\nreturn " . var_export($siteConfig, true) . ";\n";
		if (file_put_contents($storageFile, $content) === false) {
			throw new \RuntimeException('Failed to write settings file.');
		}
		$saved = true;
	} catch (\Throwable $e) {
		$error = $e->getMessage();
	}
}
?>

<div class="card">
	<div class="card-body">
		<?php if ($saved): ?><div class="alert alert-success">Settings saved.</div><?php endif; ?>
		<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
		<form method="post">
			<div class="mb-3">
				<label class="form-label">Site name</label>
				<input name="site_name" class="form-control" value="<?= htmlspecialchars($siteConfig['site_name']) ?>">
			</div>
			<div class="mb-3">
				<label class="form-label">Primary color</label>
				<input name="theme_primary" class="form-control" value="<?= htmlspecialchars($siteConfig['theme']['primary']) ?>">
			</div>
			<h6 class="mt-3">Features</h6>
			<div class="row g-2">
				<div class="col-md-4 form-check">
					<input class="form-check-input" type="checkbox" id="feat_articles" name="feat_articles" <?= $siteConfig['features']['articles'] ? 'checked' : '' ?>>
					<label class="form-check-label" for="feat_articles">Articles</label>
				</div>
				<div class="col-md-4 form-check">
					<input class="form-check-input" type="checkbox" id="feat_houses" name="feat_houses" <?= $siteConfig['features']['houses'] ? 'checked' : '' ?>>
					<label class="form-check-label" for="feat_houses">Houses</label>
				</div>
				<div class="col-md-4 form-check">
					<input class="form-check-input" type="checkbox" id="feat_businesses" name="feat_businesses" <?= $siteConfig['features']['businesses'] ? 'checked' : '' ?>>
					<label class="form-check-label" for="feat_businesses">Businesses</label>
				</div>
				<div class="col-md-4 form-check">
					<input class="form-check-input" type="checkbox" id="feat_news" name="feat_news" <?= $siteConfig['features']['news'] ? 'checked' : '' ?>>
					<label class="form-check-label" for="feat_news">News</label>
				</div>
				<div class="col-md-4 form-check">
					<input class="form-check-input" type="checkbox" id="feat_jobs" name="feat_jobs" <?= $siteConfig['features']['jobs'] ? 'checked' : '' ?>>
					<label class="form-check-label" for="feat_jobs">Jobs</label>
				</div>
			</div>
			<div class="mt-3">
				<button class="btn btn-primary" type="submit">Save Settings</button>
			</div>
		</form>
	</div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>


