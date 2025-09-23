-- Force fix user_roles table structure
-- This migration fixes the user_roles table structure without transaction control

-- Drop the user_roles table if it exists (this will fail gracefully if there are foreign key constraints)
DROP TABLE IF EXISTS user_roles;

-- Create user_roles table with correct structure
CREATE TABLE user_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_role (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assign admin role to admin user
INSERT IGNORE INTO user_roles (user_id, role_id) 
SELECT u.id, r.id 
FROM users u, roles r 
WHERE u.email = 'admin@varsityresource.com' AND r.name = 'admin';
