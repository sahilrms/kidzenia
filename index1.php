<?php
require_once 'config/config.php';

// Get CMS content
function get_cms_content($db, $section = null, $content_key = null) {
    $query = "SELECT * FROM homepage_cms WHERE is_active = 1";
    $params = [];
    
    if ($section) {
        $query .= " AND section = :section";
        $params[':section'] = $section;
    }
    
    if ($content_key) {
        $query .= " AND content_key = :content_key";
        $params[':content_key'] = $content_key;
    }
    
    $query .= " ORDER BY section, content_key";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($content_key) {
        return $results[0] ?? null;
    }
    
    $content = [];
    foreach ($results as $row) {
        $content[$row['section']][$row['content_key']] = $row;
    }
    
    return $content;
}

// Get dynamic cards
function get_dynamic_cards($db, $section) {
    try {
        $query = "SELECT * FROM dynamic_cards WHERE section = :section AND is_active = 1 ORDER BY sort_order ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':section', $section);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $exception) {
        // Return empty array if table doesn't exist or has error
        return [];
    }
}

// Get all data from database
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get CMS content
    $cms_content = get_cms_content($db);
    
    // Get dynamic cards
    $feature_cards = get_dynamic_cards($db, 'features');
    $program_cards = get_dynamic_cards($db, 'programs');
    
    // Get latest announcements
    $query = "SELECT a.*, u.full_name as author_name FROM announcements a 
              LEFT JOIN users u ON a.author_id = u.id 
              WHERE a.is_active = 1 AND a.publish_date <= CURDATE() 
              AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
              ORDER BY a.created_at DESC LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get upcoming events
    $events_query = "SELECT * FROM events WHERE is_active = 1 AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3";
    $events_stmt = $db->prepare($events_query);
    $events_stmt->execute();
    $events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get gallery images
    $gallery_query = "SELECT * FROM gallery WHERE is_active = 1 ORDER BY created_at DESC LIMIT 6";
    $gallery_stmt = $db->prepare($gallery_query);
    $gallery_stmt->execute();
    $gallery_images = $gallery_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    $cms_content = [];
    $feature_cards = [];
    $program_cards = [];
    $announcements = [];
    $events = [];
    $gallery_images = [];
}

// Create dummy events if no events exist
if (empty($events)) {
    $events = [
        [
            'id' => 1,
            'title' => 'Annual Sports Day',
            'description' => 'Join us for a fun-filled day of sports activities and games for all our little champions. Parents are welcome to cheer for their children!',
            'event_date' => date('Y-m-d', strtotime('+2 weeks')),
            'location' => 'School Playground',
            'image_path' => null
        ],
        [
            'id' => 2,
            'title' => 'Parent-Teacher Meeting',
            'description' => 'Important meeting to discuss your child\'s progress and upcoming activities. Please make sure to attend this valuable session.',
            'event_date' => date('Y-m-d', strtotime('+1 week')),
            'location' => 'Conference Room',
            'image_path' => null
        ],
        [
            'id' => 3,
            'title' => 'Art & Craft Exhibition',
            'description' => 'Come and see the amazing artwork created by our talented students. Exhibition will showcase paintings, crafts, and creative projects.',
            'event_date' => date('Y-m-d', strtotime('+3 weeks')),
            'location' => 'School Auditorium',
            'image_path' => null
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kidzenia Kindergarten - Where Learning Begins with Joy</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    :root {
      --primary: #FF6B9D;
      --secondary: #FFA726;
      --accent: #4ECDC4;
      --accent2: #A78BFA;
      --accent3: #FFD93D;
      --accent4: #6BCB77;
      --dark: #2D1B69;
      --text: #5D5D7A;
      --white: #ffffff;
      --cream: #FFF8F0;
      --light-pink: #FFE4EC;
      --light-orange: #FFF3E0;
      --light-teal: #E0F7FA;
      --light-purple: #F3E8FF;
      --light-yellow: #FFFDE7;
      --light-green: #E8F5E9;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Nunito', sans-serif;
      color: var(--text);
      overflow-x: hidden;
      background: var(--cream);
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Fredoka', cursive;
      color: var(--dark);
      font-weight: 700;
    }

    /* ===== FUN BACKGROUND SHAPES ===== */
    .bg-shape {
      position: absolute;
      border-radius: 50%;
      z-index: 0;
      opacity: 0.15;
    }

    .bg-shape-1 {
      width: 300px; height: 300px;
      background: var(--primary);
      top: -100px; right: -50px;
      animation: floatShape 6s ease-in-out infinite;
    }

    .bg-shape-2 {
      width: 200px; height: 200px;
      background: var(--accent);
      bottom: -50px; left: -50px;
      animation: floatShape 8s ease-in-out infinite reverse;
    }

    .bg-shape-3 {
      width: 150px; height: 150px;
      background: var(--secondary);
      top: 40%; right: 10%;
      animation: floatShape 7s ease-in-out infinite;
      animation-delay: 1s;
    }

    @keyframes floatShape {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-30px) rotate(10deg); }
    }

    /* ===== WAVY DIVIDERS ===== */
    .wave-divider {
      position: absolute;
      bottom: 0; left: 0;
      width: 100%;
      overflow: hidden;
      line-height: 0;
    }

    .wave-divider svg {
      position: relative;
      display: block;
      width: calc(100% + 1.3px);
      height: 80px;
    }

    .wave-divider .shape-fill { fill: #FAFAFF; }

    /* ===== NAVBAR ===== */
    .navbar {
      padding: 15px 0;
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(15px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      border-bottom: 3px solid var(--primary);
    }

    .navbar-brand {
      font-family: 'Fredoka', cursive;
      font-size: 32px;
      font-weight: 700;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .navbar-brand span { color: var(--secondary); }

    .nav-link {
      font-weight: 700;
      margin: 0 8px;
      color: var(--dark);
      transition: 0.3s;
      font-size: 16px;
      border-radius: 30px;
      padding: 8px 18px !important;
    }

    .nav-link:hover {
      color: var(--primary);
      background: var(--light-pink);
    }

    .btn-theme {
      background: linear-gradient(135deg, var(--primary), #FF8FAB);
      color: white;
      padding: 12px 30px;
      border-radius: 50px;
      border: none;
      font-weight: 700;
      font-size: 16px;
      transition: 0.4s;
      box-shadow: 0 10px 30px rgba(255,107,157,0.35);
      position: relative;
      overflow: hidden;
    }

    .btn-theme:hover {
      transform: translateY(-3px) scale(1.02);
      color: white;
      box-shadow: 0 15px 40px rgba(255,107,157,0.45);
    }

    .btn-outline-fun {
      background: white;
      color: var(--primary);
      padding: 12px 30px;
      border-radius: 50px;
      border: 3px solid var(--primary);
      font-weight: 700;
      font-size: 16px;
      transition: 0.4s;
    }

    .btn-outline-fun:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-3px);
    }

    /* ===== HERO SECTION ===== */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      padding-top: 100px;
      background: linear-gradient(135deg, #FFF5F8 0%, #F0FDFC 50%, #FFF8E7 100%);
    }

    .hero::before {
      content: '';
      position: absolute;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(255,107,157,0.12) 0%, transparent 70%);
      border-radius: 50%;
      top: -100px; right: -100px;
    }

    .hero::after {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(78,205,196,0.12) 0%, transparent 70%);
      border-radius: 50%;
      bottom: -100px; left: -100px;
    }

    .hero-tag {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: white;
      padding: 12px 24px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 15px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      margin-bottom: 25px;
      border: 2px solid var(--light-pink);
      color: var(--primary);
    }

    .hero h1 {
      font-size: 64px;
      line-height: 1.15;
      font-weight: 700;
      margin-bottom: 25px;
      color: var(--dark);
    }

    .hero h1 .highlight-1 {
      color: var(--primary);
      position: relative;
      display: inline-block;
    }

    .hero h1 .highlight-1::after {
      content: '';
      position: absolute;
      bottom: 5px; left: 0;
      width: 100%; height: 12px;
      background: var(--light-pink);
      border-radius: 10px;
      z-index: -1;
    }

    .hero h1 .highlight-2 {
      color: var(--accent);
      position: relative;
      display: inline-block;
    }

    .hero h1 .highlight-2::after {
      content: '';
      position: absolute;
      bottom: 5px; left: 0;
      width: 100%; height: 12px;
      background: var(--light-teal);
      border-radius: 10px;
      z-index: -1;
    }

    .hero p {
      font-size: 18px;
      line-height: 1.8;
      margin-bottom: 35px;
      max-width: 600px;
      color: var(--text);
    }

    .hero-image {
      position: relative;
      z-index: 2;
    }

    .hero-image img {
      width: 100%;
      border-radius: 50px;
      box-shadow: 0 30px 80px rgba(0,0,0,0.12);
      position: relative;
      z-index: 2;
      border: 8px solid white;
    }

    .hero-image-frame {
      position: absolute;
      top: -20px; right: -20px;
      bottom: 20px; left: 20px;
      border: 4px dashed var(--accent);
      border-radius: 50px;
      z-index: 1;
    }

    .floating-card {
      position: absolute;
      background: white;
      padding: 18px 22px;
      border-radius: 25px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
      z-index: 3;
      animation: float 4s ease-in-out infinite;
      border: 3px solid transparent;
    }

    .floating-card h5 {
      margin: 0;
      font-size: 18px;
      font-weight: 700;
    }

    .floating-card p {
      margin: 0;
      font-size: 13px;
      color: var(--text);
    }

    .card-1 { top: 8%; left: -12%; border-color: var(--accent); }
    .card-2 { bottom: 10%; right: -8%; animation-delay: 1.5s; border-color: var(--secondary); }
    .card-3 { top: 50%; right: -15%; animation-delay: 0.8s; border-color: var(--accent2); }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-15px) rotate(2deg); }
    }

    .stats {
      margin-top: 50px;
      background: white;
      border-radius: 30px;
      padding: 30px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.06);
      border: 3px solid var(--light-pink);
    }

    .stat-box {
      text-align: center;
      position: relative;
    }

    .stat-box:not(:last-child)::after {
      content: '';
      position: absolute;
      right: 0; top: 20%;
      height: 60%; width: 2px;
      background: var(--light-pink);
    }

    .stat-box h3 {
      font-size: 42px;
      color: var(--primary);
      margin-bottom: 5px;
      font-weight: 700;
    }

    .stat-box p {
      margin: 0;
      font-size: 15px;
      font-weight: 600;
      color: var(--text);
    }

    .stat-icon {
      width: 50px; height: 50px;
      border-radius: 15px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 10px;
    }

    /* ===== SECTION STYLES ===== */
    section {
      padding: 100px 0;
      position: relative;
    }

    .section-title {
      text-align: center;
      margin-bottom: 70px;
    }

    .section-title .title-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--light-pink);
      color: var(--primary);
      padding: 10px 24px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 20px;
    }

    .section-title h2 {
      font-size: 48px;
      margin-top: 10px;
      position: relative;
      display: inline-block;
    }

    .section-title h2 .fun-underline {
      position: relative;
    }

    .section-title h2 .fun-underline::after {
      content: '';
      position: absolute;
      bottom: -5px; left: 0;
      width: 100%; height: 8px;
      background: var(--accent3);
      border-radius: 10px;
      opacity: 0.6;
    }

    /* ===== FEATURE CARDS ===== */
    .feature-card {
      background: white;
      padding: 40px 30px;
      border-radius: 35px;
      transition: 0.4s;
      height: 100%;
      position: relative;
      overflow: hidden;
      border: 3px solid transparent;
      text-align: center;
    }

    .feature-card:hover {
      transform: translateY(-12px) rotate(1deg);
      box-shadow: 0 25px 50px rgba(0,0,0,0.1);
    }

    .feature-icon {
      width: 90px; height: 90px;
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      margin: 0 auto 25px;
      position: relative;
      z-index: 1;
    }

    .feature-icon::after {
      content: '';
      position: absolute;
      width: 100%; height: 100%;
      border-radius: 30px;
      background: inherit;
      opacity: 0.3;
      transform: scale(1.2);
      z-index: -1;
      animation: pulseIcon 2s ease-in-out infinite;
    }

    @keyframes pulseIcon {
      0%, 100% { transform: scale(1.2); opacity: 0.3; }
      50% { transform: scale(1.3); opacity: 0.1; }
    }

    .purple { background: var(--light-purple); color: var(--accent2); }
    .orange { background: var(--light-orange); color: var(--secondary); }
    .pink { background: var(--light-pink); color: var(--primary); }
    .teal { background: var(--light-teal); color: var(--accent); }
    .yellow { background: var(--light-yellow); color: #F9A825; }
    .green { background: var(--light-green); color: var(--accent4); }

    .feature-card h4 {
      margin-bottom: 15px;
      font-size: 26px;
    }

    .feature-card p {
      font-size: 15px;
      line-height: 1.7;
    }

    /* ===== PROGRAM CARDS ===== */
    .program-card {
      background: white;
      border-radius: 40px;
      overflow: hidden;
      transition: 0.4s;
      box-shadow: 0 10px 30px rgba(0,0,0,0.06);
      border: 3px solid transparent;
      position: relative;
    }

    .program-card:hover {
      transform: translateY(-10px) rotate(-1deg);
      border-color: var(--accent);
    }

    .program-card .program-img-wrapper {
      position: relative;
      overflow: hidden;
      height: 260px;
    }

    .program-card img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: 0.5s;
    }

    .program-card:hover img { transform: scale(1.1); }

    .program-img-overlay {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 50%;
      background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
    }

    .program-content { padding: 30px; position: relative; }

    .program-badge {
      display: inline-block;
      background: var(--light-pink);
      color: var(--primary);
      padding: 8px 20px;
      border-radius: 30px;
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 15px;
      border: 2px solid var(--primary);
    }

    .program-card h3 {
      font-size: 26px;
      margin-bottom: 12px;
    }

    .program-features {
      display: flex;
      gap: 15px;
      margin-top: 20px;
      flex-wrap: wrap;
    }

    .program-feature-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      background: var(--cream);
      padding: 6px 14px;
      border-radius: 20px;
    }

    .program-feature-item i { color: var(--accent); }

    /* ===== CTA SECTION ===== */
    .cta-section {
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent2) 50%, var(--accent) 100%);
      color: white;
      border-radius: 60px;
      padding: 80px 60px;
      position: relative;
      overflow: hidden;
      text-align: center;
    }

    .cta-section::before {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: rgba(255,255,255,0.08);
      border-radius: 50%;
      top: -150px; right: -100px;
      animation: floatShape 8s ease-in-out infinite;
    }

    .cta-section::after {
      content: '';
      position: absolute;
      width: 300px; height: 300px;
      background: rgba(255,255,255,0.06);
      border-radius: 50%;
      bottom: -100px; left: -50px;
      animation: floatShape 10s ease-in-out infinite reverse;
    }

    .cta-section h2, .cta-section p {
      color: white;
      position: relative;
      z-index: 2;
    }

    .cta-section h2 {
      font-size: 48px;
      margin-bottom: 20px;
    }

    .cta-section p {
      font-size: 18px;
      opacity: 0.95;
    }

    .cta-section .btn-light {
      background: white;
      color: var(--primary);
      font-weight: 700;
      padding: 15px 40px;
      border-radius: 50px;
      border: none;
      font-size: 18px;
      transition: 0.4s;
      position: relative;
      z-index: 2;
    }

    .cta-section .btn-light:hover {
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    }

    /* ===== GALLERY ===== */
    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .gallery-item {
      overflow: hidden;
      border-radius: 30px;
      position: relative;
      height: 280px;
      cursor: pointer;
      border: 4px solid white;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      transition: 0.4s;
    }

    .gallery-item:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .gallery-item img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: 0.5s;
    }

    .gallery-item:hover img { transform: scale(1.15); }

    .gallery-overlay {
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(255, 107, 157, 0.85);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: 26px;
    }

    .gallery-item:hover .gallery-overlay { opacity: 1; }

    .gallery-overlay-content {
      text-align: center;
      color: white;
      transform: translateY(20px);
      transition: 0.3s;
    }

    .gallery-item:hover .gallery-overlay-content { transform: translateY(0); }

    .gallery-overlay-content i {
      font-size: 2.5rem;
      margin-bottom: 10px;
      display: block;
    }

    .gallery-overlay-content span {
      font-size: 1rem;
      font-weight: 700;
    }

    /* ===== ANNOUNCEMENTS & EVENTS ===== */
    .announcement-card {
      background: white;
      border-radius: 25px;
      padding: 28px;
      margin-bottom: 20px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.06);
      border-left: 5px solid var(--primary);
      transition: all 0.3s;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .announcement-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: linear-gradient(135deg, var(--light-pink) 0%, transparent 100%);
      opacity: 0;
      transition: 0.3s;
    }

    .announcement-card:hover {
      transform: translateY(-5px) translateX(5px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    }

    .announcement-card:hover::before { opacity: 0.3; }
    .announcement-card > * { position: relative; z-index: 1; }

    .announcement-date {
      background: var(--light-pink);
      color: var(--primary);
      font-size: 0.85rem;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 20px;
      display: inline-block;
    }

    .event-card {
      background: white;
      border-radius: 25px;
      padding: 28px;
      margin-bottom: 20px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.06);
      border-left: 5px solid var(--secondary);
      transition: all 0.3s;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .event-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: linear-gradient(135deg, var(--light-orange) 0%, transparent 100%);
      opacity: 0;
      transition: 0.3s;
    }

    .event-card:hover {
      transform: translateY(-5px) translateX(5px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    }

    .event-card:hover::before { opacity: 0.3; }
    .event-card > * { position: relative; z-index: 1; }

    .event-date-badge {
      background: var(--light-orange);
      color: var(--secondary);
      padding: 8px 18px;
      border-radius: 20px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      margin-bottom: 12px;
    }

    .section-header-icon {
      width: 60px; height: 60px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      margin-bottom: 15px;
    }

    /* ===== FOOTER ===== */
    .footer {
      background: var(--dark);
      color: rgba(255,255,255,0.8);
      padding: 80px 0 30px;
      position: relative;
      overflow: hidden;
    }

    .footer::before {
      content: '';
      position: absolute;
      top: -100px; left: 50%;
      transform: translateX(-50%);
      width: 200px; height: 200px;
      background: var(--primary);
      border-radius: 50%;
      opacity: 0.1;
    }

    .footer h4 {
      color: white;
      margin-bottom: 25px;
      font-size: 22px;
    }

    .footer a {
      color: rgba(255,255,255,0.7);
      text-decoration: none;
      display: block;
      margin-bottom: 12px;
      transition: 0.3s;
      font-weight: 500;
    }

    .footer a:hover {
      color: var(--primary);
      padding-left: 8px;
    }

    .social-icons a {
      width: 48px; height: 48px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 12px;
      color: white;
      transition: 0.3s;
      font-size: 18px;
    }

    .social-icons a:hover {
      background: var(--primary);
      transform: translateY(-5px) rotate(10deg);
    }

    .footer-bottom {
      border-top: 1px solid rgba(255,255,255,0.1);
      margin-top: 50px;
      padding-top: 20px;
      text-align: center;
    }

    /* ===== CAROUSEL MODAL ===== */
    .carousel-modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(45, 27, 105, 0.95);
      animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .carousel-modal-content {
      position: relative;
      width: 100%; height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .carousel-image-container {
      max-width: 90%;
      max-height: 90%;
      text-align: center;
    }

    .carousel-image {
      max-width: 100%;
      max-height: 70vh;
      border-radius: 30px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
      border: 8px solid white;
    }

    .carousel-caption {
      color: white;
      text-align: center;
      margin-top: 20px;
      padding: 0 20px;
    }

    .carousel-caption h3 {
      font-size: 1.8rem;
      margin-bottom: 10px;
      color: white;
    }

    .carousel-caption p {
      font-size: 1.1rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
    }

    .carousel-close {
      position: absolute;
      top: 25px; right: 35px;
      color: white;
      font-size: 2rem;
      cursor: pointer;
      background: rgba(255, 107, 157, 0.8);
      border: none;
      padding: 12px 18px;
      border-radius: 50%;
      transition: all 0.3s;
    }

    .carousel-close:hover {
      background: var(--primary);
      transform: rotate(90deg);
    }

    .carousel-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
      background: rgba(255, 255, 255, 0.15);
      border: 3px solid rgba(255,255,255,0.3);
      padding: 18px 22px;
      border-radius: 50%;
      transition: all 0.3s;
    }

    .carousel-nav:hover {
      background: var(--primary);
      border-color: var(--primary);
      transform: translateY(-50%) scale(1.1);
    }

    .carousel-prev { left: 25px; }
    .carousel-next { right: 25px; }

    .carousel-counter {
      position: absolute;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      color: white;
      background: rgba(255, 107, 157, 0.8);
      padding: 10px 24px;
      border-radius: 30px;
      font-size: 1rem;
      font-weight: 700;
    }

    /* ===== DECORATIVE ELEMENTS ===== */
    .decorative-dots {
      position: absolute;
      width: 100px; height: 100px;
      background-image: radial-gradient(circle, var(--primary) 3px, transparent 3px);
      background-size: 20px 20px;
      opacity: 0.2;
      z-index: 0;
    }

    /* ===== ANIMATED SCROLL REVEAL ===== */
    .reveal {
      opacity: 0;
      transform: translateY(40px);
      transition: all 0.8s ease-out;
    }

    .reveal.active {
      opacity: 1;
      transform: translateY(0);
    }

    /* ===== CONTACT SECTION ===== */
    .contact-card {
      background: white;
      border-radius: 40px;
      padding: 50px;
      box-shadow: 0 15px 50px rgba(0,0,0,0.08);
      border: 4px solid var(--light-teal);
      text-align: center;
    }

    .contact-card h3 {
      font-size: 32px;
      margin-bottom: 20px;
    }

    .contact-card p {
      font-size: 18px;
      margin-bottom: 30px;
    }

    /* ===== RESPONSIVE ===== */
    @media(max-width: 991px) {
      .hero { text-align: center; }
      .hero h1 { font-size: 48px; }
      .hero-image { margin-top: 60px; }
      .card-1, .card-2, .card-3 { display: none; }
      .gallery-grid { grid-template-columns: repeat(2, 1fr); }
      .stat-box:not(:last-child)::after { display: none; }
      .stats .row { gap: 20px 0; }
    }

    @media(max-width: 576px) {
      .hero h1 { font-size: 38px; }
      .section-title h2 { font-size: 34px; }
      .gallery-grid { grid-template-columns: 1fr; }
      .cta-section { padding: 50px 25px; border-radius: 40px; }
      .cta-section h2 { font-size: 32px; }
      .contact-card { padding: 30px 20px; }
    }

    /* ===== SCROLLBAR ===== */
    ::-webkit-scrollbar {
      width: 12px;
    }
    ::-webkit-scrollbar-track {
      background: var(--cream);
    }
    ::-webkit-scrollbar-thumb {
      background: linear-gradient(var(--primary), var(--accent));
      border-radius: 10px;
      border: 3px solid var(--cream);
    }
  </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <i class="fas fa-child-reaching" style="color: var(--primary); font-size: 28px;"></i>
      <?php 
      $logo_image = $cms_content['header']['logo_image']['content_value'] ?? $cms_content['header']['logo_image']['image_path'] ?? '';
      if (!empty($logo_image)) {
          if (strpos($logo_image, 'http') === 0) {
              $logo_src = $logo_image;
          } else {
              $logo_src = 'uploads/homepage/' . $logo_image;
          }
          echo '<img src="' . htmlspecialchars($logo_src) . '" alt="Kidzenia Logo" style="height: 40px; margin-right: 10px;">';
      } else {
          echo htmlspecialchars($cms_content['header']['logo_text']['content_value'] ?? 'Kidzenia');
      }
      ?>
      <?php if (empty($logo_image)): ?>
        <span><?php echo htmlspecialchars($cms_content['header']['logo_text_span']['content_value'] ?? 'Kindergarten'); ?></span>
      <?php endif; ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="#home"><i class="fas fa-home me-1" style="color: var(--primary);"></i><?php echo htmlspecialchars($cms_content['nav']['link_home']['content_value'] ?? 'Home'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#programs"><i class="fas fa-shapes me-1" style="color: var(--secondary);"></i><?php echo htmlspecialchars($cms_content['nav']['link_programs']['content_value'] ?? 'Programs'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#about"><i class="fas fa-star me-1" style="color: var(--accent);"></i><?php echo htmlspecialchars($cms_content['nav']['link_about']['content_value'] ?? 'About'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#gallery"><i class="fas fa-images me-1" style="color: var(--accent2);"></i><?php echo htmlspecialchars($cms_content['nav']['link_gallery']['content_value'] ?? 'Gallery'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#events"><i class="fas fa-calendar-star me-1" style="color: var(--accent3);"></i><?php echo htmlspecialchars($cms_content['nav']['link_events']['content_value'] ?? 'Events'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#contact"><i class="fas fa-envelope me-1" style="color: var(--accent4);"></i><?php echo htmlspecialchars($cms_content['nav']['link_contact']['content_value'] ?? 'Contact'); ?></a></li>
      </ul>

      <a href="auth/login.php" class="btn btn-theme">
        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($cms_content['header']['admin_login_text']['content_value'] ?? 'Admin Login'); ?>
      </a>
    </div>
  </div>
</nav>

<!-- ===== HERO SECTION ===== -->
<section class="hero" id="home">
  <div class="bg-shape bg-shape-1"></div>
  <div class="bg-shape bg-shape-2"></div>
  <div class="bg-shape bg-shape-3"></div>

  <div class="container" style="position: relative; z-index: 2;">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="hero-tag reveal">
          <i class="fas fa-star" style="color: var(--accent3);"></i>
          <?php echo htmlspecialchars($cms_content['hero']['tag_text']['content_value'] ?? 'Trusted Kindergarten For Tiny Explorers'); ?>
        </div>

        <h1 class="reveal">
          <?php echo htmlspecialchars($cms_content['hero']['main_heading']['content_value'] ?? 'Where'); ?> <span class="highlight-1"><?php echo htmlspecialchars($cms_content['hero']['main_heading_span']['content_value'] ?? 'Curiosity'); ?></span><br>
          <span class="highlight-2"><?php echo htmlspecialchars($cms_content['hero']['main_heading_continued']['content_value'] ?? 'Becomes Creativity'); ?></span>
        </h1>

        <p class="reveal">
          <?php echo htmlspecialchars($cms_content['hero']['hero_description']['content_value'] ?? 'A joyful learning environment where children grow through imagination, play, discovery, and meaningful experiences designed for early childhood development.'); ?>
        </p>

        <div class="d-flex gap-3 flex-wrap justify-content-lg-start justify-content-center reveal">
          <a href="#contact" class="btn btn-theme">
            <i class="fas fa-rocket me-2"></i><?php echo htmlspecialchars($cms_content['hero']['cta_button_text']['content_value'] ?? 'Start Admission'); ?>
          </a>
          <a href="#programs" class="btn btn-outline-fun">
            <i class="fas fa-compass me-2"></i><?php echo htmlspecialchars($cms_content['hero']['secondary_button_text']['content_value'] ?? 'Explore Programs'); ?>
          </a>
        </div>

        <div class="stats reveal">
          <div class="row">
            <div class="col-4 stat-box">
              <div class="stat-icon" style="background: var(--light-pink); color: var(--primary);">
                <i class="fas fa-calendar-check"></i>
              </div>
              <h3 class="counter" data-target="<?php echo htmlspecialchars($cms_content['stats']['years_number']['content_value'] ?? '12'); ?>">0</h3>
              <p><?php echo htmlspecialchars($cms_content['stats']['years_label']['content_value'] ?? 'Years'); ?></p>
            </div>

            <div class="col-4 stat-box">
              <div class="stat-icon" style="background: var(--light-teal); color: var(--accent);">
                <i class="fas fa-users"></i>
              </div>
              <h3 class="counter" data-target="<?php echo htmlspecialchars($cms_content['stats']['students_number']['content_value'] ?? '850'); ?>">0</h3>
              <p><?php echo htmlspecialchars($cms_content['stats']['students_label']['content_value'] ?? 'Students'); ?></p>
            </div>

            <div class="col-4 stat-box">
              <div class="stat-icon" style="background: var(--light-orange); color: var(--secondary);">
                <i class="fas fa-chalkboard-user"></i>
              </div>
              <h3 class="counter" data-target="<?php echo htmlspecialchars($cms_content['stats']['teachers_number']['content_value'] ?? '40'); ?>">0</h3>
              <p><?php echo htmlspecialchars($cms_content['stats']['teachers_label']['content_value'] ?? 'Teachers'); ?></p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="hero-image reveal">
          <div class="hero-image-frame"></div>
          <?php 
          $hero_image = $cms_content['hero']['hero_image_path']['content_value'] ?? $cms_content['hero']['hero_image_path']['image_path'] ?? 'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=1200&auto=format&fit=crop';
          if (strpos($hero_image, 'http') === 0) {
              $hero_image_src = $hero_image;
          } else {
              $hero_image_src = 'uploads/homepage/' . $hero_image;
          }
          ?>
          <img src="<?php echo htmlspecialchars($hero_image_src); ?>" alt="Happy Kids Learning">

          <div class="floating-card card-1">
            <h5 style="color: var(--accent);">
              <i class="fas fa-palette me-2"></i><?php echo htmlspecialchars($cms_content['hero']['floating_card_1_title']['content_value'] ?? 'Creative Learning'); ?>
            </h5>
            <p><?php echo htmlspecialchars($cms_content['hero']['floating_card_1_description']['content_value'] ?? 'Interactive & playful education'); ?></p>
          </div>

          <div class="floating-card card-2">
            <h5 style="color: var(--secondary);">
              <i class="fas fa-shield-heart me-2"></i><?php echo htmlspecialchars($cms_content['hero']['floating_card_2_title']['content_value'] ?? 'Safe Environment'); ?>
            </h5>
            <p><?php echo htmlspecialchars($cms_content['hero']['floating_card_2_description']['content_value'] ?? 'Secure & caring atmosphere'); ?></p>
          </div>

          <div class="floating-card card-3">
            <h5 style="color: var(--accent2);">
              <i class="fas fa-trophy me-2"></i><?php echo htmlspecialchars($cms_content['hero']['floating_card_3_title']['content_value'] ?? 'Top Rated'); ?>
            </h5>
            <p><?php echo htmlspecialchars($cms_content['hero']['floating_card_3_description']['content_value'] ?? '5-Star Parent Reviews'); ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== WAVE DIVIDER ===== -->
<div class="wave-divider" style="position: relative; margin-top: -1px;">
  <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
    <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="#FAFAFF"></path>
  </svg>
</div>

<!-- ===== FEATURES / ABOUT SECTION ===== -->
<section id="about" style="background: #FAFAFF;">
  <div class="container">
    <div class="section-title reveal">
      <div class="title-badge">
        <i class="fas fa-sparkles"></i>
        <?php echo htmlspecialchars($cms_content['features']['section_title']['content_value'] ?? 'Why Choose Us'); ?>
      </div>
      <h2>Building <span class="fun-underline">Bright Futures</span></h2>
    </div>

    <div class="row g-4">
      <?php if (!empty($feature_cards)): ?>
        <?php 
        $icon_colors = ['purple', 'orange', 'pink', 'teal', 'yellow', 'green'];
        $color_index = 0;
        foreach ($feature_cards as $card): 
        ?>
          <div class="col-lg-3 col-md-6 reveal">
            <div class="feature-card" style="border-color: var(--light-<?php echo ['purple'=>'purple','orange'=>'orange','pink'=>'pink','teal'=>'teal','yellow'=>'yellow','green'=>'green'][$icon_colors[$color_index % 6]]; ?>);">
              <div class="feature-icon <?php echo $icon_colors[$color_index % 6]; ?>">
                <?php if ($card['icon_type'] == 'fa'): ?>
                  <i class="<?php echo htmlspecialchars($card['icon_value'] ?? 'fa-solid fa-star'); ?>"></i>
                <?php else: ?>
                  <img src="uploads/icons/<?php echo htmlspecialchars($card['icon_value'] ?? ''); ?>" alt="<?php echo htmlspecialchars($card['title']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">
                <?php endif; ?>
              </div>
              <h4><?php echo htmlspecialchars($card['title'] ?? 'Feature Title'); ?></h4>
              <p><?php echo htmlspecialchars($card['description'] ?? 'Feature description goes here.'); ?></p>
            </div>
          </div>
        <?php 
        $color_index++;
        endforeach; 
        ?>
      <?php else: ?>
        <div class="col-lg-3 col-md-6 reveal">
          <div class="feature-card" style="border-color: var(--light-purple);">
            <div class="feature-icon purple">
              <i class="<?php echo htmlspecialchars($cms_content['features']['feature_1_icon']['content_value'] ?? 'fa-solid fa-palette'); ?>"></i>
            </div>
            <h4><?php echo htmlspecialchars($cms_content['features']['feature_1_title']['content_value'] ?? 'Creative Programs'); ?></h4>
            <p><?php echo htmlspecialchars($cms_content['features']['feature_1_description']['content_value'] ?? 'Hands-on learning experiences that inspire creativity and imagination.'); ?></p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 reveal">
          <div class="feature-card" style="border-color: var(--light-orange);">
            <div class="feature-icon orange">
              <i class="<?php echo htmlspecialchars($cms_content['features']['feature_2_icon']['content_value'] ?? 'fa-solid fa-heart'); ?>"></i>
            </div>
            <h4><?php echo htmlspecialchars($cms_content['features']['feature_2_title']['content_value'] ?? 'Safe Environment'); ?></h4>
            <p><?php echo htmlspecialchars($cms_content['features']['feature_2_description']['content_value'] ?? 'Secure campus with child-friendly infrastructure and caring educators.'); ?></p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 reveal">
          <div class="feature-card" style="border-color: var(--light-pink);">
            <div class="feature-icon pink">
              <i class="<?php echo htmlspecialchars($cms_content['features']['feature_3_icon']['content_value'] ?? 'fa-solid fa-book-open'); ?>"></i>
            </div>
            <h4><?php echo htmlspecialchars($cms_content['features']['feature_3_title']['content_value'] ?? 'Smart Curriculum'); ?></h4>
            <p><?php echo htmlspecialchars($cms_content['features']['feature_3_description']['content_value'] ?? 'Balanced academics, social development, and playful exploration.'); ?></p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 reveal">
          <div class="feature-card" style="border-color: var(--light-teal);">
            <div class="feature-icon teal">
              <i class="<?php echo htmlspecialchars($cms_content['features']['feature_4_icon']['content_value'] ?? 'fa-solid fa-bus'); ?>"></i>
            </div>
            <h4><?php echo htmlspecialchars($cms_content['features']['feature_4_title']['content_value'] ?? 'Live Tracking'); ?></h4>
            <p><?php echo htmlspecialchars($cms_content['features']['feature_4_description']['content_value'] ?? 'Parents stay connected with real-time transport monitoring.'); ?></p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== PROGRAMS SECTION ===== -->
<section class="bg-white" id="programs" style="position: relative;">
  <div class="decorative-dots" style="top: 50px; right: 50px;"></div>
  <div class="decorative-dots" style="bottom: 50px; left: 30px;"></div>

  <div class="container" style="position: relative; z-index: 1;">
    <div class="section-title reveal">
      <div class="title-badge" style="background: var(--light-orange); color: var(--secondary);">
        <i class="fas fa-graduation-cap"></i>
        <?php echo htmlspecialchars($cms_content['programs']['section_title']['content_value'] ?? 'Programs'); ?>
      </div>
      <h2>Learning By <span class="fun-underline">Age</span></h2>
    </div>

    <div class="row g-4">
      <?php if (!empty($program_cards)): ?>
        <?php foreach ($program_cards as $card): ?>
          <div class="col-lg-4 reveal">
            <div class="program-card">
              <div class="program-img-wrapper">
                <?php 
                $program_image = $card['icon_value'] ?? 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?q=80&w=1200&auto=format&fit=crop';
                if (strpos($program_image, 'http') === 0) {
                    $program_image_src = $program_image;
                } else {
                    $program_image_src = 'uploads/homepage/' . $program_image;
                }
                ?>
                <img src="<?php echo htmlspecialchars($program_image_src); ?>" alt="<?php echo htmlspecialchars($card['title'] ?? 'Program'); ?>">
                <div class="program-img-overlay"></div>
              </div>

              <div class="program-content">
                <?php if (!empty($card['badge'])): ?>
                  <div class="program-badge"><?php echo htmlspecialchars($card['badge']); ?></div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($card['title'] ?? 'Program Title'); ?></h3>
                <p><?php echo htmlspecialchars($card['description'] ?? 'Program description goes here.'); ?></p>
                <div class="program-features">
                  <span class="program-feature-item"><i class="fas fa-check-circle"></i> Certified</span>
                  <span class="program-feature-item"><i class="fas fa-clock"></i> Flexible</span>
                  <span class="program-feature-item"><i class="fas fa-users"></i> Small Groups</span>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-lg-4 reveal">
          <div class="program-card">
            <div class="program-img-wrapper">
              <?php 
              $program1_image = $cms_content['programs']['program_1_image']['content_value'] ?? $cms_content['programs']['program_1_image']['image_path'] ?? 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?q=80&w=1200&auto=format&fit=crop';
              if (strpos($program1_image, 'http') === 0) {
                  $program1_image_src = $program1_image;
              } else {
                  $program1_image_src = 'uploads/homepage/' . $program1_image;
              }
              ?>
              <img src="<?php echo htmlspecialchars($program1_image_src); ?>" alt="Toddler Program">
              <div class="program-img-overlay"></div>
            </div>

            <div class="program-content">
              <div class="program-badge"><?php echo htmlspecialchars($cms_content['programs']['program_1_age']['content_value'] ?? 'Age 2 - 3'); ?></div>
              <h3><?php echo htmlspecialchars($cms_content['programs']['program_1_title']['content_value'] ?? 'Toddler Program'); ?></h3>
              <p><?php echo htmlspecialchars($cms_content['programs']['program_1_description']['content_value'] ?? 'Focus on sensory exploration, social interaction, and foundational communication skills.'); ?></p>
              <div class="program-features">
                <span class="program-feature-item"><i class="fas fa-check-circle"></i> Certified</span>
                <span class="program-feature-item"><i class="fas fa-clock"></i> Flexible</span>
                <span class="program-feature-item"><i class="fas fa-users"></i> Small Groups</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 reveal">
          <div class="program-card">
            <div class="program-img-wrapper">
              <?php 
              $program2_image = $cms_content['programs']['program_2_image']['content_value'] ?? $cms_content['programs']['program_2_image']['image_path'] ?? 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=1200&auto=format&fit=crop';
              if (strpos($program2_image, 'http') === 0) {
                  $program2_image_src = $program2_image;
              } else {
                  $program2_image_src = 'uploads/homepage/' . $program2_image;
              }
              ?>
              <img src="<?php echo htmlspecialchars($program2_image_src); ?>" alt="Nursery Program">
              <div class="program-img-overlay"></div>
            </div>

            <div class="program-content">
              <div class="program-badge"><?php echo htmlspecialchars($cms_content['programs']['program_2_age']['content_value'] ?? 'Age 3 - 4'); ?></div>
              <h3><?php echo htmlspecialchars($cms_content['programs']['program_2_title']['content_value'] ?? 'Nursery Program'); ?></h3>
              <p><?php echo htmlspecialchars($cms_content['programs']['program_2_description']['content_value'] ?? 'Interactive learning through storytelling, art, music, and engaging activities.'); ?></p>
              <div class="program-features">
                <span class="program-feature-item"><i class="fas fa-check-circle"></i> Certified</span>
                <span class="program-feature-item"><i class="fas fa-clock"></i> Flexible</span>
                <span class="program-feature-item"><i class="fas fa-users"></i> Small Groups</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 reveal">
          <div class="program-card">
            <div class="program-img-wrapper">
              <?php 
              $program3_image = $cms_content['programs']['program_3_image']['content_value'] ?? $cms_content['programs']['program_3_image']['image_path'] ?? 'https://images.unsplash.com/photo-1513258496099-48168024aec0?q=80&w=1200&auto=format&fit=crop';
              if (strpos($program3_image, 'http') === 0) {
                  $program3_image_src = $program3_image;
              } else {
                  $program3_image_src = 'uploads/homepage/' . $program3_image;
              }
              ?>
              <img src="<?php echo htmlspecialchars($program3_image_src); ?>" alt="Kindergarten">
              <div class="program-img-overlay"></div>
            </div>

            <div class="program-content">
              <div class="program-badge"><?php echo htmlspecialchars($cms_content['programs']['program_3_age']['content_value'] ?? 'Age 4 - 5'); ?></div>
              <h3><?php echo htmlspecialchars($cms_content['programs']['program_3_title']['content_value'] ?? 'Kindergarten'); ?></h3>
              <p><?php echo htmlspecialchars($cms_content['programs']['program_3_description']['content_value'] ?? 'School readiness program focused on confidence, creativity, and communication.'); ?></p>
              <div class="program-features">
                <span class="program-feature-item"><i class="fas fa-check-circle"></i> Certified</span>
                <span class="program-feature-item"><i class="fas fa-clock"></i> Flexible</span>
                <span class="program-feature-item"><i class="fas fa-users"></i> Small Groups</span>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== NEWS & EVENTS SECTION ===== -->
<section style="background: linear-gradient(180deg, var(--cream) 0%, white 100%);">
  <div class="container">
    <div class="section-title reveal">
      <div class="title-badge" style="background: var(--light-teal); color: var(--accent);">
        <i class="fas fa-bell"></i>
        Latest Updates
      </div>
      <h2>News & <span class="fun-underline">Notices</span></h2>
    </div>

    <div class="row">
      <div class="col-lg-6 reveal">
        <div class="d-flex align-items-center mb-4">
          <div class="section-header-icon" style="background: var(--light-pink); color: var(--primary);">
            <i class="fas fa-bullhorn"></i>
          </div>
          <h4 class="mb-0 ms-3" style="color: var(--dark);">Latest Announcements</h4>
        </div>

        <?php if (!empty($announcements)): ?>
          <?php foreach ($announcements as $announcement): ?>
            <div class="announcement-card" onclick="showAnnouncementModal(<?php echo $announcement['id']; ?>)">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="mb-0" style="color: var(--dark); font-size: 18px;"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                <span class="announcement-date">
                  <i class="fas fa-calendar-day me-1"></i><?php echo date('M d, Y', strtotime($announcement['publish_date'])); ?>
                </span>
              </div>
              <p class="mb-2" style="color: var(--text); font-size: 15px;">
                <?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?>
              </p>
              <div class="d-flex justify-content-between align-items-center">
                <small style="color: var(--text); font-weight: 600;">
                  <i class="fas fa-user me-1" style="color: var(--accent);"></i><?php echo htmlspecialchars($announcement['author_name'] ?: 'Admin'); ?>
                </small>
                <small style="color: var(--primary); font-weight: 700;">
                  <i class="fas fa-eye me-1"></i>View Details
                </small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-5" style="background: white; border-radius: 25px; box-shadow: 0 8px 25px rgba(0,0,0,0.06);">
            <div class="section-header-icon mx-auto" style="background: var(--light-pink); color: var(--primary); margin-bottom: 15px;">
              <i class="fas fa-bullhorn"></i>
            </div>
            <p class="text-muted fw-bold">No announcements at this time</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-6 reveal">
        <div class="d-flex align-items-center mb-4">
          <div class="section-header-icon" style="background: var(--light-orange); color: var(--secondary);">
            <i class="fas fa-calendar-star"></i>
          </div>
          <h4 class="mb-0 ms-3" style="color: var(--dark);">Upcoming Events</h4>
        </div>

        <?php if (!empty($events)): ?>
          <?php foreach ($events as $event): ?>
            <div class="event-card" onclick="showEventModal(<?php echo $event['id']; ?>)">
              <div class="event-date-badge">
                <i class="fas fa-calendar-day me-1"></i>
                <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
              </div>
              <h5 class="mb-2" style="color: var(--dark); font-size: 18px;"><?php echo htmlspecialchars($event['title']); ?></h5>
              <p class="mb-2" style="color: var(--text); font-size: 15px;">
                <?php echo htmlspecialchars(substr($event['description'], 0, 120)) . '...'; ?>
              </p>
              <?php if ($event['location']): ?>
                <small style="color: var(--text); font-weight: 600;">
                  <i class="fas fa-map-marker-alt me-1" style="color: var(--secondary);"></i>
                  <?php echo htmlspecialchars($event['location']); ?>
                </small>
              <?php endif; ?>
              <div class="mt-2">
                <small style="color: var(--secondary); font-weight: 700;">
                  <i class="fas fa-eye me-1"></i>Click to view details
                </small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-5" style="background: white; border-radius: 25px; box-shadow: 0 8px 25px rgba(0,0,0,0.06);">
            <div class="section-header-icon mx-auto" style="background: var(--light-orange); color: var(--secondary); margin-bottom: 15px;">
              <i class="fas fa-calendar-star"></i>
            </div>
            <p class="text-muted fw-bold">No upcoming events</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section>
  <div class="container">
    <div class="cta-section reveal">
      <h2 class="display-4 fw-bold mb-4">
        Give Your Child The Best Start
      </h2>
      <p class="lead mb-4">
        Join a nurturing environment where every child is celebrated, encouraged, and inspired to grow.
      </p>
      <a href="#contact" class="btn btn-light btn-lg rounded-pill px-5 fw-bold">
        <i class="fas fa-paper-plane me-2"></i>Apply For Admission
      </a>
    </div>
  </div>
</section>

<!-- ===== GALLERY SECTION ===== -->
<section id="gallery">
  <div class="container">
    <div class="section-title reveal">
      <div class="title-badge" style="background: var(--light-purple); color: var(--accent2);">
        <i class="fas fa-camera"></i>
        Gallery
      </div>
      <h2>Moments Of <span class="fun-underline">Joy</span></h2>
    </div>

    <div class="gallery-grid">
      <?php if (!empty($gallery_images)): ?>
        <?php foreach ($gallery_images as $index => $image): ?>
          <div class="gallery-item reveal" onclick="openGalleryCarousel(<?php echo $index; ?>)">
            <?php 
            $image_src = '';
            if ($image['image_path']) {
                if (strpos($image['image_path'], 'http') === 0) {
                    $image_src = htmlspecialchars($image['image_path']);
                } elseif (file_exists('uploads/gallery/' . $image['image_path'])) {
                    $image_src = 'uploads/gallery/' . htmlspecialchars($image['image_path']);
                } else {
                    $seeds = ['preschool1', 'kindergarten2', 'kids3', 'school4', 'children5', 'education6'];
                    $image_src = "https://picsum.photos/seed/" . $seeds[$index % 6] . "/400/280";
                }
            } else {
                $seeds = ['preschool1', 'kindergarten2', 'kids3', 'school4', 'children5', 'education6'];
                $image_src = "https://picsum.photos/seed/" . $seeds[$index % 6] . "/400/280";
            }
            ?>
            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($image['title'] ?? 'Gallery Image'); ?>" 
                 data-title="<?php echo htmlspecialchars($image['title'] ?? 'Gallery Image'); ?>"
                 data-description="<?php echo htmlspecialchars($image['description'] ?? 'Beautiful moment at Kidzenia Kindergarten'); ?>">
            <div class="gallery-overlay">
              <div class="gallery-overlay-content">
                <i class="fas fa-search-plus"></i>
                <span>View Full Size</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <?php for ($i = 0; $i < 6; $i++): ?>
          <div class="gallery-item reveal" onclick="openGalleryCarousel(<?php echo $i; ?>)">
            <img src="https://picsum.photos/seed/kidzenia<?php echo $i; ?>/400/280" alt="Gallery Image">
            <div class="gallery-overlay">
              <div class="gallery-overlay-content">
                <i class="fas fa-search-plus"></i>
                <span>View Full Size</span>
              </div>
            </div>
          </div>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== CONTACT SECTION ===== -->
<section id="contact" style="background: linear-gradient(180deg, white 0%, var(--cream) 100%);">
  <div class="container">
    <div class="section-title reveal">
      <div class="title-badge" style="background: var(--light-teal); color: var(--accent);">
        <i class="fas fa-paper-plane"></i>
        Contact Us
      </div>
      <h2>Get In <span class="fun-underline">Touch</span></h2>
    </div>

    <div class="row">
      <div class="col-lg-8 mx-auto">
        <div class="contact-card reveal">
          <div class="section-header-icon mx-auto" style="background: var(--light-pink); color: var(--primary); margin-bottom: 20px;">
            <i class="fas fa-envelope-open-text"></i>
          </div>
          <h3>Ready to give your child the best start?</h3>
          <p>We would love to hear from you! Reach out to us and let us help you begin this wonderful journey.</p>
          <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="contact.php" class="btn btn-theme btn-lg">
              <i class="fas fa-envelope me-2"></i>Contact Form
            </a>
            <a href="tel:+1234567890" class="btn btn-outline-fun btn-lg">
              <i class="fas fa-phone me-2"></i>Call Us
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4">
        <h4><i class="fas fa-child-reaching me-2" style="color: var(--primary);"></i>Kidzenia Kindergarten</h4>
        <p>
          Creating joyful learning experiences for children through creativity, care, and innovation.
        </p>
        <div class="social-icons mt-4">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-youtube"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>

      <div class="col-lg-2 col-md-6">
        <h4>Quick Links</h4>
        <a href="#about"><i class="fas fa-chevron-right me-2" style="font-size: 10px;"></i>About Us</a>
        <a href="#programs"><i class="fas fa-chevron-right me-2" style="font-size: 10px;"></i>Programs</a>
        <a href="#gallery"><i class="fas fa-chevron-right me-2" style="font-size: 10px;"></i>Gallery</a>
        <a href="auth/login.php"><i class="fas fa-chevron-right me-2" style="font-size: 10px;"></i>Admin Login</a>
      </div>

      <div class="col-lg-3 col-md-6">
        <h4>Programs</h4>
        <a href="#programs"><i class="fas fa-chevron-right me-2" style="font-size: 10px;"></i>Toddler Program</a>
        <a href="#programs"><i class="fas fa-chevron-right me-2" style="font-size: 10px;"></i>Nursery</a>
        <a href="#programs"><i class="fas fa-chevron-right me-2" style="font-size: 10px;"></i>Kindergarten</a>
        <a href="#programs"><i class="fas fa-chevron-right me-2" style="font-size: 10px;"></i>Day Care</a>
      </div>

      <div class="col-lg-3">
        <h4>Contact</h4>
        <p><i class="fa-solid fa-location-dot me-2" style="color: var(--primary);"></i> 123 Education Street, Learning City</p>
        <p><i class="fa-solid fa-phone me-2" style="color: var(--primary);"></i> +91 9876543210</p>
        <p><i class="fa-solid fa-envelope me-2" style="color: var(--primary);"></i> hello@kidzenia.com</p>
      </div>
    </div>

    <div class="footer-bottom">
      <p><i class="fas fa-heart me-2" style="color: var(--primary);"></i> <?php echo date('Y'); ?> Kidzenia Kindergarten. All Rights Reserved.</p>
    </div>
  </div>
</footer>

<!-- ===== GALLERY CAROUSEL MODAL ===== -->
<div id="galleryCarousel" class="carousel-modal">
  <div class="carousel-modal-content">
    <button class="carousel-close" onclick="closeGalleryCarousel()">
      <i class="fas fa-times"></i>
    </button>

    <button class="carousel-nav carousel-prev" onclick="navigateCarousel(-1)">
      <i class="fas fa-chevron-left"></i>
    </button>

    <button class="carousel-nav carousel-next" onclick="navigateCarousel(1)">
      <i class="fas fa-chevron-right"></i>
    </button>

    <div class="carousel-image-container">
      <img id="carouselImage" class="carousel-image" src="" alt="">
      <div class="carousel-caption">
        <h3 id="carouselTitle"></h3>
        <p id="carouselDescription"></p>
      </div>
    </div>

    <div class="carousel-counter" id="carouselCounter"></div>
  </div>
</div>

<!-- ===== ANNOUNCEMENT MODAL ===== -->
<div class="modal fade" id="announcementModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 30px; overflow: hidden; border: none;">
      <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--accent2)); color: white; border: none;">
        <h5 class="modal-title">
          <i class="fas fa-bullhorn me-2"></i>
          <span id="modalAnnouncementTitle">Announcement Details</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding: 30px;">
        <div class="mb-3">
          <span class="announcement-date" id="modalAnnouncementDate"></span>
        </div>
        <div class="mb-3">
          <p id="modalAnnouncementContent" style="font-size: 16px; line-height: 1.8;"></p>
        </div>
        <div class="border-top pt-3">
          <small class="text-muted">
            <i class="fas fa-user me-2" style="color: var(--accent);"></i><strong>Author:</strong> <span id="modalAnnouncementAuthor"></span>
          </small>
        </div>
      </div>
      <div class="modal-footer" style="border: none; padding: 20px 30px;">
        <button type="button" class="btn btn-outline-fun" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-theme" onclick="shareAnnouncement()">
          <i class="fas fa-share me-2"></i>Share
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===== EVENT MODAL ===== -->
<div class="modal fade" id="eventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 30px; overflow: hidden; border: none;">
      <div class="modal-header" style="background: linear-gradient(135deg, var(--secondary), #FFD166); color: white; border: none;">
        <h5 class="modal-title">
          <i class="fas fa-calendar-star me-2"></i>
          <span id="modalEventTitle">Event Details</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding: 30px;">
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="event-date-badge" id="modalEventDateBadge">
              <i class="fas fa-calendar-day me-2"></i>
              <span id="modalEventDate"></span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="text-muted" style="font-weight: 600;">
              <i class="fas fa-map-marker-alt me-2" style="color: var(--secondary);"></i>
              <span id="modalEventLocation"></span>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <p id="modalEventDescription" style="font-size: 16px; line-height: 1.8;"></p>
        </div>
        <div class="alert" style="background: var(--light-teal); color: var(--accent); border: none; border-radius: 20px;">
          <i class="fas fa-info-circle me-2"></i>
          <strong>RSVP:</strong> Please contact the school office to confirm your attendance.
        </div>
      </div>
      <div class="modal-footer" style="border: none; padding: 20px 30px;">
        <button type="button" class="btn btn-outline-fun" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-theme" style="background: linear-gradient(135deg, var(--secondary), #FFD166);" onclick="addToCalendar()">
          <i class="fas fa-calendar-plus me-2"></i>Add to Calendar
        </button>
        <button type="button" class="btn btn-theme" onclick="shareEvent()">
          <i class="fas fa-share me-2"></i>Share Event
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Store data for modal use
  const announcements = <?php echo json_encode($announcements); ?>;
  const events = <?php echo json_encode($events); ?>;
  const galleryImages = <?php echo json_encode($gallery_images); ?>;
  let currentCarouselIndex = 0;

  // ===== SCROLL REVEAL =====
  function revealOnScroll() {
    const reveals = document.querySelectorAll('.reveal');
    reveals.forEach(element => {
      const windowHeight = window.innerHeight;
      const elementTop = element.getBoundingClientRect().top;
      const elementVisible = 100;
      if (elementTop < windowHeight - elementVisible) {
        element.classList.add('active');
      }
    });
  }
  window.addEventListener('scroll', revealOnScroll);
  window.addEventListener('load', revealOnScroll);

  // ===== GALLERY CAROUSEL =====
  function openGalleryCarousel(index) {
    currentCarouselIndex = index;
    updateCarouselImage();
    document.getElementById('galleryCarousel').style.display = 'block';
    document.body.style.overflow = 'hidden';
  }

  function closeGalleryCarousel() {
    document.getElementById('galleryCarousel').style.display = 'none';
    document.body.style.overflow = 'auto';
  }

  function navigateCarousel(direction) {
    const totalImages = galleryImages.length || 6;
    currentCarouselIndex += direction;
    if (currentCarouselIndex < 0) {
      currentCarouselIndex = totalImages - 1;
    } else if (currentCarouselIndex >= totalImages) {
      currentCarouselIndex = 0;
    }
    updateCarouselImage();
  }

  function updateCarouselImage() {
    const carouselImage = document.getElementById('carouselImage');
    const carouselTitle = document.getElementById('carouselTitle');
    const carouselDescription = document.getElementById('carouselDescription');
    const carouselCounter = document.getElementById('carouselCounter');

    let imageSrc, title, description;

    if (galleryImages.length > 0 && galleryImages[currentCarouselIndex]) {
      const image = galleryImages[currentCarouselIndex];
      if (image.image_path) {
        if (image.image_path.startsWith('http')) {
          imageSrc = image.image_path;
        } else {
          imageSrc = 'uploads/gallery/' + image.image_path;
        }
      } else {
        const seeds = ['preschool1', 'kindergarten2', 'kids3', 'school4', 'children5', 'education6'];
        imageSrc = "https://picsum.photos/seed/" + seeds[currentCarouselIndex % 6] + "/1200/800";
      }
      title = image.title || 'Gallery Image';
      description = image.description || 'Beautiful moment at Kidzenia Kindergarten';
    } else {
      const seeds = ['preschool1', 'kindergarten2', 'kids3', 'school4', 'children5', 'education6'];
      imageSrc = "https://picsum.photos/seed/" + seeds[currentCarouselIndex % 6] + "/1200/800";
      title = 'Gallery Image ' + (currentCarouselIndex + 1);
      description = 'Beautiful moment at Kidzenia Kindergarten';
    }

    carouselImage.src = imageSrc;
    carouselImage.alt = title;
    carouselTitle.textContent = title;
    carouselDescription.textContent = description;

    const totalImages = galleryImages.length || 6;
    carouselCounter.textContent = `${currentCarouselIndex + 1} / ${totalImages}`;
  }

  // Keyboard navigation
  document.addEventListener('keydown', function(event) {
    const carousel = document.getElementById('galleryCarousel');
    if (carousel.style.display === 'block') {
      if (event.key === 'Escape') closeGalleryCarousel();
      else if (event.key === 'ArrowLeft') navigateCarousel(-1);
      else if (event.key === 'ArrowRight') navigateCarousel(1);
    }
  });

  // Click outside to close
  document.getElementById('galleryCarousel').addEventListener('click', function(event) {
    if (event.target === this) closeGalleryCarousel();
  });

  // ===== ANNOUNCEMENT MODAL =====
  function showAnnouncementModal(id) {
    const announcement = announcements.find(a => a.id == id);
    if (announcement) {
      document.getElementById('modalAnnouncementTitle').textContent = announcement.title;
      document.getElementById('modalAnnouncementDate').textContent = 'Published: ' + new Date(announcement.publish_date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric' 
      });
      document.getElementById('modalAnnouncementContent').textContent = announcement.content;
      document.getElementById('modalAnnouncementAuthor').textContent = announcement.author_name || 'Admin';

      const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
      modal.show();
    }
  }

  // ===== EVENT MODAL =====
  function showEventModal(id) {
    const event = events.find(e => e.id == id);
    if (event) {
      document.getElementById('modalEventTitle').textContent = event.title;
      document.getElementById('modalEventDate').textContent = new Date(event.event_date).toLocaleDateString('en-US', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
      });
      document.getElementById('modalEventLocation').textContent = event.location || 'School Campus';
      document.getElementById('modalEventDescription').textContent = event.description;

      const modal = new bootstrap.Modal(document.getElementById('eventModal'));
      modal.show();
    }
  }

  // ===== SHARE FUNCTIONS =====
  function shareAnnouncement() {
    const title = document.getElementById('modalAnnouncementTitle').textContent;
    const content = document.getElementById('modalAnnouncementContent').textContent;

    if (navigator.share) {
      navigator.share({ title: title, text: content, url: window.location.href });
    } else {
      const text = `${title}

${content}

${window.location.href}`;
      navigator.clipboard.writeText(text).then(() => {
        alert('Announcement copied to clipboard!');
      });
    }
  }

  function shareEvent() {
    const title = document.getElementById('modalEventTitle').textContent;
    const date = document.getElementById('modalEventDate').textContent;
    const location = document.getElementById('modalEventLocation').textContent;

    if (navigator.share) {
      navigator.share({
        title: title,
        text: `${date}
${location}

${window.location.href}`,
        url: window.location.href
      });
    } else {
      const text = `${title}
${date}
${location}

${window.location.href}`;
      navigator.clipboard.writeText(text).then(() => {
        alert('Event details copied to clipboard!');
      });
    }
  }

  function addToCalendar() {
    const title = document.getElementById('modalEventTitle').textContent;
    const date = document.getElementById('modalEventDate').textContent;
    const location = document.getElementById('modalEventLocation').textContent;
    alert(`Event "${title}" has been noted!

Date: ${date}
Location: ${location}

In a full implementation, this would add to your calendar.`);
  }

  // ===== COUNTER ANIMATION =====
  const counters = document.querySelectorAll('.counter');
  counters.forEach(counter => {
    counter.innerText = '0';
    const updateCounter = () => {
      const target = +counter.getAttribute('data-target');
      const current = +counter.innerText;
      const increment = target / 80;
      if (current < target) {
        counter.innerText = `${Math.ceil(current + increment)}`;
        setTimeout(updateCounter, 30);
      } else {
        counter.innerText = target;
      }
    }
    updateCounter();
  });

  // ===== NAVBAR SCROLL EFFECT =====
  window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
      navbar.style.padding = '10px 0';
      navbar.style.background = 'rgba(255,255,255,0.98)';
    } else {
      navbar.style.padding = '15px 0';
      navbar.style.background = 'rgba(255,255,255,0.95)';
    }
  });

  // ===== SMOOTH SCROLLING =====
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
</script>

</body>
</html>