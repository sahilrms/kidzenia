<?php
require_once 'config/config.php';

echo "<h1>Add Favicon Field to CMS</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Add favicon field to header section
    $query = "INSERT INTO homepage_cms (section, content_key, content_type, content_value, image_path, is_active) 
              VALUES ('header', 'favicon', 'image', NULL, NULL, 1)
              ON DUPLICATE KEY UPDATE content_type = 'image', is_active = 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<p style='color: green;'>✓ Favicon field added to CMS</p>";
    echo "<p><a href='admin/homepage_cms.php'>Go to Homepage CMS</a></p>";
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>Error: " . $exception->getMessage() . "</p>";
}
?>
