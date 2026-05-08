<?php
// Fee Management Tab
$student_id = $_GET['id'];
$add_mode = isset($_GET['add']) ? true : false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $add_mode) {
    if (isset($_POST['add_fee_assignment'])) {
        $fee_type_id = $_POST['fee_type_id'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        $academic_year = $_POST['academic_year'];
        $notes = clean_input($_POST['notes']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO student_fee_assignments 
                      (student_id, fee_type_id, amount, due_date, academic_year, notes) 
                      VALUES (:student_id, :fee_type_id, :amount, :due_date, :academic_year, :notes)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':fee_type_id', $fee_type_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':due_date', $due_date);
            $stmt->bindParam(':academic_year', $academic_year);
            $stmt->bindParam(':notes', $notes);
            
            if ($stmt->execute()) {
                flash_message('success', 'Fee assignment added successfully!');
                redirect('student_management.php?id=' . $student_id . '&tab=fees');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
    
    if (isset($_POST['add_payment'])) {
        $assignment_id = $_POST['assignment_id'];
        $amount = $_POST['amount'];
        $payment_date = $_POST['payment_date'];
        $payment_method = $_POST['payment_method'];
        $transaction_id = clean_input($_POST['transaction_id']);
        $receipt_number = clean_input($_POST['receipt_number']);
        $notes = clean_input($_POST['notes']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO fee_payments 
                      (student_fee_assignment_id, amount, payment_date, payment_method, transaction_id, receipt_number, notes, received_by) 
                      VALUES (:student_fee_assignment_id, :amount, :payment_date, :payment_method, :transaction_id, :receipt_number, :notes, :received_by)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_fee_assignment_id', $assignment_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':payment_date', $payment_date);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->bindParam(':transaction_id', $transaction_id);
            $stmt->bindParam(':receipt_number', $receipt_number);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':received_by', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                flash_message('success', 'Payment recorded successfully!');
                redirect('student_management.php?id=' . $student_id . '&tab=fees');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
}

// Initialize variables to prevent undefined variable warnings
$fee_types = [];
$fee_assignments = [];
$payment_history = [];
$fee_stats = [];

// Get fee data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get fee types
    $fee_types_query = "SELECT * FROM fee_types WHERE is_active = 1 ORDER BY name";
    $fee_types_stmt = $db->prepare($fee_types_query);
    $fee_types_stmt->execute();
    $fee_types = $fee_types_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student fee assignments
    $assignments_query = "SELECT sfa.*, ft.name as fee_name, ft.fee_category, ft.billing_cycle,
                          COALESCE(SUM(fp.amount), 0) as paid_amount,
                          (sfa.amount - COALESCE(SUM(fp.amount), 0)) as balance
                          FROM student_fee_assignments sfa
                          JOIN fee_types ft ON sfa.fee_type_id = ft.id
                          LEFT JOIN fee_payments fp ON sfa.id = fp.student_fee_assignment_id
                          WHERE sfa.student_id = :student_id
                          GROUP BY sfa.id
                          ORDER BY sfa.due_date ASC";
    
    $stmt = $db->prepare($assignments_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $fee_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history
    $payments_query = "SELECT fp.*, ft.name as fee_name, u.full_name as received_by_name
                       FROM fee_payments fp
                       JOIN student_fee_assignments sfa ON fp.student_fee_assignment_id = sfa.id
                       JOIN fee_types ft ON sfa.fee_type_id = ft.id
                       LEFT JOIN users u ON fp.received_by = u.id
                       WHERE sfa.student_id = :student_id
                       ORDER BY fp.payment_date DESC";
    
    $stmt = $db->prepare($payments_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get fee statistics
    $stats_query = "SELECT 
        COUNT(*) as total_assignments,
        SUM(sfa.amount) as total_amount,
        COALESCE(SUM(fp.amount), 0) as total_paid,
        SUM(CASE WHEN sfa.status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN sfa.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN sfa.due_date < CURDATE() AND sfa.status != 'paid' THEN 1 ELSE 0 END) as overdue_count
        FROM student_fee_assignments sfa
        LEFT JOIN fee_payments fp ON sfa.id = fp.student_fee_assignment_id
        WHERE sfa.student_id = :student_id
        AND sfa.academic_year = '2024-2025'";
    
    $stmt = $db->prepare($stats_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $fee_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading fee data: " . $exception->getMessage();
}
?>

<?php if ($add_mode): ?>
    <?php if (isset($_GET['payment'])): ?>
        <!-- Add Payment Form -->
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-money-bill-wave me-2"></i>Record Payment
            </h5>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Fee Assignment</label>
                            <select class="form-select" name="assignment_id" required onchange="updatePaymentInfo()">
                                <option value="">Select Fee Assignment</option>
                                <?php foreach ($fee_assignments as $assignment): ?>
                                    <?php if ($assignment['balance'] > 0): ?>
                                        <option value="<?php echo $assignment['id']; ?>" 
                                                data-amount="<?php echo $assignment['balance']; ?>"
                                                data-fee-name="<?php echo htmlspecialchars($assignment['fee_name']); ?>">
                                            <?php echo htmlspecialchars($assignment['fee_name']); ?> - 
                                            Balance: $<?php echo number_format($assignment['balance'], 2); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Payment Amount</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required id="paymentAmount">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="check">Check</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="online">Online Payment</option>
                                <option value="card">Credit/Debit Card</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Receipt Number</label>
                            <input type="text" class="form-control" name="receipt_number" placeholder="Optional">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" class="form-control" name="transaction_id" placeholder="For online payments">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Payment notes..."></textarea>
                </div>
                
                <div class="alert alert-info" id="paymentInfo" style="display: none;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="paymentInfoText"></span>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="add_payment" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Record Payment
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=fees'">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Add Fee Assignment Form -->
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-plus me-2"></i>Add Fee Assignment
            </h5>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Fee Type</label>
                            <select class="form-select" name="fee_type_id" required onchange="updateFeeInfo()">
                                <option value="">Select Fee Type</option>
                                <?php foreach ($fee_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" 
                                            data-amount="<?php echo $type['amount']; ?>"
                                            data-category="<?php echo $type['fee_category']; ?>"
                                            data-cycle="<?php echo $type['billing_cycle']; ?>">
                                        <?php echo htmlspecialchars($type['name']); ?> - $<?php echo number_format($type['amount'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required id="feeAmount">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Academic Year</label>
                            <input type="text" class="form-control" name="academic_year" value="2024-2025" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Fee assignment notes..."></textarea>
                </div>
                
                <div class="alert alert-info" id="feeInfo" style="display: none;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="feeInfoText"></span>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="add_fee_assignment" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Fee Assignment
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=fees'">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
<?php else: ?>
    <!-- Fee Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-primary">
                    $<?php echo number_format($fee_stats['total_amount'] ?? 0, 2); ?>
                </div>
                <div class="text-muted">Total Fees</div>
                <small class="text-muted">2024-2025</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-success">
                    $<?php echo number_format($fee_stats['total_paid'] ?? 0, 2); ?>
                </div>
                <div class="text-muted">Paid Amount</div>
                <small class="text-muted">Received</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-warning">
                    $<?php echo number_format(($fee_stats['total_amount'] ?? 0) - ($fee_stats['total_paid'] ?? 0), 2); ?>
                </div>
                <div class="text-muted">Outstanding</div>
                <small class="text-muted">Balance</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-danger">
                    <?php echo $fee_stats['overdue_count'] ?? 0; ?>
                </div>
                <div class="text-muted">Overdue</div>
                <small class="text-muted">Payments</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Fee Assignments -->
        <div class="col-md-8">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5>
                        <i class="fas fa-file-invoice-dollar me-2"></i>Fee Assignments
                    </h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=fees&add=1'">
                            <i class="fas fa-plus me-1"></i>Add Fee
                        </button>
                        <button class="btn btn-success btn-sm" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=fees&add=1&payment=1'">
                            <i class="fas fa-money-bill-wave me-1"></i>Record Payment
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fee Type</th>
                                <th>Category</th>
                                <th>Total Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($fee_assignments)): ?>
                                <?php foreach ($fee_assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['fee_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($assignment['fee_category']); ?></span>
                                        </td>
                                        <td>$<?php echo number_format($assignment['amount'], 2); ?></td>
                                        <td>$<?php echo number_format($assignment['paid_amount'], 2); ?></td>
                                        <td class="<?php echo ($assignment['balance'] > 0) ? 'text-danger' : 'text-success'; ?>">
                                            $<?php echo number_format($assignment['balance'], 2); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $due_date = new DateTime($assignment['due_date']);
                                            $today = new DateTime();
                                            $is_overdue = $due_date < $today && $assignment['balance'] > 0;
                                            ?>
                                            <span class="<?php echo $is_overdue ? 'text-danger' : ''; ?>">
                                                <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo ($assignment['status'] == 'paid') ? 'success' : (($assignment['status'] == 'overdue') ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($assignment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($assignment['balance'] > 0): ?>
                                                    <button class="btn btn-outline-success" onclick="recordPayment(<?php echo $assignment['id']; ?>)">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-primary" onclick="viewPayments(<?php echo $assignment['id']; ?>)">
                                                    <i class="fas fa-list"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary" onclick="editAssignment(<?php echo $assignment['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No fee assignments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="col-md-4">
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-history me-2"></i>Recent Payments
                </h5>
                
                <?php 
                $recent_payments = array_slice($payment_history, 0, 5);
                if (!empty($recent_payments)): ?>
                    <?php foreach ($recent_payments as $payment): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($payment['fee_name']); ?></h6>
                                    <span class="badge bg-success">$<?php echo number_format($payment['amount'], 2); ?></span>
                                    <span class="badge bg-info ms-1"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M d', strtotime($payment['payment_date'])); ?>
                                </small>
                            </div>
                            <?php if ($payment['receipt_number']): ?>
                                <div class="small text-muted">
                                    Receipt: <?php echo htmlspecialchars($payment['receipt_number']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="small text-muted">
                                By: <?php echo htmlspecialchars($payment['received_by_name'] ?? 'Staff'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No payment history found.</p>
                <?php endif; ?>
                
                <button class="btn btn-outline-primary btn-sm w-100 mt-3" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=fees&view=payments'">
                    <i class="fas fa-list me-1"></i>View All Payments
                </button>
            </div>

            <!-- Fee Summary by Category -->
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-chart-pie me-2"></i>Fee Summary by Category
                </h5>
                <?php 
                $category_summary = [];
                foreach ($fee_assignments as $assignment) {
                    $category = $assignment['fee_category'];
                    if (!isset($category_summary[$category])) {
                        $category_summary[$category] = [
                            'total' => 0,
                            'paid' => 0,
                            'balance' => 0
                        ];
                    }
                    $category_summary[$category]['total'] += $assignment['amount'];
                    $category_summary[$category]['paid'] += $assignment['paid_amount'];
                    $category_summary[$category]['balance'] += $assignment['balance'];
                }
                ?>
                
                <?php if (!empty($category_summary)): ?>
                    <?php foreach ($category_summary as $category => $data): ?>
                        <div class="mb-2 p-2 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $category))); ?></strong>
                                <span class="badge bg-primary">$<?php echo number_format($data['balance'], 2); ?></span>
                            </div>
                            <div class="small text-muted">
                                Total: $<?php echo number_format($data['total'], 2); ?> | 
                                Paid: $<?php echo number_format($data['paid'], 2); ?>
                            </div>
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
function updateFeeInfo() {
    const select = document.querySelector('select[name="fee_type_id"]');
    const selectedOption = select.options[select.selectedIndex];
    const amountInput = document.getElementById('feeAmount');
    const infoDiv = document.getElementById('feeInfo');
    const infoText = document.getElementById('feeInfoText');
    
    if (selectedOption.value) {
        const amount = selectedOption.dataset.amount;
        const category = selectedOption.dataset.category;
        const cycle = selectedOption.dataset.billing_cycle;
        
        amountInput.value = amount;
        
        infoText.textContent = `This ${category} fee is billed ${cycle}. Default amount: $${amount}`;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}

function updatePaymentInfo() {
    const select = document.querySelector('select[name="assignment_id"]');
    const selectedOption = select.options[select.selectedIndex];
    const amountInput = document.getElementById('paymentAmount');
    const infoDiv = document.getElementById('paymentInfo');
    const infoText = document.getElementById('paymentInfoText');
    
    if (selectedOption.value) {
        const balance = selectedOption.dataset.balance;
        const feeName = selectedOption.dataset.feeName;
        
        amountInput.value = balance;
        amountInput.max = balance;
        
        infoText.textContent = `Recording payment for ${feeName}. Outstanding balance: $${balance}`;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}

function recordPayment(id) {
    window.location.href = '?id=<?php echo $student_id; ?>&tab=fees&add=1&payment=1&assignment=' + id;
}

function viewPayments(id) {
    // Implement view payments functionality
    console.log('View payments for assignment:', id);
}

function editAssignment(id) {
    // Implement edit assignment functionality
    console.log('Edit assignment:', id);
}
</script>
