<?php
// Behavior and Conduct Tab
$student_id = $_GET['id'];
$add_mode = isset($_GET['add']) ? true : false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $add_mode) {
    if (isset($_POST['add_behavior'])) {
        $behavior_category_id = $_POST['behavior_category_id'];
        $description = clean_input($_POST['description']);
        $incident_date = $_POST['incident_date'];
        $incident_time = $_POST['incident_time'];
        $location = clean_input($_POST['location']);
        $action_taken = clean_input($_POST['action_taken']);
        $parent_notified = isset($_POST['parent_notified']) ? 1 : 0;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Get points for this behavior category
            $points_query = "SELECT point_value FROM behavior_categories WHERE id = :id";
            $stmt = $db->prepare($points_query);
            $stmt->bindParam(':id', $behavior_category_id);
            $stmt->execute();
            $points = $stmt->fetch(PDO::FETCH_ASSOC)['point_value'] ?? 0;
            
            $query = "INSERT INTO behavior_records 
                      (student_id, behavior_category_id, description, incident_date, incident_time, location, action_taken, parent_notified, points_earned, reported_by) 
                      VALUES (:student_id, :behavior_category_id, :description, :incident_date, :incident_time, :location, :action_taken, :parent_notified, :points_earned, :reported_by)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':behavior_category_id', $behavior_category_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':incident_date', $incident_date);
            $stmt->bindParam(':incident_time', $incident_time);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':action_taken', $action_taken);
            $stmt->bindParam(':parent_notified', $parent_notified);
            $stmt->bindParam(':points_earned', $points);
            $stmt->bindParam(':reported_by', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                flash_message('success', 'Behavior record added successfully!');
                redirect('student_management.php?id=' . $student_id . '&tab=behavior');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
}

// Get behavior data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get behavior categories
    $categories_query = "SELECT * FROM behavior_categories WHERE is_active = 1 ORDER BY type, name";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get behavior records
    $records_query = "SELECT br.*, bc.name as category_name, bc.type as category_type, u.full_name as reported_by_name
                      FROM behavior_records br
                      JOIN behavior_categories bc ON br.behavior_category_id = bc.id
                      LEFT JOIN users u ON br.reported_by = u.id
                      WHERE br.student_id = :student_id
                      ORDER BY br.incident_date DESC, br.incident_time DESC";
    
    $stmt = $db->prepare($records_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $behavior_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get behavior statistics
    $stats_query = "SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN bc.type = 'positive' THEN 1 ELSE 0 END) as positive_count,
        SUM(CASE WHEN bc.type = 'negative' THEN 1 ELSE 0 END) as negative_count,
        SUM(CASE WHEN bc.type = 'neutral' THEN 1 ELSE 0 END) as neutral_count,
        SUM(br.points_earned) as total_points
        FROM behavior_records br
        JOIN behavior_categories bc ON br.behavior_category_id = bc.id
        WHERE br.student_id = :student_id
        AND br.incident_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $stmt = $db->prepare($stats_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $behavior_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get monthly behavior trends
    $trends_query = "SELECT 
        DATE_FORMAT(br.incident_date, '%Y-%m') as month,
        SUM(CASE WHEN bc.type = 'positive' THEN 1 ELSE 0 END) as positive,
        SUM(CASE WHEN bc.type = 'negative' THEN 1 ELSE 0 END) as negative,
        SUM(br.points_earned) as points
        FROM behavior_records br
        JOIN behavior_categories bc ON br.behavior_category_id = bc.id
        WHERE br.student_id = :student_id
        AND br.incident_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(br.incident_date, '%Y-%m')
        ORDER BY month DESC";
    
    $stmt = $db->prepare($trends_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $behavior_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading behavior data: " . $exception->getMessage();
}
?>

<?php if ($add_mode): ?>
    <!-- Add Behavior Record Form -->
    <div class="content-card">
        <h5 class="mb-4">
            <i class="fas fa-plus me-2"></i>Add Behavior Record
        </h5>
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Behavior Category</label>
                        <select class="form-select" name="behavior_category_id" required onchange="updateBehaviorInfo()">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        data-type="<?php echo $category['type']; ?>" 
                                        data-points="<?php echo $category['point_value']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?> 
                                    (<?php echo ucfirst($category['type']); ?>, <?php echo $category['point_value']; ?> points)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="e.g., Classroom, Playground">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Incident Date</label>
                        <input type="date" class="form-control" name="incident_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Incident Time</label>
                        <input type="time" class="form-control" name="incident_time" value="<?php echo date('H:i'); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="parent_notified" id="parent_notified">
                            <label class="form-check-label" for="parent_notified">
                                Parent Notified
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3" required placeholder="Describe the incident in detail..."></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Action Taken</label>
                <textarea class="form-control" name="action_taken" rows="2" placeholder="What actions were taken in response..."></textarea>
            </div>
            
            <div class="alert alert-info" id="behaviorInfo" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="behaviorInfoText"></span>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="add_behavior" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Record
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=behavior'">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Behavior Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-success">
                    <?php echo $behavior_stats['positive_count'] ?? 0; ?>
                </div>
                <div class="text-muted">Positive Behaviors</div>
                <small class="text-muted">Last 30 days</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-danger">
                    <?php echo $behavior_stats['negative_count'] ?? 0; ?>
                </div>
                <div class="text-muted">Negative Behaviors</div>
                <small class="text-muted">Last 30 days</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-info">
                    <?php echo $behavior_stats['total_points'] ?? 0; ?>
                </div>
                <div class="text-muted">Total Points</div>
                <small class="text-muted">Current balance</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-primary">
                    <?php echo $behavior_stats['total_records'] ?? 0; ?>
                </div>
                <div class="text-muted">Total Records</div>
                <small class="text-muted">Last 30 days</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Behavior Records -->
        <div class="col-md-8">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5>
                        <i class="fas fa-list me-2"></i>Behavior Records
                    </h5>
                    <div>
                        <select class="form-select form-select-sm d-inline-block w-auto" id="typeFilter" onchange="filterRecords()">
                            <option value="">All Types</option>
                            <option value="positive">Positive</option>
                            <option value="negative">Negative</option>
                            <option value="neutral">Neutral</option>
                        </select>
                        <button class="btn btn-primary btn-sm ms-2" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=behavior&add=1'">
                            <i class="fas fa-plus me-1"></i>Add Record
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($behavior_records)): ?>
                    <?php foreach ($behavior_records as $record): ?>
                        <div class="behavior-item <?php echo 'behavior-' . $record['category_type']; ?>" 
                             data-type="<?php echo $record['category_type']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-3"><?php echo htmlspecialchars($record['category_name']); ?></h6>
                                        <span class="badge bg-<?php echo ($record['category_type'] == 'positive') ? 'success' : (($record['category_type'] == 'negative') ? 'danger' : 'secondary'); ?>">
                                            <?php echo ucfirst($record['category_type']); ?>
                                        </span>
                                        <?php if ($record['points_earned'] != 0): ?>
                                            <span class="badge bg-info ms-2">
                                                <?php echo ($record['points_earned'] > 0) ? '+' : ''; ?><?php echo $record['points_earned']; ?> points
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($record['description']); ?></p>
                                    <?php if ($record['action_taken']): ?>
                                        <p class="mb-2"><strong>Action Taken:</strong> <?php echo htmlspecialchars($record['action_taken']); ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center text-muted small">
                                        <span class="me-3">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($record['incident_date'])); ?>
                                        </span>
                                        <span class="me-3">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('h:i A', strtotime($record['incident_time'])); ?>
                                        </span>
                                        <?php if ($record['location']): ?>
                                            <span class="me-3">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($record['location']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($record['parent_notified']): ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>Parent Notified
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block mb-2">
                                        By: <?php echo htmlspecialchars($record['reported_by_name']); ?>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editRecord(<?php echo $record['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteRecord(<?php echo $record['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No behavior records found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Behavior Trends -->
        <div class="col-md-4">
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-chart-line me-2"></i>Behavior Trends
                </h5>
                <?php if (!empty($behavior_trends)): ?>
                    <?php foreach ($behavior_trends as $trend): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><?php echo date('F Y', strtotime($trend['month'] . '-01')); ?></h6>
                                <span class="badge bg-info"><?php echo $trend['points']; ?> points</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-success">
                                    <i class="fas fa-arrow-up me-1"></i><?php echo $trend['positive']; ?> positive
                                </span>
                                <span class="text-danger">
                                    <i class="fas fa-arrow-down me-1"></i><?php echo $trend['negative']; ?> negative
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No trend data available.</p>
                <?php endif; ?>
            </div>

            <!-- Behavior Categories Summary -->
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-tags me-2"></i>Categories Summary
                </h5>
                <?php 
                $category_summary = [];
                foreach ($categories as $cat) {
                    $count = 0;
                    foreach ($behavior_records as $record) {
                        if ($record['behavior_category_id'] == $cat['id']) {
                            $count++;
                        }
                    }
                    if ($count > 0) {
                        $category_summary[] = ['category' => $cat, 'count' => $count];
                    }
                }
                ?>
                <?php if (!empty($category_summary)): ?>
                    <?php foreach ($category_summary as $summary): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?php echo htmlspecialchars($summary['category']['name']); ?></span>
                            <span class="badge bg-secondary"><?php echo $summary['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No category data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function updateBehaviorInfo() {
    const select = document.querySelector('select[name="behavior_category_id"]');
    const selectedOption = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('behaviorInfo');
    const infoText = document.getElementById('behaviorInfoText');
    
    if (selectedOption.value) {
        const type = selectedOption.dataset.type;
        const points = selectedOption.dataset.points;
        
        let message = `This is a ${type} behavior record worth ${points} point(s).`;
        if (type === 'positive') {
            message += " This will add to the student's positive behavior points.";
        } else if (type === 'negative') {
            message += " This will deduct from the student's behavior points.";
        }
        
        infoText.textContent = message;
        infoDiv.style.display = 'block';
        
        // Update alert color based on type
        infoDiv.className = `alert alert-${type === 'positive' ? 'success' : (type === 'negative' ? 'danger' : 'info')}`;
    } else {
        infoDiv.style.display = 'none';
    }
}

function filterRecords() {
    const typeFilter = document.getElementById('typeFilter').value;
    const records = document.querySelectorAll('.behavior-item');
    
    records.forEach(record => {
        if (!typeFilter || record.dataset.type === typeFilter) {
            record.style.display = 'block';
        } else {
            record.style.display = 'none';
        }
    });
}

function editRecord(id) {
    // Implement edit functionality
    console.log('Edit record:', id);
}

function deleteRecord(id) {
    if (confirm('Are you sure you want to delete this behavior record?')) {
        // Implement delete functionality
        console.log('Delete record:', id);
    }
}
</script>
