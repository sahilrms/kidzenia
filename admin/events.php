<?php
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_event'])) {
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $event_date = $_POST['event_date'];
        $event_time = $_POST['event_time'] ?? null;
        $location = clean_input($_POST['location']);
        $type = $_POST['type'];
        $target_audience = $_POST['target_audience'];
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO events (title, description, event_date, event_time, location, type, target_audience, class_id, organizer_id) 
                      VALUES (:title, :description, :event_date, :event_time, :location, :type, :target_audience, :class_id, :organizer_id)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':event_date', $event_date);
            $stmt->bindParam(':event_time', $event_time);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':target_audience', $target_audience);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->bindParam(':organizer_id', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                flash_message('success', 'Event created successfully!');
            } else {
                flash_message('error', 'Failed to create event.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('events.php');
    }
    
    if (isset($_POST['edit_event'])) {
        $event_id = $_POST['event_id'];
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $event_date = $_POST['event_date'];
        $event_time = $_POST['event_time'] ?? null;
        $location = clean_input($_POST['location']);
        $type = $_POST['type'];
        $target_audience = $_POST['target_audience'];
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE events SET title = :title, description = :description, event_date = :event_date, 
                      event_time = :event_time, location = :location, type = :type, target_audience = :target_audience, 
                      class_id = :class_id WHERE id = :event_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':event_date', $event_date);
            $stmt->bindParam(':event_time', $event_time);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':target_audience', $target_audience);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->bindParam(':event_id', $event_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Event updated successfully!');
            } else {
                flash_message('error', 'Failed to update event.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('events.php');
    }
    
    if (isset($_POST['delete_event'])) {
        $event_id = $_POST['event_id'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE events SET is_active = 0 WHERE id = :event_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':event_id', $event_id);
            
            if ($stmt->execute()) {
                flash_message('success', 'Event deleted successfully!');
            } else {
                flash_message('error', 'Failed to delete event.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
        
        redirect('events.php');
    }
}

// Get events data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all events with class info
    $query = "SELECT e.*, c.name as class_name, u.full_name as organizer_name 
              FROM events e 
              LEFT JOIN classes c ON e.class_id = c.id 
              LEFT JOIN users u ON e.organizer_id = u.id 
              WHERE e.is_active = 1 
              ORDER BY e.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get classes for dropdown
    $classes_query = "SELECT id, name FROM classes WHERE status = 'active' ORDER BY name";
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->execute();
    $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $events = [];
    $classes = [];
    $error_message = "Error loading data: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Management - Kidzenia Kindergarten</title>
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
        
        .event-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .event-date {
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .event-type {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .event-type.school { background: rgba(102, 126, 234, 0.1); color: var(--primary-color); }
        .event-type.class { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .event-type.holiday { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .event-type.meeting { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .event-type.other { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        
        .event-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .event-status.upcoming { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .event-status.past { background: rgba(108, 117, 125, 0.1); color: #6c757d; }
        .event-status.today { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        
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
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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
                    <a class="nav-link active" href="events.php">
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
            <h4>Events Management</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                <i class="fas fa-plus me-2"></i>Add New Event
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
                <div class="col-md-3">
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="school">School</option>
                        <option value="class">Class</option>
                        <option value="holiday">Holiday</option>
                        <option value="meeting">Meeting</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Events</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="today">Today</option>
                        <option value="past">Past</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search events...">
                </div>
            </div>
        </div>

        <!-- Events List -->
        <div id="eventsList">
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <?php 
                    $event_date = new DateTime($event['event_date']);
                    $today = new DateTime();
                    $today->setTime(0, 0, 0);
                    $event_date->setTime(0, 0, 0);
                    
                    if ($event_date > $today) {
                        $status = 'upcoming';
                        $status_text = 'Upcoming';
                    } elseif ($event_date == $today) {
                        $status = 'today';
                        $status_text = 'Today';
                    } else {
                        $status = 'past';
                        $status_text = 'Past';
                    }
                    ?>
                    <div class="event-card" data-type="<?php echo $event['type']; ?>" data-status="<?php echo $status; ?>" data-title="<?php echo strtolower($event['title']); ?>">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="event-date">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                    <?php if ($event['event_time']): ?>
                                        <i class="fas fa-clock ms-3"></i>
                                        <?php echo date('h:i A', strtotime($event['event_time'])); ?>
                                    <?php endif; ?>
                                </div>
                                <span class="event-type <?php echo $event['type']; ?>"><?php echo $event['type']; ?></span>
                                <span class="event-status <?php echo $status; ?> ms-2"><?php echo $status_text; ?></span>
                            </div>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-info" onclick="viewEvent(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editEvent(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteEvent(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <h5 class="mb-2"><?php echo htmlspecialchars($event['title']); ?></h5>
                        <p class="text-muted mb-2">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 200)) . '...'; ?>
                        </p>
                        
                        <div class="row">
                            <?php if ($event['location']): ?>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($event['location']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            <?php if ($event['class_name']): ?>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="fas fa-school me-1"></i>
                                        <?php echo htmlspecialchars($event['class_name']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($event['organizer_name'] ?: 'Admin'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No events found</h5>
                    <p class="text-muted">Start by creating your first event.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Title</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Date</label>
                                    <input type="date" class="form-control" name="event_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Time</label>
                                    <input type="time" class="form-control" name="event_time">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Event Type</label>
                                    <select class="form-select" name="type" required>
                                        <option value="school">School</option>
                                        <option value="class">Class</option>
                                        <option value="holiday">Holiday</option>
                                        <option value="meeting">Meeting</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Target Audience</label>
                                    <select class="form-select" name="target_audience" required>
                                        <option value="all">All</option>
                                        <option value="parents">Parents</option>
                                        <option value="teachers">Teachers</option>
                                        <option value="students">Students</option>
                                        <option value="specific_class">Specific Class</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
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
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_event" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Event Modal -->
    <div class="modal fade" id="viewEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewEventTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="event-date">
                                <i class="fas fa-calendar me-2"></i>
                                <span id="viewEventDate"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <span id="viewEventLocation"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <p id="viewEventDescription"></p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>Type:</strong> <span id="viewEventType"></span>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>Organizer:</strong> <span id="viewEventOrganizer"></span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="event_id" id="editEventId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Title</label>
                                    <input type="text" class="form-control" name="title" id="editEventTitle" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Date</label>
                                    <input type="date" class="form-control" name="event_date" id="editEventDate" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Time</label>
                                    <input type="time" class="form-control" name="event_time" id="editEventTime">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" id="editEventLocation">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Event Type</label>
                                    <select class="form-select" name="type" id="editEventType" required>
                                        <option value="school">School</option>
                                        <option value="class">Class</option>
                                        <option value="holiday">Holiday</option>
                                        <option value="meeting">Meeting</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Target Audience</label>
                                    <select class="form-select" name="target_audience" required>
                                        <option value="all">All</option>
                                        <option value="parents">Parents</option>
                                        <option value="teachers">Teachers</option>
                                        <option value="students">Students</option>
                                        <option value="specific_class">Specific Class</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
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
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editEventDescription" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_event" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.getElementById('typeFilter').addEventListener('change', filterEvents);
        document.getElementById('statusFilter').addEventListener('change', filterEvents);
        document.getElementById('searchInput').addEventListener('input', filterEvents);
        
        function filterEvents() {
            const type = document.getElementById('typeFilter').value;
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.event-card');
            
            items.forEach(item => {
                const itemType = item.dataset.type;
                const itemStatus = item.dataset.status;
                const itemTitle = item.dataset.title;
                
                const matchesType = !type || itemType === type;
                const matchesStatus = !status || itemStatus === status;
                const matchesSearch = !search || itemTitle.includes(search);
                
                if (matchesType && matchesStatus && matchesSearch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function viewEvent(id) {
            // Find the event data and show in modal
            const items = document.querySelectorAll('.event-card');
            items.forEach(item => {
                if (item.querySelector('.action-buttons button[onclick*="' + id + '"]')) {
                    const title = item.querySelector('h5').textContent;
                    const date = item.querySelector('.event-date').textContent.trim();
                    const location = item.querySelector('.fa-map-marker-alt')?.parentElement?.textContent?.trim() || '';
                    const description = item.querySelector('.text-muted.mb-2').textContent.trim();
                    const type = item.querySelector('.event-type').textContent;
                    const organizer = item.querySelector('.fa-user')?.parentElement?.textContent?.trim() || '';
                    
                    document.getElementById('viewEventTitle').textContent = title;
                    document.getElementById('viewEventDate').textContent = date;
                    document.getElementById('viewEventLocation').textContent = location.replace('📍', '').trim();
                    document.getElementById('viewEventDescription').textContent = description;
                    document.getElementById('viewEventType').textContent = type;
                    document.getElementById('viewEventOrganizer').textContent = organizer.replace('👤', '').trim();
                    
                    new bootstrap.Modal(document.getElementById('viewEventModal')).show();
                }
            });
        }
        
        function editEvent(id) {
            // Find the event data and populate edit modal
            const items = document.querySelectorAll('.event-card');
            items.forEach(item => {
                if (item.querySelector('.action-buttons button[onclick*="' + id + '"]')) {
                    const title = item.querySelector('h5').textContent;
                    const dateStr = item.querySelector('.event-date').textContent.trim();
                    const location = item.querySelector('.fa-map-marker-alt')?.parentElement?.textContent?.trim() || '';
                    const description = item.querySelector('.text-muted.mb-2').textContent.trim();
                    const type = item.querySelector('.event-type').textContent;
                    
                    // Parse date and time from the date string
                    const dateMatch = dateStr.match(/(\w{3} \d{2}, \d{4})/);
                    const timeMatch = dateStr.match(/(\d{1,2}:\d{2}\s[AP]M)/);
                    
                    document.getElementById('editEventId').value = id;
                    document.getElementById('editEventTitle').value = title;
                    document.getElementById('editEventDate').value = dateMatch ? formatDateForInput(dateMatch[1]) : '';
                    document.getElementById('editEventTime').value = timeMatch ? timeMatch[1] : '';
                    document.getElementById('editEventLocation').value = location.replace('📍', '').trim();
                    document.getElementById('editEventDescription').value = description.replace('...', '').trim();
                    document.getElementById('editEventType').value = type.toLowerCase();
                    
                    new bootstrap.Modal(document.getElementById('editEventModal')).show();
                }
            });
        }
        
        function formatDateForInput(dateStr) {
            const date = new Date(dateStr);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        function deleteEvent(id) {
            if (confirm('Are you sure you want to delete this event?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="event_id" value="' + id + '"><input type="hidden" name="delete_event" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
