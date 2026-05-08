<?php
require_once 'config/config.php';

echo "<h1>Add Logo Image Field to CMS</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Add logo_image field to header section
    $query = "INSERT INTO homepage_cms (section, content_key, content_type, content_value, image_path, is_active) 
              VALUES ('header', 'logo_image', 'image', NULL, NULL, 1)
              ON DUPLICATE KEY UPDATE content_type = 'image', is_active = 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<p style='color: green;'>✓ Logo image field added to CMS</p>";
    echo "<p><a href='admin/visual_homepage_cms.php'>Go to Visual CMS</a></p>";
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>Error: " . $exception->getMessage() . "</p>";
}
?>
