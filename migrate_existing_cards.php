<?php
require_once 'config/config.php';

echo "<h1>Migrate Existing Cards to Dynamic System</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if dynamic_cards table is empty
    $check_query = "SELECT COUNT(*) as count FROM dynamic_cards";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "<p style='color: blue;'>ℹ Dynamic cards table is empty. Migrating existing CMS data...</p>";
        
        // Get existing feature data from homepage_cms
        $features_query = "SELECT * FROM homepage_cms WHERE section = 'features' AND content_key LIKE 'feature_%' AND is_active = 1 ORDER BY content_key";
        $features_stmt = $db->prepare($features_query);
        $features_stmt->execute();
        $features = $features_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group features by card number
        $feature_cards = [];
        foreach ($features as $feature) {
            if (preg_match('/feature_(\d+)_(.+)/', $feature['content_key'], $matches)) {
                $card_num = $matches[1];
                $field = $matches[2];
                
                if (!isset($feature_cards[$card_num])) {
                    $feature_cards[$card_num] = [
                        'title' => '',
                        'description' => '',
                        'badge' => '',
                        'icon_type' => 'fa',
                        'icon_value' => 'fa-solid fa-star',
                        'sort_order' => $card_num
                    ];
                }
                
                if ($field == 'title') {
                    $feature_cards[$card_num]['title'] = $feature['content_value'];
                } elseif ($field == 'description') {
                    $feature_cards[$card_num]['description'] = $feature['content_value'];
                } elseif ($field == 'icon') {
                    $feature_cards[$card_num]['icon_value'] = $feature['content_value'];
                }
            }
        }
        
        // Insert feature cards
        foreach ($feature_cards as $sort_order => $card) {
            $insert_query = "INSERT INTO dynamic_cards (section, title, description, icon_type, icon_value, sort_order, is_active, created_at) 
                              VALUES ('features', ?, ?, 'fa', ?, ?, 1, NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->execute([
                $card['title'],
                $card['description'],
                $card['icon_value'],
                $sort_order
            ]);
        }
        
        echo "<p style='color: green;'>✓ Migrated " . count($feature_cards) . " feature cards</p>";
        
        // Get existing program data from homepage_cms
        $programs_query = "SELECT * FROM homepage_cms WHERE section = 'programs' AND content_key LIKE 'program_%' AND is_active = 1 ORDER BY content_key";
        $programs_stmt = $db->prepare($programs_query);
        $programs_stmt->execute();
        $programs = $programs_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group programs by card number
        $program_cards = [];
        foreach ($programs as $program) {
            if (preg_match('/program_(\d+)_(.+)/', $program['content_key'], $matches)) {
                $card_num = $matches[1];
                $field = $matches[2];
                
                if (!isset($program_cards[$card_num])) {
                    $program_cards[$card_num] = [
                        'title' => '',
                        'description' => '',
                        'badge' => '',
                        'icon_type' => 'image',
                        'icon_value' => '',
                        'sort_order' => $card_num
                    ];
                }
                
                if ($field == 'title') {
                    $program_cards[$card_num]['title'] = $program['content_value'];
                } elseif ($field == 'description') {
                    $program_cards[$card_num]['description'] = $program['content_value'];
                } elseif ($field == 'age') {
                    $program_cards[$card_num]['badge'] = $program['content_value'];
                } elseif ($field == 'image') {
                    $program_cards[$card_num]['icon_value'] = $program['image_path'] ?? $program['content_value'];
                }
            }
        }
        
        // Insert program cards
        foreach ($program_cards as $sort_order => $card) {
            $insert_query = "INSERT INTO dynamic_cards (section, title, description, badge, icon_type, icon_value, sort_order, is_active, created_at) 
                              VALUES ('programs', ?, ?, ?, 'image', ?, ?, 1, NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->execute([
                $card['title'],
                $card['description'],
                $card['badge'],
                $card['icon_value'],
                $sort_order
            ]);
        }
        
        echo "<p style='color: green;'>✓ Migrated " . count($program_cards) . " program cards</p>";
        
    } else {
        echo "<p style='color: orange;'>ℹ Dynamic cards table already has data. No migration needed.</p>";
    }
    
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
