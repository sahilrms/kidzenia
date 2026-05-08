<?php
require_once 'config/config.php';

echo "<h1>Check Dynamic Cards Table</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if table exists
    $table_check = $db->query("SHOW TABLES LIKE 'dynamic_cards'");
    $table_exists = $table_check->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p style='color: green;'>✓ Dynamic cards table exists</p>";
        
        // Check count
        $count_query = "SELECT COUNT(*) as count FROM dynamic_cards WHERE is_active = 1";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p><strong>Total active cards:</strong> {$count}</p>";
        
        if ($count > 0) {
            // Show sample data
            $sample_query = "SELECT * FROM dynamic_cards WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 5";
            $sample_stmt = $db->prepare($sample_query);
            $sample_stmt->execute();
            $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Cards:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Section</th><th>Title</th><th>Description</th><th>Sort Order</th></tr>";
            
            foreach ($samples as $sample) {
                echo "<tr>";
                echo "<td>{$sample['id']}</td>";
                echo "<td>{$sample['section']}</td>";
                echo "<td>" . htmlspecialchars($sample['title']) . "</td>";
                echo "<td>" . htmlspecialchars($sample['description']) . "</td>";
                echo "<td>{$sample['sort_order']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No active cards found in dynamic_cards table</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Dynamic cards table does not exist</p>";
    }
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>❌ Database Error: " . $exception->getMessage() . "</p>";
}
?>
