<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_gallery'])) {
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $category = $_POST['category'];
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_result = upload_file($_FILES['image'], '../uploads/gallery/');
                if ($upload_result) {
                    $query = "INSERT INTO gallery (title, description, image_path, category, class_id, uploaded_by) 
                              VALUES (:title, :description, :image_path, :category, :class_id, :uploaded_by)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':image_path', $upload_result);
                    $stmt->bindParam(':category', $category);
                    $stmt->bindParam(':class_id', $class_id);
                    $stmt->bindParam(':uploaded_by', $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        flash_message('success', 'Image added to gallery successfully!');
                    } else {
                        flash_message('error', 'Failed to add image to gallery.');
                    }
                } else {
                    flash_message('error', 'Failed to upload image. Please check file format and size.');
                }
            } else {
                flash_message('error', 'Please select an image to upload.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('gallery.php');
    }
    
    if (isset($_POST['edit_gallery'])) {
        $gallery_id = $_POST['gallery_id'];
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $category = $_POST['category'];
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE gallery SET title = :title, description = :description, category = :category, class_id = :class_id WHERE id = :gallery_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->bindParam(':gallery_id', $gallery_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Gallery item updated successfully!');
            } else {
                flash_message('error', 'Failed to update gallery item.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('gallery.php');
    }
    
    if (isset($_POST['delete_gallery'])) {
        $gallery_id = $_POST['gallery_id'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Get image path before deletion
            $get_query = "SELECT image_path FROM gallery WHERE id = :gallery_id";
            $get_stmt = $db->prepare($get_query);
            $get_stmt->bindParam(':gallery_id', $gallery_id);
            $get_stmt->execute();
            $image = $get_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete from database
            $query = "UPDATE gallery SET is_active = 0 WHERE id = :gallery_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':gallery_id', $gallery_id);
            
            if ($stmt->execute()) {
                // Optionally delete the file from server
                if ($image && file_exists('../uploads/gallery/' . $image['image_path'])) {
                    unlink('../uploads/gallery/' . $image['image_path']);
                }
                flash_message('success', 'Gallery item deleted successfully!');
            } else {
                flash_message('error', 'Failed to delete gallery item.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('gallery.php');
    }
}

// Get gallery data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all gallery images with class info
    $query = "SELECT g.*, c.name as class_name 
              FROM gallery g 
              LEFT JOIN classes c ON g.class_id = c.id 
              WHERE g.is_active = 1 
              ORDER BY g.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $gallery_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get classes for dropdown
    $classes_query = "SELECT id, name FROM classes WHERE status = 'active' ORDER BY name";
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->execute();
    $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $gallery_items = [];
    $classes = [];
    $error_message = "Error loading data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - Kidzenia Kindergarten</title>
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
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .gallery-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .gallery-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .gallery-item:hover .gallery-image {
            transform: scale(1.05);
        }
        
        .gallery-content {
            padding: 20px;
        }
        
        .gallery-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .gallery-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .gallery-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .category-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .category-badge.classroom { background: rgba(102, 126, 234, 0.1); color: var(--primary-color); }
        .category-badge.activities { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .category-badge.events { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .category-badge.students { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .category-badge.facilities { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        
        .gallery-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .filter-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
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
            
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
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
                    <a class="nav-link active" href="gallery.php">
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
            <h4>Gallery Management</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGalleryModal">
                <i class="fas fa-plus me-2"></i>Add New Image
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

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="classroom">Classroom</option>
                        <option value="activities">Activities</option>
                        <option value="events">Events</option>
                        <option value="students">Students</option>
                        <option value="facilities">Facilities</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="classFilter">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search images...">
                </div>
            </div>
        </div>

        <!-- Gallery Grid -->
        <div class="gallery-grid" id="galleryGrid">
            <?php if (!empty($gallery_items)): ?>
                <?php foreach ($gallery_items as $item): ?>
                    <div class="gallery-item" data-category="<?php echo $item['category']; ?>" data-class="<?php echo $item['class_id']; ?>" data-title="<?php echo strtolower($item['title']); ?>">
                        <?php 
$image_src = '';
if ($item['image_path']) {
    // Check if it's an external URL
    if (strpos($item['image_path'], 'http') === 0) {
        $image_src = htmlspecialchars($item['image_path']);
    } 
    // Check if it's a local file that exists
    elseif (file_exists('../uploads/gallery/' . $item['image_path'])) {
        $image_src = '../uploads/gallery/' . htmlspecialchars($item['image_path']);
    } 
    // Fallback to placeholder
    else {
        $image_src = "https://picsum.photos/seed/gallery" . $item['id'] . "/300/200";
    }
} else {
    // Fallback to placeholder
    $image_src = "https://picsum.photos/seed/gallery" . $item['id'] . "/300/200";
}
?>
<img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="gallery-image">
                        <div class="gallery-content">
                            <h5 class="gallery-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="gallery-description"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="gallery-meta">
                                <span class="category-badge <?php echo $item['category']; ?>"><?php echo $item['category']; ?></span>
                                <span class="gallery-date"><?php echo format_date($item['created_at']); ?></span>
                            </div>
                            <?php if ($item['class_name']): ?>
                                <div class="mb-2">
                                    <small class="text-muted"><i class="fas fa-school me-1"></i><?php echo htmlspecialchars($item['class_name']); ?></small>
                                </div>
                            <?php endif; ?>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-info" onclick="viewImage(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editImage(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteImage(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-images fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No images in gallery</h5>
                        <p class="text-muted">Start by adding your first image to the gallery.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Gallery Modal -->
    <div class="modal fade" id="addGalleryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category" required>
                                        <option value="classroom">Classroom</option>
                                        <option value="activities">Activities</option>
                                        <option value="events">Events</option>
                                        <option value="students">Students</option>
                                        <option value="facilities">Facilities</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Class (Optional)</label>
                                    <select class="form-select" name="class_id">
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*" required>
                            <div class="form-text">Supported formats: JPG, PNG, GIF. Max size: 5MB</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_gallery" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Image
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Image Modal -->
    <div class="modal fade" id="viewImageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewImageTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="viewImageSrc" src="" alt="" class="img-fluid rounded">
                    <div class="mt-3">
                        <p id="viewImageDescription"></p>
                        <small id="viewImageMeta" class="text-muted"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.getElementById('categoryFilter').addEventListener('change', filterGallery);
        document.getElementById('classFilter').addEventListener('change', filterGallery);
        document.getElementById('searchInput').addEventListener('input', filterGallery);
        
        function filterGallery() {
            const category = document.getElementById('categoryFilter').value;
            const classFilter = document.getElementById('classFilter').value;
            const search = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.gallery-item');
            
            items.forEach(item => {
                const itemCategory = item.dataset.category;
                const itemClass = item.dataset.class;
                const itemTitle = item.dataset.title;
                
                const matchesCategory = !category || itemCategory === category;
                const matchesClass = !classFilter || itemClass === classFilter;
                const matchesSearch = !search || itemTitle.includes(search);
                
                if (matchesCategory && matchesClass && matchesSearch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function viewImage(id) {
            // Find the image data and show in modal
            const items = document.querySelectorAll('.gallery-item');
            items.forEach(item => {
                if (item.querySelector('.action-buttons button[onclick*="' + id + '"]')) {
                    const img = item.querySelector('.gallery-image');
                    const title = item.querySelector('.gallery-title').textContent;
                    const description = item.querySelector('.gallery-description').textContent;
                    const meta = item.querySelector('.gallery-date').textContent;
                    
                    document.getElementById('viewImageTitle').textContent = title;
                    document.getElementById('viewImageSrc').src = img.src;
                    document.getElementById('viewImageSrc').alt = title;
                    document.getElementById('viewImageDescription').textContent = description;
                    document.getElementById('viewImageMeta').textContent = 'Uploaded on ' + meta;
                    
                    new bootstrap.Modal(document.getElementById('viewImageModal')).show();
                }
            });
        }
        
        function editImage(id) {
            // Implement edit functionality
            console.log('Edit image:', id);
        }
        
        function deleteImage(id) {
            if (confirm('Are you sure you want to delete this image?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="gallery_id" value="' + id + '"><input type="hidden" name="delete_gallery" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Drag and drop functionality
        const uploadArea = document.querySelector('.upload-area');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.querySelector('input[name="image"]').files = files;
                }
            });
        }
    </script>
</body>
</html>
