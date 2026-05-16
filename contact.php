<?php
require_once 'config/config.php';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $subject = clean_input($_POST['subject']);
    $message = clean_input($_POST['message']);
    
    // Validate form data
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        flash_message('error', 'Please fill in all required fields.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_message('error', 'Please enter a valid email address.');
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Insert contact message into database (optional - for tracking)
            $query = "INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (:name, :email, :phone, :subject, :message, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            
            if ($stmt->execute()) {
                // Send email notification to admin (in a real implementation)
                $to = ADMIN_EMAIL;
                $email_subject = "New Contact Form Submission: " . $subject;
                $email_body = "You have received a new message from the contact form:\n\n";
                $email_body .= "Name: " . $name . "\n";
                $email_body .= "Email: " . $email . "\n";
                $email_body .= "Phone: " . $phone . "\n";
                $email_body .= "Subject: " . $subject . "\n\n";
                $email_body .= "Message:\n" . $message;
                
                $headers = "From: " . $email . "\r\n";
                $headers .= "Reply-To: " . $email . "\r\n";
                
                // In a real implementation, you would send the email
                // mail($to, $email_subject, $email_body, $headers);
                
                flash_message('success', 'Thank you for your message! We will get back to you soon.');
            } else {
                flash_message('error', 'Sorry, there was an error sending your message. Please try again.');
            }
        } catch(PDOException $exception) {
            flash_message('error', 'Error: ' . $exception->getMessage());
        }
    }
    
    redirect('contact.php#contact-form');
}

// Get school information from settings
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $settings_query = "SELECT setting_key, setting_value FROM settings";
    $settings_stmt = $db->prepare($settings_query);
    $settings_stmt->execute();
    $settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Get CMS content for navbar
    $cms_query = "SELECT * FROM homepage_cms WHERE is_active = 1 ORDER BY section, content_key";
    $cms_stmt = $db->prepare($cms_query);
    $cms_stmt->execute();
    $cms_results = $cms_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cms_content = [];
    foreach ($cms_results as $row) {
        $cms_content[$row['section']][$row['content_key']] = $row;
    }
    
} catch(PDOException $exception) {
    $settings = [];
    $cms_content = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Kidzenia Kindergarten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
     <link rel="stylesheet" href="assets/style.css">
     <link rel="stylesheet" href="assets/contactstyle.css">
   
</head>
<body>
    <!-- Navigation -->
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
        <li class="nav-item"><a class="nav-link" href="./index#home"><?php echo htmlspecialchars($cms_content['nav']['link_home']['content_value'] ?? 'Home'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#programs"><?php echo htmlspecialchars($cms_content['nav']['link_programs']['content_value'] ?? 'Programs'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#about"><?php echo htmlspecialchars($cms_content['nav']['link_about']['content_value'] ?? 'About'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#gallery"><?php echo htmlspecialchars($cms_content['nav']['link_gallery']['content_value'] ?? 'Gallery'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#events"><?php echo htmlspecialchars($cms_content['nav']['link_events']['content_value'] ?? 'Events'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="./index#contact"><?php echo htmlspecialchars($cms_content['nav']['link_contact']['content_value'] ?? 'Contact'); ?></a></li>
      </ul>

      <a href="auth/login.php" class="btn btn-theme"><?php echo htmlspecialchars($cms_content['header']['admin_login_text']['content_value'] ?? 'Admin Login'); ?></a>
    </div>
  </div>
</nav>


    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Get in Touch</h1>
                    <p class="lead mb-4">We'd love to hear from you! Whether you have questions about our programs, want to schedule a visit, or need more information, our team is here to help.</p>
                </div>
                <div class="col-lg-6">
                    <img src="https://picsum.photos/seed/contact/600/400" alt="Contact Us" class="img-fluid rounded-3">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <?php
            $flash = get_flash_message();
            if ($flash):
                foreach ($flash as $type => $message):
            ?>
                <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php
                endforeach;
            endif;
            ?>

            <div class="row">
                <!-- Contact Form -->
                <div class="col-lg-6 mb-4">
                    <div class="contact-card" id="contact-form">
                        <h3 class="mb-4">Send us a Message</h3>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subject *</label>
                                <select class="form-select" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="Admission Inquiry">Admission Inquiry</option>
                                    <option value="General Information">General Information</option>
                                    <option value="Schedule a Visit">Schedule a Visit</option>
                                    <option value="Fee Information">Fee Information</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Message *</label>
                                <textarea class="form-control" name="message" rows="5" required placeholder="Tell us how we can help you..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-gradient btn-lg w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-lg-6 mb-4">
                    <div class="contact-card">
                        <h3 class="mb-4">Contact Information</h3>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Address</h5>
                                <p class="text-muted mb-0"><?php echo $settings['school_address'] ?? '123 Education Street, Learning City, 12345'; ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Phone</h5>
                                <p class="text-muted mb-0"><?php echo $settings['school_phone'] ?? '+1234567890'; ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Email</h5>
                                <p class="text-muted mb-0"><?php echo $settings['school_email'] ?? 'info@kidzenia.com'; ?></p>
                            </div>
                        </div>
                        
                        <div class="working-hours">
                            <h5 class="mb-3">
                                <i class="fas fa-clock me-2"></i>Office Hours
                            </h5>
                            <div class="hours-item">
                                <span><?php echo htmlspecialchars($cms_content['office_hours']['monday_friday_label']['content_value'] ?? 'Monday - Friday'); ?></span>
                                <span class="fw-bold"><?php echo htmlspecialchars($cms_content['office_hours']['monday_friday_time']['content_value'] ?? '8:00 AM - 4:00 PM'); ?></span>
                            </div>
                            <div class="hours-item">
                                <span><?php echo htmlspecialchars($cms_content['office_hours']['saturday_label']['content_value'] ?? 'Saturday'); ?></span>
                                <span class="fw-bold"><?php echo htmlspecialchars($cms_content['office_hours']['saturday_time']['content_value'] ?? '9:00 AM - 1:00 PM'); ?></span>
                            </div>
                            <div class="hours-item">
                                <span><?php echo htmlspecialchars($cms_content['office_hours']['sunday_label']['content_value'] ?? 'Sunday'); ?></span>
                                <span class="fw-bold"><?php echo htmlspecialchars($cms_content['office_hours']['sunday_time']['content_value'] ?? 'Closed'); ?></span>
                            </div>
                        </div>
                        
                        <div class="social-links">
                            <?php if (!empty($cms_content['footer']['facebook_url']['content_value'])): ?>
                                <a href="<?php echo htmlspecialchars($cms_content['footer']['facebook_url']['content_value']); ?>" class="social-link" target="_blank">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($cms_content['footer']['twitter_url']['content_value'])): ?>
                                <a href="<?php echo htmlspecialchars($cms_content['footer']['twitter_url']['content_value']); ?>" class="social-link" target="_blank">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($cms_content['footer']['instagram_url']['content_value'])): ?>
                                <a href="<?php echo htmlspecialchars($cms_content['footer']['instagram_url']['content_value']); ?>" class="social-link" target="_blank">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($cms_content['footer']['youtube_url']['content_value'])): ?>
                                <a href="<?php echo htmlspecialchars($cms_content['footer']['youtube_url']['content_value']); ?>" class="social-link" target="_blank">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($cms_content['footer']['linkedin_url']['content_value'])): ?>
                                <a href="<?php echo htmlspecialchars($cms_content['footer']['linkedin_url']['content_value']); ?>" class="social-link" target="_blank">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <?php if (!empty($cms_content['contact']['map_url']['content_value'])): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="contact-card">
                        <h3 class="mb-4">Find Us</h3>
                        <div class="map-container">
                            <iframe src="<?php echo htmlspecialchars($cms_content['contact']['map_url']['content_value']); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 mb-4">
                    <h4><i class="fas fa-graduation-cap me-2"></i>Kidzenia Kindergarten</h4>
                    <p>Where learning begins with joy. We provide a nurturing environment for your child's early education and development.</p>
                    <div class="mt-3">
                        <?php if (!empty($cms_content['footer']['facebook_url']['content_value'])): ?>
                            <a href="<?php echo htmlspecialchars($cms_content['footer']['facebook_url']['content_value']); ?>" class="text-white me-3" target="_blank"><i class="fab fa-facebook fa-lg"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($cms_content['footer']['twitter_url']['content_value'])): ?>
                            <a href="<?php echo htmlspecialchars($cms_content['footer']['twitter_url']['content_value']); ?>" class="text-white me-3" target="_blank"><i class="fab fa-twitter fa-lg"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($cms_content['footer']['instagram_url']['content_value'])): ?>
                            <a href="<?php echo htmlspecialchars($cms_content['footer']['instagram_url']['content_value']); ?>" class="text-white me-3" target="_blank"><i class="fab fa-instagram fa-lg"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($cms_content['footer']['youtube_url']['content_value'])): ?>
                            <a href="<?php echo htmlspecialchars($cms_content['footer']['youtube_url']['content_value']); ?>" class="text-white" target="_blank"><i class="fab fa-youtube fa-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php#about" class="text-white-50">About Us</a></li>
                        <li class="mb-2"><a href="index.php#programs" class="text-white-50">Programs</a></li>
                        <li class="mb-2"><a href="index.php#gallery" class="text-white-50">Gallery</a></li>
                        <li class="mb-2"><a href="index.php#events" class="text-white-50">Events</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-white-50">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Contact Info</h5>
                    <p class="text-white-50">
                        <i class="fas fa-map-marker-alt me-2"></i><?php echo $settings['school_address'] ?? '123 Education Street, Learning City'; ?><br>
                        <i class="fas fa-phone me-2"></i><?php echo $settings['school_phone'] ?? '+1234567890'; ?><br>
                        <i class="fas fa-envelope me-2"></i><?php echo $settings['school_email'] ?? 'info@kidzenia.com'; ?>
                    </p>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Location</h5>
                    <?php if (!empty($cms_content['contact']['map_url']['content_value'])): ?>
                    <div class="map-container-footer" style="height: 200px; border-radius: 8px; overflow: hidden;">
                        <iframe src="<?php echo htmlspecialchars($cms_content['contact']['map_url']['content_value']); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <?php else: ?>
                    <p class="text-white-50">Map not configured. Please set the map URL in admin settings.</p>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Kidzenia Kindergarten. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
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

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const subject = document.querySelector('select[name="subject"]').value;
            const message = document.querySelector('textarea[name="message"]').value.trim();
            
            if (!name || !email || !subject || !message) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.backdropFilter = 'blur(10px)';
            } else {
                navbar.style.background = 'white';
                navbar.style.backdropFilter = 'none';
            }
        });
    </script>
</body>
</html>
