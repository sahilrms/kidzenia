<?php
require_once '../config/config.php';
require_once '../config/feedback.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle feedback status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $feedback_id = (int)($_POST['feedback_id'] ?? 0);
        $status = $_POST['status'];
        $allowed_statuses = ['pending', 'approved', 'rejected'];
        
        if ($feedback_id > 0 && in_array($status, $allowed_statuses)) {
            try {
                $database = new Database();
                $db = $database->getConnection();
                if (!$db) {
                    throw new RuntimeException('Database connection failed');
                }
                ensure_feedback_table($db);
                
                $query = "UPDATE feedbacks SET status = :status WHERE id = :feedback_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindValue(':feedback_id', $feedback_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    flash_message('success', 'Feedback status updated successfully!');
                } else {
                    flash_message('error', 'Failed to update feedback status.');
                }
            } catch(Throwable $exception) {
                flash_message('error', 'Error: ' . $exception->getMessage());
            }
        } else {
            flash_message('error', 'Invalid feedback or status.');
        }
        
        redirect('feedbacks.php');
    }
    
    if (isset($_POST['edit_feedback'])) {
        $feedback_id = (int)($_POST['feedback_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        
        if ($feedback_id <= 0) {
            flash_message('error', 'Invalid feedback.');
        } elseif (empty($name) || empty($email) || empty($subject) || empty($message)) {
            flash_message('error', 'All fields are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_message('error', 'Invalid email address.');
        } elseif ($rating < 1 || $rating > 5) {
            flash_message('error', 'Rating must be between 1 and 5.');
        } else {
            try {
                $database = new Database();
                $db = $database->getConnection();
                if (!$db) {
                    throw new RuntimeException('Database connection failed');
                }
                ensure_feedback_table($db);
                
                $query = "UPDATE feedbacks SET name = :name, email = :email, subject = :subject, message = :message, rating = :rating WHERE id = :feedback_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':subject', $subject);
                $stmt->bindParam(':message', $message);
                $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
                $stmt->bindValue(':feedback_id', $feedback_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    flash_message('success', 'Feedback updated successfully!');
                } else {
                    flash_message('error', 'Failed to update feedback.');
                }
            } catch(Throwable $exception) {
                flash_message('error', 'Error: ' . $exception->getMessage());
            }
        }
        
        redirect('feedbacks.php');
    }
    
    if (isset($_POST['delete_feedback'])) {
        $feedback_id = (int)($_POST['feedback_id'] ?? 0);
        
        try {
            if ($feedback_id <= 0) {
                throw new RuntimeException('Invalid feedback.');
            }

            $database = new Database();
            $db = $database->getConnection();
            if (!$db) {
                throw new RuntimeException('Database connection failed');
            }
            ensure_feedback_table($db);
            
            $query = "DELETE FROM feedbacks WHERE id = :feedback_id";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':feedback_id', $feedback_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                flash_message('success', 'Feedback deleted successfully!');
            } else {
                flash_message('error', 'Failed to delete feedback.');
            }
        } catch(Throwable $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('feedbacks.php');
    }
}

// Get all feedbacks
try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new RuntimeException('Database connection failed');
    }
    ensure_feedback_table($db);
    
    $query = "SELECT * FROM feedbacks ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    AVG(rating) as avg_rating
                   FROM feedbacks";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(Throwable $exception) {
    $feedbacks = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'avg_rating' => 0];
    $error_message = "Error loading feedbacks: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedbacks Management - Kidzenia Kindergarten</title>
    
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
        
        .feedback-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #ddd;
            transition: all 0.3s;
        }
        
        .feedback-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .feedback-card.status-pending {
            border-left-color: #ffc107;
        }
        
        .feedback-card.status-approved {
            border-left-color: #28a745;
        }
        
        .feedback-card.status-rejected {
            border-left-color: #dc3545;
        }
        
        .rating-stars {
            color: #FFD700;
            font-size: 1.2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .feedback-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .feedback-message {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Feedbacks Management</h4>
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

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Feedbacks</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" style="color: #28a745;"><?php echo $stats['approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" style="color: #FFD700;"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></div>
                    <div class="stat-label">Avg Rating</div>
                </div>
            </div>
        </div>

        <!-- Feedbacks List -->
        <div class="row">
            <div class="col-12">
                <?php if (!empty($feedbacks)): ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="feedback-card status-<?php echo $feedback['status']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-2">
                                        <?php echo htmlspecialchars($feedback['subject']); ?>
                                        <span class="status-badge status-<?php echo $feedback['status']; ?> ms-2">
                                            <?php echo ucfirst($feedback['status']); ?>
                                        </span>
                                    </h5>
                                    <div class="feedback-meta mb-2">
                                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($feedback['name']); ?>
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($feedback['email']); ?>
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-calendar me-2"></i><?php echo date('M d, Y H:i', strtotime($feedback['created_at'])); ?>
                                    </div>
                                    <div class="rating-stars mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $feedback['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="ms-2 text-muted">(<?php echo $feedback['rating']; ?>/5)</span>
                                    </div>
                                    <div class="feedback-message">
                                        <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if ($feedback['status'] === 'pending'): ?>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                                    <input type="hidden" name="status" value="approved">
                                                    <input type="hidden" name="update_status" value="true">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-check me-2 text-success"></i>Approve
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <input type="hidden" name="update_status" value="true">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-times me-2 text-danger"></i>Reject
                                                    </button>
                                                </form>
                                            </li>
                                        <?php elseif ($feedback['status'] === 'approved'): ?>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                                    <input type="hidden" name="status" value="pending">
                                                    <input type="hidden" name="update_status" value="true">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-clock me-2 text-warning"></i>Mark as Pending
                                                    </button>
                                                </form>
                                            </li>
                                        <?php elseif ($feedback['status'] === 'rejected'): ?>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                                    <input type="hidden" name="status" value="pending">
                                                    <input type="hidden" name="update_status" value="true">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-clock me-2 text-warning"></i>Mark as Pending
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $feedback['id']; ?>">
                                                <i class="fas fa-edit me-2 text-primary"></i>Edit
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                                <input type="hidden" name="delete_feedback" value="true">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No feedbacks yet</h5>
                        <p class="text-muted">Feedbacks submitted from the home page will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modals -->
    <?php foreach ($feedbacks as $feedback): ?>
        <div class="modal fade" id="editModal<?php echo $feedback['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Feedback</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                            <input type="hidden" name="edit_feedback" value="true">
                            
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($feedback['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($feedback['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($feedback['subject']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <select name="rating" class="form-select">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $feedback['rating'] == $i ? 'selected' : ''; ?>><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="4" required><?php echo htmlspecialchars($feedback['message']); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
