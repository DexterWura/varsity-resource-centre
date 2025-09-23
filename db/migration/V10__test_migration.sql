-- Test Migration V10
-- This is a test migration to demonstrate the admin migration system

-- Create a test table
CREATE TABLE IF NOT EXISTS test_migration_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some test data
INSERT INTO test_migration_table (name, description) VALUES
('Test Item 1', 'This is a test item created by migration V10'),
('Test Item 2', 'Another test item for demonstration'),
('Test Item 3', 'Final test item to show migration works');

-- Add an index for better performance
CREATE INDEX IF NOT EXISTS idx_test_migration_name ON test_migration_table(name);
