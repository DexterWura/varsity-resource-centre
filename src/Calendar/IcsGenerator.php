<?php
declare(strict_types=1);

namespace Calendar;

class IcsGenerator {
    private string $prodId;
    private string $timezone;

    public function __construct(string $prodId = '-//VarsityResourceCentre//EN', string $timezone = 'Africa/Harare') {
        $this->prodId = $prodId;
        $this->timezone = $timezone;
    }

    public function buildWeekly(array $events, string $untilUtcYmdHis): string {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:{$this->prodId}\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
        foreach ($events as $event) {
            $start = $event['start']; // DateTime
            $end = $event['end'];
            $day = $event['byday']; // MO/TU/...
            $summary = $this->escapeText($event['summary'] ?? '');
            $location = $this->escapeText($event['location'] ?? '');
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "DTSTART;TZID={$this->timezone}:" . $start->format('Ymd\THis') . "\r\n";
            $ics .= "DTEND;TZID={$this->timezone}:" . $end->format('Ymd\THis') . "\r\n";
            $ics .= "RRULE:FREQ=WEEKLY;BYDAY={$day};UNTIL={$untilUtcYmdHis}Z\r\n";
            $ics .= "SUMMARY:{$summary}\r\n";
            $ics .= "LOCATION:{$location}\r\n";
            $ics .= "END:VEVENT\r\n";
        }
        $ics .= "END:VCALENDAR\r\n";
        return $ics;
    }

    private function escapeText(string $text): string {
        return addcslashes($text, ",;\\\n");
    }
}


