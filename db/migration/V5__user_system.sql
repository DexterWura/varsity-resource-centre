-- User system with role-based access control
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Roles table (for role definitions)
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  permissions JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User role assignments (simplified)
CREATE TABLE IF NOT EXISTS user_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_role (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles
INSERT IGNORE INTO roles (name, description, permissions) VALUES
('admin', 'Administrator with full access', '["*"]'),
('user', 'Regular user with basic access', '["read", "comment"]'),
('moderator', 'Moderator with content management access', '["read", "write", "moderate"]');

-- Create default admin user
INSERT IGNORE INTO users (email, full_name, password_hash, is_active, email_verified) VALUES
('admin@varsityresource.com', 'Super Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

-- Assign admin role to the default admin user
INSERT IGNORE INTO user_roles (user_id, role_id) 
SELECT u.id, r.id 
FROM users u, roles r 
WHERE u.email = 'admin@varsityresource.com' AND r.name = 'admin';

-- Articles system
CREATE TABLE IF NOT EXISTS articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  content LONGTEXT NOT NULL,
  excerpt TEXT,
  featured_image VARCHAR(500),
  meta_title VARCHAR(255),
  meta_description TEXT,
  meta_keywords TEXT,
  author_id INT NOT NULL,
  status ENUM('draft', 'submitted', 'under_review', 'approved', 'published', 'rejected') DEFAULT 'draft',
  reviewer_id INT DEFAULT NULL,
  review_notes TEXT,
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_slug (slug),
  INDEX idx_status (status),
  INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- House rentals system
CREATE TABLE IF NOT EXISTS houses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  price_type ENUM('per_day', 'per_week', 'per_month') NOT NULL,
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  address TEXT,
  city VARCHAR(100),
  university VARCHAR(100),
  campus VARCHAR(100),
  bedrooms INT DEFAULT 1,
  bathrooms INT DEFAULT 1,
  amenities JSON,
  images JSON,
  owner_id INT NOT NULL,
  is_agent TINYINT(1) DEFAULT 0,
  agent_name VARCHAR(255),
  is_active TINYINT(1) DEFAULT 1,
  is_booked TINYINT(1) DEFAULT 0,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_location (latitude, longitude),
  INDEX idx_price (price),
  INDEX idx_expires_at (expires_at),
  INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Business listings system
CREATE TABLE IF NOT EXISTS businesses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  category VARCHAR(100),
  city VARCHAR(100),
  university VARCHAR(100),
  campus VARCHAR(100),
  location TEXT,
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  contact_email VARCHAR(255),
  contact_phone VARCHAR(50),
  website VARCHAR(255),
  social_media JSON,
  images JSON,
  owner_id INT NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_location (latitude, longitude),
  INDEX idx_category (category),
  INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles
INSERT INTO user_roles (name, description, permissions) VALUES
('user', 'Basic user with limited access', '{"read": true}'),
('writer', 'Can write and submit articles', '{"read": true, "write_articles": true}'),
('reviewer', 'Can review and approve articles', '{"read": true, "review_articles": true}'),
('business_owner', 'Can post business listings', '{"read": true, "manage_business": true}'),
('house_owner', 'Can post house rentals', '{"read": true, "manage_houses": true}'),
('agent', 'Can post house rentals as agent', '{"read": true, "manage_houses": true, "agent_tag": true}')
ON DUPLICATE KEY UPDATE name = name;
