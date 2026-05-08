<?php
require_once 'config/config.php';

echo "<h1>Setup Dynamic Cards System</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Create dynamic_cards table
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS `dynamic_cards` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `section` varchar(50) NOT NULL,
      `title` text NOT NULL,
      `description` text DEFAULT NULL,
      `badge` varchar(100) DEFAULT NULL,
      `icon_type` enum('fa','image') DEFAULT 'fa',
      `icon_value` text DEFAULT NULL,
      `sort_order` int(11) DEFAULT 0,
      `is_active` tinyint(1) DEFAULT 1,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($create_table_query);
    echo "<p style='color: green;'>✓ Dynamic cards table created</p>";
    
    // Check if we need to migrate existing data
    $check_query = "SELECT COUNT(*) as count FROM dynamic_cards";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "<p style='color: blue;'>ℹ Migrating existing feature cards to dynamic system...</p>";
        
        // Get existing feature data from homepage_cms
        $features_query = "SELECT * FROM homepage_cms WHERE section = 'features' AND content_key LIKE 'feature_%'";
        $features_stmt = $db->prepare($features_query);
        $features_stmt->execute();
        $features = $features_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group features by card number
        $cards = [];
        foreach ($features as $feature) {
            preg_match('/feature_(\d+)_(.*)/', $feature['content_key'], $matches);
            if ($matches) {
                $card_num = $matches[1];
                $field = $matches[2];
                $cards[$card_num][$field] = $feature;
            }
        }
        
        // Insert cards into dynamic_cards table
        foreach ($cards as $card_num => $card_data) {
            $title = $card_data['title']['content_value'] ?? "Feature {$card_num}";
            $description = $card_data['description']['content_value'] ?? '';
            $icon_value = $card_data['icon']['content_value'] ?? 'fa-solid fa-star';
            
            $insert_query = "INSERT INTO dynamic_cards (section, title, description, icon_type, icon_value, sort_order) 
                           VALUES ('features', :title, :description, 'fa', :icon_value, :sort_order)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':title', $title);
            $insert_stmt->bindParam(':description', $description);
            $insert_stmt->bindParam(':icon_value', $icon_value);
            $insert_stmt->bindParam(':sort_order', $card_num);
            $insert_stmt->execute();
        }
        
        echo "<p style='color: green;'>✓ Migrated " . count($cards) . " feature cards</p>";
        
        // Migrate program cards
        $programs_query = "SELECT * FROM homepage_cms WHERE section = 'programs' AND content_key LIKE 'program_%'";
        $programs_stmt = $db->prepare($programs_query);
        $programs_stmt->execute();
        $programs = $programs_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $program_cards = [];
        foreach ($programs as $program) {
            preg_match('/program_(\d+)_(.*)/', $program['content_key'], $matches);
            if ($matches) {
                $card_num = $matches[1];
                $field = $matches[2];
                $program_cards[$card_num][$field] = $program;
            }
        }
        
        foreach ($program_cards as $card_num => $card_data) {
            $title = $card_data['title']['content_value'] ?? "Program {$card_num}";
            $description = $card_data['description']['content_value'] ?? '';
            $badge = $card_data['age']['content_value'] ?? '';
            $image_path = $card_data['image']['image_path'] ?? '';
            
            $insert_query = "INSERT INTO dynamic_cards (section, title, description, badge, icon_type, icon_value, sort_order) 
                           VALUES ('programs', :title, :description, :badge, 'image', :icon_value, :sort_order)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':title', $title);
            $insert_stmt->bindParam(':description', $description);
            $insert_stmt->bindParam(':badge', $badge);
            $insert_stmt->bindParam(':icon_value', $image_path);
            $insert_stmt->bindParam(':sort_order', $card_num);
            $insert_stmt->execute();
        }
        
        echo "<p style='color: green;'>✓ Migrated " . count($program_cards) . " program cards</p>";
    }
    
    // Create uploads/icons directory
    if (!is_dir('../uploads/icons')) {
        mkdir('../uploads/icons', 0755, true);
        echo "<p style='color: green;'>✓ Created icons upload directory</p>";
    }
    
    // Verify setup
    $verify_query = "SELECT COUNT(*) as count FROM dynamic_cards WHERE is_active = 1";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute();
    $total_cards = $verify_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h3 style='color: green;'>✅ Dynamic Cards Setup Complete!</h3>";
    echo "<p><strong>Total Dynamic Cards:</strong> {$total_cards}</p>";
    echo "<p><strong>Features:</strong> Add/remove cards, upload icons, FontAwesome support</p>";
    echo "<p><strong>Programs:</strong> Add/remove cards, upload images, badge support</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='advanced_visual_cms.php' target='_blank'>🎨 Open Advanced Visual CMS</a></li>";
    echo "<li><a href='visual_homepage_cms.php' target='_blank'>📝 Open Basic Visual CMS</a></li>";
    echo "<li><a href='index.php' target='_blank'>🏠 View Homepage</a></li>";
    echo "</ol>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h4>🎯 New Features Available:</h4>";
    echo "<ul>";
    echo "<li><strong>Dynamic Card Management:</strong> Add/remove cards as needed</li>";
    echo "<li><strong>Dual Icon Support:</strong> Use FontAwesome classes OR upload images</li>";
    echo "<li><strong>Flexible Content:</strong> Edit titles, descriptions, badges</li>";
    echo "<li><strong>Visual Interface:</strong> See changes in real-time</li>";
    echo "<li><strong>Image Upload:</strong> Upload custom icons and program images</li>";
    echo "<li><strong>FontAwesome Integration:</strong> Use any FontAwesome icon</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>❌ Database Error: " . $exception->getMessage() . "</p>";
}
?>
