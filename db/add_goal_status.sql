-- Add status column to personal_goals table if it doesn't exist
ALTER TABLE personal_goals 
ADD COLUMN status ENUM('active', 'paused', 'inactive') DEFAULT 'active' 
AFTER priority;

-- Update existing goals to have 'active' status
UPDATE personal_goals 
SET status = 'active' 
WHERE status IS NULL;
