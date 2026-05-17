<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_teacher'])) {
        // Add new teacher
        $username = clean_input($_POST['username']);
        $email = clean_input($_POST['email']);
        $password = $_POST['password'];
        $full_name = clean_input($_POST['full_name']);
        $phone = clean_input($_POST['phone']);
        $address = clean_input($_POST['address']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if username or email already exists
            $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':username', $username);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                flash_message('error', 'Username or email already exists!');
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (username, email, password, full_name, role, phone, address) 
                          VALUES (:username, :email, :password, :full_name, 'teacher', :phone, :address)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                
                if ($stmt->execute()) {
                    flash_message('success', 'Teacher added successfully!');
                    
                    // Handle profile image upload
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                        $upload_result = upload_file($_FILES['profile_image'], '../uploads/teachers/');
                        if ($upload_result) {
                            $update_query = "UPDATE users SET profile_image = :profile_image WHERE id = :id";
                            $update_stmt = $db->prepare($update_query);
                            $update_stmt->bindParam(':profile_image', $upload_result);
                            $update_stmt->bindParam(':id', $db->lastInsertId());
                            $update_stmt->execute();
                        }
                    }
                } else {
                    flash_message('error', 'Failed to add teacher.');
                }
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('teachers.php');
    }
    
    if (isset($_POST['edit_teacher'])) {
        // Edit teacher
        $teacher_id = $_POST['teacher_id'];
        $username = clean_input($_POST['username']);
        $email = clean_input($_POST['email']);
        $full_name = clean_input($_POST['full_name']);
        $phone = clean_input($_POST['phone']);
        $address = clean_input($_POST['address']);
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if username or email already exists (excluding current user)
            $check_query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :teacher_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':username', $username);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->bindParam(':teacher_id', $teacher_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                flash_message('error', 'Username or email already exists!');
            } else {
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET username = :username, email = :email, full_name = :full_name, phone = :phone, address = :address, password = :password WHERE id = :teacher_id";
                } else {
                    $query = "UPDATE users SET username = :username, email = :email, full_name = :full_name, phone = :phone, address = :address WHERE id = :teacher_id";
                }
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                if ($password) {
                    $stmt->bindParam(':password', $hashed_password);
                }
                $stmt->bindParam(':teacher_id', $teacher_id);
                
                if ($stmt->execute()) {
                    flash_message('success', 'Teacher updated successfully!');
                    
                    // Handle profile image upload
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                        $upload_result = upload_file($_FILES['profile_image'], '../uploads/teachers/');
                        if ($upload_result) {
                            $update_query = "UPDATE users SET profile_image = :profile_image WHERE id = :id";
                            $update_stmt = $db->prepare($update_query);
                            $update_stmt->bindParam(':profile_image', $upload_result);
                            $update_stmt->bindParam(':id', $teacher_id);
                            $update_stmt->execute();
                        }
                    }
                } else {
                    flash_message('error', 'Failed to update teacher.');
                }
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('teachers.php');
    }
    
    if (isset($_POST['delete_teacher'])) {
        // Delete teacher (soft delete)
        $teacher_id = $_POST['teacher_id'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE users SET status = 'inactive' WHERE id = :teacher_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':teacher_id', $teacher_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Teacher deleted successfully!');
            } else {
                flash_message('error', 'Failed to delete teacher.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('teachers.php');
    }
}

// Get teachers data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all teachers with class assignment info
    $query = "SELECT u.*, 
              (SELECT COUNT(*) FROM classes WHERE teacher_id = u.id AND status = 'active') as assigned_classes,
              (SELECT COUNT(*) FROM students s JOIN classes c ON s.class_id = c.id WHERE c.teacher_id = u.id AND s.status = 'active') as total_students
              FROM users u 
              WHERE u.role = 'teacher' AND u.status = 'active' 
              ORDER BY u.full_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $teachers = [];
    $error_message = "Error loading data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management - Kidzenia Kindergarten</title>
    
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
        
        .teacher-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .teacher-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .teacher-avatar {
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
        
        .teacher-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .teacher-stats {
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
            <h4>Teacher Management</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                <i class="fas fa-plus me-2"></i>Add New Teacher
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

        <!-- Search Box -->
        <div class="search-box">
            <div class="row">
                <div class="col-md-8">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search teachers by name or email...">
                </div>
                <div class="col-md-4">
                    <select class="form-control" id="statusFilter">
                        <option value="">All Teachers</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Teachers List -->
        <div id="teachersList">
            <?php if (!empty($teachers)): ?>
                <?php foreach ($teachers as $teacher): ?>
                    <div class="teacher-card" data-name="<?php echo strtolower($teacher['full_name']); ?>" data-email="<?php echo strtolower($teacher['email']); ?>">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="teacher-avatar">
                                    <?php if ($teacher['profile_image']): ?>
                                        <img src="../uploads/teachers/<?php echo htmlspecialchars($teacher['profile_image']); ?>" alt="<?php echo htmlspecialchars($teacher['full_name']); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($teacher['full_name'], 0, 2)); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($teacher['full_name']); ?></h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($teacher['email']); ?></p>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="text-muted small">
                                            <i class="fas fa-phone me-1"></i>
                                            <?php echo htmlspecialchars($teacher['phone'] ?: 'Not provided'); ?>
                                        </span>
                                        <span class="text-muted small">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($teacher['username']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="teacher-stats">
                                        <div class="stat-item">
                                            <div class="stat-number"><?php echo $teacher['assigned_classes']; ?></div>
                                            <div class="stat-label">Classes</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-number"><?php echo $teacher['total_students']; ?></div>
                                            <div class="stat-label">Students</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-info" onclick="viewTeacher(<?php echo $teacher['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editTeacher(<?php echo $teacher['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteTeacher(<?php echo $teacher['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No teachers found</h5>
                    <p class="text-muted">Start by adding your first teacher.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Profile Image</label>
                                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            filterTeachers();
        });
        
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTeachers();
        });
        
        function filterTeachers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const teacherCards = document.querySelectorAll('.teacher-card');
            
            teacherCards.forEach(card => {
                const name = card.dataset.name;
                const email = card.dataset.email;
                
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                
                if (matchesSearch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function viewTeacher(id) {
            // Implement view functionality
            console.log('View teacher:', id);
        }
        
        function editTeacher(id) {
            // Implement edit functionality
            console.log('Edit teacher:', id);
        }
        
        function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="teacher_id" value="' + id + '"><input type="hidden" name="delete_teacher" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
