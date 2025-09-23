-- Add admin role with all permissions (if not already exists)
INSERT IGNORE INTO roles (name, description, permissions) VALUES
('admin', 'Administrator with full access', '["*"]');
