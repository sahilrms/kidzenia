-- Email Settings Table
CREATE TABLE email_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Default Email Settings
INSERT INTO email_settings (setting_key, setting_value, description) VALUES 
('smtp_host', 'smtp.gmail.com', 'SMTP server hostname'),
('smtp_port', '587', 'SMTP server port'),
('smtp_username', '', 'SMTP username (usually email address)'),
('smtp_password', '', 'SMTP password or app password'),
('smtp_encryption', 'tls', 'Encryption type (tls, ssl, or none)'),
('from_email', 'noreply@kidzenia.com', 'Default from email address'),
('from_name', 'Kidzenia Kindergarten', 'Default from name'),
('email_enabled', '0', 'Enable email sending (0/1)'),
('test_email', '', 'Email address for testing email functionality');
