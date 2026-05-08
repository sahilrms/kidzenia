<?php
require_once 'config/config.php';

echo "<h1>Fix Migration Script</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Get existing feature data from homepage_cms
    $features_query = "SELECT * FROM homepage_cms WHERE section = 'features' AND content_key LIKE 'feature_%' AND is_active = 1 ORDER BY content_key";
    $features_stmt = $db->prepare($features_query);
    $features_stmt->execute();
    $features = $features_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Insert features into dynamic_cards table
    $feature_count = 0;
    foreach ($features as $feature) {
        if (preg_match('/feature_(\d+)_(.+)/', $feature['content_key'], $matches)) {
            $card_num = $matches[1];
            $field = $matches[2];
            
            $title = '';
            $description = '';
            $icon_value = 'fa-solid fa-star';
            
            if ($field == 'title') {
                $title = $feature['content_value'];
            } elseif ($field == 'description') {
                $description = $feature['content_value'];
            } elseif ($field == 'icon') {
                $icon_value = $feature['content_value'];
            }
            
            if ($title || $description) {
                $insert_query = "INSERT INTO dynamic_cards (section, title, description, icon_type, icon_value, sort_order, is_active, created_at) 
                                  VALUES ('features', ?, ?, 'fa', ?, ?, 1, NOW())";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([$title, $description, $icon_value, $card_num]);
                $feature_count++;
            }
        }
    }
    
    echo "<p style='color: green;'>✓ Migrated {$feature_count} feature cards</p>";
    
    echo "<h3 style='color: green;'>✅ Migration Complete!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='visual_homepage_cms.php' target='_blank'>🎨 Open Visual CMS</a></li>";
    echo "<li><a href='../index.php' target='_blank'>🏠 View Homepage</a></li>";
    echo "</ol>";
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>❌ Database Error: " . $exception->getMessage() . "</p>";
}
?>
