<?php
require_once '../config/config.php';

// Destroy session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

flash_message('success', 'You have been logged out successfully.');
redirect(SITE_URL . 'auth/login.php');
?>
