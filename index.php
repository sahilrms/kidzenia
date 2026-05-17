<?php
require_once 'config/config.php';
require_once 'config/app_settings.php';

// Get general settings from database
function get_general_settings($db) {
    try {
        return load_app_settings($db);
    } catch(PDOException $exception) {
        return app_settings_defaults();
    }
}

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
    
    // Get general settings
    $general_settings = get_general_settings($db);
    
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
  <link rel="stylesheet" href="assets/style.css">
  
  <?php
  // Favicon from CMS
  $favicon = $cms_content['header']['favicon']['image_path'] ?? $cms_content['header']['favicon']['content_value'] ?? '';
  if (!empty($favicon)) {
      if (strpos($favicon, 'http') === 0) {
          $favicon_src = $favicon;
      } else {
          $favicon_src = 'uploads/homepage/' . $favicon;
      }
      echo '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($favicon_src) . '">';
  }
  ?>
  
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
        <li class="nav-item"><a class="nav-link" href="#reviews">Reviews</a></li>
        <li class="nav-item"><a class="nav-link" href="./social_media">Social Media</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact"><?php echo htmlspecialchars($cms_content['nav']['link_contact']['content_value'] ?? 'Contact'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="#feedback">Feedback</a></li>
      </ul>

     <div class="header-contact">
    
  

</div>

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
            <h5><?php echo htmlspecialchars($cms_content['hero']['floating_card_1_icon']['content_value'] ?? 'Art'); ?> <?php echo htmlspecialchars($cms_content['hero']['floating_card_1_title']['content_value'] ?? 'Creative Learning'); ?></h5>
            <p><?php echo htmlspecialchars($cms_content['hero']['floating_card_1_description']['content_value'] ?? 'Interactive & playful education'); ?></p>
          </div>

          <div class="floating-card card-2">
            <h5><?php echo htmlspecialchars($cms_content['hero']['floating_card_2_icon']['content_value'] ?? 'Bus'); ?> <?php echo htmlspecialchars($cms_content['hero']['floating_card_2_title']['content_value'] ?? 'Smart Transport'); ?></h5>
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
  
    </a>
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
   <a href="gallery.php"
    style="
    text-decoration:none;
    background:linear-gradient(135deg,#ff6b6b,#ff8e53);
    color:white;
    padding:14px 24px;
    border-radius:50px;
    font-weight:bold;
    box-shadow:0 5px 15px rgba(255,107,107,.3);
    margin-top:20px;
    margin-left:auto;
    margin-right:auto;
    display:block;
    text-align:center;
    width:fit-content;
    ">
        View Full Gallery &rarr;
    </a>
</section>

<section class="bg-white" id="reviews">
  <div class="container">
    <div class="section-title">
      <span>Testimonials</span>
      <h2>What Parents Say</h2>
    </div>

    <div class="reviews-carousel-wrapper">
      <div class="reviews-carousel" id="reviewsCarousel">
        <div class="reviews-track" id="reviewsTrack">
          <div class="col-lg-4 col-md-6">
            <div class="review-card">
              <div class="review-icon"><i class="fas fa-quote-left"></i></div>
              <p>"Kidzenia has been a wonderful experience for our child. The teachers are caring and the curriculum is excellent!"</p>
              <div class="review-name">- Sarah Johnson</div>
              <div class="review-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="review-card">
              <div class="review-icon orange"><i class="fas fa-quote-left"></i></div>
              <p>"The safe and nurturing environment gives us peace of mind. Our daughter loves going to school every day!"</p>
              <div class="review-name">- Michael Chen</div>
              <div class="review-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="review-card">
              <div class="review-icon sky"><i class="fas fa-quote-left"></i></div>
              <p>"Best decision we made for our toddler. The play-based learning approach is perfect for early childhood development."</p>
              <div class="review-name">- Emily Rodriguez</div>
              <div class="review-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            </div>
          </div>
        </div>
      </div>
      <button class="carousel-nav carousel-prev" id="carouselPrev">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button class="carousel-nav carousel-next" id="carouselNext">
        <i class="fas fa-chevron-right"></i>
      </button>
      <div class="carousel-dots" id="carouselDots"></div>
    </div>
  </div>
</section>

<section id="feedback">
  <div class="container">
    <div class="section-title">
      <span>Your Voice</span>
      <h2>Share Your Feedback</h2>
    </div>

    <div class="text-center">
      <p class="lead mb-4">We value your opinion! Share your experience with us.</p>
      <button type="button" class="btn btn-theme btn-lg" data-bs-toggle="modal" data-bs-target="#feedbackModal">
        <i class="fas fa-comment-dots me-2"></i>Give Feedback
      </button>
    </div>
  </div>
</section>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="feedbackModalLabel">Share Your Feedback</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="feedbackForm">
          <div class="mb-3">
            <label class="form-label">Your Name</label>
            <input type="text" name="name" class="form-control" required placeholder="Enter your name">
          </div>
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required placeholder="Enter your email">
          </div>
          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-control" required placeholder="Feedback subject">
          </div>
          <div class="mb-3">
            <label class="form-label">Rating</label>
            <select name="rating" class="form-select" required>
              <option value="">Select a rating</option>
              <option value="5">5 Stars - Excellent</option>
              <option value="4">4 Stars - Very Good</option>
              <option value="3">3 Stars - Good</option>
              <option value="2">2 Stars - Fair</option>
              <option value="1">1 Star - Poor</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Your Feedback</label>
            <textarea name="message" class="form-control" rows="5" required placeholder="Share your thoughts with us"></textarea>
          </div>
          <button type="submit" class="btn btn-theme w-100" id="feedbackSubmitBtn">Submit Feedback</button>
        </form>

        <div id="feedbackSuccess" class="feedback-success text-center" style="display: none;">
          <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
          <h4>Thank You!</h4>
          <p class="mb-0">Your feedback has been submitted and will appear after admin approval.</p>
        </div>
      </div>
    </div>
  </div>
</div>

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
            <?php $primaryPhone = app_setting_list($general_settings['school_phone'])[0] ?? ''; ?>
            <a href="tel:<?php echo htmlspecialchars(app_phone_link($primaryPhone)); ?>" class="btn btn-outline-primary btn-lg">
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
        <h4>Kidzenia PreSchool</h4>
        <p>
          Creating joyful learning experiences for children through creativity, care, and innovation.
        </p>

        <div class="social-icons mt-4">
          <?php if (!empty($general_settings['facebook_url'])): ?>
            <a href="<?php echo htmlspecialchars($general_settings['facebook_url']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
          <?php endif; ?>
          <?php if (!empty($general_settings['twitter_url'])): ?>
            <a href="<?php echo htmlspecialchars($general_settings['twitter_url']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
          <?php endif; ?>
          <?php if (!empty($general_settings['instagram_url'])): ?>
            <a href="<?php echo htmlspecialchars($general_settings['instagram_url']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
          <?php endif; ?>
          <?php if (!empty($general_settings['youtube_url'])): ?>
            <a href="<?php echo htmlspecialchars($general_settings['youtube_url']); ?>" target="_blank"><i class="fab fa-youtube"></i></a>
          <?php endif; ?>
          <?php if (!empty($general_settings['linkedin_url'])): ?>
            <a href="<?php echo htmlspecialchars($general_settings['linkedin_url']); ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
          <?php endif; ?>
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
        <div class="footer-contact-list">
          <span><i class="fa-solid fa-location-dot me-2"></i><?php echo htmlspecialchars($general_settings['school_address']); ?></span>
          <?php foreach (app_setting_list($general_settings['school_phone']) as $phone): ?>
            <a href="tel:<?php echo htmlspecialchars(app_phone_link($phone)); ?>">
              <i class="fa-solid fa-phone me-2"></i><?php echo htmlspecialchars($phone); ?>
            </a>
          <?php endforeach; ?>
          <?php foreach (app_setting_list($general_settings['school_email']) as $email): ?>
            <a href="mailto:<?php echo htmlspecialchars($email); ?>">
              <i class="fa-solid fa-envelope me-2"></i><?php echo htmlspecialchars($email); ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($cms_content['contact']['map_url']['content_value'])): ?>
        <div class="mt-3" style="height: 150px; border-radius: 8px; overflow: hidden;">
          <iframe src="<?php echo htmlspecialchars($cms_content['contact']['map_url']['content_value']); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Kidzenia Kindergarten. All Rights Reserved.</p>
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
        <!-- <button type="button" class="btn btn-warning" onclick="addToCalendar()">
          <i class="fas fa-calendar-plus me-2"></i>Add to Calendar
        </button> -->
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

  const feedbackForm = document.getElementById('feedbackForm');
  const feedbackSubmitBtn = document.getElementById('feedbackSubmitBtn');
  const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));

  if (feedbackForm) {
    feedbackForm.addEventListener('submit', function(event) {
      event.preventDefault();

      const formData = new FormData(feedbackForm);
      formData.append('submit_feedback', 'true');
      feedbackSubmitBtn.disabled = true;
      feedbackSubmitBtn.textContent = 'Submitting...';

      fetch('submit_feedback.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            throw new Error(data.message || 'Unable to submit feedback.');
          }

          feedbackForm.style.display = 'none';
          document.getElementById('feedbackSuccess').style.display = 'block';

          setTimeout(() => {
            feedbackModal.hide();
            feedbackForm.reset();
            feedbackForm.style.display = 'block';
            document.getElementById('feedbackSuccess').style.display = 'none';
          }, 2000);
        })
        .catch(error => {
          alert(error.message || 'Error submitting feedback. Please try again.');
        })
        .finally(() => {
          feedbackSubmitBtn.disabled = false;
          feedbackSubmitBtn.textContent = 'Submit Feedback';
        });
    });
  }

  let reviewCarouselIndex = 0;
  let reviewCarouselTimer = null;

  function getVisibleReviewCount() {
    return 1;
  }

  function updateReviewCarousel() {
    const reviewsTrack = document.getElementById('reviewsTrack');
    const items = reviewsTrack ? Array.from(reviewsTrack.children) : [];
    if (!reviewsTrack || !items.length) {
      return;
    }

    const visibleCount = getVisibleReviewCount();
    const maxIndex = Math.max(0, items.length - visibleCount);
    reviewCarouselIndex = Math.min(reviewCarouselIndex, maxIndex);

    const gap = parseFloat(window.getComputedStyle(reviewsTrack).gap) || 0;
    const slideWidth = items[0].getBoundingClientRect().width + gap;
    reviewsTrack.style.transform = `translateX(-${reviewCarouselIndex * slideWidth}px)`;

    document.querySelectorAll('#carouselDots .carousel-dot').forEach((dot, index) => {
      dot.classList.toggle('active', index === reviewCarouselIndex);
    });
  }

  function buildReviewDots(totalItems) {
    const dots = document.getElementById('carouselDots');
    if (!dots) {
      return;
    }

    const visibleCount = getVisibleReviewCount();
    const dotCount = Math.max(1, totalItems - visibleCount + 1);
    dots.innerHTML = '';

    for (let index = 0; index < dotCount; index++) {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'carousel-dot';
      dot.setAttribute('aria-label', `Show review ${index + 1}`);
      dot.addEventListener('click', () => {
        reviewCarouselIndex = index;
        updateReviewCarousel();
        startReviewCarousel();
      });
      dots.appendChild(dot);
    }
  }

  function moveReviewCarousel(direction) {
    const reviewsTrack = document.getElementById('reviewsTrack');
    const totalItems = reviewsTrack ? reviewsTrack.children.length : 0;
    const visibleCount = getVisibleReviewCount();
    const maxIndex = Math.max(0, totalItems - visibleCount);

    if (!maxIndex) {
      reviewCarouselIndex = 0;
    } else {
      reviewCarouselIndex += direction;
      if (reviewCarouselIndex > maxIndex) {
        reviewCarouselIndex = 0;
      } else if (reviewCarouselIndex < 0) {
        reviewCarouselIndex = maxIndex;
      }
    }

    updateReviewCarousel();
  }

  function startReviewCarousel() {
    clearInterval(reviewCarouselTimer);
    reviewCarouselTimer = setInterval(() => {
      moveReviewCarousel(1);
    }, 5000);
  }

  function initReviewCarousel() {
    const reviewsTrack = document.getElementById('reviewsTrack');
    if (!reviewsTrack) {
      return;
    }

    buildReviewDots(reviewsTrack.children.length);
    updateReviewCarousel();
    startReviewCarousel();

    document.getElementById('carouselPrev')?.addEventListener('click', () => {
      moveReviewCarousel(-1);
      startReviewCarousel();
    });

    document.getElementById('carouselNext')?.addEventListener('click', () => {
      moveReviewCarousel(1);
      startReviewCarousel();
    });
  }

  function renderReviews(reviews) {
    const reviewsTrack = document.getElementById('reviewsTrack');
    if (!reviewsTrack || !reviews.length) {
      initReviewCarousel();
      return;
    }

    const iconClasses = ['', 'orange', 'sky'];
    reviewsTrack.innerHTML = '';

    reviews.forEach((review, index) => {
      const rating = Math.max(1, Math.min(5, parseInt(review.rating, 10) || 5));
      const column = document.createElement('div');
      column.className = 'col-lg-4 col-md-6';

      const card = document.createElement('div');
      card.className = 'review-card';

      const icon = document.createElement('div');
      icon.className = `review-icon ${iconClasses[index % iconClasses.length]}`.trim();
      icon.innerHTML = '<i class="fas fa-quote-left"></i>';

      const message = document.createElement('p');
      message.textContent = `"${review.message || ''}"`;

      const name = document.createElement('div');
      name.className = 'review-name';
      name.textContent = `- ${review.name || 'Parent'}`;

      const stars = document.createElement('div');
      stars.className = 'review-stars';
      stars.textContent = '\u2605'.repeat(rating) + '\u2606'.repeat(5 - rating);

      card.append(icon, message, name, stars);
      column.appendChild(card);
      reviewsTrack.appendChild(column);
    });

    reviewCarouselIndex = 0;
    initReviewCarousel();
  }

  fetch('get_reviews.php')
    .then(response => response.json())
    .then(data => {
      if (data.success && Array.isArray(data.reviews)) {
        renderReviews(data.reviews);
      }
    })
    .catch(error => {
      console.error('Error loading reviews:', error);
      initReviewCarousel();
    });

  window.addEventListener('resize', () => {
    buildReviewDots(document.getElementById('reviewsTrack')?.children.length || 0);
    updateReviewCarousel();
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
