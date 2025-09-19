<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TimetableController.php';

use Security\Csrf;
use Calendar\IcsGenerator;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        echo 'Invalid request.';
        exit;
    }

    $university = isset($_POST['university']) ? (string) $_POST['university'] : '';
    $moduleCodes = isset($_POST['module_codes']) ? explode(',', (string) $_POST['module_codes']) : [];
    $moduleCodes = array_map(function ($c) { return trim(strtoupper($c)); }, $moduleCodes);
    $timetableController = new TimetableController();
    $timetable = $university ? $timetableController->getTimetable($university, $moduleCodes) : [];

    // Start ICS File
    $ics_content = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//YourApp//NONSGML v1.0//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
    
    $gen = new IcsGenerator();
    $events = [];
    foreach ($timetable as $event) {
        $start = new DateTime('2024-09-30 ' . $event['start_time'], new DateTimeZone('Africa/Harare'));
        $end = new DateTime('2024-09-30 ' . $event['end_time'], new DateTimeZone('Africa/Harare'));
        $dayMap = [
            'MONDAY' => 'MO','TUESDAY' => 'TU','WEDNESDAY' => 'WE','THURSDAY' => 'TH','FRIDAY' => 'FR','SATURDAY' => 'SA','SUNDAY' => 'SU',
            'MON' => 'MO','TUE' => 'TU','WED' => 'WE','THU' => 'TH','FRI' => 'FR','SAT' => 'SA','SUN' => 'SU'
        ];
        $dayKey = strtoupper(trim((string) $event['day_of_week']));
        $day = isset($dayMap[$dayKey]) ? $dayMap[$dayKey] : strtoupper(substr($dayKey, 0, 2));
        $events[] = [
            'start' => $start,
            'end' => $end,
            'byday' => $day,
            'summary' => ($event['module_code'] . ' ' . $event['module_name']),
            'location' => (string) $event['venue']
        ];
    }
    $ics_content = $gen->buildWeekly($events, '20241108T235959');

    // Set headers for file download
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="timetable.ics"');
    
    echo $ics_content;
    exit;
}
