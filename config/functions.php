<?php
// Helper Functions

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

function is_teacher() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'teacher';
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function format_time($time) {
    return date('h:i A', strtotime($time));
}

function generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $new_filename;
    }

    return false;
}

function send_notification($user_id, $title, $message, $type = 'info') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (:user_id, :title, :message, :type, NOW())";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        
        return $stmt->execute();
    } catch(PDOException $exception) {
        return false;
    }
}

function get_unread_notifications($user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $exception) {
        return [];
    }
}

function mark_notification_read($notification_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $notification_id);
        
        return $stmt->execute();
    } catch(PDOException $exception) {
        return false;
    }
}
?>
