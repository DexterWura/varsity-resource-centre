<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TimetableController.php';
use Security\Csrf;

$timetableController = new TimetableController();
$universities = $timetableController->getUniversities();
$selectedUniversity = isset($_POST['university']) ? $_POST['university'] : (isset($universities[0]) ? $universities[0]['key'] : null);
$faculties = $selectedUniversity ? $timetableController->getFaculties($selectedUniversity) : [];
$timetable = [];
$invalidModules = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        echo 'Invalid request.';
        exit;
    }
    $raw = isset($_POST['module_codes']) ? (string) $_POST['module_codes'] : '';
    $moduleCodes = array_map('trim', explode(',', strtoupper($raw)));

    $validModules = [];
    foreach ($moduleCodes as $code) {
        if ($code === '') { continue; }
        $moduleExists = $timetableController->checkModuleExists($selectedUniversity, $code);
        if ($moduleExists) {
            $validModules[] = $code;
        } else {
            $invalidModules[] = $code;
        }
    }

    if (!empty($validModules)) {
        $timetable = $timetableController->getTimetable($selectedUniversity, $validModules);
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

    <div class="text-center mb-4">
        <h1 class="h3 mt-2">Find Your Timetable</h1>
        <p class="text-muted mb-0">Select your university, enter module codes, get your schedule.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" class="bg-white p-4 shadow-sm rounded-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="university" class="form-label">University</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-school"></i></span>
                            <select name="university" id="university" class="form-select" required onchange="this.form.submit()">
                                <?php foreach ($universities as $uni): ?>
                                    <option value="<?= htmlspecialchars($uni['key']) ?>" <?= $selectedUniversity === $uni['key'] ? 'selected' : '' ?>><?= htmlspecialchars($uni['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="faculty" class="form-label">Faculty</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-building-columns"></i></span>
                            <select name="faculty" id="faculty" class="form-select">
                                <?php foreach ($faculties as $faculty): ?>
                                    <option value="<?= htmlspecialchars(isset($faculty['id']) && $faculty['id'] !== '' ? $faculty['id'] : (isset($faculty['code']) && $faculty['code'] !== '' ? $faculty['code'] : $faculty['name'])) ?>"><?= htmlspecialchars($faculty['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="module_codes" class="form-label">Module Codes</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-tags"></i></span>
                            <input type="text" name="module_codes" id="module_codes" class="form-control" placeholder="e.g. ACC101, ECO202, IMS303" value="<?= isset($_POST['module_codes']) ? htmlspecialchars($_POST['module_codes']) : '' ?>" required>
                        </div>
                        <div class="form-text">Enter codes separated by commas. Case-insensitive.</div>
                    </div>
                    <div class="col-12 d-grid d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-2"></i>Get Timetable</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($invalidModules)): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    The following module codes are invalid: <?= implode(', ', array_map('htmlspecialchars', $invalidModules)); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($timetable)): ?>
                <div class="card mt-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="fa-regular fa-calendar me-2"></i>Your Timetable
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Module Code</th>
                                        <th>Module Name</th>
                                        <th>Day</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Venue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($timetable as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['module_code']) ?></td>
                                            <td><?= htmlspecialchars($row['module_name']) ?></td>
                                            <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                                            <td><?= htmlspecialchars($row['start_time']) ?></td>
                                            <td><?= htmlspecialchars($row['end_time']) ?></td>
                                            <td><?= htmlspecialchars($row['venue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <form action="generate_ics.php" method="POST" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                    <input type="hidden" name="university" value="<?= htmlspecialchars($selectedUniversity) ?>">
                    <input type="hidden" name="module_codes" value="<?= htmlspecialchars(isset($_POST['module_codes']) ? $_POST['module_codes'] : '') ?>">
                    <button type="submit" class="btn btn-success"><i class="fa-regular fa-calendar-plus me-2"></i>Add to Calendar</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="min-height: 120px;">
                <div class="card-body d-flex align-items-center justify-content-center text-muted">
                    <span><i class="fa-solid fa-rectangle-ad me-2"></i>Ad space 300x100</span>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4" style="min-height: 250px;">
                <div class="card-body d-flex align-items-center justify-content-center text-muted">
                    <span><i class="fa-solid fa-rectangle-ad me-2"></i>Ad space 300x250</span>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>


