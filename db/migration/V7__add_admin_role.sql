-- Add admin role with all permissions
INSERT INTO user_roles (name, description, permissions) VALUES
('admin', 'Administrator with full access', '{"read": true, "write_articles": true, "review_articles": true, "manage_business": true, "manage_houses": true, "agent_tag": true, "admin": true}')
ON DUPLICATE KEY UPDATE name = name;
