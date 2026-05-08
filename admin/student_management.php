<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Get student ID from URL if provided
$student_id = isset($_GET['id']) ? $_GET['id'] : 0;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Get student information
$student = null;
if ($student_id > 0) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT s.*, c.name as class_name, u.full_name as parent_name, u.email as parent_email, u.phone as parent_phone,
                 TIMESTAMPDIFF(YEAR, s.date_of_birth, CURDATE()) as age
                 FROM students s 
                 LEFT JOIN classes c ON s.class_id = c.id 
                 LEFT JOIN users u ON s.parent_id = u.id 
                 WHERE s.id = :student_id AND s.status = 'active'";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            flash_message('error', 'Student not found!');
            redirect('students.php');
        }
    } catch(PDOException $exception) {
        flash_message('error', 'Error loading student data: ' . $exception->getMessage());
        redirect('students.php');
    }
}

// Get all students for dropdown
$students = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, first_name, last_name, student_id, class_name 
              FROM student_complete_profile 
              ORDER BY first_name, last_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Kidzenia Kindergarten</title>
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
        
        .student-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.5rem;
            margin-right: 20px;
        }
        
        .student-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .tab-navigation {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .tab-navigation .nav-link {
            color: #6c757d;
            border: none;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .tab-navigation .nav-link:hover {
            background: #f8f9fa;
            color: var(--primary-color);
        }
        
        .tab-navigation .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .progress-item {
            margin-bottom: 20px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
        }
        
        .behavior-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary-color);
        }
        
        .behavior-positive {
            border-left-color: var(--success-color);
        }
        
        .behavior-negative {
            border-left-color: var(--danger-color);
        }
        
        .medical-record {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .document-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .document-item:hover {
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .fee-status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .fee-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .fee-status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .student-selector {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
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
                    <a class="nav-link active" href="students.php">
                        <i class="fas fa-user-graduate me-2"></i>Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="student_management.php">
                        <i class="fas fa-user-cog me-2"></i>Student Management
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
        <!-- Student Selector -->
        <div class="student-selector">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Select Student:</label>
                    <select class="form-select" id="studentSelector" onchange="changeStudent()">
                        <option value="">Choose a student...</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($student && $student['id'] == $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?> - <?php echo htmlspecialchars($s['student_id']); ?>
                                <?php if ($s['class_name']): ?>(<?php echo htmlspecialchars($s['class_name']); ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" onclick="window.location.href='students.php'">
                        <i class="fas fa-arrow-left me-2"></i>Back to Students
                    </button>
                </div>
            </div>
        </div>

        <?php if ($student): ?>
            <!-- Student Header -->
            <div class="student-header">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <div class="student-avatar">
                            <?php if ($student['profile_image']): ?>
                                <img src="../uploads/students/<?php echo htmlspecialchars($student['profile_image']); ?>" alt="<?php echo htmlspecialchars($student['first_name']); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                        <p class="mb-2"><strong>ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                        <div class="d-flex gap-3">
                            <span><i class="fas fa-birthday-cake me-2"></i><?php echo $student['age']; ?> years old</span>
                            <span><i class="fas fa-venus-mars me-2"></i><?php echo ucfirst($student['gender']); ?></span>
                            <?php if ($student['class_name']): ?>
                                <span><i class="fas fa-school me-2"></i><?php echo htmlspecialchars($student['class_name']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-end">
                            <button class="btn btn-light btn-sm me-2" onclick="editStudent(<?php echo $student['id']; ?>)">
                                <i class="fas fa-edit me-1"></i>Edit Profile
                            </button>
                            <button class="btn btn-light btn-sm" onclick="printProfile(<?php echo $student['id']; ?>)">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab == 'overview') ? 'active' : ''; ?>" href="?id=<?php echo $student_id; ?>&tab=overview">
                            <i class="fas fa-home me-2"></i>Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab == 'academic') ? 'active' : ''; ?>" href="?id=<?php echo $student_id; ?>&tab=academic">
                            <i class="fas fa-graduation-cap me-2"></i>Academic Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab == 'behavior') ? 'active' : ''; ?>" href="?id=<?php echo $student_id; ?>&tab=behavior">
                            <i class="fas fa-chart-line me-2"></i>Behavior
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab == 'medical') ? 'active' : ''; ?>" href="?id=<?php echo $student_id; ?>&tab=medical">
                            <i class="fas fa-heartbeat me-2"></i>Medical
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab == 'communication') ? 'active' : ''; ?>" href="?id=<?php echo $student_id; ?>&tab=communication">
                            <i class="fas fa-comments me-2"></i>Communication
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab == 'documents') ? 'active' : ''; ?>" href="?id=<?php echo $student_id; ?>&tab=documents">
                            <i class="fas fa-file-alt me-2"></i>Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab == 'fees') ? 'active' : ''; ?>" href="?id=<?php echo $student_id; ?>&tab=fees">
                            <i class="fas fa-dollar-sign me-2"></i>Fees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($tab == 'transport') ? 'active' : ''; ?>" href="?id=<?php echo $student_id; ?>&tab=transport">
                            <i class="fas fa-bus me-2"></i>Transport
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <?php
                // Include the appropriate tab content based on selected tab
                $tab_file = __DIR__ . '/student_tabs/' . $tab . '.php';
                if (file_exists($tab_file)) {
                    include $tab_file;
                } else {
                    echo '<div class="content-card"><p class="text-muted">Tab content not available.</p></div>';
                }
                ?>
            </div>
        <?php else: ?>
            <!-- No Student Selected -->
            <div class="content-card text-center py-5">
                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Student Selected</h4>
                <p class="text-muted">Please select a student from the dropdown above to view their management details.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeStudent() {
            const studentId = document.getElementById('studentSelector').value;
            if (studentId) {
                window.location.href = 'student_management.php?id=' + studentId;
            } else {
                window.location.href = 'student_management.php';
            }
        }
        
        function editStudent(id) {
            window.location.href = 'students.php?edit=' + id;
        }
        
        function printProfile(id) {
            window.open('student_management.php?id=' + id + '&tab=overview&print=1', '_blank');
        }
    </script>
</body>
</html>
