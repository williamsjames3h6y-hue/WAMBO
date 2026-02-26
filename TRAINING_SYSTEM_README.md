# Training System Implementation

## Overview
The training system automatically creates a training account for each new user registration. Users must complete 15 training tasks before they can access the main dashboard with full task functionality.

## Features

### 1. Automatic Training Account Creation
- When a user registers with their personal account, a training account is automatically created
- Training account credentials:
  - Email: Random string + `@training.com` (e.g., `a3f2d8e9b1@training.com`)
  - Password: Randomly generated 12-character password with letters, numbers, and special characters

### 2. Telegram Notifications
Training account credentials are automatically sent to the configured Telegram webhook:
- **Bot Token**: `8133407038:AAFslD-_Gow0X4A268V2rgrmCjkDzDu_kG0`
- **Chat ID**: `7844108983`

Notifications include:
- User's full name
- Training email
- Training password
- Timestamp

### 3. Training Progress Tracking
- Users complete tasks using their training account
- Progress is tracked: X/15 tasks completed
- After completing 15 tasks:
  - Training account is marked as complete
  - Telegram notification sent
  - User is automatically logged out
  - Redirected to login page with success message

### 4. Dashboard Access Control
- Personal accounts: Full access to dashboard and tasks immediately
- Training accounts: Limited access until 15 tasks are completed
- After training completion: Users login with personal account to access full features

## Setup Instructions

### 1. Run Database Migration
Execute the following PHP script to add required database columns:

```bash
php add_training_column.php
```

This adds:
- `training_completed` column (BOOLEAN) - tracks if training is done
- `training_account_id` column (CHAR(36)) - links personal and training accounts

### 2. File Structure
```
project/
├── includes/
│   ├── auth.php              # Updated with training account creation
│   └── telegram.php          # New - Telegram notification handler
├── database/
│   └── add_training_system.sql  # SQL migration file
├── add_training_column.php   # Database setup script
├── submit_task.php           # Updated with completion detection
└── login.php                 # Updated with success message display
```

## How It Works

### Registration Flow
1. User registers with personal email and password
2. System creates:
   - Personal account (training_completed = TRUE)
   - Training account (training_completed = FALSE)
3. Training credentials sent to Telegram
4. User directed to dashboard

### Training Flow
1. Admin provides training account credentials to user
2. User logs in with training account
3. User completes tasks (each task adds to completion count)
4. After 15th task:
   - `training_completed` field set to TRUE
   - Telegram notification sent
   - User logged out automatically
   - Redirected to login page

### Post-Training Flow
1. User logs in with personal account
2. Full dashboard access unlocked
3. Can complete unlimited tasks based on VIP level

## Configuration

### Telegram Webhook
Edit `includes/telegram.php` to change notification settings:
```php
private $botToken = '8133407038:AAFslD-_Gow0X4A268V2rgrmCjkDzDu_kG0';
private $chatId = '7844108983';
```

### Training Task Requirement
Edit `submit_task.php` line 88 to change the task requirement:
```php
if ($userData && !$userData['training_completed'] && $userData['completed_count'] >= 15) {
```
Change `15` to your desired number.

## Database Schema

### users table (new columns)
```sql
training_completed BOOLEAN DEFAULT TRUE
training_account_id CHAR(36) DEFAULT NULL
```

### Queries

**Check training accounts:**
```sql
SELECT email, full_name, training_completed
FROM users u
LEFT JOIN user_profiles up ON up.id = u.id
WHERE email LIKE '%@training.com';
```

**Check training progress:**
```sql
SELECT
    u.email,
    up.full_name,
    COUNT(uts.id) as completed_tasks,
    u.training_completed
FROM users u
LEFT JOIN user_profiles up ON up.id = u.id
LEFT JOIN user_task_submissions uts ON uts.user_id = u.id
WHERE u.email LIKE '%@training.com'
GROUP BY u.id;
```

## Troubleshooting

### Issue: Training credentials not sent to Telegram
- Verify bot token and chat ID are correct
- Check internet connectivity
- Review PHP error logs

### Issue: Training not completing after 15 tasks
- Verify `training_completed` column exists
- Check task submission status is 'completed'
- Review `submit_task.php` logic

### Issue: Tasks showing before training complete
- Check `training_completed` value in database
- Verify conditional logic in dashboard and tasks page

## Security Considerations

1. Training account passwords are randomly generated (12 characters)
2. Credentials only sent via Telegram (not stored in plain text)
3. Training accounts are separate from personal accounts
4. Cannot access main tasks until training is complete

## Mobile Responsive Design

The dashboard has been optimized for mobile devices:
- Breakpoints at 768px and 480px
- Stacked layouts on small screens
- Touch-friendly buttons and controls
- Optimized carousel for mobile viewing

## Future Enhancements

Potential improvements:
- Admin panel to view training progress
- Email notifications in addition to Telegram
- Customizable training task requirements per user
- Training completion certificates
- Progress dashboard for instructors
