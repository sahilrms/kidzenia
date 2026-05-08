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
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    :root {
      --primary: #7C3AED;
      --secondary: #FF8A00;
      --light-purple: #F3E8FF;
      --light-orange: #FFF3E6;
      --dark: #1E1B4B;
      --text: #475569;
      --white: #ffffff;
      --success: #22C55E;
      --pink: #EC4899;
      --sky: #0EA5E9;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      color: var(--text);
      overflow-x: hidden;
      background: #FAFAFF;
    }

    h1,h2,h3,h4,h5,h6 {
      font-family: 'Baloo 2', cursive;
      color: var(--dark);
    }

    .navbar {
      padding: 18px 0;
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(10px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .navbar-brand {
      font-size: 30px;
      font-weight: 700;
      color: var(--primary);
    }

    .navbar-brand span {
      color: var(--secondary);
    }

    .nav-link {
      font-weight: 600;
      margin: 0 10px;
      color: var(--dark);
      transition: 0.3s;
    }

    .nav-link:hover {
      color: var(--primary);
    }

    .btn-theme {
      background: linear-gradient(135deg, var(--primary), var(--pink));
      color: white;
      padding: 12px 28px;
      border-radius: 50px;
      border: none;
      font-weight: 600;
      transition: 0.4s;
      box-shadow: 0 15px 25px rgba(124,58,237,0.25);
    }

    .btn-theme:hover {
      transform: translateY(-3px);
      color: white;
    }

    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      padding-top: 100px;
      background:
        radial-gradient(circle at top left, rgba(124,58,237,0.12), transparent 30%),
        radial-gradient(circle at bottom right, rgba(255,138,0,0.12), transparent 30%),
        #FAFAFF;
    }

    .hero::before {
      content: '';
      position: absolute;
      width: 600px;
      height: 600px;
      background: linear-gradient(135deg, var(--primary), var(--pink));
      border-radius: 50%;
      top: -250px;
      right: -200px;
      opacity: 0.08;
    }

    .hero::after {
      content: '';
      position: absolute;
      width: 450px;
      height: 450px;
      background: linear-gradient(135deg, var(--secondary), #FFD166);
      border-radius: 50%;
      bottom: -200px;
      left: -150px;
      opacity: 0.08;
    }

    .hero-tag {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: white;
      padding: 10px 20px;
      border-radius: 50px;
      font-weight: 600;
      box-shadow: 0 10px 25px rgba(0,0,0,0.06);
      margin-bottom: 25px;
    }

    .hero h1 {
      font-size: 72px;
      line-height: 1.1;
      font-weight: 700;
      margin-bottom: 25px;
    }

    .hero h1 span {
      color: var(--primary);
    }

    .hero p {
      font-size: 18px;
      line-height: 1.8;
      margin-bottom: 35px;
      max-width: 600px;
    }

    .hero-image {
      position: relative;
    }

    .hero-image img {
      width: 100%;
      border-radius: 40px;
      box-shadow: 0 30px 80px rgba(0,0,0,0.12);
      position: relative;
      z-index: 2;
    }

    .floating-card {
      position: absolute;
      background: white;
      padding: 18px;
      border-radius: 25px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.08);
      z-index: 3;
      animation: float 4s ease-in-out infinite;
    }

    .floating-card h5 {
      margin: 0;
      font-size: 18px;
    }

    .floating-card p {
      margin: 0;
      font-size: 13px;
    }

    .card-1 {
      top: 10%;
      left: -10%;
    }

    .card-2 {
      bottom: 8%;
      right: -10%;
      animation-delay: 1s;
    }

    @keyframes float {
      0%,100% { transform: translateY(0px); }
      50% { transform: translateY(-12px); }
    }

    .stats {
      margin-top: 40px;
    }

    .stat-box h3 {
      font-size: 40px;
      color: var(--primary);
      margin-bottom: 0;
    }

    .stat-box p {
      margin: 0;
      font-size: 15px;
    }

    section {
      padding: 100px 0;
    }

    .section-title {
      text-align: center;
      margin-bottom: 70px;
    }

    .section-title span {
      color: var(--primary);
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      font-size: 14px;
    }

    .section-title h2 {
      font-size: 54px;
      margin-top: 10px;
    }

    .feature-card {
      background: white;
      padding: 40px 30px;
      border-radius: 30px;
      transition: 0.4s;
      height: 100%;
      position: relative;
      overflow: hidden;
      border: 1px solid #f2f2f2;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 25px 50px rgba(0,0,0,0.08);
    }

    .feature-icon {
      width: 80px;
      height: 80px;
      border-radius: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      margin-bottom: 25px;
    }

    .purple { background: var(--light-purple); color: var(--primary); }
    .orange { background: var(--light-orange); color: var(--secondary); }
    .pink { background: #FFE4F2; color: var(--pink); }
    .sky { background: #E0F2FE; color: var(--sky); }

    .feature-card h4 {
      margin-bottom: 15px;
      font-size: 28px;
    }

    .program-card {
      background: white;
      border-radius: 35px;
      overflow: hidden;
      transition: 0.4s;
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .program-card:hover {
      transform: translateY(-8px);
    }

    .program-card img {
      width: 100%;
      height: 240px;
      object-fit: cover;
    }

    .program-content {
      padding: 30px;
    }

    .program-badge {
      display: inline-block;
      background: var(--light-purple);
      color: var(--primary);
      padding: 8px 16px;
      border-radius: 30px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 15px;
    }

    .cta-section {
      background: linear-gradient(135deg, var(--primary), #4F46E5);
      color: white;
      border-radius: 50px;
      padding: 80px 60px;
      position: relative;
      overflow: hidden;
    }

    .cta-section::before {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      background: rgba(255,255,255,0.08);
      border-radius: 50%;
      top: -100px;
      right: -50px;
    }

    .cta-section h2,
    .cta-section p {
      color: white;
      position: relative;
      z-index: 2;
    }

    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(4,1fr);
      gap: 20px;
    }

    .gallery-item {
      overflow: hidden;
      border-radius: 30px;
      position: relative;
      height: 280px;
    }

    .gallery-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: 0.5s;
    }

    .gallery-item:hover img {
      transform: scale(1.1);
    }
    
    .gallery-item {
      position: relative;
    }
    
    .gallery-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: 30px;
    }
    
    .gallery-item:hover .gallery-overlay {
      opacity: 1;
    }
    
    .gallery-overlay-content {
      text-align: center;
      color: white;
    }
    
    .gallery-overlay-content i {
      font-size: 2rem;
      margin-bottom: 10px;
      display: block;
    }
    
    .gallery-overlay-content span {
      font-size: 0.9rem;
      font-weight: 600;
    }
    
    /* Carousel Modal Styles */
    .carousel-modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.95);
      animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    .carousel-modal-content {
      position: relative;
      width: 100%;
      height: 100%;
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
      max-height: 80vh;
      border-radius: 10px;
      box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
    }
    
    .carousel-caption {
      color: white;
      text-align: center;
      margin-top: 20px;
      padding: 0 20px;
    }
    
    .carousel-caption h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
    }
    
    .carousel-caption p {
      font-size: 1rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
    }
    
    .carousel-close {
      position: absolute;
      top: 20px;
      right: 40px;
      color: white;
      font-size: 2rem;
      cursor: pointer;
      background: rgba(255, 255, 255, 0.1);
      border: none;
      padding: 10px 15px;
      border-radius: 50%;
      transition: background 0.3s;
    }
    
    .carousel-close:hover {
      background: rgba(255, 255, 255, 0.2);
    }
    
    .carousel-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      color: white;
      font-size: 2rem;
      cursor: pointer;
      background: rgba(255, 255, 255, 0.1);
      border: none;
      padding: 15px 20px;
      border-radius: 50%;
      transition: all 0.3s;
    }
    
    .carousel-nav:hover {
      background: rgba(255, 255, 255, 0.2);
    }
    
    .carousel-prev {
      left: 20px;
    }
    
    .carousel-next {
      right: 20px;
    }
    
    .carousel-counter {
      position: absolute;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      color: white;
      background: rgba(0, 0, 0, 0.5);
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 0.9rem;
    }

    .announcement-card {
      background: white;
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      border-left: 4px solid var(--primary);
      transition: all 0.3s;
    }

    .announcement-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .announcement-date {
      color: var(--primary);
      font-size: 0.9rem;
      font-weight: 600;
    }

    .event-card {
      background: white;
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      border-left: 4px solid var(--secondary);
      transition: all 0.3s;
    }

    .event-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .event-date {
      background: var(--light-orange);
      color: var(--secondary);
      padding: 8px 15px;
      border-radius: 15px;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 10px;
    }

    .footer {
      background: var(--dark);
      color: rgba(255,255,255,0.7);
      padding: 80px 0 30px;
    }

    .footer h4 {
      color: white;
      margin-bottom: 25px;
    }

    .footer a {
      color: rgba(255,255,255,0.7);
      text-decoration: none;
      display: block;
      margin-bottom: 12px;
      transition: 0.3s;
    }

    .footer a:hover {
      color: white;
      padding-left: 5px;
    }

    .social-icons a {
      width: 45px;
      height: 45px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
      color: white;
    }

    .footer-bottom {
      border-top: 1px solid rgba(255,255,255,0.1);
      margin-top: 50px;
      padding-top: 20px;
      text-align: center;
    }

    @media(max-width: 991px) {
      .hero {
        text-align: center;
      }

      .hero h1 {
        font-size: 52px;
      }

      .hero-image {
        margin-top: 60px;
      }

      .card-1,
      .card-2 {
        display: none;
      }

      .gallery-grid {
        grid-template-columns: repeat(2,1fr);
      }
    }

    @media(max-width: 576px) {
      .hero h1 {
        font-size: 42px;
      }

      .section-title h2 {
        font-size: 38px;
      }

      .gallery-grid {
        grid-template-columns: 1fr;
      }

      .cta-section {
        padding: 50px 30px;
        border-radius: 30px;
      }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <?php 
      // Check if logo image exists in database
      $logo_image = $cms_content['header']['logo_image']['content_value'] ?? $cms_content['header']['logo_image']['image_path'] ?? '';
      if (!empty($logo_image)) {
          // Display image logo
          if (strpos($logo_image, 'http') === 0) {
              $logo_src = $logo_image;
          } else {
              $logo_src = 'uploads/homepage/' . $logo_image;
          }
          echo '<img src="' . htmlspecialchars($logo_src) . '" alt="Kidzenia Logo" style="height: 40px; margin-right: 10px;">';
      } else {
          // Display text logo
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
        <li class="nav-item"><a class="nav-link" href="#home"><?php echo htmlspecialchars($cms_content['nav']['link_home']['content_value'] ?? 'Home'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#programs"><?php echo htmlspecialchars($cms_content['nav']['link_programs']['content_value'] ?? 'Programs'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#about"><?php echo htmlspecialchars($cms_content['nav']['link_about']['content_value'] ?? 'About'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#gallery"><?php echo htmlspecialchars($cms_content['nav']['link_gallery']['content_value'] ?? 'Gallery'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#events"><?php echo htmlspecialchars($cms_content['nav']['link_events']['content_value'] ?? 'Events'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#contact"><?php echo htmlspecialchars($cms_content['nav']['link_contact']['content_value'] ?? 'Contact'); ?></a></li>
      </ul>

      <a href="auth/login.php" class="btn btn-theme"><?php echo htmlspecialchars($cms_content['header']['admin_login_text']['content_value'] ?? 'Admin Login'); ?></a>
    </div>
  </div>
</nav>

<section class="hero" id="home">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="hero-tag">
          <i class="<?php echo htmlspecialchars($cms_content['hero']['tag_icon']['content_value'] ?? 'fa-solid fa-star text-warning'); ?>"></i>
          <?php echo htmlspecialchars($cms_content['hero']['tag_text']['content_value'] ?? 'Trusted Kindergarten For Tiny Explorers'); ?>
        </div>

        <h1>
          <?php echo htmlspecialchars($cms_content['hero']['main_heading']['content_value'] ?? 'Where'); ?> <span><?php echo htmlspecialchars($cms_content['hero']['main_heading_span']['content_value'] ?? 'Curiosity'); ?></span><br>
          <?php echo htmlspecialchars($cms_content['hero']['main_heading_continued']['content_value'] ?? 'Becomes Creativity'); ?>
        </h1>

        <p>
          <?php echo htmlspecialchars($cms_content['hero']['hero_description']['content_value'] ?? 'A joyful learning environment where children grow through imagination, play, discovery, and meaningful experiences designed for early childhood development.'); ?>
        </p>

        <div class="d-flex gap-3 flex-wrap justify-content-lg-start justify-content-center">
          <a href="#contact" class="btn btn-theme"><?php echo htmlspecialchars($cms_content['hero']['cta_button_text']['content_value'] ?? 'Start Admission'); ?></a>
          <a href="#programs" class="btn btn-light px-4 rounded-pill fw-semibold"><?php echo htmlspecialchars($cms_content['hero']['secondary_button_text']['content_value'] ?? 'Explore Programs'); ?></a>
        </div>

        <div class="row stats">
          <div class="col-4 stat-box">
            <h3 class="counter" data-target="<?php echo htmlspecialchars($cms_content['stats']['years_number']['content_value'] ?? '12'); ?>">0</h3>
            <p><?php echo htmlspecialchars($cms_content['stats']['years_label']['content_value'] ?? 'Years'); ?></p>
          </div>

          <div class="col-4 stat-box">
            <h3 class="counter" data-target="<?php echo htmlspecialchars($cms_content['stats']['students_number']['content_value'] ?? '850'); ?>">0</h3>
            <p><?php echo htmlspecialchars($cms_content['stats']['students_label']['content_value'] ?? 'Students'); ?></p>
          </div>

          <div class="col-4 stat-box">
            <h3 class="counter" data-target="<?php echo htmlspecialchars($cms_content['stats']['teachers_number']['content_value'] ?? '40'); ?>">0</h3>
            <p><?php echo htmlspecialchars($cms_content['stats']['teachers_label']['content_value'] ?? 'Teachers'); ?></p>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="hero-image">
          <?php 
          $hero_image = $cms_content['hero']['hero_image_path']['content_value'] ?? $cms_content['hero']['hero_image_path']['image_path'] ?? 'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=1200&auto=format&fit=crop';
          if (strpos($hero_image, 'http') === 0) {
              $hero_image_src = $hero_image;
          } else {
              $hero_image_src = 'uploads/homepage/' . $hero_image;
          }
          ?>
          <img src="<?php echo htmlspecialchars($hero_image_src); ?>" alt="Kids">

          <div class="floating-card card-1">
            <h5><?php echo htmlspecialchars($cms_content['hero']['floating_card_1_icon']['content_value'] ?? '🎨'); ?> <?php echo htmlspecialchars($cms_content['hero']['floating_card_1_title']['content_value'] ?? 'Creative Learning'); ?></h5>
            <p><?php echo htmlspecialchars($cms_content['hero']['floating_card_1_description']['content_value'] ?? 'Interactive & playful education'); ?></p>
          </div>

          <div class="floating-card card-2">
            <h5><?php echo htmlspecialchars($cms_content['hero']['floating_card_2_icon']['content_value'] ?? '🚌'); ?> <?php echo htmlspecialchars($cms_content['hero']['floating_card_2_title']['content_value'] ?? 'Smart Transport'); ?></h5>
            <p><?php echo htmlspecialchars($cms_content['hero']['floating_card_2_description']['content_value'] ?? 'Live GPS tracking for parents'); ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="about">
  <div class="container">
    <div class="section-title">
      <span><?php echo htmlspecialchars($cms_content['features']['section_title']['content_value'] ?? 'Why Choose Us'); ?></span>
      <h2><?php echo htmlspecialchars($cms_content['features']['section_subtitle']['content_value'] ?? 'Building Bright Futures'); ?></h2>
    </div>

    <div class="row g-4">
      <?php if (!empty($feature_cards)): ?>
        <?php 
        $icon_colors = ['purple', 'orange', 'pink', 'sky'];
        $color_index = 0;
        foreach ($feature_cards as $card): 
        ?>
          <div class="col-lg-3 col-md-6">
            <div class="feature-card">
              <div class="feature-icon <?php echo $icon_colors[$color_index % 4]; ?>">
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
        <!-- Fallback to static content if no dynamic cards -->
        <div class="col-lg-3 col-md-6">
          <div class="feature-card">
            <div class="feature-icon purple">
              <i class="<?php echo htmlspecialchars($cms_content['features']['feature_1_icon']['content_value'] ?? 'fa-solid fa-palette'); ?>"></i>
            </div>
            <h4><?php echo htmlspecialchars($cms_content['features']['feature_1_title']['content_value'] ?? 'Creative Programs'); ?></h4>
            <p><?php echo htmlspecialchars($cms_content['features']['feature_1_description']['content_value'] ?? 'Hands-on learning experiences that inspire creativity and imagination.'); ?></p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="feature-card">
            <div class="feature-icon orange">
              <i class="<?php echo htmlspecialchars($cms_content['features']['feature_2_icon']['content_value'] ?? 'fa-solid fa-heart'); ?>"></i>
            </div>
            <h4><?php echo htmlspecialchars($cms_content['features']['feature_2_title']['content_value'] ?? 'Safe Environment'); ?></h4>
            <p><?php echo htmlspecialchars($cms_content['features']['feature_2_description']['content_value'] ?? 'Secure campus with child-friendly infrastructure and caring educators.'); ?></p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="feature-card">
            <div class="feature-icon pink">
              <i class="<?php echo htmlspecialchars($cms_content['features']['feature_3_icon']['content_value'] ?? 'fa-solid fa-book-open'); ?>"></i>
            </div>
            <h4><?php echo htmlspecialchars($cms_content['features']['feature_3_title']['content_value'] ?? 'Smart Curriculum'); ?></h4>
            <p><?php echo htmlspecialchars($cms_content['features']['feature_3_description']['content_value'] ?? 'Balanced academics, social development, and playful exploration.'); ?></p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="feature-card">
            <div class="feature-icon sky">
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

<section class="bg-white" id="programs">
  <div class="container">
    <div class="section-title">
      <span><?php echo htmlspecialchars($cms_content['programs']['section_title']['content_value'] ?? 'Programs'); ?></span>
      <h2><?php echo htmlspecialchars($cms_content['programs']['section_subtitle']['content_value'] ?? 'Learning By Age'); ?></h2>
    </div>

    <div class="row g-4">
      <?php if (!empty($program_cards)): ?>
        <?php foreach ($program_cards as $card): ?>
          <div class="col-lg-4">
            <div class="program-card">
              <?php 
              $program_image = $card['icon_value'] ?? 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?q=80&w=1200&auto=format&fit=crop';
              if (strpos($program_image, 'http') === 0) {
                  $program_image_src = $program_image;
              } else {
                  $program_image_src = 'uploads/homepage/' . $program_image;
              }
              ?>
              <img src="<?php echo htmlspecialchars($program_image_src); ?>" alt="<?php echo htmlspecialchars($card['title'] ?? 'Program'); ?>">

              <div class="program-content">
                <?php if (!empty($card['badge'])): ?>
                  <div class="program-badge"><?php echo htmlspecialchars($card['badge']); ?></div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($card['title'] ?? 'Program Title'); ?></h3>
                <p><?php echo htmlspecialchars($card['description'] ?? 'Program description goes here.'); ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Fallback to static content if no dynamic cards -->
        <div class="col-lg-4">
          <div class="program-card">
            <?php 
            $program1_image = $cms_content['programs']['program_1_image']['content_value'] ?? $cms_content['programs']['program_1_image']['image_path'] ?? 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?q=80&w=1200&auto=format&fit=crop';
            if (strpos($program1_image, 'http') === 0) {
                $program1_image_src = $program1_image;
            } else {
                $program1_image_src = 'uploads/homepage/' . $program1_image;
            }
            ?>
            <img src="<?php echo htmlspecialchars($program1_image_src); ?>" alt="Toddler">

            <div class="program-content">
              <div class="program-badge"><?php echo htmlspecialchars($cms_content['programs']['program_1_age']['content_value'] ?? 'Age 2 - 3'); ?></div>
              <h3><?php echo htmlspecialchars($cms_content['programs']['program_1_title']['content_value'] ?? 'Toddler Program'); ?></h3>
              <p><?php echo htmlspecialchars($cms_content['programs']['program_1_description']['content_value'] ?? 'Focus on sensory exploration, social interaction, and foundational communication skills.'); ?></p>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="program-card">
            <?php 
            $program2_image = $cms_content['programs']['program_2_image']['content_value'] ?? $cms_content['programs']['program_2_image']['image_path'] ?? 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=1200&auto=format&fit=crop';
            if (strpos($program2_image, 'http') === 0) {
                $program2_image_src = $program2_image;
            } else {
                $program2_image_src = 'uploads/homepage/' . $program2_image;
            }
            ?>
            <img src="<?php echo htmlspecialchars($program2_image_src); ?>" alt="Nursery">

            <div class="program-content">
              <div class="program-badge"><?php echo htmlspecialchars($cms_content['programs']['program_2_age']['content_value'] ?? 'Age 3 - 4'); ?></div>
              <h3><?php echo htmlspecialchars($cms_content['programs']['program_2_title']['content_value'] ?? 'Nursery Program'); ?></h3>
              <p><?php echo htmlspecialchars($cms_content['programs']['program_2_description']['content_value'] ?? 'Interactive learning through storytelling, art, music, and engaging activities.'); ?></p>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="program-card">
            <?php 
            $program3_image = $cms_content['programs']['program_3_image']['content_value'] ?? $cms_content['programs']['program_3_image']['image_path'] ?? 'https://images.unsplash.com/photo-1513258496099-48168024aec0?q=80&w=1200&auto=format&fit=crop';
            if (strpos($program3_image, 'http') === 0) {
                $program3_image_src = $program3_image;
            } else {
                $program3_image_src = 'uploads/homepage/' . $program3_image;
            }
            ?>
            <img src="<?php echo htmlspecialchars($program3_image_src); ?>" alt="Kindergarten">

            <div class="program-content">
              <div class="program-badge"><?php echo htmlspecialchars($cms_content['programs']['program_3_age']['content_value'] ?? 'Age 4 - 5'); ?></div>
              <h3><?php echo htmlspecialchars($cms_content['programs']['program_3_title']['content_value'] ?? 'Kindergarten'); ?></h3>
              <p><?php echo htmlspecialchars($cms_content['programs']['program_3_description']['content_value'] ?? 'School readiness program focused on confidence, creativity, and communication.'); ?></p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Latest News & Notice Board Section -->
<section>
  <div class="container">
    <div class="section-title">
      <span>Latest Updates</span>
      <h2>News & Notices</h2>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <h4 class="mb-4">
          <i class="fas fa-bullhorn me-2 text-primary"></i>
          Latest Announcements
        </h4>
        <?php if (!empty($announcements)): ?>
          <?php foreach ($announcements as $announcement): ?>
            <div class="announcement-card" onclick="showAnnouncementModal(<?php echo $announcement['id']; ?>)" style="cursor: pointer;">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                <span class="announcement-date">
                  <?php echo date('M d, Y', strtotime($announcement['publish_date'])); ?>
                </span>
              </div>
              <p class="text-muted mb-2">
                <?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?>
              </p>
              <small class="text-muted">
                By: <?php echo htmlspecialchars($announcement['author_name'] ?: 'Admin'); ?>
              </small>
              <div class="mt-2">
                <small class="text-primary">
                  <i class="fas fa-eye me-1"></i>Click to view details
                </small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="fas fa-bullhorn fa-2x text-muted mb-3"></i>
            <p class="text-muted">No announcements at this time</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-6">
        <h4 class="mb-4">
          <i class="fas fa-calendar-alt me-2 text-warning"></i>
          Upcoming Events
        </h4>
        <?php if (!empty($events)): ?>
          <?php foreach ($events as $event): ?>
            <div class="event-card" onclick="showEventModal(<?php echo $event['id']; ?>)" style="cursor: pointer;">
              <div class="event-date">
                <i class="fas fa-calendar me-2"></i>
                <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
              </div>
              <h5 class="mb-2"><?php echo htmlspecialchars($event['title']); ?></h5>
              <p class="text-muted mb-2">
                <?php echo htmlspecialchars(substr($event['description'], 0, 120)) . '...'; ?>
              </p>
              <?php if ($event['location']): ?>
                <small class="text-muted">
                  <i class="fas fa-map-marker-alt me-1"></i>
                  <?php echo htmlspecialchars($event['location']); ?>
                </small>
              <?php endif; ?>
              <div class="mt-2">
                <small class="text-warning">
                  <i class="fas fa-eye me-1"></i>Click to view details
                </small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-4">
            <i class="fas fa-calendar-alt fa-2x text-muted mb-3"></i>
            <p class="text-muted">No upcoming events</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="cta-section text-center">
      <h2 class="display-4 fw-bold mb-4">
        Give Your Child The Best Start
      </h2>

      <p class="lead mb-4">
        Join a nurturing environment where every child is celebrated, encouraged, and inspired to grow.
      </p>

      <a href="#contact" class="btn btn-light btn-lg rounded-pill px-5 fw-bold">
        Apply For Admission
      </a>
    </div>
  </div>
</section>

<section id="gallery">
  <div class="container">
    <div class="section-title">
      <span>Gallery</span>
      <h2>Moments Of Joy</h2>
    </div>

    <div class="gallery-grid">
      <?php if (!empty($gallery_images)): ?>
        <?php foreach ($gallery_images as $index => $image): ?>
          <div class="gallery-item" onclick="openGalleryCarousel(<?php echo $index; ?>)" style="cursor: pointer;">
            <?php 
            $image_src = '';
            if ($image['image_path']) {
                // Check if it's an external URL
                if (strpos($image['image_path'], 'http') === 0) {
                    $image_src = htmlspecialchars($image['image_path']);
                } 
                // Check if it's a local file that exists
                elseif (file_exists('uploads/gallery/' . $image['image_path'])) {
                    $image_src = 'uploads/gallery/' . htmlspecialchars($image['image_path']);
                } 
                // Fallback to placeholder
                else {
                    $seeds = ['preschool1', 'kindergarten2', 'kids3', 'school4', 'children5', 'education6'];
                    $image_src = "https://picsum.photos/seed/" . $seeds[$index % 6] . "/400/280";
                }
            } else {
                // Use placeholder images based on index
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
        <!-- Default gallery images if no images in database -->
        <?php for ($i = 0; $i < 6; $i++): ?>
          <div class="gallery-item" onclick="openGalleryCarousel(<?php echo $i; ?>)" style="cursor: pointer;">
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

<section id="contact">
  <div class="container">
    <div class="section-title">
      <span>Contact Us</span>
      <h2>Get In Touch</h2>
    </div>

    <div class="row">
      <div class="col-lg-8 mx-auto">
        <div class="text-center">
          <p class="lead mb-4">
            Ready to give your child the best start? We'd love to hear from you!
          </p>
          <div class="d-flex gap-3 justify-content-center">
            <a href="contact.php" class="btn btn-theme btn-lg">
              <i class="fas fa-envelope me-2"></i>Contact Form
            </a>
            <a href="tel:+1234567890" class="btn btn-outline-primary btn-lg">
              <i class="fas fa-phone me-2"></i>Call Us
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4">
        <h4>Kidzenia Kindergarten</h4>
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
        <a href="#about">About Us</a>
        <a href="#programs">Programs</a>
        <a href="#gallery">Gallery</a>
        <a href="auth/login.php">Admin Login</a>
      </div>

      <div class="col-lg-3 col-md-6">
        <h4>Programs</h4>
        <a href="#programs">Toddler Program</a>
        <a href="#programs">Nursery</a>
        <a href="#programs">Kindergarten</a>
        <a href="#programs">Day Care</a>
      </div>

      <div class="col-lg-3">
        <h4>Contact</h4>
        <p><i class="fa-solid fa-location-dot me-2"></i> 123 Education Street, Learning City</p>
        <p><i class="fa-solid fa-phone me-2"></i> +91 9876543210</p>
        <p><i class="fa-solid fa-envelope me-2"></i> hello@kidzenia.com</p>
      </div>
    </div>

    <div class="footer-bottom">
      <p>© <?php echo date('Y'); ?> Kidzenia Kindergarten. All Rights Reserved.</p>
    </div>
  </div>
</footer>

<!-- Gallery Carousel Modal -->
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

<!-- Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="fas fa-bullhorn me-2"></i>
          <span id="modalAnnouncementTitle">Announcement Details</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <small class="text-muted" id="modalAnnouncementDate"></small>
        </div>
        <div class="mb-3">
          <p id="modalAnnouncementContent"></p>
        </div>
        <div class="border-top pt-3">
          <small class="text-muted">
            <strong>Author:</strong> <span id="modalAnnouncementAuthor"></span>
          </small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="shareAnnouncement()">
          <i class="fas fa-share me-2"></i>Share
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title">
          <i class="fas fa-calendar-alt me-2"></i>
          <span id="modalEventTitle">Event Details</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="event-date">
              <i class="fas fa-calendar me-2"></i>
              <span id="modalEventDate"></span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="text-muted">
              <i class="fas fa-map-marker-alt me-2"></i>
              <span id="modalEventLocation"></span>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <p id="modalEventDescription"></p>
        </div>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <strong>RSVP:</strong> Please contact the school office to confirm your attendance.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning" onclick="addToCalendar()">
          <i class="fas fa-calendar-plus me-2"></i>Add to Calendar
        </button>
        <button type="button" class="btn btn-primary" onclick="shareEvent()">
          <i class="fas fa-share me-2"></i>Share Event
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Store announcements and events data for modal use
  const announcements = <?php echo json_encode($announcements); ?>;
  const events = <?php echo json_encode($events); ?>;
  
  // Store gallery data for carousel
  const galleryImages = <?php echo json_encode($gallery_images); ?>;
  let currentCarouselIndex = 0;
  
  // Gallery Carousel Functions
  function openGalleryCarousel(index) {
    currentCarouselIndex = index;
    updateCarouselImage();
    document.getElementById('galleryCarousel').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
  }
  
  function closeGalleryCarousel() {
    document.getElementById('galleryCarousel').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
  }
  
  function navigateCarousel(direction) {
    const totalImages = galleryImages.length || 6; // Fallback to 6 if no database images
    currentCarouselIndex += direction;
    
    // Wrap around navigation
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
      
      // Use real image if path exists, otherwise use placeholder
      if (image.image_path) {
        // Check if it's an external URL
        if (image.image_path.startsWith('http')) {
          imageSrc = image.image_path;
        } else {
          imageSrc = 'uploads/gallery/' + image.image_path;
        }
      } else {
        // Use placeholder image
        const seeds = ['preschool1', 'kindergarten2', 'kids3', 'school4', 'children5', 'education6'];
        imageSrc = "https://picsum.photos/seed/" + seeds[currentCarouselIndex % 6] + "/1200/800";
      }
      
      title = image.title || 'Gallery Image';
      description = image.description || 'Beautiful moment at Kidzenia Kindergarten';
    } else {
      // Fallback for when no database images
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
  
  // Keyboard navigation for carousel
  document.addEventListener('keydown', function(event) {
    const carousel = document.getElementById('galleryCarousel');
    if (carousel.style.display === 'block') {
      if (event.key === 'Escape') {
        closeGalleryCarousel();
      } else if (event.key === 'ArrowLeft') {
        navigateCarousel(-1);
      } else if (event.key === 'ArrowRight') {
        navigateCarousel(1);
      }
    }
  });
  
  // Close carousel when clicking outside the image
  document.getElementById('galleryCarousel').addEventListener('click', function(event) {
    if (event.target === this) {
      closeGalleryCarousel();
    }
  });

  // Announcement modal functions
  function showAnnouncementModal(id) {
    const announcement = announcements.find(a => a.id == id);
    if (announcement) {
      document.getElementById('modalAnnouncementTitle').textContent = announcement.title;
      document.getElementById('modalAnnouncementDate').textContent = 'Published: ' + new Date(announcement.publish_date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
      document.getElementById('modalAnnouncementContent').textContent = announcement.content;
      document.getElementById('modalAnnouncementAuthor').textContent = announcement.author_name || 'Admin';
      
      const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
      modal.show();
    }
  }

  // Event modal functions
  function showEventModal(id) {
    const event = events.find(e => e.id == id);
    if (event) {
      document.getElementById('modalEventTitle').textContent = event.title;
      document.getElementById('modalEventDate').textContent = new Date(event.event_date).toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
      document.getElementById('modalEventLocation').textContent = event.location || 'School Campus';
      document.getElementById('modalEventDescription').textContent = event.description;
      
      const modal = new bootstrap.Modal(document.getElementById('eventModal'));
      modal.show();
    }
  }

  // Share functions
  function shareAnnouncement() {
    const title = document.getElementById('modalAnnouncementTitle').textContent;
    const content = document.getElementById('modalAnnouncementContent').textContent;
    
    if (navigator.share) {
      navigator.share({
        title: title,
        text: content,
        url: window.location.href
      });
    } else {
      // Fallback - copy to clipboard
      const text = `${title}\n\n${content}\n\n${window.location.href}`;
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
        text: `${date}\n${location}\n\n${window.location.href}`,
        url: window.location.href
      });
    } else {
      // Fallback - copy to clipboard
      const text = `${title}\n${date}\n${location}\n\n${window.location.href}`;
      navigator.clipboard.writeText(text).then(() => {
        alert('Event details copied to clipboard!');
      });
    }
  }

  function addToCalendar() {
    const title = document.getElementById('modalEventTitle').textContent;
    const date = document.getElementById('modalEventDate').textContent;
    const location = document.getElementById('modalEventLocation').textContent;
    
    // Create a simple calendar event (in a real app, this would integrate with calendar APIs)
    const calendarEvent = `BEGIN:VEVENT
SUMMARY:${title}
DTSTART:${new Date().toISOString()}
LOCATION:${location}
END:VEVENT`;
    
    // For demo purposes, just show an alert
    alert(`Event "${title}" has been noted!\n\nDate: ${date}\nLocation: ${location}\n\nIn a full implementation, this would add to your calendar.`);
  }

  const counters = document.querySelectorAll('.counter');

  counters.forEach(counter => {
    counter.innerText = '0';

    const updateCounter = () => {
      const target = +counter.getAttribute('data-target');
      const current = +counter.innerText;

      const increment = target / 80;

      if(current < target) {
        counter.innerText = `${Math.ceil(current + increment)}`;
        setTimeout(updateCounter, 30);
      } else {
        counter.innerText = target;
      }
    }

    updateCounter();
  });

  window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');

    if(window.scrollY > 50) {
      navbar.style.padding = '12px 0';
      navbar.style.background = 'rgba(255,255,255,0.95)';
    } else {
      navbar.style.padding = '18px 0';
      navbar.style.background = 'rgba(255,255,255,0.85)';
    }
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
</script>

</body>
</html>
