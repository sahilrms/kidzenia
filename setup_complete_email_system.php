<?php
require_once 'config/database.php';

echo "<h1>Complete Email System Setup</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Create email_settings table
    echo "<h3>Setting up Email Settings Table...</h3>";
    $createSettingsTableSQL = "
    CREATE TABLE IF NOT EXISTS email_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($createSettingsTableSQL);
    echo "<p style='color: green;'>✓ Email settings table created successfully</p>";
    
    // Insert default email settings
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
        echo "<p style='color: green;'>✓ Email setting '$key' inserted/updated</p>";
    }
    
    // Create email_templates table
    echo "<h3>Setting up Email Templates Table...</h3>";
    $createTemplatesTableSQL = "
    CREATE TABLE IF NOT EXISTS email_templates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        template_name VARCHAR(100) UNIQUE NOT NULL,
        template_key VARCHAR(50) UNIQUE NOT NULL,
        subject TEXT NOT NULL,
        html_content TEXT,
        text_content TEXT,
        template_type ENUM('system', 'custom') DEFAULT 'custom',
        variables TEXT,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($createTemplatesTableSQL);
    echo "<p style='color: green;'>✓ Email templates table created successfully</p>";
    
    // Insert default system templates
    $defaultTemplates = [
        'welcome' => [
            'name' => 'Welcome Email',
            'subject' => 'Welcome to Kidzenia Kindergarten',
            'html' => '<h1>Welcome {{name}}!</h1>
<p>Thank you for joining Kidzenia Kindergarten. We are excited to have you with us.</p>
<p><strong>Your Details:</strong></p>
<ul>
    <li>Username: {{username}}</li>
    <li>Email: {{email}}</li>
    <li>Role: {{role}}</li>
</ul>
<p>If you have any questions, please don\'t hesitate to contact us.</p>
<p>Best regards,<br>Kidzenia Kindergarten Team</p>',
            'text' => 'Welcome {{name}}!

Thank you for joining Kidzenia Kindergarten. We are excited to have you with us.

Your Details:
- Username: {{username}}
- Email: {{email}}
- Role: {{role}}

If you have any questions, please don\'t hesitate to contact us.

Best regards,
Kidzenia Kindergarten Team',
            'variables' => 'name,username,email,role',
            'description' => 'Welcome email for new users'
        ],
        'announcement' => [
            'name' => 'Announcement',
            'subject' => '{{title}}',
            'html' => '<h2>{{title}}</h2>
<p>{{content}}</p>
<p><strong>Date:</strong> {{date}}</p>
<p><strong>From:</strong> {{sender}}</p>
<hr>
<p><em>This is an automated announcement from Kidzenia Kindergarten.</em></p>',
            'text' => '{{title}}

{{content}}

Date: {{date}}
From: {{sender}}

---
This is an automated announcement from Kidzenia Kindergarten.',
            'variables' => 'title,content,date,sender',
            'description' => 'General announcement template'
        ],
        'password_reset' => [
            'name' => 'Password Reset',
            'subject' => 'Password Reset Request',
            'html' => '<h1>Password Reset</h1>
<p>Hello {{name}},</p>
<p>You requested to reset your password. Click the link below to reset your password:</p>
<p><a href="{{reset_link}}" style="background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Reset Password</a></p>
<p>If you didn\'t request this password reset, please ignore this email.</p>
<p><strong>Note:</strong> This link will expire in {{expiry_hours}} hours.</p>
<p>Best regards,<br>Kidzenia Kindergarten Team</p>',
            'text' => 'Hello {{name}},

You requested to reset your password. Visit this link to reset your password:
{{reset_link}}

If you didn\'t request this password reset, please ignore this email.

Note: This link will expire in {{expiry_hours}} hours.

Best regards,
Kidzenia Kindergarten Team',
            'variables' => 'name,reset_link,expiry_hours',
            'description' => 'Password reset email template'
        ],
        'attendance_alert' => [
            'name' => 'Student Attendance Alert',
            'subject' => 'Attendance Alert - {{student_name}}',
            'html' => '<h2>Attendance Alert</h2>
<p>Dear {{parent_name}},</p>
<p>This is to inform you that your child <strong>{{student_name}}</strong> was marked as <strong>{{attendance_status}}</strong> on {{date}}.</p>
<p><strong>Details:</strong></p>
<ul>
    <li>Student: {{student_name}}</li>
    <li>Date: {{date}}</li>
    <li>Status: {{attendance_status}}</li>
    <li>Check-in: {{check_in_time}}</li>
    <li>Check-out: {{check_out_time}}</li>
    <li>Notes: {{notes}}</li>
</ul>
<p>If you have any questions about this attendance record, please contact us.</p>
<p>Best regards,<br>Kidzenia Kindergarten</p>',
            'text' => 'Dear {{parent_name}},

This is to inform you that your child {{student_name}} was marked as {{attendance_status}} on {{date}}.

Details:
- Student: {{student_name}}
- Date: {{date}}
- Status: {{attendance_status}}
- Check-in: {{check_in_time}}
- Check-out: {{check_out_time}}
- Notes: {{notes}}

If you have any questions about this attendance record, please contact us.

Best regards,
Kidzenia Kindergarten',
            'variables' => 'parent_name,student_name,date,attendance_status,check_in_time,check_out_time,notes',
            'description' => 'Attendance notification to parents'
        ],
        'event_reminder' => [
            'name' => 'Event Reminder',
            'subject' => 'Reminder: {{event_title}}',
            'html' => '<h2>Event Reminder</h2>
<p>Dear {{name}},</p>
<p>This is a friendly reminder about the upcoming event:</p>
<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
    <h3>{{event_title}}</h3>
    <p><strong>Date:</strong> {{event_date}}</p>
    <p><strong>Time:</strong> {{event_time}}</p>
    <p><strong>Location:</strong> {{event_location}}</p>
    <p><strong>Description:</strong> {{event_description}}</p>
</div>
<p>We look forward to seeing you there!</p>
<p>Best regards,<br>Kidzenia Kindergarten</p>',
            'text' => 'Dear {{name}},

This is a friendly reminder about the upcoming event:

{{event_title}}
Date: {{event_date}}
Time: {{event_time}}
Location: {{event_location}}
Description: {{event_description}}

We look forward to seeing you there!

Best regards,
Kidzenia Kindergarten',
            'variables' => 'name,event_title,event_date,event_time,event_location,event_description',
            'description' => 'Event reminder notification'
        ]
    ];
    
    $insertTemplateSQL = "INSERT INTO email_templates (template_name, template_key, subject, html_content, text_content, template_type, variables, description) 
                          VALUES (?, ?, ?, ?, ?, 'system', ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                          subject = VALUES(subject), 
                          html_content = VALUES(html_content), 
                          text_content = VALUES(text_content), 
                          variables = VALUES(variables), 
                          description = VALUES(description)";
    
    $stmt = $conn->prepare($insertTemplateSQL);
    
    foreach ($defaultTemplates as $key => $template) {
        $stmt->execute([
            $template['name'],
            $key,
            $template['subject'],
            $template['html'],
            $template['text'],
            $template['variables'],
            $template['description']
        ]);
        echo "<p style='color: green;'>✓ Template '$template[name]' inserted/updated</p>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<div class='alert alert-success'>";
    echo "<h4>✓ Email system has been successfully set up!</h4>";
    echo "<p><strong>What's been created:</strong></p>";
    echo "<ul>";
    echo "<li>Email settings table with default configuration</li>";
    echo "<li>Email templates table with 5 system templates</li>";
    echo "<li>Complete settings interface with email configuration</li>";
    echo "<li>Template management system</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li><a href='admin/settings.php?tab=email'>Configure Email Settings</a> - Set up SMTP configuration</li>";
    echo "<li><a href='admin/settings.php?tab=templates'>Manage Email Templates</a> - Create custom templates</li>";
    echo "<li><a href='test_email_service.php'>Test Email Service</a> - Verify everything works</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h4>Important:</h4>";
    echo "<p><strong>PHPMailer Required:</strong> Install PHPMailer for SMTP functionality:</p>";
    echo "<code>composer require phpmailer/phpmailer</code>";
    echo "<p>Or download manually from <a href='https://github.com/PHPMailer/PHPMailer' target='_blank'>GitHub</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Email System Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 900px;
        }
        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .btn {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3><i class="fas fa-envelope me-2"></i>Email System Setup Complete</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="admin/settings.php?tab=email" class="btn btn-primary btn-lg">
                                <i class="fas fa-cog me-2"></i>Configure Email Settings
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="admin/settings.php?tab=templates" class="btn btn-success btn-lg">
                                <i class="fas fa-file-alt me-2"></i>Manage Templates
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="test_email_service.php" class="btn btn-info btn-lg">
                                <i class="fas fa-vial me-2"></i>Test Email Service
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="admin/index.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h5>Usage Examples:</h5>
                    <pre class="bg-light p-3 rounded">
// Send template email
$emailService = new EmailService();
$result = $emailService->sendTemplateEmail(
    'welcome', 
    'user@example.com', 
    ['name' => 'John Doe', 'username' => 'john', 'email' => 'user@example.com', 'role' => 'parent']
);

// Send bulk template email
$result = $emailService->sendBulkTemplateEmail(
    'announcement', 
    ['parent1@example.com', 'parent2@example.com'], 
    ['title' => 'School Holiday', 'content' => 'School closed on Monday', 'date' => date('Y-m-d'), 'sender' => 'Admin']
);
                    </pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
