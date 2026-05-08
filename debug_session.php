<?php
require_once 'config/config.php';

echo "<h1>Login System Debug</h1>";
echo "<h2>Current Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Authentication Status:</h2>";
if (is_logged_in()) {
    echo "<p style='color: green;'>✓ User is logged in with ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Username: " . ($_SESSION['username'] ?? 'N/A') . "</p>";
    echo "<p>Full Name: " . ($_SESSION['full_name'] ?? 'N/A') . "</p>";
    echo "<p>Role: " . ($_SESSION['role'] ?? 'N/A') . "</p>";
    
    if (is_admin()) {
        echo "<p style='color: green;'>✓ User has admin privileges</p>";
        echo "<p><a href='admin/settings.php?tab=email' class='btn btn-primary'>Go to Email Settings</a></p>";
        echo "<p><a href='admin/index.php' class='btn btn-secondary'>Go to Admin Dashboard</a></p>";
    } else {
        echo "<p style='color: orange;'>User is not admin</p>";
        echo "<p><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></p>";
    }
} else {
    echo "<p style='color: red;'>✗ User is not logged in</p>";
    echo "<p><a href='auth/login.php' class='btn btn-primary'>Login Here</a></p>";
}

echo "<h2>Test Actions:</h2>";
echo "<p><a href='auth/login.php' class='btn btn-outline-primary'>Go to Login</a></p>";
echo "<p><a href='auth/logout.php' class='btn btn-outline-danger'>Logout</a></p>";

echo "<h2>Default Credentials:</h2>";
echo "<div class='alert alert-info'>";
echo "<strong>Admin Login:</strong> admin / admin123<br>";
echo "<strong>Note:</strong> Make sure the database has been set up with the schema.sql file";
echo "</div>";

echo "<h2>Database Check:</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    echo "<p>Active admin users: " . $result['count'] . "</p>";
    
    if ($result['count'] == 0) {
        echo "<p style='color: orange;'>⚠ No active admin users found. Run setup_database.php first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        
    </div>
</body>
</html>
