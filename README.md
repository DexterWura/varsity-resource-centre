# Varsity Resource Centre (Zim)

Varsity Resource Centre is a lightweight PHP app that brings together student essentials:

- University timetables (via Google Sheets CSV)
- Student jobs (Arbeitnow public API)
- Articles (Crossref public API)
- Student news (HN Algolia public API)
- Resume creator (browser-based, exports PDF)

No database is required. Timetable data is read from public Google Sheets CSV links you control.

## Project structure (key files)
- index.php – Landing page with feature cards
- timetable.php – Timetable search and results, ICS export
- jobs.php – Jobs feed page
- articles.php – Articles feed page
- news.php – Student news page
- resume.php – One-page resume creator with PDF export
- TimetableController.php – CSV ingestion and filtering
- config/universities.php – Per-university CSV URLs
- includes/header.php, includes/footer.php – Shared layout
- lib/http.php – Tiny HTTP helper for JSON APIs

## Requirements
- PHP 7.4+ (8.x recommended)
- Apache or Nginx serving PHP
- Internet access (to fetch Google Sheets CSV and public APIs)

## Timetable data via Google Sheets
Set your university tabs and publish them as CSV exports.

Expected headers per tab:
- Faculties: name,id,code (id and code optional, but at least name)
- Modules: module_code,module_name
- Timetable: module_code,day_of_week,start_time,end_time,venue

CSV export URLs format (important):
```
https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/export?format=csv&gid=SHEET_GID
```
Update `config/universities.php` with your CSV export links for each tab. Do not use `/edit?...` links.

Day/time formats:
- day_of_week: MONDAY/TUESDAY/... or MON/TUE/...
- time: 24-hour HH:MM (seconds optional)

## Running locally (XAMPP/WAMP/MAMP)
1. Clone the repo.
2. Place the folder under your web root (e.g., `htdocs/msu-time-table-master`).
3. Ensure PHP is enabled and version ≥ 7.4.
4. Configure `config/universities.php` with your CSV export links.
5. Visit:
   - http://localhost/msu-time-table-master/index.php (landing)
   - http://localhost/msu-time-table-master/timetable.php
   - http://localhost/msu-time-table-master/jobs.php
   - http://localhost/msu-time-table-master/articles.php
   - http://localhost/msu-time-table-master/news.php
   - http://localhost/msu-time-table-master/resume.php

If deploying to a subfolder (e.g., `/timetable`), the navbar links are absolute to `/timetable/...`. Adjust paths in `includes/header.php` if your folder name differs.

## Deployment (Apache)
Upload all files to `public_html/timetable/` (or your chosen folder). Create `.htaccess` in that folder if needed:
```
DirectoryIndex index.php
Options -Indexes
```
Then visit `https://your-domain.com/timetable/`.

## ICS export
From `timetable.php`, after fetching results you can click “Add to Calendar” to download an `.ics` file. Events repeat weekly until a set end date, in Africa/Harare timezone.

## Resume creator
Open `resume.php`, fill in details, choose a design, preview, and click “Download PDF”. PDF is generated client-side using html2pdf (no server dependencies).

## Contributing
Pull requests are welcome. For new universities, add a new entry in `config/universities.php` with CSV URLs for faculties, modules, and timetable.

## License
MIT

