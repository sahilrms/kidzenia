<?php
// Fetch approved reviews from database
header('Content-Type: application/json');

require_once 'config/database.php';
require_once 'config/feedback.php';

try {
    ob_start();
    $database = new Database();
    $db = $database->getConnection();
    ob_end_clean();
    
    if (!$db) {
        throw new RuntimeException('Database connection failed');
    }

    ensure_feedback_table($db);
    
    // Get only approved feedbacks (reviews)
    $query = "SELECT name, subject, message, rating FROM feedbacks WHERE status = 'approved' ORDER BY created_at DESC LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'reviews' => $reviews]);
    
} catch (Throwable $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load reviews right now.']);
}
?>
