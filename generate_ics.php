<?php
require_once 'TimetableController.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $university = isset($_POST['university']) ? $_POST['university'] : '';
    $moduleCodes = isset($_POST['module_codes']) ? explode(',', $_POST['module_codes']) : [];
    $moduleCodes = array_map(function ($c) { return trim(strtoupper($c)); }, $moduleCodes);
    $timetableController = new TimetableController();
    $timetable = $university ? $timetableController->getTimetable($university, $moduleCodes) : [];

    // Start ICS File
    $ics_content = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//YourApp//NONSGML v1.0//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
    
    foreach ($timetable as $event) {
        $start = new DateTime("2024-09-30 " . $event['start_time'], new DateTimeZone('Africa/Harare'));
        $end = new DateTime("2024-09-30 " . $event['end_time'], new DateTimeZone('Africa/Harare'));
        $dayMap = [
            'MONDAY' => 'MO','TUESDAY' => 'TU','WEDNESDAY' => 'WE','THURSDAY' => 'TH','FRIDAY' => 'FR','SATURDAY' => 'SA','SUNDAY' => 'SU',
            'MON' => 'MO','TUE' => 'TU','WED' => 'WE','THU' => 'TH','FRI' => 'FR','SAT' => 'SA','SUN' => 'SU'
        ];
        $dayKey = strtoupper(trim($event['day_of_week']));
        $day = isset($dayMap[$dayKey]) ? $dayMap[$dayKey] : strtoupper(substr($dayKey, 0, 2));

        $ics_content .= "BEGIN:VEVENT\r\n";
        $ics_content .= "DTSTART;TZID=Africa/Harare:" . $start->format('Ymd\THis') . "\r\n";
        $ics_content .= "DTEND;TZID=Africa/Harare:" . $end->format('Ymd\THis') . "\r\n";
        $ics_content .= "RRULE:FREQ=WEEKLY;BYDAY=$day;UNTIL=20241108T235959Z\r\n";
        $ics_content .= "SUMMARY:{$event['module_code']} {$event['module_name']}\r\n";
        $ics_content .= "LOCATION:{$event['venue']}\r\n";
        $ics_content .= "END:VEVENT\r\n";
    }

    $ics_content .= "END:VCALENDAR\r\n";

    // Set headers for file download
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="timetable.ics"');
    
    echo $ics_content;
    exit;
}
