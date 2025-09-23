# Varsity Resource Centre - Installation Guide

## Quick Installation

1. **Upload Files**: Upload all files to your web server
2. **Set Permissions**: Ensure `storage/` directory is writable
3. **Visit Installer**: Go to `http://yoursite.com/install/`
4. **Configure**: Fill in database details and site settings
5. **Install**: Click "Install" to run migrations and setup

## Detailed Installation Steps

### 1. Server Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **Web Server**: Apache, Nginx, or IIS
- **Extensions**: PDO, PDO_MySQL, JSON, mbstring

### 2. File Upload

Upload all files to your web server's document root or subdirectory.

```
/your-website/
├── admin/
├── assets/
├── config/
├── controllers/
├── db/
├── errors/
├── includes/
├── install/
├── src/
├── storage/
├── bootstrap.php
├── index.php
└── ...
```

### 3. Directory Permissions

Make sure these directories are writable by the web server:

```bash
chmod 755 storage/
chmod 755 storage/logs/
```

### 4. Database Setup

#### Option A: Let the installer create the database
- The installer will attempt to create the database automatically
- Ensure your database user has CREATE privileges

#### Option B: Create database manually
```sql
CREATE DATABASE varsity_resource_centre 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### 5. Run Installation

1. Visit `http://yoursite.com/install/`
2. Fill in the installation form:

#### Database Configuration
- **DB Host**: Usually `localhost` or your database server IP
- **DB Name**: `varsity_resource_centre` (or your preferred name)
- **DB User**: Your MySQL username
- **DB Password**: Your MySQL password

#### Site Configuration
- **Site Name**: Your website name (default: "Varsity Resource Centre")
- **Theme Color**: Choose your primary theme color

#### Feature Selection
- **Articles**: Enable article management system
- **Houses**: Enable accommodation listings
- **Businesses**: Enable business directory
- **News**: Enable news management
- **Jobs**: Enable job board
- **Timetable**: Enable timetable builder
- **Pro Plagiarism Checker**: Enable premium plagiarism detection (requires payment setup)

3. Click **"Install"**

### 6. What Happens During Installation

The installer will:

1. **Test Database Connection**: Verify your database credentials
2. **Create Database** (if permissions allow): Create the database if it doesn't exist
3. **Run Migrations**: Execute all SQL migration files in order:
   - `V1__init.sql` - Core tables
   - `V2__jobs.sql` - Job system
   - `V3__popular.sql` - Popular content tracking
   - `V4__add_job_expiry.sql` - Job expiry dates
   - `V5__user_system.sql` - User authentication
   - `V6__reviews_system.sql` - Review system
   - `V7__add_admin_role.sql` - Admin roles
   - `V8__add_image_support.sql` - Image management
   - `V9__enhanced_timetable.sql` - Enhanced timetable system
4. **Create Configuration**: Save your settings to `storage/app.php`
5. **Redirect**: Take you to the main site

### 7. Post-Installation

#### Default Admin Account
After installation, you can create an admin account by:
1. Going to `/register.php`
2. Creating an account
3. Manually setting the user role to 'admin' in the database

#### Admin Panel Access
- Visit `/admin/` to access the admin panel
- Configure additional settings, manage content, and enable/disable features

### 8. Troubleshooting

#### Common Issues

**"Install failed: Access denied"**
- Check database credentials
- Ensure database user has proper privileges
- Verify database exists (or user can create databases)

**"Install failed: Table already exists"**
- Database may have been partially installed
- Drop existing tables or use a fresh database
- Check migration files for conflicts

**"Permission denied"**
- Ensure `storage/` directory is writable
- Check file permissions on the web server

**"Headers already sent"**
- Check for whitespace or output before PHP tags
- Ensure no BOM (Byte Order Mark) in PHP files

#### Reset Installation

To test the installation process again:

```bash
php reset_installation.php
```

This will:
- Backup your current configuration
- Reset to fresh installation state
- Allow you to run the installer again

### 9. Configuration Files

#### Main Configuration: `storage/app.php`
```php
<?php
return [
    'installed' => true,
    'db' => [
        'host' => 'localhost',
        'name' => 'varsity_resource_centre',
        'user' => 'your_username',
        'pass' => 'your_password'
    ],
    'site_name' => 'Your Site Name',
    'theme' => [
        'primary' => '#0d6efd'
    ],
    'features' => [
        'articles' => true,
        'houses' => true,
        'businesses' => true,
        'news' => true,
        'jobs' => true,
        'timetable' => true,
        'plagiarism_checker' => false
    ],
    'plagiarism_apis' => [
        'copyleaks' => false,
        'quetext' => false,
        'smallseotools' => false,
        'plagiarism_detector' => false,
        'duplichecker' => false
    ]
];
```

### 10. Security Notes

- Change default database credentials after installation
- Set up proper file permissions
- Consider using environment variables for sensitive data
- Enable HTTPS in production
- Regularly update the application

### 11. Support

If you encounter issues:
1. Check the error logs in `storage/logs/app.log`
2. Verify server requirements
3. Test database connectivity manually
4. Check file permissions

## Features Overview

After installation, your Varsity Resource Centre will include:

- **Articles System**: Manage and publish articles
- **Houses Directory**: List accommodation options
- **Business Directory**: Showcase local businesses
- **News Management**: Publish news and updates
- **Job Board**: Post and manage job listings
- **Timetable Builder**: Create academic timetables
- **User System**: Registration, login, and profiles
- **Admin Panel**: Complete site management
- **Review System**: User reviews and ratings
- **Image Management**: Upload and manage images
- **Pro Plagiarism Checker**: Premium content checking (optional)

All features can be enabled/disabled through the admin panel after installation.
