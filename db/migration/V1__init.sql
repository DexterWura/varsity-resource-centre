CREATE DATABASE IF NOT EXISTS `varsity_resource_centre` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `varsity_resource_centre`;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_super TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message TEXT NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'info',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admins (username, password_hash, is_super)
VALUES ('superadmin', '$2y$10$YXq1r7JZc7QqKfS3oK4GKeCaZ3iC0xQ.2K8xX0o3G3m8kz.2m2XrK', 1)
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO notifications (message, type, is_active)
VALUES ('Welcome to Varsity Resource Centre!', 'info', 1)
ON DUPLICATE KEY UPDATE message = message;

