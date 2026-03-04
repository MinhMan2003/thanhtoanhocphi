-- Migration: Add api_key and update roles for API authentication
-- Run this script to update the users table for API v2

-- Add api_key column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS api_key VARCHAR(64) NULL UNIQUE;

-- Update role ENUM to include new roles (MySQL 8.0+)
-- For older MySQL, drop and recreate
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'ketoan', 'giaovien', 'phuhuynh') DEFAULT 'admin';

-- Generate API keys for existing users (optional)
-- UPDATE users SET api_key = MD5(CONCAT(username, NOW())) WHERE api_key IS NULL;

-- Create a sample API key for testing (use in production)
-- INSERT INTO users (username, password_hash, full_name, role, api_key) 
-- VALUES ('api_user', '$2y$10$...', 'API User', 'ketoan', 'your-api-key-here');
