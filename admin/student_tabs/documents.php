<?php
// Documents Management Tab
$student_id = $_GET['id'];
$add_mode = isset($_GET['add']) ? true : false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $add_mode) {
    if (isset($_POST['upload_document'])) {
        $document_type = $_POST['document_type'];
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $file_path = null;
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
                $upload_result = upload_file($_FILES['document_file'], '../uploads/documents/');
                if ($upload_result) {
                    $file_path = $upload_result;
                }
            }
            
            if ($file_path) {
                $query = "INSERT INTO student_documents 
                          (student_id, document_type, title, description, file_path, file_size, file_type, upload_date, expiry_date, is_required, uploaded_by) 
                          VALUES (:student_id, :document_type, :title, :description, :file_path, :file_size, :file_type, :upload_date, :expiry_date, :is_required, :uploaded_by)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':document_type', $document_type);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':file_path', $file_path);
                $stmt->bindParam(':file_size', $_FILES['document_file']['size']);
                $stmt->bindParam(':file_type', $_FILES['document_file']['type']);
                $stmt->bindParam(':upload_date', date('Y-m-d'));
                $stmt->bindParam(':expiry_date', $expiry_date);
                $stmt->bindParam(':is_required', $is_required);
                $stmt->bindParam(':uploaded_by', $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    flash_message('success', 'Document uploaded successfully!');
                    redirect('student_management.php?id=' . $student_id . '&tab=documents');
                }
            } else {
                flash_message('error', 'Failed to upload document file.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
}

// Get documents data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get student documents
    $documents_query = "SELECT sd.*, u.full_name as uploaded_by_name 
                        FROM student_documents sd 
                        LEFT JOIN users u ON sd.uploaded_by = u.id 
                        WHERE sd.student_id = :student_id 
                        ORDER BY sd.upload_date DESC";
    
    $stmt = $db->prepare($documents_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get document statistics
    $stats_query = "SELECT 
        COUNT(*) as total_documents,
        COUNT(CASE WHEN document_type = 'birth_certificate' THEN 1 END) as birth_cert,
        COUNT(CASE WHEN document_type = 'medical_form' THEN 1 END) as medical_forms,
        COUNT(CASE WHEN document_type = 'immunization_record' THEN 1 END) as immunization,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
        COUNT(CASE WHEN expiry_date < CURDATE() AND expiry_date IS NOT NULL THEN 1 END) as expired
        FROM student_documents 
        WHERE student_id = :student_id";
    
    $stmt = $db->prepare($stats_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $doc_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading documents data: " . $exception->getMessage();
}
?>

<?php if ($add_mode): ?>
    <!-- Upload Document Form -->
    <div class="content-card">
        <h5 class="mb-4">
            <i class="fas fa-upload me-2"></i>Upload Document
        </h5>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Document Type</label>
                        <select class="form-select" name="document_type" required>
                            <option value="">Select Document Type</option>
                            <option value="birth_certificate">Birth Certificate</option>
                            <option value="medical_form">Medical Form</option>
                            <option value="immunization_record">Immunization Record</option>
                            <option value="permission_slip">Permission Slip</option>
                            <option value="report_card">Report Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required placeholder="Document title...">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Document description..."></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Document File</label>
                        <input type="file" class="form-control" name="document_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        <div class="form-text">Accepted formats: PDF, DOC, DOCX, JPG, PNG (Max 5MB)</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Expiry Date (Optional)</label>
                        <input type="date" class="form-control" name="expiry_date">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_required" id="is_required">
                    <label class="form-check-label" for="is_required">
                        Mark as Required Document
                    </label>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="upload_document" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Upload Document
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=documents'">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Document Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stat-card">
                <div class="stat-number text-primary">
                    <?php echo $doc_stats['total_documents'] ?? 0; ?>
                </div>
                <div class="text-muted">Total Documents</div>
                <small class="text-muted">All types</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <div class="stat-number text-success">
                    <?php echo $doc_stats['approved'] ?? 0; ?>
                </div>
                <div class="text-muted">Approved</div>
                <small class="text-muted">Verified</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <div class="stat-number text-warning">
                    <?php echo $doc_stats['pending'] ?? 0; ?>
                </div>
                <div class="text-muted">Pending</div>
                <small class="text-muted">Review needed</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <div class="stat-number text-danger">
                    <?php echo $doc_stats['expired'] ?? 0; ?>
                </div>
                <div class="text-muted">Expired</div>
                <small class="text-muted">Need renewal</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <div class="stat-number text-info">
                    <?php echo $doc_stats['birth_cert'] ?? 0; ?>
                </div>
                <div class="text-muted">Birth Cert</div>
                <small class="text-muted">On file</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <div class="stat-number text-secondary">
                    <?php echo $doc_stats['medical_forms'] ?? 0; ?>
                </div>
                <div class="text-muted">Medical</div>
                <small class="text-muted">Forms</small>
            </div>
        </div>
    </div>

    <!-- Required Documents Checklist -->
    <div class="content-card mb-4">
        <h5 class="mb-4">
            <i class="fas fa-clipboard-check me-2"></i>Required Documents Checklist
        </h5>
        <div class="row">
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="req_birth_cert" <?php echo ($doc_stats['birth_cert'] > 0) ? 'checked disabled' : ''; ?>>
                    <label class="form-check-label" for="req_birth_cert">
                        Birth Certificate
                        <?php if ($doc_stats['birth_cert'] > 0): ?>
                            <span class="badge bg-success ms-2">On File</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">Missing</span>
                        <?php endif; ?>
                    </label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="req_medical" <?php echo ($doc_stats['medical_forms'] > 0) ? 'checked disabled' : ''; ?>>
                    <label class="form-check-label" for="req_medical">
                        Medical Form
                        <?php if ($doc_stats['medical_forms'] > 0): ?>
                            <span class="badge bg-success ms-2">On File</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">Missing</span>
                        <?php endif; ?>
                    </label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="req_immunization" <?php echo ($doc_stats['immunization'] > 0) ? 'checked disabled' : ''; ?>>
                    <label class="form-check-label" for="req_immunization">
                        Immunization Record
                        <?php if ($doc_stats['immunization'] > 0): ?>
                            <span class="badge bg-success ms-2">On File</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">Missing</span>
                        <?php endif; ?>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents List -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5>
                <i class="fas fa-folder-open me-2"></i>Student Documents
            </h5>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto" id="typeFilter" onchange="filterDocuments()">
                    <option value="">All Types</option>
                    <option value="birth_certificate">Birth Certificate</option>
                    <option value="medical_form">Medical Form</option>
                    <option value="immunization_record">Immunization Record</option>
                    <option value="permission_slip">Permission Slip</option>
                    <option value="report_card">Report Card</option>
                    <option value="other">Other</option>
                </select>
                <button class="btn btn-primary btn-sm ms-2" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=documents&add=1'">
                    <i class="fas fa-plus me-1"></i>Upload Document
                </button>
            </div>
        </div>
        
        <?php if (!empty($documents)): ?>
            <div class="row">
                <?php foreach ($documents as $doc): ?>
                    <div class="col-md-6 col-lg-4 mb-3 document-item" data-type="<?php echo $doc['document_type']; ?>">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0"><?php echo htmlspecialchars($doc['title']); ?></h6>
                                    <span class="badge bg-<?php echo ($doc['status'] == 'approved') ? 'success' : (($doc['status'] == 'pending') ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($doc['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars(str_replace('_', ' ', $doc['document_type'])); ?></span>
                                    <?php if ($doc['is_required']): ?>
                                        <span class="badge bg-danger ms-1">Required</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($doc['description']): ?>
                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($doc['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Uploaded: <?php echo date('M d, Y', strtotime($doc['upload_date'])); ?>
                                    </small>
                                    <?php if ($doc['expiry_date']): ?>
                                        <br>
                                        <small class="<?php echo (strtotime($doc['expiry_date']) < time()) ? 'text-danger' : 'text-warning'; ?>">
                                            <i class="fas fa-clock me-1"></i>
                                            Expires: <?php echo date('M d, Y', strtotime($doc['expiry_date'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        By: <?php echo htmlspecialchars($doc['uploaded_by_name'] ?? 'System'); ?>
                                    </small>
                                </div>
                                
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewDocument('<?php echo $doc['file_path']; ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="downloadDocument('<?php echo $doc['file_path']; ?>')">
                                        <i class="fas fa-download"></i> Download
                                    </button>
                                    <?php if ($doc['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-warning" onclick="approveDocument(<?php echo $doc['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Documents Found</h5>
                <p class="text-muted">Upload documents to get started.</p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
function filterDocuments() {
    const typeFilter = document.getElementById('typeFilter').value;
    const documents = document.querySelectorAll('.document-item');
    
    documents.forEach(doc => {
        if (!typeFilter || doc.dataset.type === typeFilter) {
            doc.style.display = 'block';
        } else {
            doc.style.display = 'none';
        }
    });
}

function viewDocument(filePath) {
    window.open('../uploads/documents/' + filePath, '_blank');
}

function downloadDocument(filePath) {
    const link = document.createElement('a');
    link.href = '../uploads/documents/' + filePath;
    link.download = filePath;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function approveDocument(id) {
    if (confirm('Approve this document?')) {
        // Implement approve functionality
        console.log('Approve document:', id);
    }
}

function deleteDocument(id) {
    if (confirm('Are you sure you want to delete this document?')) {
        // Implement delete functionality
        console.log('Delete document:', id);
    }
}
</script>
