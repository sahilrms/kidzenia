<?php
// Overview Tab - Student Dashboard
$student_id = $_GET['id'];

// Initialize variables to prevent undefined variable warnings
$attendance_stats = ['total_days' => 0, 'present_days' => 0, 'absent_days' => 0, 'late_days' => 0];
$academic_stats = ['total_assessments' => 0, 'average_score' => 0, 'last_assessment' => null];
$behavior_stats = ['total_records' => 0, 'positive_records' => 0, 'negative_records' => 0, 'total_points' => 0];
$fee_stats = ['total_fees' => 0, 'total_amount' => 0, 'paid_amount' => 0, 'pending_amount' => 0];
$recent_activities = [];

// Get student statistics
$stats = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Attendance stats
    $attendance_query = "SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
        FROM attendance 
        WHERE student_id = :student_id 
        AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $stmt = $db->prepare($attendance_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $attendance_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Academic progress stats
    $progress_query = "SELECT 
        COUNT(*) as total_assessments,
        AVG(score) as average_score,
        MAX(assessment_date) as last_assessment
        FROM student_progress sp
        WHERE sp.student_id = :student_id
        AND sp.academic_year = '2024-2025'";
    
    $stmt = $db->prepare($progress_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $academic_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Behavior stats
    $behavior_query = "SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN bc.type = 'positive' THEN 1 ELSE 0 END) as positive_records,
        SUM(CASE WHEN bc.type = 'negative' THEN 1 ELSE 0 END) as negative_records,
        SUM(points_earned) as total_points
        FROM behavior_records br
        JOIN behavior_categories bc ON br.behavior_category_id = bc.id
        WHERE br.student_id = :student_id
        AND br.incident_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $stmt = $db->prepare($behavior_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $behavior_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fee status
    $fee_query = "SELECT 
        COUNT(*) as total_fees,
        SUM(amount) as total_amount,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN status != 'paid' THEN amount ELSE 0 END) as pending_amount
        FROM student_fee_assignments
        WHERE student_id = :student_id
        AND academic_year = '2024-2025'";
    
    $stmt = $db->prepare($fee_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $fee_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Recent activities
    $activities_query = "SELECT 
        'Academic' as type,
        sp.assessment_date as date,
        sub.name as description,
        CONCAT('Score: ', sp.score) as details
        FROM student_progress sp
        JOIN subjects sub ON sp.subject_id = sub.id
        WHERE sp.student_id = :student_id
        UNION ALL
        SELECT 
        'Behavior' as type,
        br.incident_date as date,
        bc.name as description,
        br.description as details
        FROM behavior_records br
        JOIN behavior_categories bc ON br.behavior_category_id = bc.id
        WHERE br.student_id = :student_id
        UNION ALL
        SELECT 
        'Medical' as type,
        mv.visit_date as date,
        mv.reason as description,
        mv.symptoms as details
        FROM medical_visits mv
        WHERE mv.student_id = :student_id
        ORDER BY date DESC
        LIMIT 5";
    
    $stmt = $db->prepare($activities_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading statistics: " . $exception->getMessage();
}
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number text-primary">
                <?php echo $attendance_stats['total_days'] ? round(($attendance_stats['present_days'] / $attendance_stats['total_days']) * 100) : 0; ?>%
            </div>
            <div class="text-muted">Attendance (30 days)</div>
            <small class="text-muted">
                <?php echo $attendance_stats['present_days'] ?? 0; ?> present / <?php echo $attendance_stats['total_days'] ?? 0; ?> total
            </small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number text-success">
                <?php echo $academic_stats['average_score'] ? round($academic_stats['average_score'], 1) : 0; ?>
            </div>
            <div class="text-muted">Average Score</div>
            <small class="text-muted">
                <?php echo $academic_stats['total_assessments'] ?? 0; ?> assessments
            </small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number text-info">
                <?php echo $behavior_stats['total_points'] ?? 0; ?>
            </div>
            <div class="text-muted">Behavior Points</div>
            <small class="text-muted">
                <?php echo ($behavior_stats['positive_records'] ?? 0); ?> positive / <?php echo ($behavior_stats['negative_records'] ?? 0); ?> negative
            </small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number text-warning">
                <?php echo ($fee_stats['pending_amount'] ?? 0) > 0 ? '$' . number_format($fee_stats['pending_amount'], 2) : '$0'; ?>
            </div>
            <div class="text-muted">Pending Fees</div>
            <small class="text-muted">
                Total: $<?php echo number_format($fee_stats['total_amount'] ?? 0, 2); ?>
            </small>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activities -->
    <div class="col-md-8">
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-clock me-2"></i>Recent Activities
            </h5>
            <?php if (!empty($recent_activities)): ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                        <div class="me-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-2">
                                <?php
                                $icon = 'fas fa-circle';
                                $color = 'text-primary';
                                switch($activity['type']) {
                                    case 'Academic':
                                        $icon = 'fas fa-graduation-cap';
                                        $color = 'text-success';
                                        break;
                                    case 'Behavior':
                                        $icon = 'fas fa-chart-line';
                                        $color = 'text-info';
                                        break;
                                    case 'Medical':
                                        $icon = 'fas fa-heartbeat';
                                        $color = 'text-danger';
                                        break;
                                }
                                ?>
                                <i class="<?php echo $icon; ?> <?php echo $color; ?>"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></h6>
                                    <p class="text-muted mb-1 small"><?php echo htmlspecialchars($activity['details']); ?></p>
                                </div>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($activity['date'])); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No recent activities found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-bolt me-2"></i>Quick Actions
            </h5>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=academic&add=1'">
                    <i class="fas fa-plus me-2"></i>Add Academic Record
                </button>
                <button class="btn btn-outline-info" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=behavior&add=1'">
                    <i class="fas fa-plus me-2"></i>Record Behavior
                </button>
                <button class="btn btn-outline-danger" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=medical&add=1'">
                    <i class="fas fa-plus me-2"></i>Medical Visit
                </button>
                <button class="btn btn-outline-success" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=communication&add=1'">
                    <i class="fas fa-plus me-2"></i>Contact Parent
                </button>
                <button class="btn btn-outline-warning" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=documents&add=1'">
                    <i class="fas fa-plus me-2"></i>Upload Document
                </button>
            </div>
        </div>

        <!-- Parent Information -->
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-user-friends me-2"></i>Parent Information
            </h5>
            <?php if ($student['parent_name']): ?>
                <div class="mb-3">
                    <strong>Name:</strong> <?php echo htmlspecialchars($student['parent_name']); ?>
                </div>
                <div class="mb-3">
                    <strong>Email:</strong> <?php echo htmlspecialchars($student['parent_email']); ?>
                </div>
                <div class="mb-3">
                    <strong>Phone:</strong> <?php echo htmlspecialchars($student['parent_phone']); ?>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-sm btn-primary" onclick="sendEmail()">
                        <i class="fas fa-envelope me-1"></i>Send Email
                    </button>
                    <button class="btn btn-sm btn-success" onclick="scheduleMeeting()">
                        <i class="fas fa-calendar me-1"></i>Schedule Meeting
                    </button>
                </div>
            <?php else: ?>
                <p class="text-muted">No parent information available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function sendEmail() {
    <?php if ($student['parent_email']): ?>
        window.location.href = '?id=<?php echo $student_id; ?>&tab=communication&add=1&type=email';
    <?php else: ?>
        alert('Parent email not available');
    <?php endif; ?>
}

function scheduleMeeting() {
    window.location.href = '?id=<?php echo $student_id; ?>&tab=communication&add=1&type=meeting';
}
</script>
