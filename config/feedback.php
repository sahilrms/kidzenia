<?php
// Shared helpers for the public feedback/reviews feature.

function ensure_feedback_table(PDO $db) {
    $query = "CREATE TABLE IF NOT EXISTS feedbacks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        rating INT DEFAULT 5,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_feedbacks_status (status),
        INDEX idx_feedbacks_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $db->exec($query);
}
?>
