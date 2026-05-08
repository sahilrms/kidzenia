<?php
session_start();
require_once '../config/EmailService.php';
require_once '../config/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$emailService = new EmailService();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_email_settings'])) {
        $settings = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '587',
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'from_email' => $_POST['from_email'] ?? '',
            'from_name' => $_POST['from_name'] ?? '',
            'email_enabled' => isset($_POST['email_enabled']) ? '1' : '0',
            'test_email' => $_POST['test_email'] ?? ''
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            $result = $emailService->updateSetting($key, $value);
            if (!$result['success']) {
                $success = false;
                $message = $result['message'];
                $messageType = 'error';
                break;
            }
        }
        
        if ($success) {
            $message = 'Email settings saved successfully!';
            $messageType = 'success';
        }
    } elseif (isset($_POST['test_email'])) {
        $result = $emailService->testEmailConfiguration();
        if ($result['success']) {
            $message = 'Test email sent successfully!';
            $messageType = 'success';
        } else {
            $message = 'Test email failed: ' . $result['message'];
            $messageType = 'error';
        }
    } elseif (isset($_POST['save_template'])) {
        try {
            $conn = $emailService->db->getConnection();
            $query = "INSERT INTO email_templates (template_name, template_key, subject, html_content, text_content, variables, description, template_type) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'custom') 
                     ON DUPLICATE KEY UPDATE 
                     subject = VALUES(subject), 
                     html_content = VALUES(html_content), 
                     text_content = VALUES(text_content), 
                     variables = VALUES(variables), 
                     description = VALUES(description),
                     updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $_POST['template_name'],
                $_POST['template_key'],
                $_POST['subject'],
                $_POST['html_content'],
                $_POST['text_content'],
                $_POST['variables'],
                $_POST['description']
            ]);
            
            $message = 'Template saved successfully!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Failed to save template: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_template'])) {
        try {
            $conn = $emailService->db->getConnection();
            $query = "DELETE FROM email_templates WHERE id = ? AND template_type = 'custom'";
            $stmt = $conn->prepare($query);
            $stmt->execute([$_POST['template_id']]);
            
            $message = 'Template deleted successfully!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Failed to delete template: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif (isset($_POST['toggle_template'])) {
        try {
            $conn = $emailService->db->getConnection();
            $query = "UPDATE email_templates SET is_active = NOT is_active WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$_POST['template_id']]);
            
            $message = 'Template status updated!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Failed to update template: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$settings = $emailService->getSettings();

// Get email templates
$templates = $emailService->getAllTemplates();

$activeTab = $_GET['tab'] ?? 'general';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Kidzenia Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-radius: 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #34495e;
            color: #fff;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 600;
            color: #2c3e50;
        }
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            border-bottom-color: #3498db;
            color: #3498db;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .alert {
            border: none;
            border-radius: 8px;
        }
        .template-card {
            transition: transform 0.2s;
        }
        .template-card:hover {
            transform: translateY(-2px);
        }
        .badge-system {
            background-color: #6c757d;
        }
        .badge-custom {
            background-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0 sidebar">
                <?php include 'components/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container-fluid p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-cog me-2"></i>Settings</h2>
                        <button class="btn btn-outline-primary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </button>
                    </div>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'general' ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?tab=general'">
                                <i class="fas fa-cog me-2"></i>General Settings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'email' ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?tab=email'">
                                <i class="fas fa-envelope me-2"></i>Email Configuration
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'templates' ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?tab=templates'">
                                <i class="fas fa-file-alt me-2"></i>Email Templates
                            </button>
                        </li>
                    </ul>
                    
                    <?php if ($activeTab === 'general'): ?>
                        <!-- General Settings Tab -->
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-school me-2"></i>School Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="mb-3">
                                                <label for="school_name" class="form-label">School Name</label>
                                                <input type="text" class="form-control" id="school_name" name="school_name" 
                                                       value="Kidzenia Kindergarten" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label for="school_address" class="form-label">School Address</label>
                                                <textarea class="form-control" id="school_address" name="school_address" rows="3" readonly>123 Education Street, Learning City</textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="school_phone" class="form-label">School Phone</label>
                                                        <input type="tel" class="form-control" id="school_phone" name="school_phone" 
                                                               value="+1234567890" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="academic_year" class="form-label">Academic Year</label>
                                                        <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                                               value="2024-2025" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($activeTab === 'email'): ?>
                        <!-- Email Configuration Tab -->
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>SMTP Configuration</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled" 
                                                           <?php echo ($settings['email_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="email_enabled">
                                                        <strong>Enable Email Service</strong>
                                                    </label>
                                                </div>
                                                <small class="text-muted">Turn on to enable email sending functionality</small>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="smtp_host" class="form-label">SMTP Host</label>
                                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com'); ?>" required>
                                                        <small class="text-muted">e.g., smtp.gmail.com</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>" required>
                                                        <small class="text-muted">e.g., 587 (TLS) or 465 (SSL)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="smtp_username" class="form-label">SMTP Username</label>
                                                <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                                                       value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" required>
                                                <small class="text-muted">Your email address</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="smtp_password" class="form-label">SMTP Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                                           value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('smtp_password')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">Use app password for Gmail, not regular password</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="smtp_encryption" class="form-label">Encryption</label>
                                                <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                                    <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                    <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                    <option value="none" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'none' ? 'selected' : ''; ?>>None</option>
                                                </select>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Email Identity</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="mb-3">
                                                <label for="from_email" class="form-label">From Email Address</label>
                                                <input type="email" class="form-control" id="from_email" name="from_email" 
                                                       value="<?php echo htmlspecialchars($settings['from_email'] ?? 'noreply@kidzenia.com'); ?>" required>
                                                <small class="text-muted">Default sender email address</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="from_name" class="form-label">From Name</label>
                                                <input type="text" class="form-control" id="from_name" name="from_name" 
                                                       value="<?php echo htmlspecialchars($settings['from_name'] ?? 'Kidzenia Kindergarten'); ?>" required>
                                                <small class="text-muted">Default sender name</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="test_email" class="form-label">Test Email Address</label>
                                                <input type="email" class="form-control" id="test_email" name="test_email" 
                                                       value="<?php echo htmlspecialchars($settings['test_email'] ?? ''); ?>">
                                                <small class="text-muted">Email address for testing configuration</small>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="submit" name="save_email_settings" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Save Settings
                                                </button>
                                                <button type="submit" name="test_email" class="btn btn-success">
                                                    <i class="fas fa-paper-plane me-2"></i>Send Test Email
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Help & Tips</h5>
                                    </div>
                                    <div class="card-body">
                                        <h6>Gmail Configuration:</h6>
                                        <ul class="small">
                                            <li>Host: smtp.gmail.com</li>
                                            <li>Port: 587 (TLS) or 465 (SSL)</li>
                                            <li>Use App Password, not regular password</li>
                                            <li>Enable 2-factor authentication</li>
                                        </ul>
                                        
                                        <h6 class="mt-3">Outlook Configuration:</h6>
                                        <ul class="small">
                                            <li>Host: smtp-mail.outlook.com</li>
                                            <li>Port: 587 (TLS)</li>
                                            <li>Username: your email</li>
                                        </ul>
                                        
                                        <div class="alert alert-info mt-3">
                                            <small><strong>Note:</strong> For Gmail, generate an App Password from your Google Account settings under Security.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($activeTab === 'templates'): ?>
                        <!-- Email Templates Tab -->
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card mb-4">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Create/Edit Template</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="template_name" class="form-label">Template Name</label>
                                                        <input type="text" class="form-control" id="template_name" name="template_name" 
                                                               placeholder="e.g., Parent Newsletter" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="template_key" class="form-label">Template Key</label>
                                                        <input type="text" class="form-control" id="template_key" name="template_key" 
                                                               placeholder="e.g., parent_newsletter" required>
                                                        <small class="text-muted">Unique identifier for code</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="subject" class="form-label">Subject</label>
                                                <input type="text" class="form-control" id="subject" name="subject" 
                                                       placeholder="e.g., {{title}} - Kidzenia Newsletter" required>
                                                <small class="text-muted">Use {{variable}} for placeholders</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="html_content" class="form-label">HTML Content</label>
                                                <textarea class="form-control" id="html_content" name="html_content" rows="6" 
                                                          placeholder="<h1>{{title}}</h1><p>{{content}}</p>" required></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="text_content" class="form-label">Text Content</label>
                                                <textarea class="form-control" id="text_content" name="text_content" rows="4" 
                                                          placeholder="{{title}}&#10;{{content}}" required></textarea>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="variables" class="form-label">Variables</label>
                                                        <input type="text" class="form-control" id="variables" name="variables" 
                                                               placeholder="title,content,date,sender">
                                                        <small class="text-muted">Comma-separated variable names</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="description" class="form-label">Description</label>
                                                        <input type="text" class="form-control" id="description" name="description" 
                                                               placeholder="Template description">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" name="save_template" class="btn btn-success">
                                                <i class="fas fa-save me-2"></i>Save Template
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><i class="fas fa-info me-2"></i>Template Variables</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="small">Use these variables in your templates:</p>
                                        <ul class="small">
                                            <li><code>{{name}}</code> - Recipient name</li>
                                            <li><code>{{email}}</code> - Recipient email</li>
                                            <li><code>{{title}}</code> - Message title</li>
                                            <li><code>{{content}}</code> - Main content</li>
                                            <li><code>{{date}}</code> - Current date</li>
                                            <li><code>{{sender}}</code> - Sender name</li>
                                            <li><code>{{school_name}}</code> - School name</li>
                                            <li><code>{{student_name}}</code> - Student name</li>
                                            <li><code>{{class_name}}</code> - Class name</li>
                                        </ul>
                                        
                                        <div class="alert alert-warning mt-3">
                                            <small><strong>Tip:</strong> System templates (marked in gray) cannot be edited or deleted.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Existing Templates -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Existing Templates</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($templates as $template): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card template-card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0"><?php echo htmlspecialchars($template['template_name']); ?></h6>
                                                        <span class="badge <?php echo $template['template_type'] === 'system' ? 'badge-system' : 'badge-custom'; ?>">
                                                            <?php echo ucfirst($template['template_type']); ?>
                                                        </span>
                                                    </div>
                                                    <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars($template['description']); ?></p>
                                                    <p class="small mb-2"><strong>Key:</strong> <code><?php echo htmlspecialchars($template['template_key']); ?></code></p>
                                                    <p class="small mb-2"><strong>Subject:</strong> <?php echo htmlspecialchars($template['subject']); ?></p>
                                                    <p class="small mb-3"><strong>Variables:</strong> <?php echo htmlspecialchars($template['variables']); ?></p>
                                                    
                                                    <div class="d-flex gap-1">
                                                        <?php if ($template['template_type'] === 'custom'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                                <button type="submit" name="toggle_template" class="btn btn-sm <?php echo $template['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                                    <i class="fas <?php echo $template['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                                    <?php echo $template['is_active'] ? 'Disable' : 'Enable'; ?>
                                                                </button>
                                                            </form>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                                <button type="submit" name="delete_template" class="btn btn-sm btn-danger" 
                                                                        onclick="return confirm('Are you sure you want to delete this template?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="badge bg-<?php echo $template['is_active'] ? 'success' : 'secondary'; ?>">
                                                                <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
