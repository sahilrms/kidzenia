<?php
// Database Setup Script for Kidzenia Kindergarten

echo "<h1>Kidzenia Kindergarten - Database Setup</h1>";

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "kidzenia_db";

try {
    // Connect to MySQL without database name
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<p style='color: green;'>✓ Database '$dbname' created or already exists</p>";
    
    // Connect to the specific database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Connected to database '$dbname'</p>";
    
    // Read and execute the schema file
    $schema_file = __DIR__ . '/database/schema.sql';
    if (file_exists($schema_file)) {
        $schema = file_get_contents($schema_file);
        
        // Split the schema into individual statements
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $conn->exec($statement);
                } catch (PDOException $e) {
                    // Ignore errors for statements that might already exist
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "<p style='color: orange;'>⚠ Warning: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
        
        echo "<p style='color: green;'>✓ Database schema imported successfully</p>";
        
        // Verify that the admin user was created
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin_user) {
            echo "<p style='color: green;'>✓ Admin user created successfully</p>";
            echo "<p><strong>Login Credentials:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Username:</strong> admin</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>✗ Admin user not found. Creating manually...</p>";
            
            // Create admin user manually
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@kidzenia.com', $password, 'System Administrator', 'admin', 'active']);
            
            echo "<p style='color: green;'>✓ Admin user created manually</p>";
        }
        
        // Show database statistics
        echo "<h3>Database Statistics:</h3>";
        $tables = ['users', 'students', 'classes', 'attendance', 'announcements', 'notifications', 'gallery', 'events', 'messages', 'feedbacks', 'settings'];
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p><strong>$table:</strong> $count records</p>";
        }
        
        echo "<h3 style='color: green;'>Setup Complete!</h3>";
        echo "<p>You can now login to the system:</p>";
        echo "<p><a href='auth/login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
        
    } else {
        echo "<p style='color: red;'>✗ Schema file not found: $schema_file</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your MySQL server and credentials.</p>";
}

echo "<hr>";
echo "<p><strong>Troubleshooting:</strong></p>";
echo "<ul>";
echo "<li>Make sure XAMPP MySQL service is running</li>";
echo "<li>Check that MySQL username is 'root' and password is empty</li>";
echo "<li>Ensure the database/schema.sql file exists</li>";
echo "</ul>";
?>
