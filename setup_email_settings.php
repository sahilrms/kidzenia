<?php
require_once 'config/database.php';

echo "<h1>Setup Email Settings</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Create email_settings table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS email_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($createTableSQL);
    echo "<p style='color: green;'>✓ Email settings table created successfully</p>";
    
    // Insert default settings
    $defaultSettings = [
        'smtp_host' => ['value' => 'smtp.gmail.com', 'description' => 'SMTP server hostname'],
        'smtp_port' => ['value' => '587', 'description' => 'SMTP server port'],
        'smtp_username' => ['value' => '', 'description' => 'SMTP username (usually email address)'],
        'smtp_password' => ['value' => '', 'description' => 'SMTP password or app password'],
        'smtp_encryption' => ['value' => 'tls', 'description' => 'Encryption type (tls, ssl, or none)'],
        'from_email' => ['value' => 'noreply@kidzenia.com', 'description' => 'Default from email address'],
        'from_name' => ['value' => 'Kidzenia Kindergarten', 'description' => 'Default from name'],
        'email_enabled' => ['value' => '0', 'description' => 'Enable email sending (0/1)'],
        'test_email' => ['value' => '', 'description' => 'Email address for testing email functionality']
    ];
    
    $insertSQL = "INSERT INTO email_settings (setting_key, setting_value, description) VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description)";
    
    $stmt = $conn->prepare($insertSQL);
    
    foreach ($defaultSettings as $key => $setting) {
        $stmt->execute([$key, $setting['value'], $setting['description']]);
        echo "<p style='color: green;'>✓ Setting '$key' inserted/updated</p>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p><a href='admin/email_settings.php'>Go to Email Settings</a></p>";
    echo "<p><a href='test_email_service.php'>Test Email Service</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Email Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h3>Email Service Setup</h3>
            </div>
            <div class="card-body">
                <p>This script sets up the email settings table and default configuration for the Kidzenia email service.</p>
                
                <div class="alert alert-warning">
                    <strong>Note:</strong> Make sure you have PHPMailer installed for SMTP functionality:
                    <code>composer require phpmailer/phpmailer</code>
                </div>
                
                <div class="mt-3">
                    <a href="admin/email_settings.php" class="btn btn-primary">Configure Email Settings</a>
                    <a href="test_email_service.php" class="btn btn-success">Test Email Service</a>
                    <a href="index.php" class="btn btn-secondary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
