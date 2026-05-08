<?php
require_once 'config/database.php';

echo "<h1>Admin User Check</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if admin user exists
    $query = "SELECT * FROM users WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<h2>Admin User Found:</h2>";
        echo "<pre>";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Full Name: " . $admin['full_name'] . "\n";
        echo "Role: " . $admin['role'] . "\n";
        echo "Status: " . $admin['status'] . "\n";
        echo "Password Hash: " . $admin['password'] . "\n";
        echo "Created: " . $admin['created_at'] . "\n";
        echo "</pre>";
        
        // Test password verification
        $test_password = 'admin123';
        if (password_verify($test_password, $admin['password'])) {
            echo "<p style='color: green;'>✓ Password verification SUCCESS for 'admin123'</p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification FAILED for 'admin123'</p>";
            
            // Check if it's plain text
            if ($admin['password'] === 'admin123') {
                echo "<p style='color: orange;'>⚠ Password is stored as PLAIN TEXT</p>";
            }
        }
        
        // Test with different hash
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        echo "<h3>New hash for 'admin123':</h3>";
        echo "<code>" . $new_hash . "</code>";
        
        // Update password if needed
        echo "<h3>Update Password:</h3>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='update_password' value='1'>";
        echo "<button type='submit' class='btn btn-primary'>Update Admin Password</button>";
        echo "</form>";
        
    } else {
        echo "<p style='color: red;'>✗ No admin user found</p>";
        echo "<h3>Create Admin User:</h3>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='create_admin' value='1'>";
        echo "<button type='submit' class='btn btn-success'>Create Admin User</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_password'])) {
            $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = :password WHERE username = 'admin'";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':password', $new_hash);
            $update_stmt->execute();
            echo "<p style='color: green;'>✓ Admin password updated successfully!</p>";
            echo "<p><a href='auth/login.php'>Try Login Now</a></p>";
        }
        
        if (isset($_POST['create_admin'])) {
            $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (username, email, password, full_name, role, status) VALUES 
                          ('admin', 'admin@kidzenia.com', :password, 'System Administrator', 'admin', 'active')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':password', $hashed_password);
            $insert_stmt->execute();
            echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
            echo "<p><a href='auth/login.php'>Try Login Now</a></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h3>Admin User Diagnosis</h3>
            </div>
            <div class="card-body">
                <p><a href="debug_session.php" class="btn btn-secondary">Back to Debug</a></p>
                <p><a href="auth/login.php" class="btn btn-primary">Go to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
