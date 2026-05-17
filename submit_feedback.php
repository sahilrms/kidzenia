<?php
// Feedback Submission Handler
header('Content-Type: application/json');

require_once 'config/database.php';
require_once 'config/feedback.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    try {
        ob_start();
        $database = new Database();
        $db = $database->getConnection();
        ob_end_clean();
        
        if (!$db) {
            throw new RuntimeException('Database connection failed');
        }

        ensure_feedback_table($db);
        
        // Get and sanitize input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        
        // Validate input
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }
        
        // Insert feedback into database
        $query = "INSERT INTO feedbacks (name, email, subject, message, rating, status) 
                  VALUES (:name, :email, :subject, :message, :rating, 'pending')";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
        }
        
    } catch (Throwable $e) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to submit feedback right now. Please try again later.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
