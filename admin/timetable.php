<?php
require_once '../bootstrap.php';

use Timetable\TimetableManager;
use Auth\UserAuth;

$userAuth = new UserAuth();

// Check if user is admin
if (!$userAuth->check() || !$userAuth->user()->hasRole('admin')) {
    header('Location: /admin/login.php');
    exit;
}

$timetableManager = new TimetableManager();
$action = $_GET['action'] ?? 'dashboard';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'add_university':
            // Add university logic here
            break;
        case 'add_faculty':
            // Add faculty logic here
            break;
        case 'add_module':
            // Add module logic here
            break;
    }
}

$pageTitle = 'Timetable Management';
include '../admin/_layout_start.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fa-solid fa-calendar-alt text-primary me-2"></i>
                    Timetable Management
                </h1>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" id="timetableTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="universities-tab" data-bs-toggle="tab" 
                            data-bs-target="#universities" type="button" role="tab">
                        <i class="fa-solid fa-university me-2"></i>Universities
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="faculties-tab" data-bs-toggle="tab" 
                            data-bs-target="#faculties" type="button" role="tab">
                        <i class="fa-solid fa-graduation-cap me-2"></i>Faculties
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="modules-tab" data-bs-toggle="tab" 
                            data-bs-target="#modules" type="button" role="tab">
                        <i class="fa-solid fa-book me-2"></i>Modules
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="schedules-tab" data-bs-toggle="tab" 
                            data-bs-target="#schedules" type="button" role="tab">
                        <i class="fa-solid fa-clock me-2"></i>Schedules
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="timetableTabContent">
                <!-- Universities Tab -->
                <div class="tab-pane fade show active" id="universities" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Universities</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUniversityModal">
                                    <i class="fa-solid fa-plus me-1"></i>Add University
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Country</th>
                                            <th>Website</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $universities = $timetableManager->getUniversities();
                                        foreach ($universities as $university):
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($university['name']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($university['code']) ?></span></td>
                                            <td><?= htmlspecialchars($university['country']) ?></td>
                                            <td>
                                                <?php if ($university['website']): ?>
                                                    <a href="<?= htmlspecialchars($university['website']) ?>" target="_blank">
                                                        <i class="fa-solid fa-external-link-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $university['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $university['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Faculties Tab -->
                <div class="tab-pane fade" id="faculties" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Faculties</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                                    <i class="fa-solid fa-plus me-1"></i>Add Faculty
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                $universities = $timetableManager->getUniversities();
                                foreach ($universities as $university):
                                    $faculties = $timetableManager->getFacultiesByUniversity($university['id']);
                                ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><?= htmlspecialchars($university['name']) ?></h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($faculties)): ?>
                                                <p class="text-muted mb-0">No faculties added yet.</p>
                                            <?php else: ?>
                                                <ul class="list-unstyled mb-0">
                                                    <?php foreach ($faculties as $faculty): ?>
                                                    <li class="mb-2">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong><?= htmlspecialchars($faculty['name']) ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?= htmlspecialchars($faculty['code']) ?></small>
                                                            </div>
                                                            <div>
                                                                <button class="btn btn-sm btn-outline-primary">
                                                                    <i class="fa-solid fa-edit"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modules Tab -->
                <div class="tab-pane fade" id="modules" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Modules</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                                    <i class="fa-solid fa-plus me-1"></i>Add Module
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Faculty</th>
                                            <th>Credits</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $universities = $timetableManager->getUniversities();
                                        foreach ($universities as $university):
                                            $faculties = $timetableManager->getFacultiesByUniversity($university['id']);
                                            foreach ($faculties as $faculty):
                                                $modules = $timetableManager->getModulesByFaculty($faculty['id']);
                                                foreach ($modules as $module):
                                        ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($module['code']) ?></span></td>
                                            <td><?= htmlspecialchars($module['name']) ?></td>
                                            <td>
                                                <small>
                                                    <?= htmlspecialchars($faculty['name']) ?><br>
                                                    <span class="text-muted"><?= htmlspecialchars($university['name']) ?></span>
                                                </small>
                                            </td>
                                            <td><?= $module['credits'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= $module['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $module['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                                endforeach;
                                            endforeach;
                                        endforeach;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedules Tab -->
                <div class="tab-pane fade" id="schedules" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Module Schedules</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                    <i class="fa-solid fa-plus me-1"></i>Add Schedule
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Schedule management interface will be implemented here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add University Modal -->
<div class="modal fade" id="addUniversityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add University</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_university">
                    <div class="mb-3">
                        <label for="university_name" class="form-label">University Name</label>
                        <input type="text" class="form-control" id="university_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="university_code" class="form-label">Code</label>
                        <input type="text" class="form-control" id="university_code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="university_country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="university_country" name="country" value="Zimbabwe">
                    </div>
                    <div class="mb-3">
                        <label for="university_website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="university_website" name="website">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add University</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../admin/_layout_end.php'; ?>
