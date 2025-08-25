-- Privacy System for Budget App
-- Creates tables for PIN protection and privacy settings

CREATE TABLE IF NOT EXISTS user_privacy_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    privacy_pin VARCHAR(255), -- Hashed PIN
    privacy_enabled BOOLEAN DEFAULT FALSE,
    pin_set_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_pin_reset TIMESTAMP NULL,
    pin_reset_token VARCHAR(100) NULL,
    pin_reset_expires TIMESTAMP NULL,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_privacy (user_id)
);

-- Privacy session tracking (for temporary access)
CREATE TABLE IF NOT EXISTS privacy_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_token (user_id, session_token),
    INDEX idx_expires (expires_at)
);

-- Insert default privacy settings for existing users
INSERT IGNORE INTO user_privacy_settings (user_id, privacy_enabled) 
SELECT id, FALSE FROM users;
