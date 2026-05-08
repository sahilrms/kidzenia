<?php
// Site Configuration
define('SITE_URL', 'http://localhost/kidzenia/');
define('SITE_NAME', 'Kidzenia Kindergarten');
define('ADMIN_EMAIL', 'admin@kidzenia.com');
define('SUPPORT_EMAIL', 'support@kidzenia.com');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include Database and Functions
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
