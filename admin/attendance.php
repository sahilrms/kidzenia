<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_attendance'])) {
        $date = $_POST['attendance_date'];
        $attendance_data = $_POST['attendance'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            foreach ($attendance_data as $student_id => $status) {
                // Check if attendance already exists for this student and date
                $check_query = "SELECT id FROM attendance WHERE student_id = :student_id AND date = :date";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':student_id', $student_id);
                $check_stmt->bindParam(':date', $date);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    // Update existing attendance
                    $query = "UPDATE attendance SET status = :status, recorded_by = :recorded_by WHERE student_id = :student_id AND date = :date";
                } else {
                    // Insert new attendance
                    $query = "INSERT INTO attendance (student_id, date, status, recorded_by) VALUES (:student_id, :date, :status, :recorded_by)";
                }
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':date', $date);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':recorded_by', $_SESSION['user_id']);
                $stmt->execute();
            }
            
            flash_message('success', 'Attendance marked successfully!');
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('attendance.php');
    }
}

// Get attendance data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get selected date (default to today)
    $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // Get all active students with class info
    $students_query = "SELECT s.*, c.name as class_name 
                      FROM students s 
                      LEFT JOIN classes c ON s.class_id = c.id 
                      WHERE s.status = 'active' 
                      ORDER BY c.name, s.first_name, s.last_name";
    $students_stmt = $db->prepare($students_query);
    $students_stmt->execute();
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attendance for selected date
    $attendance_query = "SELECT * FROM attendance WHERE date = :date";
    $attendance_stmt = $db->prepare($attendance_query);
    $attendance_stmt->bindParam(':date', $selected_date);
    $attendance_stmt->execute();
    $attendance_records = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create attendance lookup array
    $attendance_lookup = [];
    foreach ($attendance_records as $record) {
        $attendance_lookup[$record['student_id']] = $record['status'];
    }
    
    // Get attendance statistics for the month
    $stats_query = "SELECT 
                    COUNT(*) as total_students,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_today,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_today,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_today
                    FROM attendance WHERE date = :date";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':date', $selected_date);
    $stats_stmt->execute();
    $daily_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get monthly attendance summary
    $monthly_query = "SELECT 
                     DATE(date) as attendance_date,
                     SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                     SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                     SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                     FROM attendance 
                     WHERE MONTH(date) = MONTH(:date) AND YEAR(date) = YEAR(:date)
                     GROUP BY DATE(date)
                     ORDER BY attendance_date DESC
                     LIMIT 10";
    $monthly_stmt = $db->prepare($monthly_query);
    $monthly_stmt->bindParam(':date', $selected_date);
    $monthly_stmt->execute();
    $monthly_summary = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $students = [];
    $attendance_lookup = [];
    $daily_stats = [];
    $monthly_summary = [];
    $error_message = "Error loading data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Kidzenia Kindergarten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
        
        .attendance-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
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
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.2rem;
        }
        
        .stat-card.success .stat-icon { background: rgba(40, 167, 69, 0.1); color: var(--success-color); }
        .stat-card.danger .stat-icon { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        .stat-card.warning .stat-icon { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        .stat-card.primary .stat-icon { background: rgba(102, 126, 234, 0.1); color: var(--primary-color); }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .attendance-table {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .student-row {
            border-bottom: 1px solid #e9ecef;
            padding: 15px 0;
        }
        
        .student-row:last-child {
            border-bottom: none;
        }
        
        .student-info {
            display: flex;
            align-items: center;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
            font-size: 0.9rem;
        }
        
        .attendance-radio {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .radio-option {
            position: relative;
        }
        
        .radio-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .radio-option label {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 20px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .radio-option input[type="radio"]:checked + label.present {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .radio-option input[type="radio"]:checked + label.absent {
            background: var(--danger-color);
            border-color: var(--danger-color);
            color: white;
        }
        
        .radio-option input[type="radio"]:checked + label.late {
            background: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }
        
        .radio-option input[type="radio"]:checked + label.excused {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        
        .date-selector {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .monthly-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-top: 25px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-badges {
            display: flex;
            gap: 10px;
        }
        
        .badge-custom {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
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
            
            .attendance-radio {
                flex-direction: column;
                gap: 5px;
            }
            
            .date-selector {
                flex-direction: column;
                align-items: stretch;
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
                    <a class="nav-link active" href="attendance.php">
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
        <div class="attendance-header">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Attendance Management</h4>
                <div class="date-selector">
                    <input type="date" class="form-control" id="attendanceDate" value="<?php echo $selected_date; ?>" style="width: auto;">
                    <button class="btn btn-primary" onclick="changeDate()">
                        <i class="fas fa-sync me-2"></i>Load Date
                    </button>
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

            <!-- Daily Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo $daily_stats['total_students'] ?? 0; ?></div>
                        <div class="text-muted">Total Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $daily_stats['present_today'] ?? 0; ?></div>
                        <div class="text-muted">Present</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $daily_stats['absent_today'] ?? 0; ?></div>
                        <div class="text-muted">Absent</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $daily_stats['late_today'] ?? 0; ?></div>
                        <div class="text-muted">Late</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Form -->
        <div class="attendance-table">
            <h5 class="mb-4">Mark Attendance - <?php echo date('F d, Y', strtotime($selected_date)); ?></h5>
            
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                
                <?php if (!empty($students)): ?>
                    <?php $current_class = ''; ?>
                    <?php foreach ($students as $student): ?>
                        <?php if ($current_class != $student['class_name']): ?>
                            <?php if ($current_class != '') echo '</div>'; ?>
                            <?php $current_class = $student['class_name']; ?>
                            <div class="class-section mb-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-school me-2"></i>
                                    <?php echo $student['class_name'] ? htmlspecialchars($student['class_name']) : 'Unassigned'; ?>
                                </h6>
                        <?php endif; ?>
                        
                        <div class="student-row">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="student-info">
                                    <div class="student-avatar">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                        <div class="text-muted small">ID: <?php echo htmlspecialchars($student['student_id']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="attendance-radio">
                                    <div class="radio-option">
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="present" id="present_<?php echo $student['id']; ?>" <?php echo (isset($attendance_lookup[$student['id']]) && $attendance_lookup[$student['id']] == 'present') ? 'checked' : ''; ?>>
                                        <label for="present_<?php echo $student['id']; ?>" class="present">
                                            <i class="fas fa-check me-1"></i>Present
                                        </label>
                                    </div>
                                    
                                    <div class="radio-option">
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="absent" id="absent_<?php echo $student['id']; ?>" <?php echo (isset($attendance_lookup[$student['id']]) && $attendance_lookup[$student['id']] == 'absent') ? 'checked' : ''; ?>>
                                        <label for="absent_<?php echo $student['id']; ?>" class="absent">
                                            <i class="fas fa-times me-1"></i>Absent
                                        </label>
                                    </div>
                                    
                                    <div class="radio-option">
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="late" id="late_<?php echo $student['id']; ?>" <?php echo (isset($attendance_lookup[$student['id']]) && $attendance_lookup[$student['id']] == 'late') ? 'checked' : ''; ?>>
                                        <label for="late_<?php echo $student['id']; ?>" class="late">
                                            <i class="fas fa-clock me-1"></i>Late
                                        </label>
                                    </div>
                                    
                                    <div class="radio-option">
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="excused" id="excused_<?php echo $student['id']; ?>" <?php echo (isset($attendance_lookup[$student['id']]) && $attendance_lookup[$student['id']] == 'excused') ? 'checked' : ''; ?>>
                                        <label for="excused_<?php echo $student['id']; ?>" class="excused">
                                            <i class="fas fa-file-medical me-1"></i>Excused
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($current_class != '') echo '</div>'; ?>
                    
                    <div class="text-center mt-4">
                        <button type="submit" name="mark_attendance" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Save Attendance
                        </button>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No students found</h5>
                        <p class="text-muted">Add students to start marking attendance.</p>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Monthly Summary -->
        <div class="monthly-summary">
            <h5 class="mb-4">Recent Attendance Summary</h5>
            <?php if (!empty($monthly_summary)): ?>
                <?php foreach ($monthly_summary as $summary): ?>
                    <div class="summary-item">
                        <div>
                            <div class="fw-bold"><?php echo date('F d, Y', strtotime($summary['attendance_date'])); ?></div>
                            <div class="text-muted small"><?php echo date('l', strtotime($summary['attendance_date'])); ?></div>
                        </div>
                        <div class="summary-badges">
                            <span class="badge-custom bg-success"><?php echo $summary['present']; ?> Present</span>
                            <span class="badge-custom bg-danger"><?php echo $summary['absent']; ?> Absent</span>
                            <span class="badge-custom bg-warning"><?php echo $summary['late']; ?> Late</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center">No attendance data available for this month.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeDate() {
            const date = document.getElementById('attendanceDate').value;
            window.location.href = 'attendance.php?date=' + date;
        }
        
        // Auto-save functionality (optional)
        let autoSaveTimer;
        document.getElementById('attendanceForm').addEventListener('change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                console.log('Auto-save would happen here');
            }, 5000);
        });
        
        // Quick date selection buttons
        document.addEventListener('DOMContentLoaded', function() {
            const dateSelector = document.querySelector('.date-selector');
            const todayBtn = document.createElement('button');
            todayBtn.className = 'btn btn-outline-secondary';
            todayBtn.innerHTML = '<i class="fas fa-calendar-day me-2"></i>Today';
            todayBtn.onclick = function() {
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('attendanceDate').value = today;
                changeDate();
            };
            dateSelector.appendChild(todayBtn);
        });
    </script>
</body>
</html>
