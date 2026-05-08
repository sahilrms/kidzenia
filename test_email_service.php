<?php
require_once 'config/EmailService.php';

// Create EmailService instance
$emailService = new EmailService();

// Test basic email functionality
echo "<h1>Email Service Test</h1>";

// Check if email is enabled
if (!$emailService->isEnabled()) {
    echo "<p style='color: orange;'>Email service is disabled. Please configure email settings in admin panel.</p>";
} else {
    echo "<p style='color: green;'>Email service is enabled.</p>";
}

// Get current settings
$settings = $emailService->getSettings();
echo "<h2>Current Email Settings:</h2>";
echo "<ul>";
echo "<li>SMTP Host: " . htmlspecialchars($settings['smtp_host'] ?? 'Not set') . "</li>";
echo "<li>SMTP Port: " . htmlspecialchars($settings['smtp_port'] ?? 'Not set') . "</li>";
echo "<li>SMTP Username: " . htmlspecialchars($settings['smtp_username'] ?? 'Not set') . "</li>";
echo "<li>From Email: " . htmlspecialchars($settings['from_email'] ?? 'Not set') . "</li>";
echo "<li>From Name: " . htmlspecialchars($settings['from_name'] ?? 'Not set') . "</li>";
echo "<li>Encryption: " . htmlspecialchars($settings['smtp_encryption'] ?? 'Not set') . "</li>";
echo "</ul>";

// Test template functionality
echo "<h2>Template Test:</h2>";
$template = $emailService->createEmailTemplate('welcome', ['name' => 'John Doe']);
echo "<p><strong>Subject:</strong> " . htmlspecialchars($template['subject']) . "</p>";
echo "<p><strong>HTML:</strong> " . htmlspecialchars($template['html']) . "</p>";
echo "<p><strong>Text:</strong> " . htmlspecialchars($template['text']) . "</p>";

// Test email sending if test email is configured
if (!empty($settings['test_email'])) {
    echo "<h2>Send Test Email:</h2>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='send_test' value='1'>";
    echo "<button type='submit' class='btn btn-primary'>Send Test Email to " . htmlspecialchars($settings['test_email']) . "</button>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
        $result = $emailService->sendEmail(
            $settings['test_email'],
            'Test Email from Kidzenia',
            'This is a test email to verify the email service is working correctly.',
            null,
            null,
            false
        );
        
        if ($result['success']) {
            echo "<p style='color: green;'>Test email sent successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to send test email: " . htmlspecialchars($result['message']) . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>No test email configured. Please set a test email address in the admin settings.</p>";
}

// Usage examples
echo "<h2>Usage Examples:</h2>";
echo "<pre>";
echo "// Basic usage
\$emailService = new EmailService();
\$result = \$emailService->sendEmail(
    'recipient@example.com',
    'Subject',
    'Email message',
    'sender@example.com',
    'Sender Name'
);

// Send HTML email
\$result = \$emailService->sendEmail(
    'recipient@example.com',
    'HTML Subject',
    '&lt;h1&gt;HTML Message&lt;/h1&gt;',
    null,
    null,
    true
);

// Bulk email
\$recipients = ['user1@example.com', 'user2@example.com'];
\$result = \$emailService->sendBulkEmail(
    \$recipients,
    'Bulk Subject',
    'Bulk message'
);

// Use template
\$template = \$emailService->createEmailTemplate('welcome', ['name' => 'John']);
\$result = \$emailService->sendEmail(
    'user@example.com',
    \$template['subject'],
    \$template['html'],
    null,
    null,
    true
);
";
echo "</pre>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Service Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h3>Email Service Configuration Status</h3>
            </div>
            <div class="card-body">
                <p><strong>Admin Panel:</strong> <a href="admin/email_settings.php">Configure Email Settings</a></p>
                <p><strong>Requirements:</strong> PHPMailer library for SMTP functionality</p>
                
                <div class="alert alert-info">
                    <h5>PHPMailer Installation:</h5>
                    <p>Install PHPMailer using Composer:</p>
                    <code>composer require phpmailer/phpmailer</code>
                    <p class="mt-2">Or download manually from: <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">GitHub</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
