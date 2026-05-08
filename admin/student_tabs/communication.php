<?php
// Parent Communication Tab
$student_id = $_GET['id'];
$add_mode = isset($_GET['add']) ? true : false;
$communication_type = isset($_GET['type']) ? $_GET['type'] : 'portal_message';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $add_mode) {
    if (isset($_POST['send_communication'])) {
        $receiver_id = $_POST['receiver_id'];
        $subject = clean_input($_POST['subject']);
        $message = clean_input($_POST['message']);
        $comm_type = $_POST['communication_type'];
        $priority = $_POST['priority'];
        $follow_up_required = isset($_POST['follow_up_required']) ? 1 : 0;
        $follow_up_date = $_POST['follow_up_date'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO communication_logs 
                      (student_id, sender_id, receiver_id, subject, message, communication_type, priority, follow_up_required, follow_up_date) 
                      VALUES (:student_id, :sender_id, :receiver_id, :subject, :message, :communication_type, :priority, :follow_up_required, :follow_up_date)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':sender_id', $_SESSION['user_id']);
            $stmt->bindParam(':receiver_id', $receiver_id);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':communication_type', $comm_type);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindParam(':follow_up_required', $follow_up_required);
            $stmt->bindParam(':follow_up_date', $follow_up_date);
            
            if ($stmt->execute()) {
                flash_message('success', 'Communication sent successfully!');
                redirect('student_management.php?id=' . $student_id . '&tab=communication');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
    
    if (isset($_POST['schedule_meeting'])) {
        $parent_id = $_POST['parent_id'];
        $meeting_date = $_POST['meeting_date'];
        $meeting_time = $_POST['meeting_time'];
        $duration = $_POST['duration'];
        $meeting_type = $_POST['meeting_type'];
        $agenda = clean_input($_POST['agenda']);
        $location = clean_input($_POST['location']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO parent_meetings 
                      (student_id, teacher_id, parent_id, meeting_date, meeting_time, duration_minutes, meeting_type, agenda, location) 
                      VALUES (:student_id, :teacher_id, :parent_id, :meeting_date, :meeting_time, :duration_minutes, :meeting_type, :agenda, :location)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':teacher_id', $_SESSION['user_id']);
            $stmt->bindParam(':parent_id', $parent_id);
            $stmt->bindParam(':meeting_date', $meeting_date);
            $stmt->bindParam(':meeting_time', $meeting_time);
            $stmt->bindParam(':duration_minutes', $duration);
            $stmt->bindParam(':meeting_type', $meeting_type);
            $stmt->bindParam(':agenda', $agenda);
            $stmt->bindParam(':location', $location);
            
            if ($stmt->execute()) {
                flash_message('success', 'Meeting scheduled successfully!');
                redirect('student_management.php?id=' . $student_id . '&tab=communication');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
}

// Get communication data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get parent information
    $parent_query = "SELECT u.* FROM users u JOIN students s ON u.id = s.parent_id WHERE s.id = :student_id";
    $stmt = $db->prepare($parent_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get communication logs
    $comm_query = "SELECT cl.*, 
                   sender.full_name as sender_name, 
                   receiver.full_name as receiver_name 
                   FROM communication_logs cl
                   LEFT JOIN users sender ON cl.sender_id = sender.id
                   LEFT JOIN users receiver ON cl.receiver_id = receiver.id
                   WHERE cl.student_id = :student_id
                   ORDER BY cl.created_at DESC";
    
    $stmt = $db->prepare($comm_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $communications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get scheduled meetings
    $meetings_query = "SELECT pm.*, 
                      teacher.full_name as teacher_name,
                      parent.full_name as parent_name
                      FROM parent_meetings pm
                      LEFT JOIN users teacher ON pm.teacher_id = teacher.id
                      LEFT JOIN users parent ON pm.parent_id = parent.id
                      WHERE pm.student_id = :student_id
                      ORDER BY pm.meeting_date DESC, pm.meeting_time DESC";
    
    $stmt = $db->prepare($meetings_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get communication statistics
    $stats_query = "SELECT 
        COUNT(*) as total_communications,
        COUNT(CASE WHEN communication_type = 'email' THEN 1 END) as email_count,
        COUNT(CASE WHEN communication_type = 'phone' THEN 1 END) as phone_count,
        COUNT(CASE WHEN communication_type = 'meeting' THEN 1 END) as meeting_count,
        COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_count,
        COUNT(CASE WHEN status = 'replied' THEN 1 END) as replied_count
        FROM communication_logs 
        WHERE student_id = :student_id
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $stmt = $db->prepare($stats_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $comm_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading communication data: " . $exception->getMessage();
}
?>

<?php if ($add_mode): ?>
    <?php if ($communication_type == 'meeting'): ?>
        <!-- Schedule Meeting Form -->
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-calendar-plus me-2"></i>Schedule Parent Meeting
            </h5>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Parent</label>
                            <select class="form-select" name="parent_id" required>
                                <?php if ($parent): ?>
                                    <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['full_name']); ?></option>
                                <?php else: ?>
                                    <option value="">No parent assigned</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Meeting Type</label>
                            <select class="form-select" name="meeting_type" required>
                                <option value="regular">Regular</option>
                                <option value="concern">Concern</option>
                                <option value="progress">Progress</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Meeting Date</label>
                            <input type="date" class="form-control" name="meeting_date" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Meeting Time</label>
                            <input type="time" class="form-control" name="meeting_time" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" name="duration" value="30" min="15" max="120" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" placeholder="e.g., Teacher's Office, Classroom">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Agenda</label>
                            <textarea class="form-control" name="agenda" rows="2" placeholder="Meeting agenda items..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="schedule_meeting" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Schedule Meeting
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=communication'">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Send Communication Form -->
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-paper-plane me-2"></i>Send Communication
            </h5>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Recipient</label>
                            <select class="form-select" name="receiver_id" required>
                                <?php if ($parent): ?>
                                    <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['full_name']); ?> (Parent)</option>
                                <?php else: ?>
                                    <option value="">No parent assigned</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="communication_type" required>
                                <option value="portal_message">Portal Message</option>
                                <option value="email">Email</option>
                                <option value="phone">Phone Call</option>
                                <option value="note">Note</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority" required>
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" name="subject" required placeholder="Communication subject...">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" name="message" rows="6" required placeholder="Type your message here..."></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="follow_up_required" id="follow_up_required" onchange="toggleFollowUpDate()">
                                <label class="form-check-label" for="follow_up_required">
                                    Follow-up Required
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3" id="followUpDateDiv" style="display: none;">
                            <label class="form-label">Follow-up Date</label>
                            <input type="date" class="form-control" name="follow_up_date">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="send_communication" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Message
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=communication'">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
<?php else: ?>
    <!-- Communication Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-primary">
                    <?php echo $comm_stats['total_communications'] ?? 0; ?>
                </div>
                <div class="text-muted">Total Communications</div>
                <small class="text-muted">Last 30 days</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-success">
                    <?php echo $comm_stats['replied_count'] ?? 0; ?>
                </div>
                <div class="text-muted">Replies Received</div>
                <small class="text-muted">Parent responses</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-warning">
                    <?php echo $comm_stats['meeting_count'] ?? 0; ?>
                </div>
                <div class="text-muted">Meetings</div>
                <small class="text-muted">Scheduled/Completed</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-danger">
                    <?php echo $comm_stats['urgent_count'] ?? 0; ?>
                </div>
                <div class="text-muted">Urgent Messages</div>
                <small class="text-muted">High priority</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Communication Logs -->
        <div class="col-md-8">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5>
                        <i class="fas fa-comments me-2"></i>Communication History
                    </h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=communication&add=1&type=portal_message'">
                            <i class="fas fa-envelope me-1"></i>Message
                        </button>
                        <button class="btn btn-success btn-sm" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=communication&add=1&type=meeting'">
                            <i class="fas fa-calendar me-1"></i>Meeting
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($communications)): ?>
                    <?php foreach ($communications as $comm): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($comm['subject']); ?></h6>
                                    <div class="d-flex align-items-center text-muted small">
                                        <span class="me-3">
                                            <i class="fas fa-user me-1"></i>
                                            From: <?php echo htmlspecialchars($comm['sender_name']); ?>
                                        </span>
                                        <span class="me-3">
                                            <i class="fas fa-user me-1"></i>
                                            To: <?php echo htmlspecialchars($comm['receiver_name']); ?>
                                        </span>
                                        <span class="me-3">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($comm['communication_type']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo ($comm['priority'] == 'urgent') ? 'danger' : (($comm['priority'] == 'high') ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($comm['priority']); ?>
                                        </span>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M d, Y h:i A', strtotime($comm['created_at'])); ?>
                                </small>
                            </div>
                            
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($comm['message'])); ?></p>
                            
                            <?php if ($comm['parent_response']): ?>
                                <div class="alert alert-info mb-2">
                                    <strong>Parent Response:</strong> <?php echo nl2br(htmlspecialchars($comm['parent_response'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small text-muted">
                                    Status: 
                                    <span class="badge bg-<?php echo ($comm['status'] == 'replied') ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($comm['status']); ?>
                                    </span>
                                    <?php if ($comm['follow_up_required']): ?>
                                        <span class="badge bg-warning ms-2">Follow-up Required</span>
                                    <?php endif; ?>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="replyToCommunication(<?php echo $comm['id']; ?>)">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="viewCommunication(<?php echo $comm['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No communications recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scheduled Meetings -->
        <div class="col-md-4">
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-calendar-alt me-2"></i>Scheduled Meetings
                </h5>
                
                <?php if (!empty($meetings)): ?>
                    <?php foreach ($meetings as $meeting): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($meeting['agenda'] ?: 'Parent Meeting'); ?></h6>
                                    <span class="badge bg-<?php echo ($meeting['meeting_type'] == 'emergency') ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($meeting['meeting_type']); ?>
                                    </span>
                                    <span class="badge bg-<?php echo ($meeting['status'] == 'completed') ? 'success' : (($meeting['status'] == 'scheduled') ? 'info' : 'secondary'); ?>">
                                        <?php echo ucfirst($meeting['status']); ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M d', strtotime($meeting['meeting_date'])); ?>
                                </small>
                            </div>
                            
                            <div class="small text-muted mb-2">
                                <div><i class="fas fa-clock me-1"></i><?php echo date('h:i A', strtotime($meeting['meeting_time'])); ?> (<?php echo $meeting['duration_minutes']; ?> min)</div>
                                <?php if ($meeting['location']): ?>
                                    <div><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($meeting['location']); ?></div>
                                <?php endif; ?>
                                <div><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($meeting['teacher_name']); ?> & <?php echo htmlspecialchars($meeting['parent_name']); ?></div>
                            </div>
                            
                            <div class="btn-group btn-group-sm w-100">
                                <button class="btn btn-outline-primary" onclick="editMeeting(<?php echo $meeting['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-success" onclick="completeMeeting(<?php echo $meeting['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="cancelMeeting(<?php echo $meeting['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No meetings scheduled.</p>
                <?php endif; ?>
            </div>

            <!-- Quick Contact -->
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-phone me-2"></i>Quick Contact
                </h5>
                
                <?php if ($parent): ?>
                    <div class="mb-3">
                        <strong><?php echo htmlspecialchars($parent['full_name']); ?></strong>
                        <div class="text-muted small">
                            <div><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($parent['email']); ?></div>
                            <?php if ($parent['phone']): ?>
                                <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($parent['phone']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=communication&add=1&type=email'">
                            <i class="fas fa-envelope me-1"></i>Send Email
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=communication&add=1&type=phone'">
                            <i class="fas fa-phone me-1"></i>Log Call
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=communication&add=1&type=meeting'">
                            <i class="fas fa-calendar me-1"></i>Schedule Meeting
                        </button>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No parent contact information available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleFollowUpDate() {
    const checkbox = document.getElementById('follow_up_required');
    const dateDiv = document.getElementById('followUpDateDiv');
    
    if (checkbox.checked) {
        dateDiv.style.display = 'block';
    } else {
        dateDiv.style.display = 'none';
    }
}

function replyToCommunication(id) {
    // Implement reply functionality
    console.log('Reply to communication:', id);
}

function viewCommunication(id) {
    // Implement view functionality
    console.log('View communication:', id);
}

function editMeeting(id) {
    // Implement edit meeting functionality
    console.log('Edit meeting:', id);
}

function completeMeeting(id) {
    if (confirm('Mark this meeting as completed?')) {
        // Implement complete meeting functionality
        console.log('Complete meeting:', id);
    }
}

function cancelMeeting(id) {
    if (confirm('Cancel this meeting?')) {
        // Implement cancel meeting functionality
        console.log('Cancel meeting:', id);
    }
}
</script>
