<?php
// Include configuration and authentication check
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="kidzenia_backup_' . date('Y-m-d_H-i-s') . '.sql"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');
header('Content-Transfer-Encoding: binary');

// Turn off output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed");
}

// Get all tables
$tables = [];
$stmt = $conn->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

// Start SQL dump
$output = "-- Kidzenia Database Backup\n";
$output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- Database: kidzenia_db\n\n";

// Set character set
$output .= "SET NAMES utf8;\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Process each table
foreach ($tables as $table) {
    // Get table structure
    $stmt = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $output .= "-- Table structure for `$table`\n";
    $output .= "DROP TABLE IF EXISTS `$table`;\n";
    $output .= $row[1] . ";\n\n";
    
    // Get table data
    $stmt = $conn->query("SELECT * FROM `$table`");
    $column_count = $stmt->columnCount();
    
    if ($stmt->rowCount() > 0) {
        $output .= "-- Data for table `$table`\n";
        $output .= "INSERT INTO `$table` VALUES\n";
        
        $row_count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }
            
            $output .= "(" . implode(', ', $values) . ")";
            $row_count++;
            
            if ($row_count < $stmt->rowCount()) {
                $output .= ",\n";
            } else {
                $output .= ";\n\n";
            }
        }
    }
}

// Reset foreign key checks
$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$output .= "-- Backup completed successfully\n";

// Output the SQL dump
echo $output;
exit();
?>
