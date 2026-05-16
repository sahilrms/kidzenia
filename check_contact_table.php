<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=kidzenia_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "Table contact_messages exists.\n";
        
        // Check count
        $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages");
        $count = $stmt->fetchColumn();
        echo "Number of messages: $count\n";
        
        // Show sample data
        if ($count > 0) {
            $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\nSample messages:\n";
            foreach ($messages as $msg) {
                echo "ID: {$msg['id']}, Name: {$msg['name']}, Subject: {$msg['subject']}, Status: {$msg['status']}\n";
            }
        }
    } else {
        echo "Table contact_messages does NOT exist.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
