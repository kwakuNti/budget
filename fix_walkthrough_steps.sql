-- Fix walkthrough steps - remove select_template step and renumber
USE budget;

-- Remove the select_template step that causes tooltip behind modal issues
DELETE FROM walkthrough_steps WHERE step_name = 'select_template' AND sequence_name = 'initial_setup';

-- Update step numbers to close the gap
UPDATE walkthrough_steps 
SET step_order = step_order - 1 
WHERE sequence_name = 'initial_setup' 
AND step_order > 4;

-- Verify the updated steps
SELECT step_order, step_name, title, description 
FROM walkthrough_steps 
WHERE sequence_name = 'initial_setup' 
ORDER BY step_order;
