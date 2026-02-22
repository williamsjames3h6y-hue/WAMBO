-- MySQL Database Schema for EarningsLLC Platform
-- Converted from Supabase PostgreSQL to MySQL
-- This file contains the complete database structure

-- Create database (run this separately if needed)
-- CREATE DATABASE IF NOT EXISTS earningsllc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE earningsllc;

-- ============================================
-- Table: users (replaces auth.users from Supabase)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  email_confirmed BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: vip_tiers
-- ============================================
CREATE TABLE IF NOT EXISTS vip_tiers (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  name VARCHAR(100) NOT NULL UNIQUE,
  level INT NOT NULL UNIQUE,
  price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  max_annotations_per_month INT NOT NULL DEFAULT 100,
  max_projects INT NOT NULL DEFAULT 1,
  max_team_members INT NOT NULL DEFAULT 1,
  max_tasks_per_day INT NOT NULL DEFAULT 35,
  commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.50,
  weekly_earning_limit DECIMAL(10,2) DEFAULT 100.00,
  features JSON DEFAULT NULL,
  requires_support_contact BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_vip_tiers_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: user_profiles
-- ============================================
CREATE TABLE IF NOT EXISTS user_profiles (
  id CHAR(36) PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  full_name VARCHAR(255) DEFAULT '',
  vip_tier_id CHAR(36),
  subscription_status VARCHAR(50) DEFAULT 'active',
  subscription_ends_at TIMESTAMP NULL,
  annotations_this_month INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (vip_tier_id) REFERENCES vip_tiers(id) ON DELETE SET NULL,
  INDEX idx_user_profiles_vip (vip_tier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: projects
-- ============================================
CREATE TABLE IF NOT EXISTS projects (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  owner_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT DEFAULT '',
  project_type VARCHAR(50) DEFAULT 'text',
  status VARCHAR(50) DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
  INDEX idx_projects_owner (owner_id),
  INDEX idx_projects_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: datasets
-- ============================================
CREATE TABLE IF NOT EXISTS datasets (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  project_id CHAR(36) NOT NULL,
  data_type VARCHAR(50) NOT NULL,
  data_url TEXT NOT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  INDEX idx_datasets_project (project_id),
  INDEX idx_datasets_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: annotations
-- ============================================
CREATE TABLE IF NOT EXISTS annotations (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  dataset_id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  annotation_data JSON DEFAULT NULL,
  confidence_score DECIMAL(3,2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
  INDEX idx_annotations_dataset (dataset_id),
  INDEX idx_annotations_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: team_members
-- ============================================
CREATE TABLE IF NOT EXISTS team_members (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  project_id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  role VARCHAR(50) DEFAULT 'viewer',
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
  UNIQUE KEY unique_project_user (project_id, user_id),
  INDEX idx_team_members_project (project_id),
  INDEX idx_team_members_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: wallets
-- ============================================
CREATE TABLE IF NOT EXISTS wallets (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL UNIQUE,
  balance DECIMAL(10,2) DEFAULT 0.00 NOT NULL CHECK (balance >= 0),
  total_earnings DECIMAL(10,2) DEFAULT 0.00 NOT NULL,
  can_withdraw BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_wallets_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: transactions
-- ============================================
CREATE TABLE IF NOT EXISTS transactions (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  wallet_id CHAR(36) NOT NULL,
  type VARCHAR(50) NOT NULL CHECK (type IN ('deposit', 'withdraw', 'earnings')),
  amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
  status VARCHAR(50) DEFAULT 'completed' CHECK (status IN ('pending', 'completed', 'failed')),
  description TEXT DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
  INDEX idx_transactions_user (user_id),
  INDEX idx_transactions_wallet (wallet_id),
  INDEX idx_transactions_type (type),
  INDEX idx_transactions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: daily_earnings
-- ============================================
CREATE TABLE IF NOT EXISTS daily_earnings (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  date DATE DEFAULT (CURRENT_DATE) NOT NULL,
  tasks_completed INT DEFAULT 0 NOT NULL CHECK (tasks_completed >= 0),
  commission_earned DECIMAL(10,2) DEFAULT 0.00 NOT NULL,
  base_salary DECIMAL(10,2) DEFAULT 0.00 NOT NULL,
  total_earnings DECIMAL(10,2) DEFAULT 0.00 NOT NULL,
  can_withdraw BOOLEAN DEFAULT FALSE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_date (user_id, date),
  INDEX idx_daily_earnings_user (user_id),
  INDEX idx_daily_earnings_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: admin_tasks (brand identification tasks)
-- ============================================
CREATE TABLE IF NOT EXISTS admin_tasks (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  task_order INT NOT NULL DEFAULT 0,
  vip_level_required INT NOT NULL DEFAULT 1,
  image_url TEXT NOT NULL,
  brand_name VARCHAR(255) NOT NULL,
  earning_amount DECIMAL(10,2) NOT NULL DEFAULT 2.14,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_tasks_vip_order (vip_level_required, task_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: user_task_submissions
-- ============================================
CREATE TABLE IF NOT EXISTS user_task_submissions (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  task_id CHAR(36) NOT NULL,
  user_answer TEXT NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'completed' CHECK (status IN ('pending', 'completed', 'rejected')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES admin_tasks(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_task (user_id, task_id),
  INDEX idx_user_submissions_user (user_id),
  INDEX idx_user_submissions_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: admin_users
-- ============================================
CREATE TABLE IF NOT EXISTS admin_users (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL UNIQUE,
  role VARCHAR(50) DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_admin_users_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: payment_methods
-- ============================================
CREATE TABLE IF NOT EXISTS payment_methods (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  user_id CHAR(36) NOT NULL,
  method_type VARCHAR(50) NOT NULL,
  account_details JSON DEFAULT NULL,
  is_primary BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_payment_methods_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: payment_gateways
-- ============================================
CREATE TABLE IF NOT EXISTS payment_gateways (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  gateway_name VARCHAR(100) NOT NULL,
  gateway_type VARCHAR(50) NOT NULL,
  api_credentials JSON DEFAULT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Default VIP Tiers
-- ============================================
INSERT INTO vip_tiers (name, level, price_monthly, max_annotations_per_month, max_projects, max_team_members, max_tasks_per_day, commission_rate, weekly_earning_limit, features, requires_support_contact) VALUES
('VIP 1', 1, 0.00, 100, 1, 1, 35, 0.50, 100.00, JSON_ARRAY('Basic annotation tools', 'Text data support', '35 tasks per day', 'Weekly limit $100+'), FALSE),
('VIP 2', 2, 29.00, 1000, 5, 3, 50, 0.75, 500.00, JSON_ARRAY('All VIP 1 features', 'Image annotation', '50 tasks per day', 'Weekly limit $500+', 'Priority support'), FALSE),
('VIP 3', 3, 79.00, 10000, 20, 10, 75, 1.00, 1000.00, JSON_ARRAY('All VIP 2 features', 'Video annotation', '75 tasks per day', 'Weekly limit $1000+', 'Advanced analytics'), FALSE),
('VIP 4', 4, 199.00, 50000, 50, 25, 100, 1.25, 2000.00, JSON_ARRAY('All VIP 3 features', 'Audio annotation', '100 tasks per day', 'Weekly limit $2000+', 'API access'), FALSE),
('VIP 5', 5, 499.00, 100000, 100, 50, 999999, 1.50, 5000.00, JSON_ARRAY('All VIP 4 features', 'Unlimited tasks per day', 'Weekly limit $5000+', 'Dedicated support', 'Custom workflows'), TRUE);

-- ============================================
-- Insert Sample Brand Identification Tasks for VIP 1 (35 tasks)
-- ============================================
INSERT INTO admin_tasks (task_order, vip_level_required, image_url, brand_name, earning_amount) VALUES
(1, 1, '/1.jpg', 'Brand 1', 2.25),
(2, 1, '/2.jpg', 'Brand 2', 2.10),
(3, 1, '/3.jpg', 'Brand 3', 2.30),
(4, 1, '/4.jpg', 'Brand 4', 1.95),
(5, 1, '/5.jpg', 'Brand 5', 2.40),
(6, 1, '/6.jpg', 'Brand 6', 2.15),
(7, 1, '/7.jpg', 'Brand 7', 2.05),
(8, 1, '/8.jpg', 'Brand 8', 2.35),
(9, 1, '/9.jpg', 'Brand 9', 2.20),
(10, 1, '/1.jpg', 'Brand 10', 2.00),
(11, 1, '/2.jpg', 'Brand 11', 2.25),
(12, 1, '/3.jpg', 'Brand 12', 2.10),
(13, 1, '/4.jpg', 'Brand 13', 2.30),
(14, 1, '/5.jpg', 'Brand 14', 1.85),
(15, 1, '/6.jpg', 'Brand 15', 2.45),
(16, 1, '/7.jpg', 'Brand 16', 2.15),
(17, 1, '/8.jpg', 'Brand 17', 2.05),
(18, 1, '/9.jpg', 'Brand 18', 2.30),
(19, 1, '/1.jpg', 'Brand 19', 2.20),
(20, 1, '/2.jpg', 'Brand 20', 2.00),
(21, 1, '/3.jpg', 'Brand 21', 2.25),
(22, 1, '/4.jpg', 'Brand 22', 2.10),
(23, 1, '/5.jpg', 'Brand 23', 2.35),
(24, 1, '/6.jpg', 'Brand 24', 1.90),
(25, 1, '/7.jpg', 'Brand 25', 2.40),
(26, 1, '/8.jpg', 'Brand 26', 2.15),
(27, 1, '/9.jpg', 'Brand 27', 2.05),
(28, 1, '/1.jpg', 'Brand 28', 2.30),
(29, 1, '/2.jpg', 'Brand 29', 2.20),
(30, 1, '/3.jpg', 'Brand 30', 2.00),
(31, 1, '/4.jpg', 'Brand 31', 2.25),
(32, 1, '/5.jpg', 'Brand 32', 2.10),
(33, 1, '/6.jpg', 'Brand 33', 2.30),
(34, 1, '/7.jpg', 'Brand 34', 2.15),
(35, 1, '/8.jpg', 'Brand 35', 2.05);
