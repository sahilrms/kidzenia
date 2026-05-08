<?php
require_once 'config/config.php';

echo "<h1>Logo Upload Debug Test</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if logo_image field exists
    $query = "SELECT * FROM homepage_cms WHERE section = 'header' AND content_key = 'logo_image'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Logo image field exists in database</p>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>✗ Logo image field not found</p>";
        
        // Create the field
        $insert_query = "INSERT INTO homepage_cms (section, content_key, content_type, content_value, image_path, is_active) 
                         VALUES ('header', 'logo_image', 'image', NULL, NULL, 1)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute();
        echo "<p style='color: green;'>✓ Created logo image field</p>";
    }
    
    // Test upload directory
    $upload_dir = '../uploads/homepage';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "<p style='color: green;'>✓ Created upload directory</p>";
    } else {
        echo "<p style='color: green;'>✓ Upload directory exists</p>";
    }
    
    // Check permissions
    if (is_writable($upload_dir)) {
        echo "<p style='color: green;'>✓ Upload directory is writable</p>";
    } else {
        echo "<p style='color: red;'>✗ Upload directory is not writable</p>";
    }
    
    echo "<h3>Test Upload Form</h3>";
    echo "<form method='POST' enctype='multipart/form-data'>";
    echo "<input type='file' name='test_logo' accept='image/*' required>";
    echo "<button type='submit' name='test_upload'>Test Upload</button>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_upload'])) {
        if (isset($_FILES['test_logo']) && $_FILES['test_logo']['error'] == 0) {
            $file = $_FILES['test_logo'];
            $filename = time() . '_' . basename($file['name']);
            $target_path = $upload_dir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                echo "<p style='color: green;'>✓ File uploaded successfully: {$filename}</p>";
                echo "<img src='{$target_path}' style='max-width: 200px;'>";
                
                // Update database
                $update_query = "UPDATE homepage_cms SET image_path = :image_path, updated_at = NOW() WHERE section = 'header' AND content_key = 'logo_image'";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':image_path', $filename);
                $update_stmt->execute();
                
                echo "<p style='color: green;'>✓ Database updated</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to move uploaded file</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Upload error: " . $_FILES['test_logo']['error'] . "</p>";
        }
    }
    
} catch(PDOException $exception) {
    echo "<p style='color: red;'>Database Error: " . $exception->getMessage() . "</p>";
}
?>
