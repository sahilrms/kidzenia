<?php
require_once 'config/config.php';

echo "<h1>Setup CMS History System</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Create cms_history table
    $create_history_query = "
    CREATE TABLE IF NOT EXISTS `cms_history` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `section` varchar(50) NOT NULL,
      `content_key` varchar(100) NOT NULL,
      `old_value` text DEFAULT NULL,
      `new_value` text DEFAULT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($create_history_query);
    echo "<p style='color: green;'>✓ CMS history table created</p>";
    
    // Add status and draft_value columns to homepage_cms table if they don't exist
    $alter_query1 = "
    ALTER TABLE `homepage_cms` 
    ADD COLUMN IF NOT EXISTS `status` enum('draft','published','live') DEFAULT 'live' AFTER `is_active`";
    
    $alter_query2 = "
    ALTER TABLE `homepage_cms` 
    ADD COLUMN IF NOT EXISTS `draft_value` text DEFAULT NULL AFTER `content_value`";
    
    $alter_query3 = "
    ALTER TABLE `homepage_cms` 
    ADD COLUMN IF NOT EXISTS `published_value` text DEFAULT NULL AFTER `draft_value`";
    
    $alter_query4 = "
    ALTER TABLE `homepage_cms` 
    ADD COLUMN IF NOT EXISTS `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `published_value`";
    
    $db->exec($alter_query1);
    $db->exec($alter_query2);
    $db->exec($alter_query3);
    $db->exec($alter_query4);
    echo "<p style='color: green;'>✓ Homepage CMS table updated with status columns</p>";
    
    // Add status column to dynamic_cards table if it doesn't exist
    $alter_cards_query = "
    ALTER TABLE `dynamic_cards` 
    ADD COLUMN IF NOT EXISTS `status` enum('draft','published','live') DEFAULT 'live' AFTER `is_active`";
    
    $db->exec($alter_cards_query);
    echo "<p style='color: green;'>✓ Dynamic cards table updated with status column</p>";
    
    echo "<h3 style='color: green;'>✅ CMS History System Setup Complete!</h3>";
    echo "<p><strong>Features Added:</strong></p>";
    echo "<ul>";
    echo "<li>✓ Draft/Publish system for content management</li>";
    echo "<li>✓ Undo functionality with change history tracking</li>";
    echo "<li>✓ Status tracking (draft/published/live)</li>";
    echo "<li>✓ Automatic timestamp tracking</li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='visual_homepage_cms.php' target='_blank'>🎨 Open Enhanced Visual CMS</a></li>";
    echo "<li><a href='../index.php' target='_blank'>🏠 View Homepage</a></li>";
    echo "</ol>";
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>❌ Database Error: " . $exception->getMessage() . "</p>";
}
?>
