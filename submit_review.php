<?php
require_once __DIR__ . '/bootstrap.php';
use Database\DB;

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $reviewerName = trim($_POST['reviewer_name'] ?? '');
    $reviewerEmail = trim($_POST['reviewer_email'] ?? '');
    
    // Validate input
    if (!in_array($type, ['business', 'house']) || $rating < 1 || $rating > 5 || empty($comment)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }
    
    $itemId = (int)($_POST[$type . '_id'] ?? 0);
    if ($itemId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid item ID']);
        exit;
    }
    
    try {
        $pdo = DB::pdo();
        
        // Check if item exists and is active
        $table = $type === 'business' ? 'businesses' : 'houses';
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE id = ? AND is_active = 1");
        $stmt->execute([$itemId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
            exit;
        }
        
        // Insert review
        $reviewTable = $type . '_reviews';
        $stmt = $pdo->prepare("INSERT INTO $reviewTable (business_id, house_id, reviewer_name, reviewer_email, rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($type === 'business') {
            $stmt->execute([$itemId, null, $reviewerName, $reviewerEmail, $rating, $comment]);
        } else {
            $stmt->execute([null, $itemId, $reviewerName, $reviewerEmail, $rating, $comment]);
        }
        
        // Redirect back to the item page
        $redirectUrl = $type . '.php?id=' . $itemId;
        header('Location: ' . $redirectUrl . '?review=success');
        exit;
        
    } catch (\Throwable $e) {
        error_log('Review submission error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit review']);
        exit;
    }
}

// If not POST, redirect to home
header('Location: index.php');
exit;
?>