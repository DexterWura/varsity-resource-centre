<?php
require_once 'bootstrap.php';

use Timetable\TimetableManager;
use Auth\UserAuth;

$pageTitle = 'Timetable Builder';
$metaDescription = 'Create your personalized academic timetable by selecting your university, semester, faculty, and modules.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/timetable.php';

$userAuth = new UserAuth();
$timetableManager = new TimetableManager();

// Check if user is logged in
if (!$userAuth->check()) {
    header('Location: /login.php?redirect=' . urlencode('/timetable.php'));
    exit;
}

$user = $userAuth->user();
$step = $_GET['step'] ?? 1;
$timetableId = $_GET['timetable_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_timetable':
                $result = $timetableManager->createStudentTimetable(
                    $user->getId(),
                    (int)$_POST['university_id'],
                    (int)$_POST['semester_id'],
                    (int)$_POST['faculty_id'],
                    $_POST['timetable_name'],
                    $_POST['module_ids'] ?? []
                );
                
                if ($result['success']) {
                    header('Location: /timetable.php?step=5&timetable_id=' . $result['timetable_id']);
                    exit;
                } else {
                    $error = $result['error'];
                }
                break;
                
            case 'delete_timetable':
                $timetableManager->deleteStudentTimetable((int)$_POST['timetable_id'], $user->getId());
                header('Location: /timetable.php');
                exit;
                break;
        }
    }
}

// Get data based on current step
$universities = [];
$faculties = [];
$semesters = [];
$modules = [];
$conflicts = [];

if ($step >= 1) {
    $universities = $timetableManager->getUniversities();
}

if ($step >= 2 && isset($_GET['university_id'])) {
    $universityId = (int)$_GET['university_id'];
    $faculties = $timetableManager->getFacultiesByUniversity($universityId);
    $semesters = $timetableManager->getSemestersByUniversity($universityId);
}

if ($step >= 3 && isset($_GET['faculty_id'])) {
    $facultyId = (int)$_GET['faculty_id'];
    $modules = $timetableManager->getModulesByFaculty($facultyId);
}

if ($step >= 4 && isset($_GET['module_ids']) && isset($_GET['semester_id'])) {
    $moduleIds = array_map('intval', explode(',', $_GET['module_ids']));
    $semesterId = (int)$_GET['semester_id'];
    $conflicts = $timetableManager->checkScheduleConflicts($moduleIds, $semesterId);
}

// Get existing timetables
$existingTimetables = $timetableManager->getStudentTimetables($user->getId());

// Get timetable details if viewing
$timetableDetails = null;
if ($timetableId) {
    $timetableDetails = $timetableManager->getTimetableDetails((int)$timetableId);
    if ($timetableDetails && $timetableDetails['timetable']['user_id'] !== $user->getId()) {
        $timetableDetails = null; // User doesn't own this timetable
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fa-solid fa-calendar-alt text-primary me-2"></i>
                        Timetable Builder
                    </h1>
                    <p class="text-muted mb-0">Create your personalized academic timetable</p>
                </div>
                <div>
                    <a href="/timetable.php" class="btn btn-outline-primary">
                        <i class="fa-solid fa-plus me-1"></i>New Timetable
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Existing Timetables -->
    <?php if (!empty($existingTimetables) && $step == 1): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Your Timetables</h5>
            <div class="row">
                <?php foreach ($existingTimetables as $timetable): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($timetable['name']) ?></h6>
                            <p class="card-text small text-muted">
                                <i class="fa-solid fa-university me-1"></i>
                                <?= htmlspecialchars($timetable['university_name']) ?><br>
                                <i class="fa-solid fa-graduation-cap me-1"></i>
                                <?= htmlspecialchars($timetable['faculty_name']) ?><br>
                                <i class="fa-solid fa-calendar me-1"></i>
                                <?= htmlspecialchars($timetable['semester_name']) ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100" role="group">
                                <a href="/timetable.php?step=5&timetable_id=<?= $timetable['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-eye me-1"></i>View
                                </a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_timetable">
                                    <input type="hidden" name="timetable_id" value="<?= $timetable['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Are you sure you want to delete this timetable?')">
                                        <i class="fa-solid fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timetable Creation Steps -->
    <?php if ($step <= 4): ?>
    <div class="row">
        <div class="col-12">
            <!-- Progress Steps -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-3 text-center">
                            <div class="step <?= $step >= 1 ? 'active' : '' ?>">
                                <div class="step-number">1</div>
                                <div class="step-label">University</div>
                            </div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="step <?= $step >= 2 ? 'active' : '' ?>">
                                <div class="step-number">2</div>
                                <div class="step-label">Semester</div>
                            </div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="step <?= $step >= 3 ? 'active' : '' ?>">
                                <div class="step-number">3</div>
                                <div class="step-label">Faculty</div>
                            </div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                                <div class="step-number">4</div>
                                <div class="step-label">Modules</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step Content -->
            <div class="card">
                <div class="card-body">
                    <?php if ($step == 1): ?>
                        <!-- Step 1: University Selection -->
                        <h5 class="mb-4">Select Your University</h5>
                        <div class="row">
                            <?php foreach ($universities as $university): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="university-card" data-university-id="<?= $university['id'] ?>">
                                    <div class="card h-100 border-2">
                                        <div class="card-body text-center">
                                            <div class="university-logo mb-3">
                                                <i class="fa-solid fa-university fa-3x text-primary"></i>
                                            </div>
                                            <h6 class="card-title"><?= htmlspecialchars($university['name']) ?></h6>
                                            <p class="card-text small text-muted">
                                                <?= htmlspecialchars($university['code']) ?> • <?= htmlspecialchars($university['country']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($step == 2): ?>
                        <!-- Step 2: Semester Selection -->
                        <h5 class="mb-4">Select Semester</h5>
                        <div class="row">
                            <?php foreach ($semesters as $semester): ?>
                            <div class="col-md-6 mb-3">
                                <div class="semester-card" data-semester-id="<?= $semester['id'] ?>">
                                    <div class="card h-100 border-2">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <?= htmlspecialchars($semester['name']) ?>
                                                <?php if ($semester['is_current']): ?>
                                                    <span class="badge bg-success ms-2">Current</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="card-text small text-muted">
                                                <i class="fa-solid fa-calendar me-1"></i>
                                                <?= date('M j', strtotime($semester['start_date'])) ?> - 
                                                <?= date('M j, Y', strtotime($semester['end_date'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($step == 3): ?>
                        <!-- Step 3: Faculty Selection -->
                        <h5 class="mb-4">Select Your Faculty</h5>
                        <div class="row">
                            <?php foreach ($faculties as $faculty): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="faculty-card" data-faculty-id="<?= $faculty['id'] ?>">
                                    <div class="card h-100 border-2">
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($faculty['name']) ?></h6>
                                            <p class="card-text small text-muted">
                                                <?= htmlspecialchars($faculty['code']) ?>
                                            </p>
                                            <?php if ($faculty['description']): ?>
                                                <p class="card-text small"><?= htmlspecialchars($faculty['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($step == 4): ?>
                        <!-- Step 4: Module Selection -->
                        <h5 class="mb-4">Select Your Modules</h5>
                        
                        <?php if (!empty($conflicts)): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fa-solid fa-exclamation-triangle me-2"></i>Schedule Conflicts Detected</h6>
                            <p>The following modules have conflicting schedules:</p>
                            <ul class="mb-0">
                                <?php foreach ($conflicts as $conflict): ?>
                                <li>
                                    <strong><?= $conflict['day'] ?> at <?= $conflict['time'] ?>:</strong>
                                    <?= implode(', ', $conflict['modules']) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" id="timetableForm">
                            <input type="hidden" name="action" value="create_timetable">
                            <input type="hidden" name="university_id" value="<?= $_GET['university_id'] ?>">
                            <input type="hidden" name="semester_id" value="<?= $_GET['semester_id'] ?>">
                            <input type="hidden" name="faculty_id" value="<?= $_GET['faculty_id'] ?>">
                            
                            <div class="mb-4">
                                <label for="timetable_name" class="form-label">Timetable Name</label>
                                <input type="text" class="form-control" id="timetable_name" name="timetable_name" 
                                       value="My Timetable" required>
                            </div>

                            <div class="row">
                                <?php foreach ($modules as $module): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="module-card">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input module-checkbox" 
                                                           type="checkbox" 
                                                           name="module_ids[]" 
                                                           value="<?= $module['id'] ?>" 
                                                           id="module_<?= $module['id'] ?>">
                                                    <label class="form-check-label w-100" for="module_<?= $module['id'] ?>">
                                                        <h6 class="mb-1"><?= htmlspecialchars($module['name']) ?></h6>
                                                        <p class="mb-1 small text-muted">
                                                            <?= htmlspecialchars($module['code']) ?> • 
                                                            <?= $module['credits'] ?> credits
                                                        </p>
                                                        <?php if ($module['description']): ?>
                                                            <p class="mb-0 small"><?= htmlspecialchars($module['description']) ?></p>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa-solid fa-calendar-plus me-2"></i>Create Timetable
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($step == 5 && $timetableDetails): ?>
        <!-- Step 5: View Timetable -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($timetableDetails['timetable']['name']) ?></h5>
                                <small class="text-muted">
                                    <?= htmlspecialchars($timetableDetails['timetable']['university_name']) ?> • 
                                    <?= htmlspecialchars($timetableDetails['timetable']['faculty_name']) ?> • 
                                    <?= htmlspecialchars($timetableDetails['timetable']['semester_name']) ?>
                                </small>
                            </div>
                            <div>
                                <button class="btn btn-outline-primary" onclick="window.print()">
                                    <i class="fa-solid fa-print me-1"></i>Print
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $grid = $timetableManager->generateTimetableGrid($timetableDetails['schedules']);
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        $timeSlots = array_keys($grid['Monday']);
                        ?>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered timetable-grid">
                                <thead>
                                    <tr>
                                        <th class="time-header">Time</th>
                                        <?php foreach ($days as $day): ?>
                                        <th class="day-header"><?= $day ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($timeSlots as $time): ?>
                                    <tr>
                                        <td class="time-cell"><?= $time ?></td>
                                        <?php foreach ($days as $day): ?>
                                        <td class="schedule-cell">
                                            <?php if ($grid[$day][$time]): ?>
                                                <?php $schedule = $grid[$day][$time]; ?>
                                                <div class="schedule-item" 
                                                     style="background-color: <?= $this->getScheduleColor($schedule['schedule_type']) ?>">
                                                    <div class="schedule-title"><?= htmlspecialchars($schedule['module_name']) ?></div>
                                                    <div class="schedule-details">
                                                        <?= htmlspecialchars($schedule['module_code']) ?> • 
                                                        <?= htmlspecialchars($schedule['schedule_type']) ?>
                                                    </div>
                                                    <?php if ($schedule['venue']): ?>
                                                        <div class="schedule-venue">
                                                            <i class="fa-solid fa-map-marker-alt me-1"></i>
                                                            <?= htmlspecialchars($schedule['venue']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($schedule['lecturer']): ?>
                                                        <div class="schedule-lecturer">
                                                            <i class="fa-solid fa-user me-1"></i>
                                                            <?= htmlspecialchars($schedule['lecturer']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.step {
    position: relative;
    margin-bottom: 1rem;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: bold;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background-color: var(--bs-primary);
    color: white;
}

.step-label {
    font-size: 0.875rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.step.active .step-label {
    color: var(--bs-primary);
    font-weight: 600;
}

.university-card, .semester-card, .faculty-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.university-card .card, .semester-card .card, .faculty-card .card {
    transition: all 0.3s ease;
}

.university-card:hover .card, .semester-card:hover .card, .faculty-card:hover .card {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-color: var(--bs-primary) !important;
}

.module-card .card {
    transition: all 0.3s ease;
}

.module-card .form-check-input:checked + .form-check-label .card {
    border-color: var(--bs-primary) !important;
    background-color: rgba(13, 110, 253, 0.05);
}

.timetable-grid {
    font-size: 0.875rem;
}

.time-header, .day-header {
    background-color: #f8f9fa;
    font-weight: 600;
    text-align: center;
    padding: 0.75rem 0.5rem;
}

.time-cell {
    background-color: #f8f9fa;
    font-weight: 500;
    text-align: center;
    padding: 0.5rem;
    width: 80px;
}

.schedule-cell {
    padding: 2px;
    height: 60px;
    vertical-align: top;
}

.schedule-item {
    padding: 4px 6px;
    border-radius: 4px;
    color: white;
    font-size: 0.75rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.schedule-title {
    font-weight: 600;
    margin-bottom: 2px;
}

.schedule-details, .schedule-venue, .schedule-lecturer {
    font-size: 0.7rem;
    opacity: 0.9;
}

@media print {
    .btn, .card-header .d-flex > div:last-child {
        display: none !important;
    }
    
    .timetable-grid {
        font-size: 0.75rem;
    }
    
    .schedule-item {
        font-size: 0.7rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // University selection
    document.querySelectorAll('.university-card').forEach(card => {
        card.addEventListener('click', function() {
            const universityId = this.dataset.universityId;
            window.location.href = `/timetable.php?step=2&university_id=${universityId}`;
        });
    });

    // Semester selection
    document.querySelectorAll('.semester-card').forEach(card => {
        card.addEventListener('click', function() {
            const semesterId = this.dataset.semesterId;
            const universityId = new URLSearchParams(window.location.search).get('university_id');
            window.location.href = `/timetable.php?step=3&university_id=${universityId}&semester_id=${semesterId}`;
        });
    });

    // Faculty selection
    document.querySelectorAll('.faculty-card').forEach(card => {
        card.addEventListener('click', function() {
            const facultyId = this.dataset.facultyId;
            const urlParams = new URLSearchParams(window.location.search);
            const universityId = urlParams.get('university_id');
            const semesterId = urlParams.get('semester_id');
            window.location.href = `/timetable.php?step=4&university_id=${universityId}&semester_id=${semesterId}&faculty_id=${facultyId}`;
        });
    });

    // Module selection with conflict checking
    document.querySelectorAll('.module-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectedModules = Array.from(document.querySelectorAll('.module-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedModules.length > 0) {
                const urlParams = new URLSearchParams(window.location.search);
                const semesterId = urlParams.get('semester_id');
                
                // Check for conflicts
                fetch('/api/check-conflicts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        module_ids: selectedModules,
                        semester_id: semesterId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.conflicts && data.conflicts.length > 0) {
                        // Show conflict warning
                        showConflictWarning(data.conflicts);
                    } else {
                        hideConflictWarning();
                    }
                })
                .catch(error => {
                    console.error('Error checking conflicts:', error);
                });
            } else {
                hideConflictWarning();
            }
        });
    });
});

function showConflictWarning(conflicts) {
    let warningHtml = `
        <div class="alert alert-warning" id="conflictWarning">
            <h6><i class="fa-solid fa-exclamation-triangle me-2"></i>Schedule Conflicts Detected</h6>
            <p>The following modules have conflicting schedules:</p>
            <ul class="mb-0">
    `;
    
    conflicts.forEach(conflict => {
        warningHtml += `<li><strong>${conflict.day} at ${conflict.time}:</strong> ${conflict.modules.join(', ')}</li>`;
    });
    
    warningHtml += `</ul></div>`;
    
    const existingWarning = document.getElementById('conflictWarning');
    if (existingWarning) {
        existingWarning.remove();
    }
    
    const form = document.getElementById('timetableForm');
    form.insertAdjacentHTML('afterbegin', warningHtml);
}

function hideConflictWarning() {
    const existingWarning = document.getElementById('conflictWarning');
    if (existingWarning) {
        existingWarning.remove();
    }
}
</script>

<?php
// Helper function for schedule colors
function getScheduleColor($type) {
    $colors = [
        'Lecture' => '#007bff',
        'Tutorial' => '#28a745',
        'Practical' => '#ffc107',
        'Seminar' => '#dc3545'
    ];
    return $colors[$type] ?? '#6c757d';
}

include 'includes/footer.php';
?>