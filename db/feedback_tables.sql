-- ============================================================================
-- USER FEEDBACK SYSTEM TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS user_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    feedback_type ENUM('bug_report', 'feature_request', 'general', 'complaint', 'compliment') DEFAULT 'general',
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    page_url VARCHAR(500) NULL,
    browser_info TEXT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
    admin_response TEXT NULL,
    admin_user_id INT NULL,
    rating INT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_feedback_user (user_id),
    INDEX idx_feedback_status (status, created_at),
    INDEX idx_feedback_type (feedback_type, created_at),
    INDEX idx_feedback_priority (priority, status)
);

-- Create feedback attachments table for future use
CREATE TABLE IF NOT EXISTS feedback_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    feedback_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (feedback_id) REFERENCES user_feedback(id) ON DELETE CASCADE,
    INDEX idx_attachment_feedback (feedback_id)
);

SELECT 'Feedback System Tables Created Successfully!' as STATUS;
