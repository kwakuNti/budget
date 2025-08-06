-- Database Update Script for Cycle Integration
-- Run this on your existing database to add cycle functionality

USE budget;

-- Update triggers to handle cycle performance updates
DROP TRIGGER IF EXISTS after_contribution_insert;

DELIMITER //
CREATE TRIGGER after_contribution_insert 
AFTER INSERT ON family_contributions
FOR EACH ROW
BEGIN
    DECLARE v_member_id INT;
    DECLARE v_member_type VARCHAR(10);
    
    -- Update family pool
    UPDATE family_groups 
    SET total_pool = total_pool + NEW.amount,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.family_id;
    
    -- Determine member info
    IF NEW.contributor_type = 'user' AND NEW.member_id IS NOT NULL THEN
        SET v_member_id = NEW.member_id;
        SET v_member_type = 'user';
        
        UPDATE family_members 
        SET total_contributed = total_contributed + NEW.amount,
            current_month_contributed = current_month_contributed + NEW.amount,
            last_payment_date = NEW.contribution_date
        WHERE id = NEW.member_id;
        
    ELSEIF NEW.contributor_type = 'member' AND NEW.member_only_id IS NOT NULL THEN
        SET v_member_id = NEW.member_only_id;
        SET v_member_type = 'member';
        
        UPDATE family_members_only 
        SET total_contributed = total_contributed + NEW.amount,
            current_month_contributed = current_month_contributed + NEW.amount,
            last_payment_date = NEW.contribution_date
        WHERE id = NEW.member_only_id;
    END IF;
    
    -- Update member monthly performance if cycle exists
    IF v_member_id IS NOT NULL THEN
        UPDATE member_monthly_performance mmp
        JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
        SET 
            mmp.contributed_amount = mmp.contributed_amount + NEW.amount,
            mmp.contribution_count = mmp.contribution_count + 1,
            mmp.last_contribution_date = NEW.contribution_date,
            mmp.first_contribution_date = COALESCE(mmp.first_contribution_date, NEW.contribution_date),
            mmp.is_completed = (mmp.contributed_amount + NEW.amount >= mmp.target_amount),
            mmp.completed_date = CASE 
                WHEN (mmp.contributed_amount + NEW.amount >= mmp.target_amount) AND mmp.completed_date IS NULL 
                THEN NEW.contribution_date 
                ELSE mmp.completed_date 
            END
        WHERE mc.family_id = NEW.family_id 
        AND mc.is_current = TRUE 
        AND mc.is_closed = FALSE
        AND ((v_member_type = 'user' AND mmp.member_id = v_member_id) OR 
             (v_member_type = 'member' AND mmp.member_only_id = v_member_id));
             
        -- Update cycle totals
        UPDATE monthly_cycles mc
        SET 
            mc.total_collected = (
                SELECT COALESCE(SUM(mmp2.contributed_amount), 0) 
                FROM member_monthly_performance mmp2 
                WHERE mmp2.cycle_id = mc.id
            ),
            mc.members_completed = (
                SELECT COUNT(*) 
                FROM member_monthly_performance mmp2 
                WHERE mmp2.cycle_id = mc.id AND mmp2.is_completed = TRUE
            ),
            mc.members_pending = (
                SELECT COUNT(*) 
                FROM member_monthly_performance mmp2 
                WHERE mmp2.cycle_id = mc.id AND mmp2.is_completed = FALSE
            )
        WHERE mc.family_id = NEW.family_id 
        AND mc.is_current = TRUE 
        AND mc.is_closed = FALSE;
        
        -- Update member goal completion status
        IF v_member_type = 'user' THEN
            UPDATE family_members fm
            SET goal_met_this_month = (fm.current_month_contributed >= fm.monthly_contribution_goal)
            WHERE fm.id = v_member_id;
        ELSE
            UPDATE family_members_only fmo
            SET goal_met_this_month = (fmo.current_month_contributed >= fmo.monthly_contribution_goal)
            WHERE fmo.id = v_member_id;
        END IF;
    END IF;
END//

DELIMITER ;

-- Create initialization procedure for existing families
DELIMITER //

CREATE PROCEDURE InitializeExistingFamilyCycles()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_family_id INT;
    DECLARE v_current_month VARCHAR(7);
    DECLARE v_cycle_exists INT;
    
    DECLARE family_cursor CURSOR FOR
        SELECT id FROM family_groups WHERE id > 0;
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    SET v_current_month = DATE_FORMAT(CURDATE(), '%Y-%m');
    
    OPEN family_cursor;
    
    family_loop: LOOP
        FETCH family_cursor INTO v_family_id;
        
        IF done THEN
            LEAVE family_loop;
        END IF;
        
        -- Check if current cycle already exists
        SELECT COUNT(*) INTO v_cycle_exists
        FROM monthly_cycles 
        WHERE family_id = v_family_id 
        AND cycle_month = v_current_month;
        
        -- Create cycle if it doesn't exist
        IF v_cycle_exists = 0 THEN
            CALL CreateNewMonthlyCycle(v_family_id, YEAR(CURDATE()), MONTH(CURDATE()));
        END IF;
        
    END LOOP;
    
    CLOSE family_cursor;
END //

DELIMITER ;

-- Reset current month contributions (since we're implementing cycles)
UPDATE family_members SET current_month_contributed = 0.00, goal_met_this_month = FALSE;
UPDATE family_members_only SET current_month_contributed = 0.00, goal_met_this_month = FALSE;

-- Initialize cycles for all existing families
CALL InitializeExistingFamilyCycles();

-- Update current month contributions based on current month's actual contributions
UPDATE family_members fm
SET current_month_contributed = (
    SELECT COALESCE(SUM(fc.amount), 0)
    FROM family_contributions fc
    WHERE fc.member_id = fm.id 
    AND fc.contributor_type = 'user'
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
),
goal_met_this_month = (
    SELECT COALESCE(SUM(fc.amount), 0)
    FROM family_contributions fc
    WHERE fc.member_id = fm.id 
    AND fc.contributor_type = 'user'
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
) >= fm.monthly_contribution_goal;

UPDATE family_members_only fmo
SET current_month_contributed = (
    SELECT COALESCE(SUM(fc.amount), 0)
    FROM family_contributions fc
    WHERE fc.member_only_id = fmo.id 
    AND fc.contributor_type = 'member'
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
),
goal_met_this_month = (
    SELECT COALESCE(SUM(fc.amount), 0)
    FROM family_contributions fc
    WHERE fc.member_only_id = fmo.id 
    AND fc.contributor_type = 'member'
    AND YEAR(fc.contribution_date) = YEAR(CURDATE())
    AND MONTH(fc.contribution_date) = MONTH(CURDATE())
) >= fmo.monthly_contribution_goal;

-- Update member performance records with current month data
UPDATE member_monthly_performance mmp
JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
SET mmp.contributed_amount = (
    CASE 
        WHEN mmp.member_type = 'user' THEN (
            SELECT COALESCE(SUM(fc.amount), 0)
            FROM family_contributions fc
            WHERE fc.member_id = mmp.member_id 
            AND fc.contributor_type = 'user'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        )
        WHEN mmp.member_type = 'member' THEN (
            SELECT COALESCE(SUM(fc.amount), 0)
            FROM family_contributions fc
            WHERE fc.member_only_id = mmp.member_only_id 
            AND fc.contributor_type = 'member'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        )
        ELSE 0
    END
),
mmp.contribution_count = (
    CASE 
        WHEN mmp.member_type = 'user' THEN (
            SELECT COUNT(*)
            FROM family_contributions fc
            WHERE fc.member_id = mmp.member_id 
            AND fc.contributor_type = 'user'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        )
        WHEN mmp.member_type = 'member' THEN (
            SELECT COUNT(*)
            FROM family_contributions fc
            WHERE fc.member_only_id = mmp.member_only_id 
            AND fc.contributor_type = 'member'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        )
        ELSE 0
    END
),
mmp.is_completed = (
    CASE 
        WHEN mmp.member_type = 'user' THEN (
            SELECT COALESCE(SUM(fc.amount), 0)
            FROM family_contributions fc
            WHERE fc.member_id = mmp.member_id 
            AND fc.contributor_type = 'user'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        ) >= mmp.target_amount
        WHEN mmp.member_type = 'member' THEN (
            SELECT COALESCE(SUM(fc.amount), 0)
            FROM family_contributions fc
            WHERE fc.member_only_id = mmp.member_only_id 
            AND fc.contributor_type = 'member'
            AND YEAR(fc.contribution_date) = mc.cycle_year
            AND MONTH(fc.contribution_date) = mc.cycle_month_num
        ) >= mmp.target_amount
        ELSE FALSE
    END
)
WHERE mc.is_current = TRUE;

-- Update cycle totals
UPDATE monthly_cycles mc
SET 
    mc.total_collected = (
        SELECT COALESCE(SUM(mmp.contributed_amount), 0) 
        FROM member_monthly_performance mmp 
        WHERE mmp.cycle_id = mc.id
    ),
    mc.members_completed = (
        SELECT COUNT(*) 
        FROM member_monthly_performance mmp 
        WHERE mmp.cycle_id = mc.id AND mmp.is_completed = TRUE
    ),
    mc.members_pending = (
        SELECT COUNT(*) 
        FROM member_monthly_performance mmp 
        WHERE mmp.cycle_id = mc.id AND mmp.is_completed = FALSE
    )
WHERE mc.is_current = TRUE;

-- Create a maintenance job procedure
DELIMITER //

CREATE PROCEDURE RunCycleMaintenance()
BEGIN
    -- Auto-close overdue cycles (3 days after month end)
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_cycle_id INT;
    DECLARE v_family_id INT;
    DECLARE v_end_date DATE;
    
    DECLARE overdue_cursor CURSOR FOR
        SELECT id, family_id, end_date 
        FROM monthly_cycles 
        WHERE is_current = TRUE 
        AND is_closed = FALSE 
        AND end_date <= DATE_SUB(CURDATE(), INTERVAL 3 DAY);
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN overdue_cursor;
    
    maintenance_loop: LOOP
        FETCH overdue_cursor INTO v_cycle_id, v_family_id, v_end_date;
        
        IF done THEN
            LEAVE maintenance_loop;
        END IF;
        
        -- Auto-close cycle
        CALL CloseMonthlyCycle(v_cycle_id, 1); -- System user
        
        -- Log the auto-close
        INSERT INTO activity_logs (family_id, action_type, description, created_at)
        VALUES (v_family_id, 'cycle_auto_closed', 
                CONCAT('Cycle automatically closed after grace period: ', v_end_date), 
                NOW());
        
    END LOOP;
    
    CLOSE overdue_cursor;
    
    -- Ensure all families have current cycles
    INSERT INTO monthly_cycles (
        family_id,
        cycle_month,
        cycle_year,
        cycle_month_num,
        start_date,
        end_date,
        total_target,
        members_pending
    )
    SELECT 
        fg.id,
        DATE_FORMAT(CURDATE(), '%Y-%m'),
        YEAR(CURDATE()),
        MONTH(CURDATE()),
        DATE_FORMAT(CURDATE(), '%Y-%m-01'),
        LAST_DAY(CURDATE()),
        COALESCE(SUM(fm.monthly_contribution_goal), 0) + COALESCE(SUM(fmo.monthly_contribution_goal), 0),
        COUNT(fm.id) + COUNT(fmo.id)
    FROM family_groups fg
    LEFT JOIN family_members fm ON fg.id = fm.family_id AND fm.is_active = TRUE
    LEFT JOIN family_members_only fmo ON fg.id = fmo.family_id AND fmo.is_active = TRUE
    LEFT JOIN monthly_cycles mc ON fg.id = mc.family_id AND mc.is_current = TRUE
    WHERE mc.id IS NULL
    GROUP BY fg.id
    HAVING COUNT(fm.id) + COUNT(fmo.id) > 0;
    
    -- Create performance records for new cycles
    INSERT INTO member_monthly_performance (
        cycle_id,
        family_id,
        member_id,
        member_type,
        target_amount
    )
    SELECT 
        mc.id,
        mc.family_id,
        fm.id,
        'user',
        fm.monthly_contribution_goal
    FROM monthly_cycles mc
    JOIN family_members fm ON mc.family_id = fm.family_id
    LEFT JOIN member_monthly_performance mmp ON mc.id = mmp.cycle_id AND mmp.member_id = fm.id
    WHERE mc.is_current = TRUE 
    AND fm.is_active = TRUE 
    AND fm.monthly_contribution_goal > 0
    AND mmp.id IS NULL;
    
    INSERT INTO member_monthly_performance (
        cycle_id,
        family_id,
        member_only_id,
        member_type,
        target_amount
    )
    SELECT 
        mc.id,
        mc.family_id,
        fmo.id,
        'member',
        fmo.monthly_contribution_goal
    FROM monthly_cycles mc
    JOIN family_members_only fmo ON mc.family_id = fmo.family_id
    LEFT JOIN member_monthly_performance mmp ON mc.id = mmp.cycle_id AND mmp.member_only_id = fmo.id
    WHERE mc.is_current = TRUE 
    AND fmo.is_active = TRUE 
    AND fmo.monthly_contribution_goal > 0
    AND mmp.id IS NULL;
    
END //

DELIMITER ;

-- Create indexes for performance
-- Note: If you get "Duplicate key name" errors, it means indexes already exist - you can ignore these errors

-- Index for contribution queries by family and date
CREATE INDEX idx_contributions_current_month ON family_contributions(family_id, contribution_date);

-- Index for finding current active cycles  
CREATE INDEX idx_cycles_current ON monthly_cycles(family_id, is_current, is_closed);

-- Index for member performance lookups
CREATE INDEX idx_performance_cycle_member ON member_monthly_performance(cycle_id, member_id, member_only_id);

-- Create a view for easy dashboard queries
CREATE OR REPLACE VIEW v_family_dashboard_summary AS
SELECT 
    fg.id as family_id,
    fg.family_name,
    fg.total_pool,
    mc.id as current_cycle_id,
    mc.cycle_month,
    mc.total_target,
    mc.total_collected,
    mc.members_completed,
    mc.members_pending,
    DATEDIFF(mc.end_date, CURDATE()) as days_remaining,
    CASE 
        WHEN mc.total_target > 0 THEN ROUND((mc.total_collected / mc.total_target) * 100, 2)
        ELSE 0 
    END as completion_percentage,
    (SELECT COUNT(*) FROM family_members WHERE family_id = fg.id AND is_active = TRUE) +
    (SELECT COUNT(*) FROM family_members_only WHERE family_id = fg.id AND is_active = TRUE) as total_members,
    (SELECT COALESCE(SUM(deficit_amount), 0) FROM member_debt_history WHERE family_id = fg.id AND is_cleared = FALSE) as total_outstanding_debt,
    (SELECT COUNT(DISTINCT CASE 
        WHEN member_type = 'user' THEN member_id 
        ELSE member_only_id 
    END) FROM member_debt_history WHERE family_id = fg.id AND is_cleared = FALSE) as members_with_debt
FROM family_groups fg
LEFT JOIN monthly_cycles mc ON fg.id = mc.family_id AND mc.is_current = TRUE AND mc.is_closed = FALSE;

-- Add system setting for auto-maintenance
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('cycle_auto_maintenance', '1', 'Enable automatic cycle maintenance (auto-close overdue cycles)'),
('cycle_grace_period_days', '3', 'Days after month end before auto-closing cycles'),
('debt_auto_clear', '1', 'Automatically clear oldest debt when member completes monthly goal'),
('cycle_reminder_enabled', '1', 'Send reminders before cycle ends'),
('cycle_reminder_days_before', '7,3,1', 'Days before cycle end to send reminders (comma-separated)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Log the update
INSERT INTO activity_logs (action_type, description, created_at)
VALUES ('system_update', 'Cycle management system installed and initialized', NOW());

-- Display completion message
SELECT 'Cycle management system successfully installed!' as status,
       'Run CALL RunCycleMaintenance(); periodically to maintain cycles' as maintenance_note,
       'All existing data has been preserved and integrated' as data_note;