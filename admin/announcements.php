<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = clean_input($_POST['title']);
        $content = clean_input($_POST['content']);
        $type = $_POST['type'];
        $target_audience = $_POST['target_audience'];
        $publish_date = $_POST['publish_date'];
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO announcements (title, content, type, target_audience, author_id, publish_date, expiry_date) 
                      VALUES (:title, :content, :type, :target_audience, :author_id, :publish_date, :expiry_date)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':target_audience', $target_audience);
            $stmt->bindParam(':author_id', $_SESSION['user_id']);
            $stmt->bindParam(':publish_date', $publish_date);
            $stmt->bindParam(':expiry_date', $expiry_date);
            
            if ($stmt->execute()) {
                // Send notifications to relevant users
                $notification_title = "New Announcement: " . $title;
                $notification_message = substr($content, 0, 100) . (strlen($content) > 100 ? "..." : "");
                
                if ($target_audience == 'all') {
                    // Send to all active users
                    $users_query = "SELECT id FROM users WHERE status = 'active'";
                    $users_stmt = $db->prepare($users_query);
                    $users_stmt->execute();
                    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($users as $user) {
                        send_notification($user['id'], $notification_title, $notification_message, 'announcement');
                    }
                } elseif ($target_audience == 'parents') {
                    $users_query = "SELECT id FROM users WHERE role = 'parent' AND status = 'active'";
                    $users_stmt = $db->prepare($users_query);
                    $users_stmt->execute();
                    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($users as $user) {
                        send_notification($user['id'], $notification_title, $notification_message, 'announcement');
                    }
                } elseif ($target_audience == 'teachers') {
                    $users_query = "SELECT id FROM users WHERE role = 'teacher' AND status = 'active'";
                    $users_stmt = $db->prepare($users_query);
                    $users_stmt->execute();
                    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($users as $user) {
                        send_notification($user['id'], $notification_title, $notification_message, 'announcement');
                    }
                }
                
                flash_message('success', 'Announcement added successfully!');
            } else {
                flash_message('error', 'Failed to add announcement.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('announcements.php');
    }
    
    if (isset($_POST['edit_announcement'])) {
        $announcement_id = $_POST['announcement_id'];
        $title = clean_input($_POST['title']);
        $content = clean_input($_POST['content']);
        $type = $_POST['type'];
        $target_audience = $_POST['target_audience'];
        $publish_date = $_POST['publish_date'];
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE announcements SET title = :title, content = :content, type = :type, target_audience = :target_audience, publish_date = :publish_date, expiry_date = :expiry_date WHERE id = :announcement_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':target_audience', $target_audience);
            $stmt->bindParam(':publish_date', $publish_date);
            $stmt->bindParam(':expiry_date', $expiry_date);
            $stmt->bindParam(':announcement_id', $announcement_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Announcement updated successfully!');
            } else {
                flash_message('error', 'Failed to update announcement.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('announcements.php');
    }
    
    if (isset($_POST['delete_announcement'])) {
        $announcement_id = $_POST['announcement_id'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE announcements SET is_active = 0 WHERE id = :announcement_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':announcement_id', $announcement_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Announcement deleted successfully!');
            } else {
                flash_message('error', 'Failed to delete announcement.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('announcements.php');
    }
}

// Get announcements data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all announcements with author info
    $query = "SELECT a.*, u.full_name as author_name 
              FROM announcements a 
              LEFT JOIN users u ON a.author_id = u.id 
              ORDER BY a.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $announcements = [];
    $error_message = "Error loading data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Kidzenia Kindergarten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
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
            overflow-y: auto;
            max-height: 100vh;
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
        
        .announcement-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
            border-left: 4px solid;
        }
        
        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .announcement-card.general { border-left-color: var(--info-color); }
        .announcement-card.urgent { border-left-color: var(--danger-color); }
        .announcement-card.event { border-left-color: var(--success-color); }
        .announcement-card.holiday { border-left-color: var(--warning-color); }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .announcement-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .announcement-meta {
            display: flex;
            gap: 15px;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .type-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-badge.general { background: rgba(23, 162, 184, 0.1); color: var(--info-color); }
        .type-badge.urgent { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        .type-badge.event { background: rgba(40, 167, 69, 0.1); color: var(--success-color); }
        .type-badge.holiday { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        
        .audience-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            background: #e9ecef;
            color: #495057;
        }
        
        .announcement-content {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .announcement-status {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-dot.active { background: var(--success-color); }
        .status-dot.expired { background: var(--danger-color); }
        .status-dot.scheduled { background: var(--warning-color); }
        
        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.85rem;
            margin: 0 2px;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .editor-toolbar {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-bottom: none;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }
        
        .editor-toolbar button {
            background: white;
            border: 1px solid #dee2e6;
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .editor-toolbar button:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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
            
            .announcement-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .action-buttons {
                margin-top: 10px;
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
                    <a class="nav-link active" href="announcements.php">
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
                    <a class="nav-link" href="messages.php">
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
            <h4>Announcements Management</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                <i class="fas fa-plus me-2"></i>New Announcement
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

        <!-- Announcements List -->
        <div id="announcementsList">
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <?php
                    $status = 'active';
                    if ($announcement['publish_date'] > date('Y-m-d')) {
                        $status = 'scheduled';
                    } elseif ($announcement['expiry_date'] && $announcement['expiry_date'] < date('Y-m-d')) {
                        $status = 'expired';
                    }
                    ?>
                    
                    <div class="announcement-card <?php echo $announcement['type']; ?>">
                        <div class="announcement-header">
                            <div>
                                <h5 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                <div class="announcement-meta">
                                    <span><i class="fas fa-calendar me-1"></i><?php echo format_date($announcement['publish_date']); ?></span>
                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($announcement['author_name']); ?></span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="type-badge <?php echo $announcement['type']; ?>"><?php echo $announcement['type']; ?></span>
                                <span class="audience-badge"><?php echo $announcement['target_audience']; ?></span>
                            </div>
                        </div>
                        
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="announcement-status">
                                <div class="status-dot <?php echo $status; ?>"></div>
                                <span class="text-muted">
                                    <?php 
                                    if ($status == 'scheduled') {
                                        echo 'Scheduled for ' . format_date($announcement['publish_date']);
                                    } elseif ($status == 'expired') {
                                        echo 'Expired on ' . format_date($announcement['expiry_date']);
                                    } else {
                                        echo 'Active';
                                    }
                                    ?>
                                </span>
                                <?php if ($announcement['expiry_date']): ?>
                                    <span class="text-muted">| Expires: <?php echo format_date($announcement['expiry_date']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-info" onclick="viewAnnouncement(<?php echo $announcement['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editAnnouncement(<?php echo $announcement['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No announcements found</h5>
                    <p class="text-muted">Create your first announcement to keep everyone informed.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Announcement Modal -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea class="form-control" name="content" rows="6" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type" required>
                                        <option value="general">General</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="event">Event</option>
                                        <option value="holiday">Holiday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Target Audience</label>
                                    <select class="form-select" name="target_audience" required>
                                        <option value="all">All Users</option>
                                        <option value="parents">Parents Only</option>
                                        <option value="teachers">Teachers Only</option>
                                        <option value="admin">Admin Only</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Publish Date</label>
                                    <input type="date" class="form-control" name="publish_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Expiry Date (Optional)</label>
                            <input type="date" class="form-control" name="expiry_date">
                            <div class="form-text">Leave empty if announcement doesn't expire</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_announcement" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Publish Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewAnnouncement(id) {
            // Implement view functionality
            console.log('View announcement:', id);
        }
        
        function editAnnouncement(id) {
            // Implement edit functionality
            console.log('Edit announcement:', id);
        }
        
        function deleteAnnouncement(id) {
            if (confirm('Are you sure you want to delete this announcement?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="announcement_id" value="' + id + '"><input type="hidden" name="delete_announcement" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto-save draft functionality
        let autoSaveTimer;
        document.querySelector('#addAnnouncementModal textarea').addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                localStorage.setItem('announcement_draft', this.value);
                console.log('Draft saved');
            }, 2000);
        });
        
        // Load draft on modal open
        document.getElementById('addAnnouncementModal').addEventListener('show.bs.modal', function() {
            const draft = localStorage.getItem('announcement_draft');
            if (draft) {
                document.querySelector('#addAnnouncementModal textarea').value = draft;
            }
        });
        
        // Clear draft on successful submission
        document.querySelector('#addAnnouncementModal form').addEventListener('submit', function() {
            localStorage.removeItem('announcement_draft');
        });
    </script>
</body>
</html>
