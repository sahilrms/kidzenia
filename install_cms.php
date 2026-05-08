<?php
require_once 'config/config.php';

echo "<h1>Kidzenia Kindergarten - CMS Installation</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Create homepage_cms table
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS `homepage_cms` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `section` varchar(50) NOT NULL,
      `content_key` varchar(100) NOT NULL,
      `content_type` enum('text','textarea','image','url','number','boolean') NOT NULL,
      `content_value` text DEFAULT NULL,
      `image_path` varchar(255) DEFAULT NULL,
      `is_active` tinyint(1) DEFAULT 1,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_section_key` (`section`,`content_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($create_table_query);
    echo "<p style='color: green;'>✓ Homepage CMS table created</p>";
    
    // Check if data already exists
    $check_query = "SELECT COUNT(*) as count FROM homepage_cms";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo "<p style='color: blue;'>ℹ CMS table already has {$count} entries</p>";
    } else {
        // Insert default content
        $default_content = [
            // Header Section
            ['header', 'logo_text', 'text', 'Kidzenia', NULL],
            ['header', 'logo_text_span', 'text', 'Kindergarten', NULL],
            ['header', 'admin_login_text', 'text', 'Admin Login', NULL],
            
            // Hero Section
            ['hero', 'tag_text', 'text', 'Trusted Kindergarten For Little Learners', NULL],
            ['hero', 'tag_icon', 'text', 'fa-solid fa-star text-warning', NULL],
            ['hero', 'main_heading', 'text', 'Where', NULL],
            ['hero', 'main_heading_span', 'text', 'Curiosity', NULL],
            ['hero', 'main_heading_continued', 'text', 'Becomes Creativity', NULL],
            ['hero', 'hero_description', 'textarea', 'A joyful learning environment where children grow through imagination, play, discovery, and meaningful experiences designed for early childhood development.', NULL],
            ['hero', 'hero_image_path', 'image', 'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=1200&auto=format&fit=crop', NULL],
            ['hero', 'cta_button_text', 'text', 'Start Admission', NULL],
            ['hero', 'secondary_button_text', 'text', 'Explore Programs', NULL],
            ['hero', 'floating_card_1_title', 'text', 'Creative Learning', NULL],
            ['hero', 'floating_card_1_description', 'text', 'Interactive & playful education', NULL],
            ['hero', 'floating_card_1_icon', 'text', '🎨', NULL],
            ['hero', 'floating_card_2_title', 'text', 'Smart Transport', NULL],
            ['hero', 'floating_card_2_description', 'text', 'Live GPS tracking for parents', NULL],
            ['hero', 'floating_card_2_icon', 'text', '🚌', NULL],
            
            // Statistics
            ['stats', 'years_number', 'number', '12', NULL],
            ['stats', 'years_label', 'text', 'Years', NULL],
            ['stats', 'students_number', 'number', '850', NULL],
            ['stats', 'students_label', 'text', 'Students', NULL],
            ['stats', 'teachers_number', 'number', '40', NULL],
            ['stats', 'teachers_label', 'text', 'Teachers', NULL],
            
            // Features Section
            ['features', 'section_title', 'text', 'Why Choose Us', NULL],
            ['features', 'section_subtitle', 'text', 'Building Bright Futures', NULL],
            ['features', 'feature_1_title', 'text', 'Creative Programs', NULL],
            ['features', 'feature_1_description', 'textarea', 'Hands-on learning experiences that inspire creativity and imagination.', NULL],
            ['features', 'feature_1_icon', 'text', 'fa-solid fa-palette', NULL],
            ['features', 'feature_2_title', 'text', 'Safe Environment', NULL],
            ['features', 'feature_2_description', 'textarea', 'Secure campus with child-friendly infrastructure and caring educators.', NULL],
            ['features', 'feature_2_icon', 'text', 'fa-solid fa-heart', NULL],
            ['features', 'feature_3_title', 'text', 'Smart Curriculum', NULL],
            ['features', 'feature_3_description', 'textarea', 'Balanced academics, social development, and playful exploration.', NULL],
            ['features', 'feature_3_icon', 'text', 'fa-solid fa-book-open', NULL],
            ['features', 'feature_4_title', 'text', 'Live Tracking', NULL],
            ['features', 'feature_4_description', 'textarea', 'Parents stay connected with real-time transport monitoring.', NULL],
            ['features', 'feature_4_icon', 'text', 'fa-solid fa-bus', NULL],
            
            // Programs Section
            ['programs', 'section_title', 'text', 'Programs', NULL],
            ['programs', 'section_subtitle', 'text', 'Learning By Age', NULL],
            ['programs', 'program_1_title', 'text', 'Toddler Program', NULL],
            ['programs', 'program_1_age', 'text', 'Age 2 - 3', NULL],
            ['programs', 'program_1_description', 'textarea', 'Focus on sensory exploration, social interaction, and foundational communication skills.', NULL],
            ['programs', 'program_1_image', 'image', 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?q=80&w=1200&auto=format&fit=crop', NULL],
            ['programs', 'program_2_title', 'text', 'Nursery Program', NULL],
            ['programs', 'program_2_age', 'text', 'Age 3 - 4', NULL],
            ['programs', 'program_2_description', 'textarea', 'Interactive learning through storytelling, art, music, and engaging activities.', NULL],
            ['programs', 'program_2_image', 'image', 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=1200&auto=format&fit=crop', NULL],
            ['programs', 'program_3_title', 'text', 'Kindergarten', NULL],
            ['programs', 'program_3_age', 'text', 'Age 4 - 5', NULL],
            ['programs', 'program_3_description', 'textarea', 'School readiness program focused on confidence, creativity, and communication.', NULL],
            ['programs', 'program_3_image', 'image', 'https://images.unsplash.com/photo-1513258496099-48168024aec0?q=80&w=1200&auto=format&fit=crop', NULL],
            
            // CTA Section
            ['cta', 'section_title', 'text', 'Give Your Child The Best Start', NULL],
            ['cta', 'section_description', 'textarea', 'Join a nurturing environment where every child is celebrated, encouraged, and inspired to grow.', NULL],
            ['cta', 'button_text', 'text', 'Apply For Admission', NULL],
            
            // Gallery Section
            ['gallery', 'section_title', 'text', 'Gallery', NULL],
            ['gallery', 'section_subtitle', 'text', 'Moments Of Joy', NULL],
            
            // Contact Section
            ['contact', 'section_title', 'text', 'Contact Us', NULL],
            ['contact', 'section_subtitle', 'text', 'Get In Touch', NULL],
            ['contact', 'contact_description', 'textarea', 'Ready to give your child the best start? We\'d love to hear from you!', NULL],
            ['contact', 'contact_button_text', 'text', 'Contact Form', NULL],
            ['contact', 'phone_button_text', 'text', 'Call Us', NULL],
            ['contact', 'phone_number', 'text', '+91 9876543210', NULL],
            
            // Footer
            ['footer', 'school_name', 'text', 'Kidzenia Kindergarten', NULL],
            ['footer', 'school_description', 'textarea', 'Creating joyful learning experiences for children through creativity, care, and innovation.', NULL],
            ['footer', 'address', 'text', '123 Education Street, Learning City', NULL],
            ['footer', 'phone', 'text', '+91 9876543210', NULL],
            ['footer', 'email', 'text', 'hello@kidzenia.com', NULL],
            ['footer', 'facebook_url', 'url', '#', NULL],
            ['footer', 'instagram_url', 'url', '#', NULL],
            ['footer', 'youtube_url', 'url', '#', NULL],
            ['footer', 'linkedin_url', 'url', '#', NULL],
            
            // Navigation
            ['nav', 'link_home', 'text', 'Home', NULL],
            ['nav', 'link_programs', 'text', 'Programs', NULL],
            ['nav', 'link_about', 'text', 'About', NULL],
            ['nav', 'link_gallery', 'text', 'Gallery', NULL],
            ['nav', 'link_events', 'text', 'Events', NULL],
            ['nav', 'link_contact', 'text', 'Contact', NULL],
        ];
        
        $insert_query = "INSERT INTO homepage_cms (section, content_key, content_type, content_value, image_path) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        $inserted_count = 0;
        foreach ($default_content as $content) {
            try {
                $insert_stmt->execute($content);
                $inserted_count++;
            } catch(PDOException $e) {
                echo "<p style='color: orange;'>⚠ Warning: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p style='color: green;'>✓ Inserted {$inserted_count} default CMS entries</p>";
    }
    
    // Create uploads directory
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
        echo "<p style='color: green;'>✓ Created uploads directory</p>";
    }
    
    if (!is_dir('uploads/homepage')) {
        mkdir('uploads/homepage', 0755, true);
        echo "<p style='color: green;'>✓ Created homepage uploads directory</p>";
    }
    
    // Verify installation
    $verify_query = "SELECT COUNT(*) as count FROM homepage_cms WHERE is_active = 1";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute();
    $active_count = $verify_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h3 style='color: green;'>✅ CMS Installation Complete!</h3>";
    echo "<p><strong>Active CMS Entries:</strong> {$active_count}</p>";
    echo "<p><strong>Database:</strong> Connected and ready</p>";
    echo "<p><strong>Upload Directory:</strong> uploads/homepage/</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='admin/homepage_cms.php' target='_blank'>📝 Open Homepage CMS Admin</a></li>";
    echo "<li><a href='index.php' target='_blank'>🏠 View Updated Homepage</a></li>";
    echo "<li><a href='setup_gallery.php' target='_blank'>🖼️ Setup Gallery Content</a></li>";
    echo "</ol>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h4>🎉 Success! Your CMS is now ready!</h4>";
    echo "<p>You can now manage every aspect of your homepage through the admin panel.</p>";
    echo "<p>All text content, images, statistics, and navigation items are fully editable.</p>";
    echo "</div>";
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>❌ Database Error: " . $exception->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
    echo "<p>Make sure the database 'kidzenia_db' exists and your credentials are correct.</p>";
}
?>
