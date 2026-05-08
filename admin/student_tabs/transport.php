<?php
// Transportation Management Tab
$student_id = $_GET['id'];
$add_mode = isset($_GET['add']) ? true : false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $add_mode) {
    if (isset($_POST['add_transport'])) {
        $route_id = $_POST['route_id'];
        $pickup_stop_id = !empty($_POST['pickup_stop_id']) ? $_POST['pickup_stop_id'] : null;
        $dropoff_stop_id = !empty($_POST['dropoff_stop_id']) ? $_POST['dropoff_stop_id'] : null;
        $service_type = $_POST['service_type'];
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $monthly_fee = !empty($_POST['monthly_fee']) ? $_POST['monthly_fee'] : null;
        $notes = clean_input($_POST['notes']);
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO transportation_assignments 
                      (student_id, route_id, pickup_stop_id, dropoff_stop_id, service_type, start_date, end_date, monthly_fee, notes) 
                      VALUES (:student_id, :route_id, :pickup_stop_id, :dropoff_stop_id, :service_type, :start_date, :end_date, :monthly_fee, :notes)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':route_id', $route_id);
            $stmt->bindParam(':pickup_stop_id', $pickup_stop_id);
            $stmt->bindParam(':dropoff_stop_id', $dropoff_stop_id);
            $stmt->bindParam(':service_type', $service_type);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':monthly_fee', $monthly_fee);
            $stmt->bindParam(':notes', $notes);
            
            if ($stmt->execute()) {
                flash_message('success', 'Transportation assignment added successfully!');
                redirect('student_management.php?id=' . $student_id . '&tab=transport');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
}

// Get transportation data
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get bus routes
    $routes_query = "SELECT * FROM bus_routes WHERE is_active = 1 ORDER BY route_name";
    $routes_stmt = $db->prepare($routes_query);
    $routes_stmt->execute();
    $routes = $routes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get bus stops
    $stops_query = "SELECT bs.*, br.route_name FROM bus_stops bs 
                    JOIN bus_routes br ON bs.route_id = br.id 
                    WHERE bs.is_active = 1 
                    ORDER BY br.route_name, bs.stop_order";
    $stops_stmt = $db->prepare($stops_query);
    $stops_stmt->execute();
    $stops = $stops_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student transportation assignments
    $assignments_query = "SELECT ta.*, br.route_name, br.route_number,
                          pickup.stop_name as pickup_stop_name,
                          dropoff.stop_name as dropoff_stop_name
                          FROM transportation_assignments ta
                          JOIN bus_routes br ON ta.route_id = br.id
                          LEFT JOIN bus_stops pickup ON ta.pickup_stop_id = pickup.id
                          LEFT JOIN bus_stops dropoff ON ta.dropoff_stop_id = dropoff.id
                          WHERE ta.student_id = :student_id
                          ORDER BY ta.start_date DESC";
    
    $stmt = $db->prepare($assignments_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $transport_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get transportation statistics
    $stats_query = "SELECT 
        COUNT(*) as total_assignments,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_assignments,
        COUNT(CASE WHEN service_type = 'morning' THEN 1 END) as morning_services,
        COUNT(CASE WHEN service_type = 'afternoon' THEN 1 END) as afternoon_services,
        COUNT(CASE WHEN service_type = 'both' THEN 1 END) as both_services,
        SUM(CASE WHEN monthly_fee IS NOT NULL THEN monthly_fee ELSE 0 END) as total_monthly_fees
        FROM transportation_assignments 
        WHERE student_id = :student_id
        AND (end_date IS NULL OR end_date >= CURDATE())";
    
    $stmt = $db->prepare($stats_query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $transport_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $error_message = "Error loading transportation data: " . $exception->getMessage();
}
?>

<?php if ($add_mode): ?>
    <!-- Add Transportation Assignment Form -->
    <div class="content-card">
        <h5 class="mb-4">
            <i class="fas fa-bus me-2"></i>Add Transportation Assignment
        </h5>
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Bus Route</label>
                        <select class="form-select" name="route_id" required onchange="loadRouteStops()">
                            <option value="">Select Route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['id']; ?>" 
                                        data-route-name="<?php echo htmlspecialchars($route['route_name']); ?>"
                                        data-route-number="<?php echo htmlspecialchars($route['route_number']); ?>">
                                    <?php echo htmlspecialchars($route['route_name']); ?> (<?php echo htmlspecialchars($route['route_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Service Type</label>
                        <select class="form-select" name="service_type" required>
                            <option value="">Select Service</option>
                            <option value="morning">Morning Only</option>
                            <option value="afternoon">Afternoon Only</option>
                            <option value="both">Both Ways</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Pickup Stop</label>
                        <select class="form-select" name="pickup_stop_id" id="pickupStop">
                            <option value="">Select Route First</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Drop-off Stop</label>
                        <select class="form-select" name="dropoff_stop_id" id="dropoffStop">
                            <option value="">Select Route First</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">End Date (Optional)</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Monthly Fee</label>
                        <input type="number" class="form-control" name="monthly_fee" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3" placeholder="Transportation notes..."></textarea>
            </div>
            
            <div class="alert alert-info" id="routeInfo" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="routeInfoText"></span>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="add_transport" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Assignment
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=transport'">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Transportation Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-primary">
                    <?php echo $transport_stats['active_assignments'] ?? 0; ?>
                </div>
                <div class="text-muted">Active Routes</div>
                <small class="text-muted">Current assignments</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-success">
                    <?php echo $transport_stats['morning_services'] ?? 0; ?>
                </div>
                <div class="text-muted">Morning Services</div>
                <small class="text-muted">Pickup routes</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-info">
                    <?php echo $transport_stats['afternoon_services'] ?? 0; ?>
                </div>
                <div class="text-muted">Afternoon Services</div>
                <small class="text-muted">Drop-off routes</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number text-warning">
                    $<?php echo number_format($transport_stats['total_monthly_fees'] ?? 0, 2); ?>
                </div>
                <div class="text-muted">Monthly Fees</div>
                <small class="text-muted">Total cost</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Transportation Assignments -->
        <div class="col-md-8">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5>
                        <i class="fas fa-bus me-2"></i>Transportation Assignments
                    </h5>
                    <button class="btn btn-primary btn-sm" onclick="window.location.href='?id=<?php echo $student_id; ?>&tab=transport&add=1'">
                        <i class="fas fa-plus me-1"></i>Add Assignment
                    </button>
                </div>
                
                <?php if (!empty($transport_assignments)): ?>
                    <?php foreach ($transport_assignments as $assignment): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($assignment['route_name']); ?> 
                                        <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($assignment['route_number']); ?></span>
                                    </h6>
                                    <div class="mb-2">
                                        <span class="badge bg-<?php echo ($assignment['status'] == 'active') ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($assignment['status']); ?>
                                        </span>
                                        <span class="badge bg-info ms-1"><?php echo ucfirst($assignment['service_type']); ?></span>
                                        <?php if ($assignment['monthly_fee']): ?>
                                            <span class="badge bg-warning ms-1">$<?php echo number_format($assignment['monthly_fee'], 2); ?>/month</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editAssignment(<?php echo $assignment['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-<?php echo ($assignment['status'] == 'active') ? 'warning' : 'success'; ?>" 
                                            onclick="toggleStatus(<?php echo $assignment['id']; ?>, '<?php echo $assignment['status']; ?>')">
                                        <i class="fas fa-<?php echo ($assignment['status'] == 'active') ? 'pause' : 'play'; ?>"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteAssignment(<?php echo $assignment['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="small text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <strong>Pickup:</strong> <?php echo htmlspecialchars($assignment['pickup_stop_name'] ?? 'Not specified'); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <strong>Drop-off:</strong> <?php echo htmlspecialchars($assignment['dropoff_stop_name'] ?? 'Not specified'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <strong>Start:</strong> <?php echo date('M d, Y', strtotime($assignment['start_date'])); ?>
                                    </div>
                                    <?php if ($assignment['end_date']): ?>
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <strong>End:</strong> <?php echo date('M d, Y', strtotime($assignment['end_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($assignment['notes']): ?>
                                <div class="mt-2">
                                    <small><strong>Notes:</strong> <?php echo htmlspecialchars($assignment['notes']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bus fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Transportation Assignments</h5>
                        <p class="text-muted">This student is not assigned to any bus routes.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Routes -->
        <div class="col-md-4">
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-route me-2"></i>Available Routes
                </h5>
                
                <?php if (!empty($routes)): ?>
                    <?php foreach ($routes as $route): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($route['route_name']); ?></h6>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($route['route_number']); ?></span>
                                </div>
                                <?php if ($route['capacity']): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i><?php echo $route['capacity']; ?> seats
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="small text-muted mb-2">
                                <div><i class="fas fa-map-signs me-1"></i><?php echo htmlspecialchars($route['start_location']); ?> to <?php echo htmlspecialchars($route['end_location']); ?></div>
                                <?php if ($route['distance_km']): ?>
                                    <div><i class="fas fa-road me-1"></i><?php echo $route['distance_km']; ?> km</div>
                                <?php endif; ?>
                                <?php if ($route['estimated_duration_minutes']): ?>
                                    <div><i class="fas fa-clock me-1"></i><?php echo $route['estimated_duration_minutes']; ?> min</div>
                                <?php endif; ?>
                            </div>
                            
                            <?php 
                            // Get stops for this route
                            $route_stops = array_filter($stops, function($stop) use ($route) {
                                return $stop['route_id'] == $route['id'];
                            });
                            ?>
                            
                            <?php if (!empty($route_stops)): ?>
                                <div class="small">
                                    <strong>Stops:</strong>
                                    <ul class="list-unstyled mb-0 mt-1">
                                        <?php 
                                        usort($route_stops, function($a, $b) {
                                            return $a['stop_order'] - $b['stop_order'];
                                        });
                                        ?>
                                        <?php foreach (array_slice($route_stops, 0, 3) as $stop): ?>
                                            <li><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($stop['stop_name']); ?></li>
                                        <?php endforeach; ?>
                                        <?php if (count($route_stops) > 3): ?>
                                            <li><em>+<?php echo count($route_stops) - 3; ?> more stops</em></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No bus routes available.</p>
                <?php endif; ?>
            </div>

            <!-- Transportation Guidelines -->
            <div class="content-card">
                <h5 class="mb-4">
                    <i class="fas fa-info-circle me-2"></i>Transportation Guidelines
                </h5>
                <div class="small">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Students must be at pickup stop 5 minutes early
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Parent/guardian authorization required for changes
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Notify office in advance for temporary changes
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Emergency contact information must be up to date
                        </li>
                        <li>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Students must follow bus safety rules
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Load route stops based on selected route
const stopsData = <?php echo json_encode($stops); ?>;

function loadRouteStops() {
    const routeSelect = document.querySelector('select[name="route_id"]');
    const pickupSelect = document.getElementById('pickupStop');
    const dropoffSelect = document.getElementById('dropoffStop');
    const routeInfo = document.getElementById('routeInfo');
    const routeInfoText = document.getElementById('routeInfoText');
    
    const selectedOption = routeSelect.options[routeSelect.selectedIndex];
    const routeId = selectedOption.value;
    
    // Clear stops
    pickupSelect.innerHTML = '<option value="">Select Pickup Stop</option>';
    dropoffSelect.innerHTML = '<option value="">Select Drop-off Stop</option>';
    
    if (routeId) {
        const routeStops = stopsData.filter(stop => stop.route_id == routeId);
        
        // Sort by stop order
        routeStops.sort((a, b) => a.stop_order - b.stop_order);
        
        // Populate stop selects
        routeStops.forEach(stop => {
            const pickupOption = document.createElement('option');
            pickupOption.value = stop.id;
            pickupOption.textContent = `${stop.stop_name} (${stop.stop_order})`;
            pickupSelect.appendChild(pickupOption);
            
            const dropoffOption = document.createElement('option');
            dropoffOption.value = stop.id;
            dropoffOption.textContent = `${stop.stop_name} (${stop.stop_order})`;
            dropoffSelect.appendChild(dropoffOption);
        });
        
        // Show route info
        const routeName = selectedOption.dataset.routeName;
        const routeNumber = selectedOption.dataset.routeNumber;
        routeInfoText.textContent = `Route ${routeNumber}: ${routeName}. ${routeStops.length} stops available.`;
        routeInfo.style.display = 'block';
    } else {
        routeInfo.style.display = 'none';
    }
}

function editAssignment(id) {
    // Implement edit functionality
    console.log('Edit assignment:', id);
}

function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    
    if (confirm(`Are you sure you want to ${action} this transportation assignment?`)) {
        // Implement toggle status functionality
        console.log('Toggle status:', id, newStatus);
    }
}

function deleteAssignment(id) {
    if (confirm('Are you sure you want to delete this transportation assignment?')) {
        // Implement delete functionality
        console.log('Delete assignment:', id);
    }
}
</script>
