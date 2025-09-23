<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Auth\UserAuth;
use Security\Csrf;

$userAuth = new UserAuth();
$userAuth->requireAuth();

$user = $userAuth->user();

// Redirect admin users to admin dashboard
if ($user->hasRole('admin')) {
    header('Location: /admin/dashboard.php');
    exit;
}

$pageTitle = 'User Profile';
$metaDescription = 'Manage your user profile and account settings.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/profile.php';

$successMessage = '';
$errorMessage = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($csrf)) {
            $errorMessage = 'Invalid request token.';
        } else {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            
            if (empty($fullName) || empty($email)) {
                $errorMessage = 'Full name and email are required.';
            } else {
                try {
                    // Update user profile
                    $pdo = \Database\DB::pdo();
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                    $stmt->execute([$fullName, $email, $user->getId()]);
                    
                    $successMessage = 'Profile updated successfully!';
                    
                    // Refresh user data
                    $user = $userAuth->user();
                } catch (Exception $e) {
                    $errorMessage = 'Failed to update profile: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'change_password') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($csrf)) {
            $errorMessage = 'Invalid request token.';
        } else {
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errorMessage = 'All password fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 6) {
                $errorMessage = 'New password must be at least 6 characters long.';
            } else {
                try {
                    // Verify current password
                    if (!password_verify($currentPassword, $user->getPasswordHash())) {
                        $errorMessage = 'Current password is incorrect.';
                    } else {
                        // Update password
                        $pdo = \Database\DB::pdo();
                        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user->getId()]);
                        
                        $successMessage = 'Password changed successfully!';
                    }
                } catch (Exception $e) {
                    $errorMessage = 'Failed to change password: ' . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold text-primary">
                        <i class="fa-solid fa-user-circle me-2"></i>User Profile
                    </h2>
                    
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errorMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Profile Information -->
                    <div class="mb-4">
                        <h5 class="fw-bold text-secondary mb-3">
                            <i class="fa-solid fa-info-circle me-2"></i>Profile Information
                        </h5>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user->getFullName()) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user->getEmail()) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">User ID</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user->getId()) ?>" readonly>
                                <small class="form-text text-muted">This is your unique user identifier.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Account Status</label>
                                <input type="text" class="form-control" value="<?= $user->isActive() ? 'Active' : 'Inactive' ?>" readonly>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Change Password -->
                    <div class="mb-4">
                        <h5 class="fw-bold text-secondary mb-3">
                            <i class="fa-solid fa-lock me-2"></i>Change Password
                        </h5>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" minlength="6" required>
                                <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fa-solid fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Account Actions -->
                    <div class="text-center">
                        <a href="<?= htmlspecialchars($base) ?>/dashboard.php" class="btn btn-outline-primary me-2">
                            <i class="fa-solid fa-tachometer-alt me-2"></i>Back to Dashboard
                        </a>
                        <a href="<?= htmlspecialchars($base) ?>/logout.php" class="btn btn-outline-danger">
                            <i class="fa-solid fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
