<?php
header('Content-Type: application/json');
require_once '../bootstrap.php';

use Timetable\TimetableManager;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['module_ids']) || !isset($input['semester_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    $timetableManager = new TimetableManager();
    $conflicts = $timetableManager->checkScheduleConflicts(
        $input['module_ids'],
        (int)$input['semester_id']
    );
    
    echo json_encode(['conflicts' => $conflicts]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
