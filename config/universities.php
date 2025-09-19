<?php
// University configurations: each university maps to published Google Sheets CSV URLs
// How to get a CSV URL: In Google Sheets → File → Share → Publish to web → Select the specific sheet → CSV
// Provide three tabs per university: Faculties, Modules, Timetable
// Expected headers:
// Faculties: id,name
// Modules: module_code,module_name,faculty_id
// Timetable: module_code,day_of_week,start_time,end_time,venue

return [
    // Example university entries. Replace the CSV URLs with your published sheet links.
    'msu' => [
        'name' => 'Midlands State University',
        'faculties_url' => 'https://docs.google.com/spreadsheets/d/1k4UF32H7jf9q3FviCb84CiINNJe79PbvxLt7ulKSjIE/export?format=csv&gid=0',
        'modules_url' => 'https://docs.google.com/spreadsheets/d/1k4UF32H7jf9q3FviCb84CiINNJe79PbvxLt7ulKSjIE/export?format=csv&gid=1395780808',
        'timetable_url' => 'https://docs.google.com/spreadsheets/d/1k4UF32H7jf9q3FviCb84CiINNJe79PbvxLt7ulKSjIE/export?format=csv&gid=279139838',
    ],
    // Add more universities below
    'uz' => [
        'name' => 'University of Zimbabwe',
        'faculties_url' => 'https://docs.google.com/spreadsheets/d/EXAMPLE2_ID/export?format=csv&gid=FACULTIES_GID',
        'modules_url' => 'https://docs.google.com/spreadsheets/d/EXAMPLE2_ID/export?format=csv&gid=MODULES_GID',
        'timetable_url' => 'https://docs.google.com/spreadsheets/d/EXAMPLE2_ID/export?format=csv&gid=TIMETABLE_GID',
    ],
];


