# Migration Management Guide

## Overview

The Varsity Resource Centre uses a Flyway-style migration system that allows you to manage database schema changes through the admin interface. This guide explains how to create, run, and manage migrations.

## Migration System Features

### ✅ **Admin Interface**
- **Visual Migration Management**: See all migrations in a clean table interface
- **Individual Migration Running**: Run specific migrations by version
- **Bulk Migration Running**: Run all pending migrations at once
- **Migration History**: Track executed migrations with timestamps
- **Migration Validation**: Check for checksum mismatches and file integrity
- **Detailed Migration View**: View SQL content and metadata for each migration

### ✅ **Migration Tracking**
- **Version Control**: Each migration has a unique version number
- **Execution History**: Track when migrations were run and by whom
- **Checksum Validation**: Ensure migration files haven't been modified
- **Success/Failure Tracking**: Monitor migration execution status
- **Rollback Prevention**: Migrations are designed to be forward-only

## Creating New Migrations

### 1. **Migration File Naming**
Follow this naming convention:
```
V{version_number}__{description}.sql
```

Examples:
- `V10__test_migration.sql`
- `V11__add_user_preferences.sql`
- `V12__enhance_search_functionality.sql`

### 2. **Migration File Structure**
```sql
-- Migration V{number}: {Description}
-- Brief description of what this migration does

-- Your SQL statements here
CREATE TABLE IF NOT EXISTS example_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add indexes, constraints, etc.
CREATE INDEX idx_example_name ON example_table(name);
```

### 3. **Best Practices**

#### **Always Use IF NOT EXISTS**
```sql
-- Good
CREATE TABLE IF NOT EXISTS new_table (
    id INT AUTO_INCREMENT PRIMARY KEY
);

-- Bad - will fail if table exists
CREATE TABLE new_table (
    id INT AUTO_INCREMENT PRIMARY KEY
);
```

#### **Use Transactions (Automatic)**
The migration runner automatically wraps each migration in a transaction, so if any statement fails, the entire migration is rolled back.

#### **Test Your Migrations**
Always test migrations on a development database before deploying to production.

#### **Keep Migrations Small**
Break large changes into multiple smaller migrations for easier debugging and rollback.

## Running Migrations

### **Through Admin Interface**

1. **Access Admin Panel**
   - Go to `/admin/`
   - Login with admin credentials
   - Navigate to "Migrations"

2. **View All Migrations**
   - See all available migrations in the table
   - Check status (Executed/Pending)
   - View file size and modification date

3. **Run Individual Migration**
   - Click "Run" button next to pending migration
   - Confirm the action
   - View results in the migration results section

4. **Run All Pending Migrations**
   - Click "Run All Pending Migrations" button
   - All pending migrations will execute in order
   - View detailed results for each migration

5. **View Migration Details**
   - Click "View" button to see migration details
   - View SQL content, metadata, and checksum
   - Run individual migration from the modal

### **Migration Status Indicators**

- **🟢 Executed**: Migration has been successfully run
- **🟡 Pending**: Migration is ready to be executed
- **🔴 Failed**: Migration failed during execution (check error logs)

## Migration Management

### **Migration History**
- View all executed migrations with timestamps
- See execution time and success status
- Track which admin user ran each migration

### **Validation**
- Click "Validate Migrations" to check for issues
- Detects checksum mismatches
- Identifies missing migration files
- Ensures migration integrity

### **Error Handling**
- Failed migrations are logged with detailed error messages
- Execution stops on first failure
- Check error logs in `storage/logs/app.log`
- Fix issues and re-run failed migrations

## Migration Examples

### **Example 1: Adding a New Table**
```sql
-- Migration V10: Add user preferences table
-- Adds user preferences functionality

CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key)
);

CREATE INDEX idx_user_preferences_user_id ON user_preferences(user_id);
```

### **Example 2: Adding Columns to Existing Table**
```sql
-- Migration V11: Add email verification to users
-- Adds email verification fields to users table

ALTER TABLE users 
ADD COLUMN email_verified_at TIMESTAMP NULL AFTER email_verified,
ADD COLUMN email_verification_token VARCHAR(255) NULL AFTER email_verified_at;

CREATE INDEX idx_users_email_verification_token ON users(email_verification_token);
```

### **Example 3: Creating Indexes for Performance**
```sql
-- Migration V12: Add performance indexes
-- Adds indexes to improve query performance

CREATE INDEX idx_articles_published_at ON articles(published_at);
CREATE INDEX idx_articles_featured ON articles(featured);
CREATE INDEX idx_jobs_expires_at ON jobs(expires_at);
CREATE INDEX idx_reviews_rating ON reviews(rating);
```

## Troubleshooting

### **Common Issues**

#### **Migration Fails with "Table Already Exists"**
- Use `CREATE TABLE IF NOT EXISTS` instead of `CREATE TABLE`
- Check if migration was partially executed before

#### **Migration Fails with "Column Already Exists"**
- Use `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` (MySQL 8.0+)
- Or check column existence before adding

#### **Checksum Mismatch**
- Migration file was modified after execution
- Restore original file or create new migration to fix

#### **Permission Denied**
- Ensure database user has necessary privileges
- Check file permissions on migration files

### **Recovery Procedures**

#### **Failed Migration Recovery**
1. Check error logs for specific error message
2. Fix the SQL in the migration file
3. Re-run the migration through admin interface
4. If migration was partially executed, manually clean up

#### **Rollback Strategy**
- Migrations are designed to be forward-only
- For rollbacks, create new migrations that undo changes
- Example: If V10 adds a table, V11 should drop it

## Migration File Locations

- **Migration Files**: `db/migration/`
- **Migration History**: Database table `flyway_schema_history`
- **Error Logs**: `storage/logs/app.log`

## Security Considerations

- **Admin Access Only**: Only admin users can run migrations
- **CSRF Protection**: All migration actions are protected with CSRF tokens
- **Transaction Safety**: Each migration runs in its own transaction
- **Backup Recommended**: Always backup database before running migrations

## Best Practices Summary

1. **Version Numbers**: Use sequential version numbers (V10, V11, V12, etc.)
2. **Descriptive Names**: Use clear, descriptive migration names
3. **Test First**: Always test migrations on development database
4. **Small Changes**: Keep migrations focused and small
5. **Documentation**: Include comments explaining what each migration does
6. **Backup**: Backup database before running migrations in production
7. **Monitor**: Check migration results and logs after execution
8. **Validation**: Regularly validate migration integrity

## Migration Workflow

1. **Development**
   - Create migration file with proper naming
   - Test on development database
   - Verify SQL syntax and logic

2. **Staging**
   - Deploy migration file to staging
   - Run migration through admin interface
   - Test application functionality

3. **Production**
   - Backup production database
   - Deploy migration file
   - Run migration during maintenance window
   - Monitor results and logs
   - Verify application functionality

This migration system provides a robust, user-friendly way to manage database schema changes with full visibility and control through the admin interface.
