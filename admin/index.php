<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Get dashboard statistics
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Total students
    $students_query = "SELECT COUNT(*) as total FROM students WHERE status = 'active'";
    $students_stmt = $db->prepare($students_query);
    $students_stmt->execute();
    $total_students = $students_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total teachers
    $teachers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND status = 'active'";
    $teachers_stmt = $db->prepare($teachers_query);
    $teachers_stmt->execute();
    $total_teachers = $teachers_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total classes
    $classes_query = "SELECT COUNT(*) as total FROM classes WHERE status = 'active'";
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->execute();
    $total_classes = $classes_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Today's attendance
    $attendance_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
        FROM attendance WHERE date = CURDATE()";
    $attendance_stmt = $db->prepare($attendance_query);
    $attendance_stmt->execute();
    $attendance_data = $attendance_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Recent notifications
    $notifications_query = "SELECT COUNT(*) as total FROM notifications WHERE is_read = 0 AND user_id = :user_id";
    $notifications_stmt = $db->prepare($notifications_query);
    $notifications_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $notifications_stmt->execute();
    $unread_notifications = $notifications_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent announcements
    $announcements_query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
    $announcements_stmt = $db->prepare($announcements_query);
    $announcements_stmt->execute();
    $recent_announcements = $announcements_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activities (last 10)
    $activities_query = "SELECT 'Student Added' as activity, CONCAT(first_name, ' ', last_name) as details, created_at 
                        FROM students ORDER BY created_at DESC LIMIT 5
                        UNION
                        SELECT 'User Registered' as activity, full_name as details, created_at 
                        FROM users ORDER BY created_at DESC LIMIT 5
                        ORDER BY created_at DESC LIMIT 10";
    $activities_stmt = $db->prepare($activities_query);
    $activities_stmt->execute();
    $recent_activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Class distribution
    $class_distribution_query = "SELECT c.name, COUNT(s.id) as student_count 
                                FROM classes c 
                                LEFT JOIN students s ON c.id = s.class_id AND s.status = 'active'
                                WHERE c.status = 'active'
                                GROUP BY c.id, c.name
                                ORDER BY student_count DESC";
    $class_dist_stmt = $db->prepare($class_distribution_query);
    $class_dist_stmt->execute();
    $class_distribution = $class_dist_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading dashboard data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kidzenia Kindergarten</title>
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
        
                
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.info { border-left-color: var(--info-color); }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card.primary .stat-icon { background: rgba(102, 126, 234, 0.1); color: var(--primary-color); }
        .stat-card.success .stat-icon { background: rgba(40, 167, 69, 0.1); color: var(--success-color); }
        .stat-card.warning .stat-icon { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        .stat-card.info .stat-icon { background: rgba(23, 162, 184, 0.1); color: var(--info-color); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .activity-details {
            flex-grow: 1;
        }
        
        .activity-time {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .progress-item {
            margin-bottom: 15px;
        }
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: 600;
        }
        
            </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none me-3" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0">Admin Dashboard</h5>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
                </div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <div class="text-muted small">Administrator</div>
                </div>
            </div>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="mt-2">
                    <a href="student_management.php" class="btn btn-sm btn-success w-100">
                        <i class="fas fa-cog me-1"></i>Comprehensive Management
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_teachers; ?></div>
                    <div class="stat-label">Total Teachers</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_classes; ?></div>
                    <div class="stat-label">Total Classes</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $attendance_data['present'] ?? 0; ?></div>
                    <div class="stat-label">Present Today</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Class Distribution -->
            <div class="col-lg-6 mb-4">
                <div class="chart-container">
                    <h5 class="mb-4">Class Distribution</h5>
                    <?php if (!empty($class_distribution)): ?>
                        <?php foreach ($class_distribution as $class): ?>
                            <div class="progress-item">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo htmlspecialchars($class['name']); ?></span>
                                    <span class="text-muted"><?php echo $class['student_count']; ?> students</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-custom" style="width: <?php echo min(100, ($class['student_count'] / 30) * 100); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No class data available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Announcements -->
            <div class="col-lg-6 mb-4">
                <div class="chart-container">
                    <h5 class="mb-4">Recent Announcements</h5>
                    <?php if (!empty($recent_announcements)): ?>
                        <?php foreach ($recent_announcements as $announcement): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div class="activity-details">
                                    <div class="fw-bold"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                    <div class="activity-time"><?php echo format_date($announcement['created_at']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No announcements yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="chart-container">
            <h5 class="mb-4">Recent Activities</h5>
            <?php if (!empty($recent_activities)): ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="activity-details">
                            <div class="fw-bold"><?php echo htmlspecialchars($activity['activity']); ?></div>
                            <div><?php echo htmlspecialchars($activity['details']); ?></div>
                            <div class="activity-time"><?php echo format_date($activity['created_at']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No recent activities.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            // You can implement AJAX notification refresh here
            console.log('Checking for new notifications...');
        }, 30000);
    </script>
</body>
</html>
