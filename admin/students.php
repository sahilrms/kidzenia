<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_student'])) {
        // Add new student
        $first_name = clean_input($_POST['first_name']);
        $last_name = clean_input($_POST['last_name']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $mother_name = clean_input($_POST['mother_name']);
        $father_name = clean_input($_POST['father_name']);
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        $admission_date = $_POST['admission_date'];
        $address = clean_input($_POST['address']);
        $medical_info = clean_input($_POST['medical_info']);
        $allergies = clean_input($_POST['allergies']);
        $emergency_contact = clean_input($_POST['emergency_contact']);
        $emergency_phone = clean_input($_POST['emergency_phone']);
        
        // Generate student ID
        $student_id = 'STU' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO students (first_name, last_name, date_of_birth, gender, mother_name, father_name, class_id, parent_id, admission_date, student_id, address, medical_info, allergies, emergency_contact, emergency_phone) 
                      VALUES (:first_name, :last_name, :date_of_birth, :gender, :mother_name, :father_name, :class_id, :parent_id, :admission_date, :student_id, :address, :medical_info, :allergies, :emergency_contact, :emergency_phone)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':mother_name', $mother_name);
            $stmt->bindParam(':father_name', $father_name);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->bindParam(':parent_id', $parent_id);
            $stmt->bindParam(':admission_date', $admission_date);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':medical_info', $medical_info);
            $stmt->bindParam(':allergies', $allergies);
            $stmt->bindParam(':emergency_contact', $emergency_contact);
            $stmt->bindParam(':emergency_phone', $emergency_phone);
            
            if ($stmt->execute()) {
                flash_message('success', 'Student added successfully!');
                
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
            } else {
                flash_message('error', 'Failed to add student.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('students.php');
    }
    
    if (isset($_POST['edit_student'])) {
        // Edit student
        $student_id = $_POST['student_id'];
        $first_name = clean_input($_POST['first_name']);
        $last_name = clean_input($_POST['last_name']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $mother_name = clean_input($_POST['mother_name']);
        $father_name = clean_input($_POST['father_name']);
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        $address = clean_input($_POST['address']);
        $medical_info = clean_input($_POST['medical_info']);
        $allergies = clean_input($_POST['allergies']);
        $emergency_contact = clean_input($_POST['emergency_contact']);
        $emergency_phone = clean_input($_POST['emergency_phone']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE students SET first_name = :first_name, last_name = :last_name, date_of_birth = :date_of_birth, gender = :gender, mother_name = :mother_name, father_name = :father_name, class_id = :class_id, parent_id = :parent_id, address = :address, medical_info = :medical_info, allergies = :allergies, emergency_contact = :emergency_contact, emergency_phone = :emergency_phone WHERE id = :student_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':mother_name', $mother_name);
            $stmt->bindParam(':father_name', $father_name);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->bindParam(':parent_id', $parent_id);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':medical_info', $medical_info);
            $stmt->bindParam(':allergies', $allergies);
            $stmt->bindParam(':emergency_contact', $emergency_contact);
            $stmt->bindParam(':emergency_phone', $emergency_phone);
            $stmt->bindParam(':student_id', $student_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Student updated successfully!');
                
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $upload_result = upload_file($_FILES['profile_image'], '../uploads/students/');
                    if ($upload_result) {
                        $update_query = "UPDATE students SET profile_image = :profile_image WHERE id = :id";
                        $update_stmt = $db->prepare($update_query);
                        $update_stmt->bindParam(':profile_image', $upload_result);
                        $update_stmt->bindParam(':id', $student_id);
                        $update_stmt->execute();
                    }
                }
            } else {
                flash_message('error', 'Failed to update student.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('students.php');
    }
    
    if (isset($_POST['delete_student'])) {
        // Delete student (soft delete)
        $student_id = $_POST['student_id'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE students SET status = 'inactive' WHERE id = :student_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Student deleted successfully!');
            } else {
                flash_message('error', 'Failed to delete student.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('students.php');
    }
}

// Get students data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all students with class and parent info
    $query = "SELECT s.*, c.name as class_name, u.full_name as parent_name 
              FROM students s 
              LEFT JOIN classes c ON s.class_id = c.id 
              LEFT JOIN users u ON s.parent_id = u.id 
              WHERE s.status = 'active' 
              ORDER BY s.first_name, s.last_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get classes for dropdown
    $classes_query = "SELECT id, name FROM classes WHERE status = 'active' ORDER BY name";
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->execute();
    $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get parents for dropdown
    $parents_query = "SELECT id, full_name FROM users WHERE role = 'parent' AND status = 'active' ORDER BY full_name";
    $parents_stmt = $db->prepare($parents_query);
    $parents_stmt->execute();
    $parents = $parents_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $students = [];
    $classes = [];
    $parents = [];
    $error_message = "Error loading data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Kidzenia Kindergarten</title>
    
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
        
        .student-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .student-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .badge-class {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.85rem;
            margin: 0 2px;
        }
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Student Management</h4>
            <div>
                <a href="student_management.php" class="btn btn-success me-2">
                    <i class="fas fa-cog me-2"></i>Comprehensive Student Management
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-2"></i>Add New Student
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

        <!-- Comprehensive Student Management Info -->
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <h6 class="mb-2">🎓 Complete Student Management System Available</h6>
                    <p class="mb-2">Access the comprehensive student management system for advanced features including:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li><i class="fas fa-chart-line me-2 text-primary"></i>Academic Progress Tracking</li>
                                <li><i class="fas fa-star me-2 text-warning"></i>Behavior & Conduct Management</li>
                                <li><i class="fas fa-heartbeat me-2 text-danger"></i>Health & Medical Records</li>
                                <li><i class="fas fa-comments me-2 text-success"></i>Parent Communication Hub</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li><i class="fas fa-file-alt me-2 text-info"></i>Document Management</li>
                                <li><i class="fas fa-dollar-sign me-2 text-success"></i>Fee Tracking & Payments</li>
                                <li><i class="fas fa-bus me-2 text-primary"></i>Transportation Management</li>
                                <li><i class="fas fa-images me-2 text-warning"></i>Student Portfolio</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="student_management.php" class="btn btn-success">
                            <i class="fas fa-arrow-right me-2"></i>Go to Comprehensive Student Management
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search students by name or ID...">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="classFilter">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="genderFilter">
                        <option value="">All Genders</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Students List -->
        <div id="studentsList">
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                    <div class="student-card" data-name="<?php echo strtolower($student['first_name'] . ' ' . $student['last_name']); ?>" data-class="<?php echo $student['class_id']; ?>" data-gender="<?php echo $student['gender']; ?>" onclick="window.location.href='student_profile.php?id=<?php echo $student['id']; ?>'" style="cursor: pointer;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center flex-grow-1" onclick="event.stopPropagation()">
                                <div class="student-avatar">
                                    <?php if ($student['profile_image']): ?>
                                        <img src="../uploads/students/<?php echo htmlspecialchars($student['profile_image']); ?>" alt="<?php echo htmlspecialchars($student['first_name']); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                                    <p class="text-muted mb-1">ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($student['class_name']): ?>
                                            <span class="badge-class"><?php echo htmlspecialchars($student['class_name']); ?></span>
                                        <?php endif; ?>
                                        <span class="text-muted small">
                                            <i class="fas fa-birthday-cake me-1"></i>
                                            <?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="action-buttons" onclick="event.stopPropagation()">
                                <a href="student_management.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-success" title="Comprehensive Student Management">
                                    <i class="fas fa-cog"></i>
                                </a>
                                <button class="btn btn-sm btn-info" onclick="viewStudent(<?php echo $student['id']; ?>)" title="Quick View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editStudent(<?php echo $student['id']; ?>)" title="Quick Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No students found</h5>
                    <p class="text-muted">Start by adding your first student.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
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
                                    <label class="form-label">Class</label>
                                    <select class="form-control" name="class_id">
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Parent</label>
                                    <select class="form-control" name="parent_id">
                                        <option value="">Select Parent</option>
                                        <?php foreach ($parents as $parent): ?>
                                            <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
                        <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            filterStudents();
        });
        
        document.getElementById('classFilter').addEventListener('change', function() {
            filterStudents();
        });
        
        document.getElementById('genderFilter').addEventListener('change', function() {
            filterStudents();
        });
        
        function filterStudents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const classFilter = document.getElementById('classFilter').value;
            const genderFilter = document.getElementById('genderFilter').value;
            const studentCards = document.querySelectorAll('.student-card');
            
            studentCards.forEach(card => {
                const name = card.dataset.name;
                const classId = card.dataset.class;
                const gender = card.dataset.gender;
                
                const matchesSearch = name.includes(searchTerm);
                const matchesClass = !classFilter || classId === classFilter;
                const matchesGender = !genderFilter || gender === genderFilter;
                
                if (matchesSearch && matchesClass && matchesGender) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function viewStudent(id) {
            // Implement view functionality
            console.log('View student:', id);
        }
        
        function editStudent(id) {
            // Implement edit functionality
            console.log('Edit student:', id);
        }
        
        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="student_id" value="' + id + '"><input type="hidden" name="delete_student" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
