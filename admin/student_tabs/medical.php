<?php
// Medical Management Tab
$student_id = $_GET['id'];
$add_mode = isset($_GET['add']) ? true : false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $add_mode) {
    if (isset($_POST['add_medical_visit'])) {
        $visit_date = $_POST['visit_date'];
        $visit_time = $_POST['visit_time'];
        $reason = clean_input($_POST['reason']);
        $symptoms = clean_input($_POST['symptoms']);
        $diagnosis = clean_input($_POST['diagnosis']);
        $treatment_given = clean_input($_POST['treatment_given']);
        $medication_administered = clean_input($_POST['medication_administered']);
        $follow_up_required = isset($_POST['follow_up_required']) ? 1 : 0;
        $follow_up_notes = clean_input($_POST['follow_up_notes']);
        $parent_notified = isset($_POST['parent_notified']) ? 1 : 0;
        $notification_time = $_POST['notification_time'];
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO medical_visits 
                      (student_id, visit_date, visit_time, reason, symptoms, diagnosis, treatment_given, medication_administered, follow_up_required, follow_up_notes, parent_notified, notification_time, staff_id) 
                      VALUES (:student_id, :visit_date, :visit_time, :reason, :symptoms, :diagnosis, :treatment_given, :medication_administered, :follow_up_required, :follow_up_notes, :parent_notified, :notification_time, :staff_id)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':visit_date', $visit_date);
            $stmt->bindParam(':visit_time', $visit_time);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':symptoms', $symptoms);
            $stmt->bindParam(':diagnosis', $diagnosis);
            $stmt->bindParam(':treatment_given', $treatment_given);
            $stmt->bindParam(':medication_administered', $medication_administered);
            $stmt->bindParam(':follow_up_required', $follow_up_required);
            $stmt->bindParam(':follow_up_notes', $follow_up_notes);
            $stmt->bindParam(':parent_notified', $parent_notified);
            $stmt->bindParam(':notification_time', $notification_time);
            $stmt->bindParam(':staff_id', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                flash_message('success', 'Medical visit recorded successfully!');
                redirect('student_management.php?id=' . $student_id . '&tab=medical');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
}

// Get medical data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get medical visits
    $visits_query = "SELECT mv.*, u.full_name as staff_name 
                     FROM medical_visits mv 
                     LEFT JOIN users u ON mv.staff_id = u.id 
                     WHERE mv.student_id = :student_id 
                     ORDER BY mv.visit_date DESC, mv.visit_time DESC";
    
    $stmt = $db->prepare($visits_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $medical_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get vaccination records
    $vaccinations_query = "SELECT * FROM vaccination_records WHERE student_id = :student_id ORDER BY administration_date DESC";
    $stmt = $db->prepare($vaccinations_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $vaccination_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get allergy records
    $allergies_query = "SELECT * FROM allergy_management WHERE student_id = :student_id ORDER BY severity DESC";
    $stmt = $db->prepare($allergies_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $allergy_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get medical statistics
    $stats_query = "SELECT 
        COUNT(*) as total_visits,
        COUNT(CASE WHEN visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as visits_last_30_days,
        COUNT(CASE WHEN parent_notified = 1 THEN 1 END) as parent_notifications,
        COUNT(CASE WHEN follow_up_required = 1 THEN 1 END) as follow_ups_required
        FROM medical_visits 
        WHERE student_id = :student_id";
    
    $stmt = $db->prepare($stats_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $medical_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading medical data: " . $exception->getMessage();
}
?>

<?php if ($add_mode): ?>
    <!-- Add Medical Visit Form -->
    <div class="content-card">
        <h5 class="mb-4">
            <i class="fas fa-plus me-2"></i>Record Medical Visit
        </h5>
        <form method="POST">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Visit Date</label>
                        <input type="date" class="form-control" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Visit Time</label>
                        <input type="time" class="form-control" name="visit_time" value="<?php echo date('H:i'); ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Parent Notified</label>
                        <input type="time" class="form-control" name="notification_time" value="<?php echo date('H:i'); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="parent_notified" id="parent_notified" checked>
                            <label class="form-check-label" for="parent_notified">
                                Parent Contacted
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Reason for Visit</label>
                <input type="text" class="form-control" name="reason" required placeholder="e.g., Fever, Injury, Stomach ache">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Symptoms</label>
                <textarea class="form-control" name="symptoms" rows="3" placeholder="Describe symptoms in detail..."></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Diagnosis</label>
                        <textarea class="form-control" name="diagnosis" rows="2" placeholder="Medical diagnosis..."></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Treatment Given</label>
                        <textarea class="form-control" name="treatment_given" rows="2" placeholder="Treatment administered..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Medication Administered</label>
                <textarea class="form-control" name="medication_administered" rows="2" placeholder="Any medication given..."></textarea>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="follow_up_required" id="follow_up_required" onchange="toggleFollowUp()">
                    <label class="form-check-label" for="follow_up_required">
                        Follow-up Required
                    </label>
                </div>
            </div>
            
            <div class="mb-3" id="followUpNotes" style="display: none;">
                <label class="form-label">Follow-up Notes</label>
                <textarea class="form-control" name="follow_up_notes" rows="2" placeholder="Follow-up instructions..."></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="add_medical_visit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Visit Record
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=medical'">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Medical Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-primary">
                    <?php echo $medical_stats['total_visits'] ?? 0; ?>
                </div>
                <div class="text-muted">Total Visits</div>
                <small class="text-muted">All time</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-warning">
                    <?php echo $medical_stats['visits_last_30_days'] ?? 0; ?>
                </div>
                <div class="text-muted">Last 30 Days</div>
                <small class="text-muted">Recent visits</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-success">
                    <?php echo $medical_stats['parent_notifications'] ?? 0; ?>
                </div>
                <div class="text-muted">Parent Notified</div>
                <small class="text-muted">Contacts made</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-danger">
                    <?php echo $medical_stats['follow_ups_required'] ?? 0; ?>
                </div>
                <div class="text-muted">Follow-ups</div>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Medical Visits -->
        <div class="col-md-8">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5>
                        <i class="fas fa-notes-medical me-2"></i>Medical Visit History
                    </h5>
                    <button class="btn btn-primary btn-sm" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=medical&add=1'">
                        <i class="fas fa-plus me-1"></i>Add Visit
                    </button>
                </div>
                
                <?php if (!empty($medical_visits)): ?>
                    <?php foreach ($medical_visits as $visit): ?>
                        <div class="medical-record">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-3"><?php echo htmlspecialchars($visit['reason']); ?></h6>
                                        <span class="badge bg-primary">
                                            <?php echo date('M d, Y', strtotime($visit['visit_date'])); ?>
                                        </span>
                                        <?php if ($visit['follow_up_required']): ?>
                                            <span class="badge bg-warning ms-2">Follow-up Required</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($visit['symptoms']): ?>
                                        <p class="mb-2"><strong>Symptoms:</strong> <?php echo htmlspecialchars($visit['symptoms']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($visit['diagnosis']): ?>
                                        <p class="mb-2"><strong>Diagnosis:</strong> <?php echo htmlspecialchars($visit['diagnosis']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($visit['treatment_given']): ?>
                                        <p class="mb-2"><strong>Treatment:</strong> <?php echo htmlspecialchars($visit['treatment_given']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($visit['medication_administered']): ?>
                                        <p class="mb-2"><strong>Medication:</strong> <?php echo htmlspecialchars($visit['medication_administered']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($visit['follow_up_required'] && $visit['follow_up_notes']): ?>
                                        <p class="mb-2"><strong>Follow-up:</strong> <?php echo htmlspecialchars($visit['follow_up_notes']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex align-items-center text-muted small">
                                        <span class="me-3">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                                        </span>
                                        <span class="me-3">
                                            <i class="fas fa-user-nurse me-1"></i>
                                            <?php echo htmlspecialchars($visit['staff_name'] ?? 'Staff'); ?>
                                        </span>
                                        <?php if ($visit['parent_notified']): ?>
                                            <span class="text-success">
                                                <i class="fas fa-phone me-1"></i>
                                                Parent notified at <?php echo date('h:i A', strtotime($visit['notification_time'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editVisit(<?php echo $visit['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteVisit(<?php echo $visit['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No medical visits recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Allergies and Vaccinations -->
        <div class="col-md-4">
            <!-- Allergies -->
            <div class="content-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-allergies me-2"></i>Allergies
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="addAllergy()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <?php if (!empty($allergy_records)): ?>
                    <?php foreach ($allergy_records as $allergy): ?>
                        <div class="alert alert-<?php echo ($allergy['severity'] == 'life_threatening') ? 'danger' : (($allergy['severity'] == 'severe') ? 'warning' : 'info'); ?> mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($allergy['allergen']); ?></strong>
                                    <div class="small">
                                        Type: <?php echo htmlspecialchars($allergy['allergy_type']); ?> | 
                                        Severity: <?php echo htmlspecialchars($allergy['severity']); ?>
                                    </div>
                                    <?php if ($allergy['emergency_action']): ?>
                                        <div class="small mt-1">
                                            <strong>Emergency Action:</strong> <?php echo htmlspecialchars($allergy['emergency_action']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editAllergy(<?php echo $allergy['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">No allergies recorded.</p>
                <?php endif; ?>
            </div>

            <!-- Vaccinations -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-syringe me-2"></i>Vaccinations
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="addVaccination()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <?php if (!empty($vaccination_records)): ?>
                    <?php foreach ($vaccination_records as $vaccine): ?>
                        <div class="mb-2 p-2 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($vaccine['vaccine_name']); ?></strong>
                                    <?php if ($vaccine['dose_number']): ?>
                                        <span class="badge bg-secondary ms-1">Dose <?php echo $vaccine['dose_number']; ?></span>
                                    <?php endif; ?>
                                    <div class="small text-muted">
                                        <?php echo date('M d, Y', strtotime($vaccine['administration_date'])); ?>
                                    </div>
                                    <?php if ($vaccine['next_due_date']): ?>
                                        <div class="small text-warning">
                                            Next due: <?php echo date('M d, Y', strtotime($vaccine['next_due_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editVaccination(<?php echo $vaccine['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">No vaccination records.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleFollowUp() {
    const checkbox = document.getElementById('follow_up_required');
    const notesDiv = document.getElementById('followUpNotes');
    
    if (checkbox.checked) {
        notesDiv.style.display = 'block';
    } else {
        notesDiv.style.display = 'none';
    }
}

function editVisit(id) {
    // Implement edit functionality
    console.log('Edit visit:', id);
}

function deleteVisit(id) {
    if (confirm('Are you sure you want to delete this medical visit record?')) {
        // Implement delete functionality
        console.log('Delete visit:', id);
    }
}

function addAllergy() {
    // Implement add allergy functionality
    console.log('Add allergy');
}

function editAllergy(id) {
    // Implement edit allergy functionality
    console.log('Edit allergy:', id);
}

function addVaccination() {
    // Implement add vaccination functionality
    console.log('Add vaccination');
}

function editVaccination(id) {
    // Implement edit vaccination functionality
    console.log('Edit vaccination:', id);
}
</script>
