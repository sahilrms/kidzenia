<?php
require_once 'config/config.php';

echo "<h1>Kidzenia Kindergarten - Gallery Setup</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if gallery table exists and has data
    $check_query = "SELECT COUNT(*) as count FROM gallery";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $gallery_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($gallery_count > 0) {
        echo "<p style='color: green;'>✓ Gallery already has {$gallery_count} entries</p>";
        
        // Display current gallery entries
        $display_query = "SELECT * FROM gallery ORDER BY created_at DESC LIMIT 10";
        $display_stmt = $db->prepare($display_query);
        $display_stmt->execute();
        $gallery_items = $display_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Gallery Entries:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Category</th><th>Image Path</th><th>Created</th></tr>";
        
        foreach ($gallery_items as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>" . htmlspecialchars($item['title'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($item['category'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($item['image_path'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($item['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: orange;'>⚠ No gallery entries found. Creating dummy entries...</p>";
        
        // Create dummy gallery entries
        $dummy_entries = [
            [
                'title' => 'Creative Art Session',
                'category' => 'classroom',
                'description' => 'Students expressing their creativity through colorful paintings and drawings.',
                'image_path' => 'art_session.jpg'
            ],
            [
                'title' => 'Outdoor Play Time',
                'category' => 'playground',
                'description' => 'Children enjoying outdoor activities and developing social skills.',
                'image_path' => 'outdoor_play.jpg'
            ],
            [
                'title' => 'Story Time Circle',
                'category' => 'classroom',
                'description' => 'Engaging storytelling session that enhances listening and imagination.',
                'image_path' => 'story_time.jpg'
            ],
            [
                'title' => 'Music and Movement',
                'category' => 'activity',
                'description' => 'Children learning rhythm and coordination through music and dance.',
                'image_path' => 'music_class.jpg'
            ],
            [
                'title' => 'Science Exploration',
                'category' => 'classroom',
                'description' => 'Little scientists discovering the wonders of the natural world.',
                'image_path' => 'science_fun.jpg'
            ],
            [
                'title' => 'Sports Day Activities',
                'category' => 'events',
                'description' => 'Annual sports day with various fun activities for all age groups.',
                'image_path' => 'sports_day.jpg'
            ],
            [
                'title' => 'Birthday Celebration',
                'category' => 'events',
                'description' => 'Celebrating birthdays with cake, songs, and fun activities.',
                'image_path' => 'birthday_fun.jpg'
            ],
            [
                'title' => 'Nature Walk',
                'category' => 'outdoor',
                'description' => 'Exploring nature and learning about plants and insects.',
                'image_path' => 'nature_walk.jpg'
            ],
            [
                'title' => 'Building Blocks',
                'category' => 'classroom',
                'description' => 'Developing motor skills and creativity with building blocks.',
                'image_path' => 'block_play.jpg'
            ],
            [
                'title' => 'Water Play',
                'category' => 'activity',
                'description' => 'Fun water activities that teach basic science concepts.',
                'image_path' => 'water_play.jpg'
            ],
            [
                'title' => 'Drama Performance',
                'category' => 'events',
                'description' => 'Students performing in their first school drama production.',
                'image_path' => 'drama_show.jpg'
            ],
            [
                'title' => 'Gardening Activity',
                'category' => 'outdoor',
                'description' => 'Children learning about plants by helping in the school garden.',
                'image_path' => 'gardening.jpg'
            ]
        ];
        
        foreach ($dummy_entries as $entry) {
            $query = "INSERT INTO gallery (title, category, description, image_path, is_active, created_at) 
                      VALUES (:title, :category, :description, :image_path, 1, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $entry['title']);
            $stmt->bindParam(':category', $entry['category']);
            $stmt->bindParam(':description', $entry['description']);
            $stmt->bindParam(':image_path', $entry['image_path']);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Added: {$entry['title']}</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to add: {$entry['title']}</p>";
            }
        }
        
        echo "<h3 style='color: green;'>Setup Complete!</h3>";
        echo "<p>Created " . count($dummy_entries) . " dummy gallery entries.</p>";
        echo "<p><a href='index.php'>View Gallery on Website</a></p>";
    }
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>Database Error: " . $exception->getMessage() . "</p>";
    echo "<p>Please ensure the gallery table exists in your database.</p>";
}
?>

<hr>
<h3>Upload Real Images</h3>
<p>To use real images instead of placeholder names:</p>
<ol>
    <li>Upload your images to the <code>uploads/gallery/</code> directory</li>
    <li>Update the image_path in the database to match your file names</li>
    <li>Or use the admin panel at <a href="admin/gallery.php">Admin Gallery</a> to upload images</li>
</ol>

<p><strong>Note:</strong> The carousel will work with any images. If image files don't exist, it will show placeholder images.</p>
