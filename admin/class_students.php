<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_student_to_class'])) {
        $student_id = $_POST['student_id'];
        $target_class_id = $_POST['class_id'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Update student's class assignment
            $query = "UPDATE students SET class_id = :class_id WHERE id = :student_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':class_id', $target_class_id);
            $stmt->bindParam(':student_id', $student_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Student added to class successfully!');
            } else {
                flash_message('error', 'Failed to add student to class.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('class_students.php?class_id=' . $target_class_id);
    }
    
    if (isset($_POST['remove_student_from_class'])) {
        $student_id = $_POST['student_id'];
        $target_class_id = $_POST['class_id'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Remove student from class (set class_id to NULL)
            $query = "UPDATE students SET class_id = NULL WHERE id = :student_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Student removed from class successfully!');
            } else {
                flash_message('error', 'Failed to remove student from class.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('class_students.php?class_id=' . $target_class_id);
    }
    
    if (isset($_POST['add_new_student'])) {
        // Add new student directly to this class
        $first_name = clean_input($_POST['first_name']);
        $last_name = clean_input($_POST['last_name']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $mother_name = clean_input($_POST['mother_name']);
        $father_name = clean_input($_POST['father_name']);
        $parent_email = clean_input($_POST['parent_email']);
        $parent_phone = clean_input($_POST['parent_phone']);
        $admission_date = $_POST['admission_date'];
        $address = clean_input($_POST['address']);
        $medical_info = clean_input($_POST['medical_info']);
        $allergies = clean_input($_POST['allergies']);
        $emergency_contact = clean_input($_POST['emergency_contact']);
        $emergency_phone = clean_input($_POST['emergency_phone']);
        $target_class_id = $_POST['class_id'];
        
        // Generate student ID and login credentials
        $student_id = 'STU' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Generate parent username and password
        $parent_username = 'PARENT' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $parent_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $hashed_password = password_hash($parent_password, PASSWORD_DEFAULT);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Store parent contact info in address field (email and phone only)
            $parent_contact_info = '';
            if ($parent_email || $parent_phone) {
                $parent_parts = [];
                if ($parent_email) $parent_parts[] = "Email: " . $parent_email;
                if ($parent_phone) $parent_parts[] = "Phone: " . $parent_phone;
                $parent_contact_info = implode(" | ", $parent_parts);
            }
            
            $full_address = $address;
            if ($parent_contact_info) {
                $full_address = $address . ($address ? "\n\n" : "") . $parent_contact_info;
            }
            
            // Start transaction
            $db->beginTransaction();
            
            // Create parent user account
            $parent_full_name = trim($mother_name . ' ' . $father_name);
            if (empty($parent_full_name)) {
                $parent_full_name = $first_name . ' ' . $last_name . ' Parent';
            }
            
            $user_query = "INSERT INTO users (username, email, password, full_name, role, phone, status) 
                          VALUES (:username, :email, :password, :full_name, 'parent', :phone, 'active')";
            
            $user_stmt = $db->prepare($user_query);
            $user_stmt->bindParam(':username', $parent_username);
            $user_stmt->bindParam(':email', $parent_email);
            $user_stmt->bindParam(':password', $hashed_password);
            $user_stmt->bindParam(':full_name', $parent_full_name);
            $user_stmt->bindParam(':phone', $parent_phone);
            $user_stmt->execute();
            
            $parent_user_id = $db->lastInsertId();
            
            // Insert student record
            $query = "INSERT INTO students (first_name, last_name, date_of_birth, gender, mother_name, father_name, class_id, parent_id, admission_date, student_id, address, medical_info, allergies, emergency_contact, emergency_phone) 
                      VALUES (:first_name, :last_name, :date_of_birth, :gender, :mother_name, :father_name, :class_id, :parent_id, :admission_date, :student_id, :address, :medical_info, :allergies, :emergency_contact, :emergency_phone)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':mother_name', $mother_name);
            $stmt->bindParam(':father_name', $father_name);
            $stmt->bindParam(':class_id', $target_class_id);
            $stmt->bindParam(':parent_id', $parent_user_id);
            $stmt->bindParam(':admission_date', $admission_date);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':address', $full_address);
            $stmt->bindParam(':medical_info', $medical_info);
            $stmt->bindParam(':allergies', $allergies);
            $stmt->bindParam(':emergency_contact', $emergency_contact);
            $stmt->bindParam(':emergency_phone', $emergency_phone);
            
            if ($stmt->execute()) {
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $upload_result = upload_file($_FILES['profile_image'], '../uploads/students/');
                    if ($upload_result) {
                        $update_query = "UPDATE students SET profile_image = :profile_image WHERE id = :id";
                        $update_stmt = $db->prepare($update_query);
                        $update_stmt->bindParam(':profile_image', $upload_result);
                        $update_stmt->bindParam(':id', $db->lastInsertId());
                        $update_stmt->execute();
                    }
                }
                
                // Commit transaction
                $db->commit();
                
                // Store credentials in session for display
                $_SESSION['parent_credentials'] = [
                    'username' => $parent_username,
                    'password' => $parent_password,
                    'email' => $parent_email,
                    'student_name' => $first_name . ' ' . $last_name
                ];
                
                flash_message('success', 'New student added to class successfully! Parent login credentials created.');
            } else {
                $db->rollBack();
                flash_message('error', 'Failed to add student to class.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('class_students.php?class_id=' . $target_class_id);
    }
}

// Get class information and students
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get class details
    $class_query = "SELECT c.*, u.full_name as teacher_name FROM classes c LEFT JOIN users u ON c.teacher_id = u.id WHERE c.id = :class_id";
    $class_stmt = $db->prepare($class_query);
    $class_stmt->bindParam(':class_id', $class_id);
    $class_stmt->execute();
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        flash_message('error', 'Class not found!');
        redirect('classes.php');
    }
    
    // Get students currently in this class
    $students_query = "SELECT * FROM students WHERE class_id = :class_id AND status = 'active' ORDER BY first_name, last_name";
    $students_stmt = $db->prepare($students_query);
    $students_stmt->bindParam(':class_id', $class_id);
    $students_stmt->execute();
    $class_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available students not in any class
    $available_students_query = "SELECT * FROM students WHERE class_id IS NULL AND status = 'active' ORDER BY first_name, last_name";
    $available_students_stmt = $db->prepare($available_students_query);
    $available_students_stmt->execute();
    $available_students = $available_students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get parents for dropdown
    $parents_query = "SELECT id, full_name FROM users WHERE role = 'parent' AND status = 'active' ORDER BY full_name";
    $parents_stmt = $db->prepare($parents_query);
    $parents_stmt->execute();
    $parents = $parents_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $class_info = [];
    $class_students = [];
    $available_students = [];
    $parents = [];
    $error_message = "Error loading data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Students - <?php echo htmlspecialchars($class_info['name'] ?? 'Class'); ?> - Kidzenia Kindergarten</title>
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
        
        .class-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .student-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .student-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .stats-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.85rem;
            margin: 0 2px;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .available-student {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary-color);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .available-student:hover {
            background: #e9ecef;
            transform: translateX(5px);
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
                    <a class="nav-link active" href="classes.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="classes.php">Classes</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($class_info['name']); ?></li>
                    </ol>
                </nav>
                <h4>Class Students Management</h4>
            </div>
            <div>
                <button class="btn btn-outline-secondary" onclick="window.history.back()">
                    <i class="fas fa-arrow-left me-2"></i>Back to Classes
                </button>
                <a href="student_management.php" class="btn btn-success me-2">
                    <i class="fas fa-cog me-2"></i>Comprehensive Student Management
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewStudentModal">
                    <i class="fas fa-user-plus me-2"></i>Add New Student
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
        
        // Display parent credentials if available
        if (isset($_SESSION['parent_credentials'])):
            $credentials = $_SESSION['parent_credentials'];
            unset($_SESSION['parent_credentials']);
        ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h5><i class="fas fa-key me-2"></i>Parent Login Credentials Created!</h5>
                <p class="mb-2"><strong>Student:</strong> <?php echo htmlspecialchars($credentials['student_name']); ?></p>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Username:</strong> <code><?php echo htmlspecialchars($credentials['username']); ?></code></p>
                        <p class="mb-1"><strong>Password:</strong> <code><?php echo htmlspecialchars($credentials['password']); ?></code></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($credentials['email']); ?></p>
                        <p class="mb-0"><small class="text-muted">Please share these credentials with the parent</small></p>
                    </div>
                </div>
                <hr>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary" onclick="copyCredentials()">
                        <i class="fas fa-copy me-1"></i>Copy Credentials
                    </button>
                    <button type="button" class="btn btn-sm btn-info" onclick="emailCredentials()">
                        <i class="fas fa-envelope me-1"></i>Email to Parent
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Class Information -->
        <div class="class-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><?php echo htmlspecialchars($class_info['name']); ?></h2>
                    <p class="mb-2"><?php echo htmlspecialchars($class_info['description']); ?></p>
                    <div class="d-flex gap-3">
                        <span><i class="fas fa-users me-2"></i><?php echo htmlspecialchars($class_info['age_group']); ?></span>
                        <span><i class="fas fa-door-open me-2"></i><?php echo htmlspecialchars($class_info['room_number']); ?></span>
                        <?php if ($class_info['teacher_name']): ?>
                            <span><i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($class_info['teacher_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-box">
                        <div class="stats-number"><?php echo count($class_students); ?></div>
                        <div class="text-white">Current Students</div>
                        <div class="text-white-50 small">Capacity: <?php echo $class_info['capacity']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Current Students -->
            <div class="col-lg-8">
                <h5 class="mb-3">Current Students (<?php echo count($class_students); ?>)</h5>
                
                <?php if (!empty($class_students)): ?>
                    <?php foreach ($class_students as $student): ?>
                        <div class="student-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="student-avatar">
                                        <?php if ($student['profile_image']): ?>
                                            <img src="../uploads/students/<?php echo htmlspecialchars($student['profile_image']); ?>" alt="<?php echo htmlspecialchars($student['first_name']); ?>">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                        <p class="text-muted mb-1">ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-primary"><?php echo date('Y') - date('Y', strtotime($student['date_of_birth'])); ?> years</span>
                                            <span class="text-muted small">
                                                <i class="fas fa-birthday-cake me-1"></i>
                                                <?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?>
                                            </span>
                                        </div>
                                        <?php if ($student['medical_info']): ?>
                                            <div class="mt-1">
                                                <small class="text-warning">
                                                    <i class="fas fa-heartbeat me-1"></i>
                                                    Medical: <?php echo htmlspecialchars(substr($student['medical_info'], 0, 50)) . '...'; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-info" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editStudent(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="removeStudent(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No students in this class yet</h5>
                        <p class="text-muted">Add students to get started!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Available Students -->
            <div class="col-lg-4">
                <h5 class="mb-3">Available Students (<?php echo count($available_students); ?>)</h5>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($available_students)): ?>
                            <?php foreach ($available_students as $student): ?>
                                <div class="available-student" onclick="addStudentToClass(<?php echo $student['id']; ?>)">
                                    <div class="d-flex align-items-center">
                                        <div class="student-avatar" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                            <small class="text-muted">ID: <?php echo htmlspecialchars($student['student_id']); ?></small>
                                        </div>
                                        <i class="fas fa-plus-circle text-primary"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <p class="text-muted small">No available students</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Student Modal -->
    <div class="modal fade" id="addNewStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student to <?php echo htmlspecialchars($class_info['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-control" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Admission Date</label>
                                    <input type="date" class="form-control" name="admission_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Mother's Name</label>
                                    <input type="text" class="form-control" name="mother_name" placeholder="Enter mother's full name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Father's Name</label>
                                    <input type="text" class="form-control" name="father_name" placeholder="Enter father's full name">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Parent Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="parent_email" placeholder="parent@email.com" required>
                                    <div class="form-text">This email will receive all communications and login credentials</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Parent Phone</label>
                                    <input type="tel" class="form-control" name="parent_phone" placeholder="+1234567890">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Emergency Contact</label>
                                    <input type="text" class="form-control" name="emergency_contact">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Emergency Phone</label>
                                    <input type="text" class="form-control" name="emergency_phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Medical Information</label>
                                    <textarea class="form-control" name="medical_info" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Allergies</label>
                                    <textarea class="form-control" name="allergies" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Profile Image</label>
                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_new_student" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addStudentToClass(studentId) {
            if (confirm('Add this student to the class?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="student_id" value="${studentId}">
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    <input type="hidden" name="add_student_to_class" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function removeStudent(studentId) {
            if (confirm('Remove this student from the class?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="student_id" value="${studentId}">
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    <input type="hidden" name="remove_student_from_class" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewStudent(id) {
            // Implement view functionality
            console.log('View student:', id);
        }
        
        function editStudent(id) {
            // Implement edit functionality
            console.log('Edit student:', id);
        }
        
        function copyCredentials() {
            const credentialsText = `Parent Login Credentials:\nUsername: ${document.querySelector('code').textContent}\nPassword: ${document.querySelectorAll('code')[1].textContent}\nEmail: ${document.querySelector('.col-md-6 p strong').nextSibling.textContent.trim()}`;
            
            navigator.clipboard.writeText(credentialsText).then(function() {
                alert('Credentials copied to clipboard!');
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
            });
        }
        
        function emailCredentials() {
            const email = document.querySelector('.col-md-6 p strong').nextSibling.textContent.trim();
            const username = document.querySelector('code').textContent;
            const password = document.querySelectorAll('code')[1].textContent;
            
            const subject = 'Your Kidzenia Kindergarten Login Credentials';
            const body = `Dear Parent,\n\nYour login credentials for Kidzenia Kindergarten Management System:\n\nUsername: ${username}\nPassword: ${password}\n\nPlease keep these credentials safe and use them to monitor your child's progress.\n\nBest regards,\nKidzenia Kindergarten Administration`;
            
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
    </script>
</body>
</html>
