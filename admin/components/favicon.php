<?php
// Get favicon from CMS
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $favicon_query = "SELECT image_path, content_value FROM homepage_cms WHERE section = 'header' AND content_key = 'favicon' AND is_active = 1";
    $favicon_stmt = $db->prepare($favicon_query);
    $favicon_stmt->execute();
    $favicon_data = $favicon_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($favicon_data) {
        $favicon = $favicon_data['image_path'] ?? $favicon_data['content_value'] ?? '';
        if (!empty($favicon)) {
            if (strpos($favicon, 'http') === 0) {
                $favicon_src = $favicon;
            } else {
                $favicon_src = '../uploads/homepage/' . $favicon;
            }
            echo '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($favicon_src) . '">';
        }
    }
} catch(PDOException $e) {
    // Silently fail if favicon query fails
}
?>
