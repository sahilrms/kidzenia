<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? $_GET['id'] : 0;

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
} else {
    flash_message('error', 'No student ID provided!');
    redirect('students.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> - Kidzenia Kindergarten</title>
    
    <?php include 'components/favicon.php'; ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="components/sidebar.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 0.9rem;
        }
        
        .badge-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .action-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .action-btn.primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .action-btn.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .action-btn.info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
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
            
            .profile-header {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="profile-avatar mx-auto">
                        <?php if ($student['profile_image']): ?>
                            <img src="../uploads/students/<?php echo htmlspecialchars($student['profile_image']); ?>" alt="<?php echo htmlspecialchars($student['first_name']); ?>">
                        <?php else: ?>
                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h2 class="mb-2"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                    <p class="mb-1"><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                    <p class="mb-1"><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] ?? 'Not Assigned'); ?></p>
                    <p class="mb-0"><strong>Age:</strong> <?php echo $student['age']; ?> years old</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge-status bg-success">Active Student</span>
                    <div class="mt-3">
                        <a href="student_management.php?id=<?php echo $student['id']; ?>" class="action-btn primary mb-2">
                            <i class="fas fa-cog"></i> Comprehensive Management
                        </a>
                        <a href="students.php" class="action-btn info ms-2 mb-2">
                            <i class="fas fa-arrow-left"></i> Back to Students
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Personal Information -->
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="mb-4">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h5>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-venus-mars"></i>
                        </div>
                        <div>
                            <strong>Gender:</strong> <?php echo ucfirst(htmlspecialchars($student['gender'])); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-birthday-cake"></i>
                        </div>
                        <div>
                            <strong>Date of Birth:</strong> <?php echo date('F d, Y', strtotime($student['date_of_birth'])); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <strong>Admission Date:</strong> <?php echo date('F d, Y', strtotime($student['admission_date'])); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div>
                            <strong>Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($student['address'] ?? 'Not provided')); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent Information -->
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="mb-4">
                        <i class="fas fa-users me-2"></i>Parent/Guardian Information
                    </h5>
                    <?php if ($student['parent_name']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div>
                                <strong>Parent Name:</strong> <?php echo htmlspecialchars($student['parent_name']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <strong>Email:</strong> <?php echo htmlspecialchars($student['parent_email']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <strong>Phone:</strong> <?php echo htmlspecialchars($student['parent_phone']); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No parent information available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Medical Information -->
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="mb-4">
                        <i class="fas fa-heartbeat me-2"></i>Medical Information
                    </h5>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                        <div>
                            <strong>Medical Information:</strong><br>
                            <?php echo nl2br(htmlspecialchars($student['medical_info'] ?? 'No medical information provided')); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-allergies"></i>
                        </div>
                        <div>
                            <strong>Allergies:</strong><br>
                            <?php echo nl2br(htmlspecialchars($student['allergies'] ?? 'No allergies reported')); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="mb-4">
                        <i class="fas fa-phone-alt me-2"></i>Emergency Contact
                    </h5>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <strong>Emergency Contact:</strong> <?php echo htmlspecialchars($student['emergency_contact'] ?? 'Not provided'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div>
                            <strong>Emergency Phone:</strong> <?php echo htmlspecialchars($student['emergency_phone'] ?? 'Not provided'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="info-card">
            <h5 class="mb-4">
                <i class="fas fa-bolt me-2"></i>Quick Actions
            </h5>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="student_management.php?id=<?php echo $student['id']; ?>&tab=academic" class="action-btn success w-100">
                        <i class="fas fa-graduation-cap"></i> Academic Progress
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="student_management.php?id=<?php echo $student['id']; ?>&tab=attendance" class="action-btn info w-100">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="student_management.php?id=<?php echo $student['id']; ?>&tab=medical" class="action-btn primary w-100">
                        <i class="fas fa-heartbeat"></i> Medical Records
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="student_management.php?id=<?php echo $student['id']; ?>&tab=communication" class="action-btn success w-100">
                        <i class="fas fa-comments"></i> Communication
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
