# Database Setup Guide

## 🔒 Security First

**NEVER commit database credentials to version control!**

The `storage/app.php` file contains sensitive database credentials and should be in your `.gitignore` file.

## 📋 Configuration

### 1. Database Configuration File

Edit `storage/app.php` and update the database section:

```php
<?php
return [
    'installed' => true,
    'db' => [
        'host' => 'localhost',           // Your database host
        'name' => 'your_database_name',  // Your database name
        'user' => 'your_username',       // Your database username
        'pass' => 'your_password'        // Your database password
    ],
    // ... rest of config
];
```

### 2. For Shared Hosting

Most shared hosting providers use:
- **Host**: `localhost`
- **Database Name**: Usually prefixed (e.g., `username_dbname`)
- **Username**: Usually same as database name or your account username
- **Password**: The password you set for the database user

### 3. Testing Connection

Run the test script to verify your configuration:

```bash
php test_db_connection.php
```

### 4. Running Migrations

After successful connection, run migrations via admin panel:
1. Go to `/admin/migrations.php`
2. Click "Run All Migrations"

## 🛠️ Troubleshooting

### Common Issues

1. **Access Denied Error**
   - Check database name, username, and password
   - Ensure the user has proper permissions
   - Verify the database exists

2. **Connection Refused**
   - Check if the host is correct (usually `localhost`)
   - Verify the database server is running

3. **Database Not Found**
   - Create the database in your hosting control panel
   - Ensure the database name matches exactly

### Getting Help

If you're still having issues:
1. Run `php update_db_config.php` for configuration help
2. Check your hosting provider's documentation
3. Verify credentials in your hosting control panel

## 🔐 Security Best Practices

1. **Use strong passwords** for database users
2. **Limit database user permissions** to only what's needed
3. **Never share credentials** in chat, email, or documentation
4. **Use environment variables** in production when possible
5. **Regularly backup** your database
