<?php
require_once 'config/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . 'auth/login.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get contact form messages
    $contact_query = "SELECT * FROM contact_messages ORDER BY created_at DESC";
    $contact_stmt = $db->prepare($contact_query);
    $contact_stmt->execute();
    $contact_messages = $contact_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Contact Messages Debug</h2>";
    echo "<p>Total messages: " . count($contact_messages) . "</p>";
    
    if (!empty($contact_messages)) {
        echo "<pre>";
        print_r($contact_messages);
        echo "</pre>";
    } else {
        echo "<p>No messages found</p>";
    }
    
} catch(PDOException $exception) {
    echo "Error: " . $exception->getMessage();
}
?>
