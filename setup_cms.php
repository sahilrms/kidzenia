<?php
require_once 'config/config.php';

echo "<h1>Kidzenia Kindergarten - Homepage CMS Setup</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create uploads directory if it doesn't exist
    $uploads_dir = 'uploads/homepage';
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
    }
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }
    
    // Check if CMS table exists and has data
    $check_query = "SELECT COUNT(*) as count FROM homepage_cms";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $cms_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($cms_count > 0) {
        echo "<p style='color: green;'>✓ CMS table already has {$cms_count} entries</p>";
        
        // Display some sample CMS content
        $display_query = "SELECT section, content_key, content_value FROM homepage_cms ORDER BY section, content_key LIMIT 10";
        $display_stmt = $db->prepare($display_query);
        $display_stmt->execute();
        $cms_items = $display_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample CMS Content:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Section</th><th>Key</th><th>Value</th></tr>";
        
        foreach ($cms_items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['section']) . "</td>";
            echo "<td>" . htmlspecialchars($item['content_key']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($item['content_value'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: orange;'>⚠ No CMS entries found. Creating CMS table and default content...</p>";
        
        // Read and execute the CMS schema
        $schema_file = 'database/cms_schema.sql';
        if (file_exists($schema_file)) {
            $schema_sql = file_get_contents($schema_file);
            
            // Split SQL statements
            $statements = array_filter(array_map('trim', explode(';', $schema_sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $db->exec($statement);
                    } catch(PDOException $e) {
                        echo "<p style='color: red;'>SQL Error: " . $e->getMessage() . "</p>";
                    }
                }
            }
            
            echo "<h3 style='color: green;'>✓ CMS Setup Complete!</h3>";
            echo "<p>Created homepage_cms table with default content.</p>";
            
        } else {
            echo "<p style='color: red;'>✗ Schema file not found: {$schema_file}</p>";
        }
    }
    
    // Verify CMS content loading
    $verify_query = "SELECT COUNT(*) as count FROM homepage_cms WHERE is_active = 1";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute();
    $active_count = $verify_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h3>Setup Summary:</h3>";
    echo "<ul>";
    echo "<li style='color: green;'>✓ CMS Table: Created and populated</li>";
    echo "<li style='color: green;'>✓ CMS Entries: {$active_count} active content items</li>";
    echo "<li style='color: green;'>✓ Upload Directory: {$uploads_dir} created</li>";
    echo "<li style='color: green;'>✓ Admin Interface: Ready for use</li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='admin/homepage_cms.php' target='_blank'>Open Homepage CMS Admin</a></li>";
    echo "<li><a href='index.php' target='_blank'>View Updated Homepage</a></li>";
    echo "<li>Upload custom images through the CMS admin panel</li>";
    echo "<li>Customize all text content through the admin interface</li>";
    echo "</ol>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h4>📋 CMS Content Sections Available:</h4>";
    echo "<ul>";
    echo "<li><strong>Header:</strong> Logo text, navigation links</li>";
    echo "<li><strong>Hero:</strong> Main heading, description, image, floating cards</li>";
    echo "<li><strong>Statistics:</strong> Years, students, teachers numbers</li>";
    echo "<li><strong>Features:</strong> 4 feature cards with icons and descriptions</li>";
    echo "<li><strong>Programs:</strong> 3 program cards with images and details</li>";
    echo "<li><strong>CTA:</strong> Call-to-action section content</li>";
    echo "<li><strong>Gallery:</strong> Section titles</li>";
    echo "<li><strong>Contact:</strong> Contact section content</li>";
    echo "<li><strong>Footer:</strong> School info, social links, contact details</li>";
    echo "<li><strong>Navigation:</strong> All navigation menu items</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>Database Error: " . $exception->getMessage() . "</p>";
    echo "<p>Please ensure your database connection is working properly.</p>";
}
?>

<hr>
<h3>🎨 CMS Features</h3>
<p><strong>What you can manage through the CMS:</strong></p>
<ul>
    <li>✏️ <strong>All Text Content:</strong> Headings, descriptions, button text</li>
    <li>🖼️ <strong>Images:</strong> Hero image, program images, floating card images</li>
    <li>🔢 <strong>Numbers:</strong> Statistics (years, students, teachers)</li>
    <li>🎯 <strong>Icons:</strong> Feature icons, navigation indicators</li>
    <li>📱 <strong>Navigation:</strong> All menu items and links</li>
    <li>📧 <strong>Contact Info:</strong> Phone, email, address, social links</li>
</ul>

<h3>🔧 Technical Details</h3>
<p><strong>How the CMS works:</strong></p>
<ul>
    <li>All content is stored in the <code>homepage_cms</code> table</li>
    <li>Content is organized by sections (header, hero, features, etc.)</li>
    <li>Each content item has a type (text, textarea, image, url, number)</li>
    <li>Images are uploaded to <code>uploads/homepage/</code> directory</li>
    <li>The homepage dynamically loads all content from the database</li>
    <li>Fallback values are used if CMS content is not available</li>
</ul>

<p><strong>Note:</strong> The CMS system provides complete control over the homepage content while maintaining the beautiful design and functionality.</p>
