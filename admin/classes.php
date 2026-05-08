<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_class'])) {
        // Add new class
        $name = clean_input($_POST['name']);
        $description = clean_input($_POST['description']);
        $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
        $capacity = $_POST['capacity'];
        $age_group = clean_input($_POST['age_group']);
        $room_number = clean_input($_POST['room_number']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO classes (name, description, teacher_id, capacity, age_group, room_number) 
                      VALUES (:name, :description, :teacher_id, :capacity, :age_group, :room_number)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->bindParam(':capacity', $capacity);
            $stmt->bindParam(':age_group', $age_group);
            $stmt->bindParam(':room_number', $room_number);
            
            if ($stmt->execute()) {
                flash_message('success', 'Class added successfully!');
            } else {
                flash_message('error', 'Failed to add class.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('classes.php');
    }
    
    if (isset($_POST['edit_class'])) {
        // Edit class
        $class_id = $_POST['class_id'];
        $name = clean_input($_POST['name']);
        $description = clean_input($_POST['description']);
        $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
        $capacity = $_POST['capacity'];
        $age_group = clean_input($_POST['age_group']);
        $room_number = clean_input($_POST['room_number']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE classes SET name = :name, description = :description, teacher_id = :teacher_id, capacity = :capacity, age_group = :age_group, room_number = :room_number WHERE id = :class_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->bindParam(':capacity', $capacity);
            $stmt->bindParam(':age_group', $age_group);
            $stmt->bindParam(':room_number', $room_number);
            $stmt->bindParam(':class_id', $class_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Class updated successfully!');
            } else {
                flash_message('error', 'Failed to update class.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('classes.php');
    }
    
    if (isset($_POST['delete_class'])) {
        // Delete class
        $class_id = $_POST['class_id'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if class has students
            $check_query = "SELECT COUNT(*) as count FROM students WHERE class_id = :class_id AND status = 'active'";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':class_id', $class_id);
            $check_stmt->execute();
            $student_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($student_count > 0) {
                flash_message('error', 'Cannot delete class with active students. Please reassign students first.');
            } else {
                $query = "UPDATE classes SET status = 'inactive' WHERE id = :class_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':class_id', $class_id);
                
                if ($stmt->execute()) {
                    flash_message('success', 'Class deleted successfully!');
                } else {
                    flash_message('error', 'Failed to delete class.');
                }
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('classes.php');
    }
}

// Get classes data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all classes with teacher and student count
    $query = "SELECT c.*, u.full_name as teacher_name,
              (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active') as student_count
              FROM classes c 
              LEFT JOIN users u ON c.teacher_id = u.id 
              WHERE c.status = 'active' 
              ORDER BY c.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get teachers for dropdown
    $teachers_query = "SELECT id, full_name FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY full_name";
    $teachers_stmt = $db->prepare($teachers_query);
    $teachers_stmt->execute();
    $teachers = $teachers_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $classes = [];
    $teachers = [];
    $error_message = "Error loading data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management - Kidzenia Kindergarten</title>
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
        
        .class-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .class-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .class-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 20px;
        }
        
        .class-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .capacity-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .capacity-fill {
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            transition: width 0.3s;
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
            <h4>Class Management</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="fas fa-plus me-2"></i>Add New Class
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

        <!-- Classes List -->
        <div id="classesList">
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                    <div class="class-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="class-icon">
                                    <i class="fas fa-school"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($class['name']); ?></h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($class['description']); ?></p>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="text-muted small">
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo htmlspecialchars($class['age_group']); ?>
                                        </span>
                                        <span class="text-muted small">
                                            <i class="fas fa-door-open me-1"></i>
                                            <?php echo htmlspecialchars($class['room_number']); ?>
                                        </span>
                                        <?php if ($class['teacher_name']): ?>
                                            <span class="text-muted small">
                                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                                <?php echo htmlspecialchars($class['teacher_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small text-muted">Capacity</span>
                                            <span class="small fw-bold"><?php echo $class['student_count']; ?>/<?php echo $class['capacity']; ?></span>
                                        </div>
                                        <div class="capacity-bar">
                                            <div class="capacity-fill" style="width: <?php echo min(100, ($class['student_count'] / $class['capacity']) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="manageStudents(<?php echo $class['id']; ?>)">
                                    <i class="fas fa-users"></i> Students
                                </button>
                                <button class="btn btn-sm btn-info" onclick="viewClass(<?php echo $class['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editClass(<?php echo $class['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteClass(<?php echo $class['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-school fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No classes found</h5>
                    <p class="text-muted">Start by adding your first class.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Class Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Age Group</label>
                                    <input type="text" class="form-control" name="age_group" placeholder="e.g., 3-4 years">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Room Number</label>
                                    <input type="text" class="form-control" name="room_number" placeholder="e.g., Room 101">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" class="form-control" name="capacity" min="1" max="50" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teacher</label>
                                    <select class="form-control" name="teacher_id">
                                        <option value="">Select Teacher</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewClass(id) {
            // Implement view functionality
            console.log('View class:', id);
        }
        
        function editClass(id) {
            // Implement edit functionality
            console.log('Edit class:', id);
        }
        
        function manageStudents(id) {
            window.location.href = 'class_students.php?class_id=' + id;
        }
        
        function deleteClass(id) {
            if (confirm('Are you sure you want to delete this class?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="class_id" value="' + id + '"><input type="hidden" name="delete_class" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
