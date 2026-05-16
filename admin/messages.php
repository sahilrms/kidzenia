<?php
require_once '../config/config.php';
require_once '../config/EmailService.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_message'])) {
        $recipient_type = $_POST['recipient_type'];
        $subject = clean_input($_POST['subject']);
        $message = clean_input($_POST['message']);
        $priority = $_POST['priority'];

        try {
            $database = new Database();
            $db = $database->getConnection();

            // Get recipients based on type
            $recipients = [];
            if ($recipient_type == 'all_parents') {
                $query = "SELECT id, email FROM users WHERE role = 'parent' AND status = 'active'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($recipient_type == 'all_teachers') {
                $query = "SELECT id, email FROM users WHERE role = 'teacher' AND status = 'active'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($recipient_type == 'all_users') {
                $query = "SELECT id, email FROM users WHERE status = 'active'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($recipient_type == 'specific_class') {
                $class_id = $_POST['class_id'];
                $query = "SELECT u.id, u.email FROM users u
                         JOIN students s ON u.id = s.parent_id
                         WHERE s.class_id = :class_id AND u.status = 'active'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':class_id', $class_id);
                $stmt->execute();
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Send messages to all recipients
            $sent_count = 0;
            foreach ($recipients as $recipient) {
                $message_query = "INSERT INTO messages (sender_id, receiver_id, subject, message)
                                  VALUES (:sender_id, :receiver_id, :subject, :message)";
                $message_stmt = $db->prepare($message_query);
                $message_stmt->bindParam(':sender_id', $_SESSION['user_id']);
                $message_stmt->bindParam(':receiver_id', $recipient['id']);
                $message_stmt->bindParam(':subject', $subject);
                $message_stmt->bindParam(':message', $message);

                if ($message_stmt->execute()) {
                    $sent_count++;
                }
            }

            if ($sent_count > 0) {
                flash_message('success', "Message sent successfully to {$sent_count} recipients!");
            } else {
                flash_message('error', 'No recipients found or failed to send messages.');
            }

        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }

        redirect('messages.php');
    }

    if (isset($_POST['delete_message'])) {
        $message_id = $_POST['message_id'];

        try {
            $database = new Database();
            $db = $database->getConnection();

            $query = "UPDATE messages
                      SET
                        is_deleted_sender = CASE WHEN sender_id = :sender_user_id THEN 1 ELSE is_deleted_sender END,
                        is_deleted_receiver = CASE WHEN receiver_id = :receiver_user_id THEN 1 ELSE is_deleted_receiver END
                      WHERE id = :message_id
                        AND (sender_id = :where_sender_user_id OR receiver_id = :where_receiver_user_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':message_id', $message_id, PDO::PARAM_INT);
            $stmt->bindParam(':sender_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':receiver_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':where_sender_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':where_receiver_user_id', $_SESSION['user_id'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                flash_message('success', 'Message deleted successfully!');
            } else {
                flash_message('error', 'Failed to delete message.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }

        redirect('messages.php');
    }

    if (isset($_POST['update_contact_status'])) {
        $contact_id = $_POST['contact_id'];
        $status = $_POST['status'];
        $allowed_statuses = ['new', 'read', 'replied', 'archived'];

        if (!in_array($status, $allowed_statuses, true)) {
            flash_message('error', 'Invalid contact message status.');
            redirect('messages.php');
        }

        try {
            $database = new Database();
            $db = $database->getConnection();

            $query = "UPDATE contact_messages SET status = :status WHERE id = :contact_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':contact_id', $contact_id);

            if ($stmt->execute()) {
                flash_message('success', 'Contact message status updated successfully!');
            } else {
                flash_message('error', 'Failed to update contact message status.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }

        redirect('messages.php');
    }

    if (isset($_POST['send_contact_reply'])) {
        $contact_id = (int)($_POST['contact_id'] ?? 0);
        $reply_subject = trim($_POST['reply_subject'] ?? '');
        $reply_message = trim($_POST['reply_message'] ?? '');

        if ($contact_id <= 0 || $reply_subject === '' || $reply_message === '') {
            flash_message('error', 'Please enter a subject and reply message.');
            redirect('messages.php');
        }

        try {
            $database = new Database();
            $db = $database->getConnection();

            $query = "SELECT id, name, email, subject, message FROM contact_messages WHERE id = :contact_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
            $stmt->execute();
            $contact_message = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$contact_message) {
                flash_message('error', 'Contact message not found.');
                redirect('messages.php');
            }

            if (!filter_var($contact_message['email'], FILTER_VALIDATE_EMAIL)) {
                flash_message('error', 'Contact message has an invalid email address.');
                redirect('messages.php');
            }

            $emailService = new EmailService();
            $email_body = $reply_message . "\n\n";
            $email_body .= "----- Original Message -----\n";
            $email_body .= "From: " . $contact_message['name'] . " <" . $contact_message['email'] . ">\n";
            $email_body .= "Subject: " . $contact_message['subject'] . "\n\n";
            $email_body .= $contact_message['message'];

            $result = $emailService->sendEmail(
                $contact_message['email'],
                $reply_subject,
                $email_body,
                null,
                null,
                false
            );

            if ($result['success']) {
                $update_query = "UPDATE contact_messages SET status = 'replied' WHERE id = :contact_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':contact_id', $contact_id, PDO::PARAM_INT);
                $update_stmt->execute();

                flash_message('success', 'Reply email sent successfully!');
            } else {
                flash_message('error', 'Failed to send reply: ' . $result['message']);
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }

        redirect('messages.php');
    }

    if (isset($_POST['delete_contact_message'])) {
        $contact_id = $_POST['contact_id'];

        try {
            $database = new Database();
            $db = $database->getConnection();

            $query = "DELETE FROM contact_messages WHERE id = :contact_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':contact_id', $contact_id);

            if ($stmt->execute()) {
                flash_message('success', 'Contact message deleted successfully!');
            } else {
                flash_message('error', 'Failed to delete contact message.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }

        redirect('messages.php');
    }
}

$sent_messages = [];
$received_messages = [];
$classes = [];
$contact_messages = [];
$error_message = '';

// Get messages data
try {
    $database = new Database();
    $db = $database->getConnection();

    // Get sent messages
    $sent_query = "SELECT m.*, 'medium' as priority, u.full_name as recipient_name FROM messages m
                   LEFT JOIN users u ON m.receiver_id = u.id
                   WHERE m.sender_id = :sender_id AND COALESCE(m.is_deleted_sender, 0) = 0
                   ORDER BY m.created_at DESC";
    $sent_stmt = $db->prepare($sent_query);
    $sent_stmt->bindParam(':sender_id', $_SESSION['user_id']);
    $sent_stmt->execute();
    $sent_messages = $sent_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get received messages
    $received_query = "SELECT m.*, 'medium' as priority, u.full_name as sender_name FROM messages m
                      LEFT JOIN users u ON m.sender_id = u.id
                      WHERE m.receiver_id = :receiver_id AND COALESCE(m.is_deleted_receiver, 0) = 0
                      ORDER BY m.created_at DESC";
    $received_stmt = $db->prepare($received_query);
    $received_stmt->bindParam(':receiver_id', $_SESSION['user_id']);
    $received_stmt->execute();
    $received_messages = $received_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get classes for class-specific messaging
    $classes_query = "SELECT id, name FROM classes WHERE status = 'active' ORDER BY name";
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->execute();
    $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
    $error_message = "Error loading data: " . $exception->getMessage();
    error_log("Messages page error: " . $exception->getMessage());
}

// Get contact form messages separately so admin can still view contact emails
// even if the internal messaging queries fail.
try {
    if (!isset($db)) {
        $database = new Database();
        $db = $database->getConnection();
    }

    $contact_query = "SELECT
                        id,
                        name,
                        email,
                        phone,
                        subject,
                        message,
                        status,
                        created_at,
                        updated_at
                      FROM contact_messages
                      ORDER BY created_at DESC";
    $contact_stmt = $db->prepare($contact_query);
    $contact_stmt->execute();
    $contact_messages = $contact_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
    $contact_messages = [];
    $error_message = "Error loading contact messages: " . $exception->getMessage();
    error_log("Contact messages load error: " . $exception->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Kidzenia Kindergarten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .message-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .message-card.high-priority {
            border-left-color: #dc3545;
        }

        .message-card.medium-priority {
            border-left-color: #ffc107;
        }

        .message-card.low-priority {
            border-left-color: #28a745;
        }

        .message-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }

        .message-subject {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .message-meta small {
            color: #6c757d;
        }

        .priority-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .priority-badge.high { background: #dc3545; color: white; }
        .priority-badge.medium { background: #ffc107; color: #212529; }
        .priority-badge.low { background: #28a745; color: white; }

        .compose-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 20px;
        }

        .tab-button {
            background: none;
            border: none;
            padding: 12px 20px;
            font-weight: 600;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
        }

        .tab-button.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stats-box {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3">
            <h4 class="text-center mb-4">
                <i class="fas fa-graduation-cap me-2"></i>Kidzenia
            </h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="students.php">
                        <i class="fas fa-user-graduate me-2"></i>Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="teachers.php">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Teachers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="classes.php">
                        <i class="fas fa-school me-2"></i>Classes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="attendance.php">
                        <i class="fas fa-calendar-check me-2"></i>Attendance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="announcements.php">
                        <i class="fas fa-bullhorn me-2"></i>Announcements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events.php">
                        <i class="fas fa-calendar-alt me-2"></i>Events
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="gallery.php">
                        <i class="fas fa-images me-2"></i>Gallery
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="messages.php">
                        <i class="fas fa-envelope me-2"></i>Messages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../readme.php" target="_blank">
                        <i class="fas fa-info-circle me-2"></i>Features Guide
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Message Center</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                <i class="fas fa-plus me-2"></i>Compose Message
            </button>
        </div>

        <?php
        $flash = get_flash_message();
        if ($flash):
            foreach ($flash as $type => $message):
        ?>
            <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php
            endforeach;
        endif;
        ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <?php
        $unread_contact_count = count(
            array_filter($contact_messages, function($msg) {
                return ($msg['status'] ?? 'new') == 'new';
            })
        );
        ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-box">
                    <div class="stats-number"><?php echo count($sent_messages); ?></div>
                    <div>Sent Messages</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-box">
                    <div class="stats-number"><?php echo count($received_messages); ?></div>
                    <div>Received Messages</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-box">
                    <div class="stats-number"><?php echo $unread_contact_count; ?></div>
                    <div>New Contact Messages</div>
                </div>
            </div>
        </div>

        <!-- Message Tabs -->
        <div class="tabs">
            <button class="tab-button active" onclick="showTab(event, 'sent')">
                <i class="fas fa-paper-plane me-2"></i>Sent Messages
            </button>
            <button class="tab-button" onclick="showTab(event, 'received')">
                <i class="fas fa-inbox me-2"></i>Received Messages
            </button>
            <button class="tab-button" onclick="showTab(event, 'contact')">
                <i class="fas fa-envelope-open-text me-2"></i>Contact Form Messages
            </button>
        </div>

        <!-- Sent Messages -->
        <div id="sent" class="tab-content active">
            <?php if (!empty($sent_messages)): ?>
                <?php foreach ($sent_messages as $message): ?>
                    <div class="message-card <?php echo $message['priority']; ?>-priority">
                        <div class="message-header">
                            <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                            <span class="priority-badge <?php echo $message['priority']; ?>">
                                <?php echo ucfirst($message['priority']); ?>
                            </span>
                        </div>
                        <div class="message-meta">
                            <small><i class="fas fa-user me-1"></i> To: <?php echo htmlspecialchars($message['recipient_name'] ?: 'Unknown'); ?></small>
                            <small><i class="fas fa-clock me-1"></i> <?php echo format_date($message['created_at']); ?></small>
                        </div>
                        <p class="message-content"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-info" onclick="viewMessage(<?php echo $message['id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteMessage(<?php echo $message['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-paper-plane fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No sent messages</h5>
                    <p class="text-muted">Start composing your first message!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Received Messages -->
        <div id="received" class="tab-content">
            <?php if (!empty($received_messages)): ?>
                <?php foreach ($received_messages as $message): ?>
                    <div class="message-card <?php echo $message['priority']; ?>-priority">
                        <div class="message-header">
                            <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                            <span class="priority-badge <?php echo $message['priority']; ?>">
                                <?php echo ucfirst($message['priority']); ?>
                            </span>
                        </div>
                        <div class="message-meta">
                            <small><i class="fas fa-user me-1"></i> From: <?php echo htmlspecialchars($message['sender_name'] ?: 'Unknown'); ?></small>
                            <small><i class="fas fa-clock me-1"></i> <?php echo format_date($message['created_at']); ?></small>
                        </div>
                        <p class="message-content"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-info" onclick="viewMessage(<?php echo $message['id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="replyMessage(<?php echo $message['id']; ?>)">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteMessage(<?php echo $message['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No received messages</h5>
                    <p class="text-muted">Your inbox is empty</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contact Form Messages -->
    <!-- Contact Form Messages -->
<div id="contact" class="tab-content">

<style>
.contact-card{
    background:#fff;
    border-radius:15px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    border-left:5px solid #667eea;
    transition:.3s;
}

.contact-card:hover{
    transform:translateY(-5px);
    box-shadow:0 10px 30px rgba(0,0,0,.15);
}

.contact-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
    flex-wrap:wrap;
}

.contact-subject{
    font-size:18px;
    font-weight:600;
    color:#2c3e50;
}

.contact-meta{
    display:flex;
    flex-wrap:wrap;
    gap:15px;
    margin-bottom:15px;
    color:#666;
    font-size:14px;
}

.contact-message{
    background:#f8f9fa;
    border-radius:10px;
    padding:15px;
    margin-bottom:15px;
}

.status-badge{
    padding:6px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:bold;
}

.status-new{
    background:#dc3545;
    color:#fff;
}

.status-read{
    background:#ffc107;
    color:#000;
}

.status-replied{
    background:#28a745;
    color:#fff;
}

.contact-buttons{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}

.contact-buttons .btn{
    border-radius:25px;
}

.reply-editor{
    min-height:180px;
    resize:vertical;
}

.empty-contact{
    text-align:center;
    padding:60px;
    background:#fff;
    border-radius:15px;
}

.empty-contact i{
    font-size:70px;
    color:#ccc;
    margin-bottom:20px;
}
</style>

<?php if(!empty($contact_messages)): ?>

<?php foreach($contact_messages as $message): ?>

<div class="contact-card">

<div class="contact-header">

<div class="contact-subject">
<?php echo htmlspecialchars($message['subject']); ?>
</div>

<div>
<?php
$statusClass='status-read';

if($message['status']=='new'){
$statusClass='status-new';
}

if($message['status']=='replied'){
$statusClass='status-replied';
}
?>

<span class="status-badge <?php echo $statusClass;?>">
<?php echo ucfirst($message['status']);?>
</span>

</div>

</div>


<div class="contact-meta">

<div>
<i class="fas fa-hashtag"></i>
ID :
<?php echo $message['id'];?>
</div>

<div>
<i class="fas fa-user"></i>
<?php echo htmlspecialchars($message['name']);?>
</div>

<div>
<i class="fas fa-envelope"></i>
<?php echo htmlspecialchars($message['email']);?>
</div>

<?php if(!empty($message['phone'])): ?>

<div>
<i class="fas fa-phone"></i>
<?php echo htmlspecialchars($message['phone']);?>
</div>

<?php endif; ?>

<div>
<i class="fas fa-clock"></i>
<?php echo date('d M Y h:i A',strtotime($message['created_at']));?>
</div>

</div>


<div class="contact-message">

<?php echo nl2br(htmlspecialchars($message['message'])); ?>

</div>


<div class="contact-buttons">

<?php if($message['status']=="new"): ?>

<button
class="btn btn-success btn-sm"
onclick="updateContactStatus(<?php echo $message['id'];?>,'read')">

<i class="fas fa-check"></i>
Mark Read

</button>

<?php endif; ?>


<?php if($message['status']!="replied"): ?>

<button
class="btn btn-primary btn-sm"
onclick="updateContactStatus(<?php echo $message['id'];?>,'replied')">

<i class="fas fa-reply"></i>
Mark Replied

</button>

<?php endif; ?>


<button
type="button"
class="btn btn-info btn-sm"
data-bs-toggle="modal"
data-bs-target="#replyContactModal<?php echo $message['id'];?>">

<i class="fas fa-envelope"></i>
Reply Email

</button>


<button
class="btn btn-danger btn-sm"
onclick="deleteContactMessage(<?php echo $message['id'];?>)">

<i class="fas fa-trash"></i>
Delete

</button>


</div>

</div>

<div class="modal fade" id="replyContactModal<?php echo $message['id'];?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reply to <?php echo htmlspecialchars($message['name']);?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="contact_id" value="<?php echo $message['id'];?>">

                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($message['email']);?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" name="reply_subject" value="Re: <?php echo htmlspecialchars($message['subject']);?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reply Message</label>
                        <textarea class="form-control reply-editor" name="reply_message" rows="8" required>Dear <?php echo htmlspecialchars($message['name']);?>,

</textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Original Message</label>
                        <textarea class="form-control" rows="5" readonly><?php echo htmlspecialchars($message['message']);?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="send_contact_reply" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endforeach; ?>

<?php else: ?>

<div class="empty-contact">

<i class="fas fa-envelope-open-text"></i>

<h4>No Contact Messages</h4>

<p class="text-muted">
Messages submitted from the contact form will appear here
</p>

</div>

<?php endif; ?>

</div>

    <!-- Compose Message Modal -->
    <div class="modal fade" id="composeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compose New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Recipient Type</label>
                            <select class="form-control" name="recipient_type" id="recipientType" onchange="toggleClassField()">
                                <option value="all_parents">All Parents</option>
                                <option value="all_teachers">All Teachers</option>
                                <option value="all_users">All Users</option>
                                <option value="specific_class">Specific Class</option>
                            </select>
                        </div>

                        <div class="mb-3" id="classField" style="display: none;">
                            <label class="form-label">Select Class</label>
                            <select class="form-control" name="class_id">
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-control" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="6" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="send_message" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showTab(event, tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');

            // Add active class to clicked button
            event.currentTarget.classList.add('active');
        }

        function toggleClassField() {
            const recipientType = document.getElementById('recipientType').value;
            const classField = document.getElementById('classField');

            if (recipientType === 'specific_class') {
                classField.style.display = 'block';
            } else {
                classField.style.display = 'none';
            }
        }

        function viewMessage(id) {
            // Implement view functionality
            console.log('View message:', id);
        }

        function replyMessage(id) {
            // Implement reply functionality
            console.log('Reply to message:', id);
        }

        function deleteMessage(id) {
            if (confirm('Are you sure you want to delete this message?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="message_id" value="' + id + '"><input type="hidden" name="delete_message" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function updateContactStatus(id, status) {
            if (confirm('Are you sure you want to update the status of this message?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="contact_id" value="' + id + '"><input type="hidden" name="update_contact_status" value="1"><input type="hidden" name="status" value="' + status + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteContactMessage(id) {
            if (confirm('Are you sure you want to delete this contact message?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="contact_id" value="' + id + '"><input type="hidden" name="delete_contact_message" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
