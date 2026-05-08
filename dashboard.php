<?php
require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Get user-specific data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    // Get unread notifications
    $unread_notifications = get_unread_notifications($user_id);
    
    // Get recent announcements based on role
    if ($user_role == 'parent') {
        $announcements_query = "SELECT * FROM announcements WHERE (target_audience = 'all' OR target_audience = 'parents') AND is_active = 1 AND publish_date <= CURDATE() ORDER BY created_at DESC LIMIT 5";
    } elseif ($user_role == 'teacher') {
        $announcements_query = "SELECT * FROM announcements WHERE (target_audience = 'all' OR target_audience = 'teachers') AND is_active = 1 AND publish_date <= CURDATE() ORDER BY created_at DESC LIMIT 5";
    } else {
        $announcements_query = "SELECT * FROM announcements WHERE is_active = 1 AND publish_date <= CURDATE() ORDER BY created_at DESC LIMIT 5";
    }
    
    $announcements_stmt = $db->prepare($announcements_query);
    $announcements_stmt->execute();
    $recent_announcements = $announcements_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user-specific data based on role
    if ($user_role == 'parent') {
        // Get parent's children
        $children_query = "SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.parent_id = :user_id AND s.status = 'active'";
        $children_stmt = $db->prepare($children_query);
        $children_stmt->bindParam(':user_id', $user_id);
        $children_stmt->execute();
        $children = $children_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get attendance for children
        $attendance_query = "SELECT a.*, s.first_name, s.last_name FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.parent_id = :user_id AND a.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY a.date DESC";
        $attendance_stmt = $db->prepare($attendance_query);
        $attendance_stmt->bindParam(':user_id', $user_id);
        $attendance_stmt->execute();
        $recent_attendance = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($user_role == 'teacher') {
        // Get teacher's classes
        $classes_query = "SELECT c.*, COUNT(s.id) as student_count FROM classes c LEFT JOIN students s ON c.id = s.class_id AND s.status = 'active' WHERE c.teacher_id = :user_id AND c.status = 'active' GROUP BY c.id";
        $classes_stmt = $db->prepare($classes_query);
        $classes_stmt->bindParam(':user_id', $user_id);
        $classes_stmt->execute();
        $teacher_classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get today's attendance for teacher's classes
        $attendance_query = "SELECT a.*, s.first_name, s.last_name, c.name as class_name FROM attendance a JOIN students s ON a.student_id = s.id JOIN classes c ON s.class_id = c.id WHERE c.teacher_id = :user_id AND a.date = CURDATE() ORDER BY c.name, s.first_name";
        $attendance_stmt = $db->prepare($attendance_query);
        $attendance_stmt->bindParam(':user_id', $user_id);
        $attendance_stmt->execute();
        $today_attendance = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $exception) {
    $error_message = "Error loading dashboard data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kidzenia Kindergarten</title>
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
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 0;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .notification-item {
            background: white;
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .notification-item.unread {
            background: #f8f9ff;
            border-left-color: var(--secondary-color);
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.3rem;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .child-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .child-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .class-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .attendance-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .attendance-badge.present { background: #d4edda; color: #155724; }
        .attendance-badge.absent { background: #f8d7da; color: #721c24; }
        .attendance-badge.late { background: #fff3cd; color: #856404; }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>Kidzenia
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="readme.php">Features Guide</a>
                    </li>
                    <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="#">
                            <i class="fas fa-bell"></i>
                            <?php if (!empty($unread_notifications)): ?>
                                <span class="notification-badge"><?php echo count($unread_notifications); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar d-inline-block me-2">
                                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
                            </div>
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
                    <p class="lead mb-0">
                        <?php 
                        if ($user_role == 'parent') {
                            echo "Here's an overview of your children's activities and progress.";
                        } elseif ($user_role == 'teacher') {
                            echo "Manage your classes and track student attendance.";
                        } else {
                            echo "Manage the kindergarten administration and operations.";
                        }
                        ?>
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="stat-icon" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto;">
                        <i class="fas fa-<?php echo $user_role == 'parent' ? 'users' : ($user_role == 'teacher' ? 'chalkboard-teacher' : 'user-shield'); ?>"></i>
                    </div>
                    <h4 class="mt-3"><?php echo ucfirst($user_role); ?></h4>
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

        <div class="row">
            <!-- Notifications -->
            <div class="col-lg-4">
                <div class="welcome-card">
                    <h5 class="mb-4">
                        <i class="fas fa-bell me-2"></i>Notifications
                        <?php if (!empty($unread_notifications)): ?>
                            <span class="badge bg-danger ms-2"><?php echo count($unread_notifications); ?></span>
                        <?php endif; ?>
                    </h5>
                    
                    <?php if (!empty($unread_notifications)): ?>
                        <?php foreach ($unread_notifications as $notification): ?>
                            <div class="notification-item unread">
                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small class="text-muted"><?php echo format_date($notification['created_at']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-bell-slash fa-2x mb-2"></i>
                            <p>No new notifications</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Stats -->
                <div class="welcome-card">
                    <h5 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Quick Stats</h5>
                    
                    <?php if ($user_role == 'parent'): ?>
                        <div class="stat-card mb-3">
                            <div class="stat-icon">
                                <i class="fas fa-child"></i>
                            </div>
                            <div class="stat-number"><?php echo count($children); ?></div>
                            <div class="text-muted">Children</div>
                        </div>
                    <?php elseif ($user_role == 'teacher'): ?>
                        <div class="stat-card mb-3">
                            <div class="stat-icon">
                                <i class="fas fa-school"></i>
                            </div>
                            <div class="stat-number"><?php echo count($teacher_classes); ?></div>
                            <div class="text-muted">Classes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number"><?php echo array_sum(array_column($teacher_classes, 'student_count')); ?></div>
                            <div class="text-muted">Total Students</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Recent Announcements -->
                <div class="welcome-card">
                    <h5 class="mb-4"><i class="fas fa-bullhorn me-2"></i>Recent Announcements</h5>
                    
                    <?php if (!empty($recent_announcements)): ?>
                        <?php foreach ($recent_announcements as $announcement): ?>
                            <div class="notification-item">
                                <h6 class="mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i><?php echo format_date($announcement['publish_date']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-bullhorn fa-2x mb-2"></i>
                            <p>No announcements at this time</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Role-specific Content -->
                <?php if ($user_role == 'parent'): ?>
                    <div class="welcome-card">
                        <h5 class="mb-4"><i class="fas fa-users me-2"></i>Your Children</h5>
                        
                        <?php if (!empty($children)): ?>
                            <?php foreach ($children as $child): ?>
                                <div class="child-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h6>
                                            <p class="text-muted mb-1">ID: <?php echo htmlspecialchars($child['student_id']); ?></p>
                                            <?php if ($child['class_name']): ?>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($child['class_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">Age: <?php echo date('Y') - date('Y', strtotime($child['date_of_birth'])); ?> years</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-user-graduate fa-2x mb-2"></i>
                                <p>No children assigned to your account</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($recent_attendance)): ?>
                        <div class="welcome-card">
                            <h5 class="mb-4"><i class="fas fa-calendar-check me-2"></i>Recent Attendance</h5>
                            
                            <?php foreach ($recent_attendance as $attendance): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></strong>
                                        <small class="text-muted ms-2"><?php echo format_date($attendance['date']); ?></small>
                                    </div>
                                    <span class="attendance-badge <?php echo $attendance['status']; ?>">
                                        <?php echo ucfirst($attendance['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($user_role == 'teacher'): ?>
                    <div class="welcome-card">
                        <h5 class="mb-4"><i class="fas fa-school me-2"></i>Your Classes</h5>
                        
                        <?php if (!empty($teacher_classes)): ?>
                            <?php foreach ($teacher_classes as $class): ?>
                                <div class="class-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($class['name']); ?></h6>
                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($class['description']); ?></p>
                                            <span class="badge bg-info"><?php echo $class['student_count']; ?> Students</span>
                                        </div>
                                        <div>
                                            <small class="text-muted">Room: <?php echo htmlspecialchars($class['room_number']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-school fa-2x mb-2"></i>
                                <p>No classes assigned to you</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($today_attendance)): ?>
                        <div class="welcome-card">
                            <h5 class="mb-4"><i class="fas fa-calendar-check me-2"></i>Today's Attendance</h5>
                            
                            <?php 
                            $current_class = '';
                            foreach ($today_attendance as $attendance): 
                                if ($current_class != $attendance['class_name']) {
                                    if ($current_class != '') echo '</div>';
                                    $current_class = $attendance['class_name'];
                                    echo '<div class="mb-3"><h6 class="text-muted">' . htmlspecialchars($current_class) . '</h6>';
                                }
                            ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></strong>
                                    </div>
                                    <span class="attendance-badge <?php echo $attendance['status']; ?>">
                                        <?php echo ucfirst($attendance['status']); ?>
                                    </span>
                                </div>
                            <?php 
                            endforeach; 
                            if ($current_class != '') echo '</div>';
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            // Implement AJAX notification refresh here
            console.log('Checking for new notifications...');
        }, 30000);
        
        // Mark notifications as read when clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.remove('unread');
                // Implement AJAX call to mark as read
            });
        });
    </script>
</body>
</html>
