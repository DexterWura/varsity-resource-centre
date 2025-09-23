# Flyway UI Troubleshooting Guide

## "Error: There is no active transaction" - What This Means

This error typically occurs when:

1. **Transaction Control Issues**: The migration contains `SET` statements that interfere with Flyway's transaction management
2. **Multiple Statements**: Complex migrations with multiple SQL statements that aren't properly handled
3. **DDL/DML Mixing**: Mixing Data Definition Language (CREATE, ALTER, DROP) with Data Manipulation Language (INSERT, UPDATE, DELETE)

## What Was Fixed

The issue was in `V14__force_fix_user_roles.sql` which contained:
```sql
SET FOREIGN_KEY_CHECKS = 0;
-- ... migration code ...
SET FOREIGN_KEY_CHECKS = 1;
```

These `SET` statements can cause transaction management issues in some database systems.

## Fixed Version

The migration now uses:
```sql
-- Drop the user_roles table if it exists (this will fail gracefully if there are foreign key constraints)
DROP TABLE IF EXISTS user_roles;

-- Create user_roles table with correct structure
CREATE TABLE user_roles (
  -- ... table definition ...
);
```

## Best Practices for Flyway Migrations

1. **Avoid Transaction Control**: Don't use `BEGIN`, `COMMIT`, `ROLLBACK`, or `SET` statements that affect transactions
2. **Use IF NOT EXISTS**: Use `CREATE TABLE IF NOT EXISTS` and `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`
3. **Use INSERT IGNORE**: Use `INSERT IGNORE` to prevent duplicate key errors
4. **Keep It Simple**: One migration should do one thing well
5. **Test First**: Test migrations on a copy of your database first

## If You Still Get Transaction Errors

1. **Check Migration Files**: Look for `SET`, `BEGIN`, `COMMIT`, or `ROLLBACK` statements
2. **Simplify Migrations**: Break complex migrations into smaller ones
3. **Use Flyway Command Line**: Sometimes the command line is more reliable than the UI
4. **Check Database Logs**: Look at your database server logs for more details

## Running Migrations

### Via Admin UI (Recommended)
1. Go to `/admin/migrations.php`
2. Click "Run All Pending Migrations"
3. Or run individual migrations

### Via Command Line (Alternative)
```bash
# Run all pending migrations
php run_migration.php

# Or use Flyway directly (if installed)
flyway migrate
```

## Common Migration Patterns

### ✅ Good Migration
```sql
-- V15__add_new_feature.sql
CREATE TABLE IF NOT EXISTS new_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO new_table (name) VALUES ('Default Value');
```

### ❌ Bad Migration
```sql
-- This can cause transaction issues
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS old_table;
SET FOREIGN_KEY_CHECKS = 1;
CREATE TABLE new_table (...);
```

## Need Help?

If you're still having issues:
1. Check the migration file that's failing
2. Look for transaction control statements
3. Simplify the migration
4. Test on a copy of your database first
