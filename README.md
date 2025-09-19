# Varsity Resource Centre (Zim)

Varsity Resource Centre is a modern PHP 8 app that aggregates student essentials:

- Timetables (via Google Sheets CSV) with ICS export
- Student jobs (Arbeitnow API) with in-app apply modal
- Articles and student news
- Pop-up notifications managed by admin
- Super admin dashboard with theme and AdSense controls

Now includes: web installer, database support (admins, notifications), logging, custom error pages, and theming.

## Features
- Installer: visit `/install/` after upload, enter DB settings, site name, and color; installer runs SQL migrations and stores config.
- Admin Dashboard: manage AdSense keys, theme colors, and notifications (CRUD, active toggle). Change password at `/admin/password.php`.
- Logging: file-based logs in `storage/logs/app.log` with request context.
- Error pages: custom 400/401/403/404/500/502/503/504.
- Theming: primary color and site name applied across the UI.
- Jobs UX: truncated descriptions, “See more” modal, and in-app “Apply now” iframe modal.

## Project structure (key files)
- `index.php` – Landing page (hero search, categories, popular)
- `timetable.php` – Timetable search and results, ICS export
- `jobs.php` – Jobs feed with modals
- `articles.php`, `news.php`, `resume.php`
- `TimetableController.php` – CSV ingestion and filtering
- `config/universities.php` – Per-university CSV URLs
- `includes/header.php`, `includes/footer.php` – Shared layout
- `src/` – Namespaced PHP code (Auth, Config, Database, Logging, Calendar)
- `db/migration/` – SQL migrations (e.g., `V1__init.sql`)
- `install/index.php` – Web installer

## Requirements
- PHP 8.0+ (8.x recommended)
- MySQL 5.7+/8.x (for admin + notifications)
- Apache or Nginx serving PHP
- Internet access (Google Sheets CSV, external APIs)

## Quick start (hosted server)
1. Upload all files to your `public_html/` (or web root).
2. Visit your domain; you’ll be redirected to `/install/`.
3. Enter DB host/name/user/pass, Site Name, and Theme color.
4. Installer runs migrations and stores config in `storage/app.php`.
5. Log in: `/admin/login.php` (default: `superadmin` / `ChangeMe123!`). Change password at `/admin/password.php`.

## Running locally
1. Clone the repo to your web root (e.g., `htdocs/varsity-resource-centre`).
2. Ensure PHP ≥ 8.0 and MySQL are running.
3. Visit `http://localhost/varsity-resource-centre/install/` and complete setup.
4. Update `config/universities.php` with your Google Sheets CSV export links.

CSV export URLs format:
```
https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/export?format=csv&gid=SHEET_GID
```
Expected headers:
- Faculties: id,name
- Modules: module_code,module_name[,faculty_id]
- Timetable: module_code,day_of_week,start_time,end_time,venue

## Optional: Flyway CLI
For local development, you can also use Flyway:
- Configure `flyway.conf` (already present) with env vars `DB_HOST/DB_NAME/DB_USER/DB_PASS`.
- Run:
```
flyway migrate | cat
```
Note: Production installer already runs `.sql` files in `db/migration/` via PHP.

## Logging
Logs are written to `storage/logs/app.log` (auto-created). Includes INFO/WARNING/ERROR with URL, method, and IP.

## Security & CSRF
- CSRF tokens are used on forms.
- Admin passwords stored as hashes (bcrypt via `password_hash`).

## Contributing
1. Fork and clone.
2. Run the installer locally.
3. Create feature branches and open PRs.
4. For new universities, add entries in `config/universities.php` with published CSV URLs.

## License
MIT

