<?php
// Academic Progress Tab
$student_id = $_GET['id'];
$add_mode = isset($_GET['add']) ? true : false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $add_mode) {
    if (isset($_POST['add_progress'])) {
        $subject_id = $_POST['subject_id'];
        $assessment_criteria_id = $_POST['assessment_criteria_id'];
        $term = $_POST['term'];
        $academic_year = $_POST['academic_year'];
        $score = $_POST['score'];
        $remarks = clean_input($_POST['remarks']);
        $assessment_date = $_POST['assessment_date'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO student_progress 
                      (student_id, subject_id, assessment_criteria_id, term, academic_year, score, remarks, assessment_date, teacher_id) 
                      VALUES (:student_id, :subject_id, :assessment_criteria_id, :term, :academic_year, :score, :remarks, :assessment_date, :teacher_id)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':subject_id', $subject_id);
            $stmt->bindParam(':assessment_criteria_id', $assessment_criteria_id);
            $stmt->bindParam(':term', $term);
            $stmt->bindParam(':academic_year', $academic_year);
            $stmt->bindParam(':score', $score);
            $stmt->bindParam(':remarks', $remarks);
            $stmt->bindParam(':assessment_date', $assessment_date);
            $stmt->bindParam(':teacher_id', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                flash_message('success', 'Academic progress added successfully!');
                redirect('student_management.php?id=' . $student_id . '&tab=academic');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
}

// Get academic data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get subjects
    $subjects_query = "SELECT * FROM subjects WHERE is_active = 1 ORDER BY name";
    $subjects_stmt = $db->prepare($subjects_query);
    $subjects_stmt->execute();
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get assessment criteria
    $criteria_query = "SELECT ac.*, s.name as subject_name FROM assessment_criteria ac 
                       JOIN subjects s ON ac.subject_id = s.id 
                       WHERE ac.is_active = 1 ORDER BY s.name, ac.name";
    $criteria_stmt = $db->prepare($criteria_query);
    $criteria_stmt->execute();
    $criteria = $criteria_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student progress
    $progress_query = "SELECT sp.*, sub.name as subject_name, ac.name as criteria_name, u.full_name as teacher_name
                       FROM student_progress sp
                       JOIN subjects sub ON sp.subject_id = sub.id
                       JOIN assessment_criteria ac ON sp.assessment_criteria_id = ac.id
                       LEFT JOIN users u ON sp.teacher_id = u.id
                       WHERE sp.student_id = :student_id
                       ORDER BY sp.academic_year DESC, sp.term DESC, sp.assessment_date DESC";
    
    $stmt = $db->prepare($progress_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $progress_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get portfolio items
    $portfolio_query = "SELECT sp.*, u.full_name as teacher_name 
                        FROM student_portfolio sp 
                        LEFT JOIN users u ON sp.teacher_id = u.id 
                        WHERE sp.student_id = :student_id 
                        ORDER BY sp.portfolio_date DESC 
                        LIMIT 10";
    
    $stmt = $db->prepare($portfolio_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $portfolio_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get academic summary
    $summary_query = "SELECT sub.name as subject_name, AVG(sp.score) as avg_score, COUNT(sp.id) as assessment_count
                      FROM student_progress sp
                      JOIN subjects sub ON sp.subject_id = sub.id
                      WHERE sp.student_id = :student_id
                      AND sp.academic_year = '2024-2025'
                      GROUP BY sub.id, sub.name";
    
    $stmt = $db->prepare($summary_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $academic_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading academic data: " . $exception->getMessage();
}
?>

<?php if ($add_mode): ?>
    <!-- Add Progress Form -->
    <div class="content-card">
        <h5 class="mb-4">
            <i class="fas fa-plus me-2"></i>Add Academic Progress
        </h5>
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select class="form-select" name="subject_id" id="subject_id" required onchange="loadCriteria()">
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Assessment Criteria</label>
                        <select class="form-select" name="assessment_criteria_id" id="assessment_criteria_id" required>
                            <option value="">Select Subject First</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Term</label>
                        <select class="form-select" name="term" required>
                            <option value="">Select Term</option>
                            <option value="term1">Term 1</option>
                            <option value="term2">Term 2</option>
                            <option value="term3">Term 3</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Academic Year</label>
                        <input type="text" class="form-control" name="academic_year" value="2024-2025" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Assessment Date</label>
                        <input type="date" class="form-control" name="assessment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Score (<?php echo $criteria[0]['max_score'] ?? 100; ?> max)</label>
                        <input type="number" class="form-control" name="score" min="0" max="<?php echo $criteria[0]['max_score'] ?? 100; ?>" step="0.01" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="1"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="add_progress" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Progress
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=academic'">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Academic Overview -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5>
                        <i class="fas fa-chart-bar me-2"></i>Academic Performance Summary
                    </h5>
                    <button class="btn btn-primary btn-sm" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=academic&add=1'">
                        <i class="fas fa-plus me-1"></i>Add Progress
                    </button>
                </div>
                
                <?php if (!empty($academic_summary)): ?>
                    <?php foreach ($academic_summary as $summary): ?>
                        <div class="progress-item">
                            <div class="progress-label">
                                <span><?php echo htmlspecialchars($summary['subject_name']); ?></span>
                                <span><?php echo round($summary['avg_score'], 1); ?>% (<?php echo $summary['assessment_count']; ?> assessments)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-<?php echo ($summary['avg_score'] >= 80) ? 'success' : (($summary['avg_score'] >= 60) ? 'warning' : 'danger'); ?>" 
                                     style="width: <?php echo $summary['avg_score']; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No academic progress recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-images me-2"></i>Recent Portfolio Items
                </h5>
                <?php if (!empty($portfolio_items)): ?>
                    <?php foreach ($portfolio_items as $item): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($item['portfolio_date'])); ?></small>
                                </div>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($item['category']); ?></span>
                            </div>
                            <?php if ($item['file_path']): ?>
                                <img src="../uploads/portfolio/<?php echo htmlspecialchars($item['file_path']); ?>" 
                                     class="img-fluid rounded mt-2" style="max-height: 100px;">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No portfolio items yet.</p>
                <?php endif; ?>
                
                <button class="btn btn-outline-primary btn-sm w-100 mt-3" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=academic&portfolio=1'">
                    <i class="fas fa-plus me-1"></i>Add Portfolio Item
                </button>
            </div>
        </div>
    </div>

    <!-- Detailed Progress Records -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5>
                <i class="fas fa-list me-2"></i>Detailed Progress Records
            </h5>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto" id="termFilter" onchange="filterProgress()">
                    <option value="">All Terms</option>
                    <option value="term1">Term 1</option>
                    <option value="term2">Term 2</option>
                    <option value="term3">Term 3</option>
                </select>
                <select class="form-select form-select-sm d-inline-block w-auto ms-2" id="subjectFilter" onchange="filterProgress()">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Assessment Criteria</th>
                        <th>Term</th>
                        <th>Score</th>
                        <th>Remarks</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($progress_records)): ?>
                        <?php foreach ($progress_records as $record): ?>
                            <tr data-term="<?php echo $record['term']; ?>" data-subject="<?php echo $record['subject_id']; ?>">
                                <td><?php echo date('M d, Y', strtotime($record['assessment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['criteria_name']); ?></td>
                                <td><span class="badge bg-info"><?php echo ucfirst($record['term']); ?></span></td>
                                <td>
                                    <span class="badge bg-<?php echo ($record['score'] >= 80) ? 'success' : (($record['score'] >= 60) ? 'warning' : 'danger'); ?>">
                                        <?php echo $record['score']; ?>%
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($record['teacher_name'] ?? '-'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editProgress(<?php echo $record['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProgress(<?php echo $record['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No progress records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
// Load assessment criteria based on selected subject
const criteriaData = <?php echo json_encode($criteria); ?>;

function loadCriteria() {
    const subjectId = document.getElementById('subject_id').value;
    const criteriaSelect = document.getElementById('assessment_criteria_id');
    
    criteriaSelect.innerHTML = '<option value="">Select Criteria</option>';
    
    if (subjectId) {
        const filteredCriteria = criteriaData.filter(c => c.subject_id == subjectId);
        filteredCriteria.forEach(criteria => {
            const option = document.createElement('option');
            option.value = criteria.id;
            option.textContent = criteria.name;
            option.dataset.maxScore = criteria.max_score;
            criteriaSelect.appendChild(option);
        });
        
        // Update max score when criteria is selected
        criteriaSelect.onchange = function() {
            const selectedOption = this.options[this.selectedIndex];
            const scoreInput = document.querySelector('input[name="score"]');
            if (selectedOption.dataset.maxScore) {
                scoreInput.max = selectedOption.dataset.maxScore;
                scoreInput.placeholder = `Max: ${selectedOption.dataset.maxScore}`;
            }
        };
    }
}

function filterProgress() {
    const termFilter = document.getElementById('termFilter').value;
    const subjectFilter = document.getElementById('subjectFilter').value;
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const matchesTerm = !termFilter || row.dataset.term === termFilter;
        const matchesSubject = !subjectFilter || row.dataset.subject === subjectFilter;
        
        row.style.display = (matchesTerm && matchesSubject) ? '' : 'none';
    });
}

function editProgress(id) {
    // Implement edit functionality
    console.log('Edit progress:', id);
}

function deleteProgress(id) {
    if (confirm('Are you sure you want to delete this progress record?')) {
        // Implement delete functionality
        console.log('Delete progress:', id);
    }
}
</script>
