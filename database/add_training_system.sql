-- Add training_completed column to users table if it doesn't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS training_completed BOOLEAN DEFAULT FALSE;

-- Add training_account_id to link personal account with training account
ALTER TABLE users
ADD COLUMN IF NOT EXISTS training_account_id CHAR(36) DEFAULT NULL;

-- Add index for training queries
CREATE INDEX IF NOT EXISTS idx_users_training ON users(training_completed);
CREATE INDEX IF NOT EXISTS idx_users_training_account ON users(training_account_id);

-- Update existing users to have training completed (so only new users need training)
UPDATE users
SET training_completed = TRUE
WHERE training_completed IS NULL OR training_completed = FALSE;

-- Create a view to easily track training progress
CREATE OR REPLACE VIEW training_progress AS
SELECT
    u.id,
    u.email,
    up.full_name,
    u.training_completed,
    COUNT(uts.id) as tasks_completed,
    COALESCE(SUM(t.reward_amount), 0) as total_earnings
FROM users u
LEFT JOIN user_profiles up ON up.id = u.id
LEFT JOIN user_task_submissions uts ON uts.user_id = u.id AND uts.status = 'completed'
LEFT JOIN tasks t ON t.id = uts.task_id
WHERE u.email LIKE '%@training.com'
GROUP BY u.id, u.email, up.full_name, u.training_completed;
