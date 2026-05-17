<?php
require_once 'config/config.php';

// Get CMS content
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $cms_query = "SELECT * FROM homepage_cms WHERE is_active = 1 ORDER BY section, content_key";
    $cms_stmt = $db->prepare($cms_query);
    $cms_stmt->execute();
    $cms_results = $cms_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cms_content = [];
    foreach ($cms_results as $row) {
        $cms_content[$row['section']][$row['content_key']] = $row;
    }
    
} catch(PDOException $exception) {
    $cms_content = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Media - Kidzenia Kindergarten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .social-media-section {
            padding: 120px 0 60px;
            background: linear-gradient(135deg, #FFF5F8 0%, #F0FDFC 50%, #FFF8E7 100%);
            min-height: 100vh;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h1 {
            font-size: 48px;
            font-weight: 700;
            color: #2D1B69;
            margin-bottom: 15px;
        }
        
        .section-title p {
            font-size: 18px;
            color: #5D5D7A;
        }
        
        .social-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .social-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            border: 4px solid transparent;
            transition: all 0.3s ease;
        }
        
        .social-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
        }
        
        .social-card.facebook { border-color: #1877F2; }
        .social-card.twitter { border-color: #1DA1F2; }
        .social-card.instagram { border-color: #E4405F; }
        .social-card.youtube { border-color: #FF0000; }
        .social-card.linkedin { border-color: #0A66C2; }
        
        .social-header {
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .social-header i {
            font-size: 28px;
        }
        
        .social-header.facebook i { color: #1877F2; }
        .social-header.twitter i { color: #1DA1F2; }
        .social-header.instagram i { color: #E4405F; }
        .social-header.youtube i { color: #FF0000; }
        .social-header.linkedin i { color: #0A66C2; }
        
        .social-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #2D1B69;
        }
        
        .social-iframe-container {
            height: 500px;
            background: #f8f9fa;
        }
        
        .social-iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .no-social {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .no-social i {
            font-size: 64px;
            color: #FF6B9D;
            margin-bottom: 20px;
        }
        
        .no-social h3 {
            font-size: 28px;
            color: #2D1B69;
            margin-bottom: 15px;
        }
        
        .no-social p {
            font-size: 16px;
            color: #5D5D7A;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .social-grid {
                grid-template-columns: 1fr;
            }
            
            .section-title h1 {
                font-size: 36px;
            }
            
            .social-iframe-container {
                height: 400px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
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
        <li class="nav-item"><a class="nav-link" href="index.php#home"><?php echo htmlspecialchars($cms_content['nav']['link_home']['content_value'] ?? 'Home'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#programs"><?php echo htmlspecialchars($cms_content['nav']['link_programs']['content_value'] ?? 'Programs'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#about"><?php echo htmlspecialchars($cms_content['nav']['link_about']['content_value'] ?? 'About'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#gallery"><?php echo htmlspecialchars($cms_content['nav']['link_gallery']['content_value'] ?? 'Gallery'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#events"><?php echo htmlspecialchars($cms_content['nav']['link_events']['content_value'] ?? 'Events'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#reviews">Reviews</a></li>
        <li class="nav-item"><a class="nav-link" href="social_media.php">Social Media</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#contact"><?php echo htmlspecialchars($cms_content['nav']['link_contact']['content_value'] ?? 'Contact'); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#feedback">Feedback</a></li>
      </ul>

     <div class="header-contact">
   

</div>
          
        </div>
    </nav>

    <!-- Social Media Section -->
    <section class="social-media-section">
        <div class="container">
            <div class="section-title">
                <h1><i class="fas fa-share-alt me-3"></i>Our Social Media</h1>
                <p>Stay connected with us on our social media platforms for updates, events, and more!</p>
            </div>

            <div class="social-grid">
                <?php if (!empty($cms_content['footer']['facebook_url']['content_value'])): ?>
                    <div class="social-card facebook">
                        <div class="social-header facebook">
                            <i class="fab fa-facebook"></i>
                            <h3>Facebook</h3>
                        </div>
                        <div class="social-iframe-container">
                            <?php
                            $fb_url = $cms_content['footer']['facebook_url']['content_value'];
                            // Extract Facebook page ID or username from URL
                            preg_match('/facebook\.com\/([^\/]+)/', $fb_url, $matches);
                            $fb_page = $matches[1] ?? '';
                            ?>
                            <iframe src="https://www.facebook.com/plugins/page.php?href=<?php echo urlencode($fb_url); ?>&tabs=timeline&width=500&height=500&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId" 
                                    width="100%" 
                                    height="100%" 
                                    style="border:none;overflow:hidden" 
                                    scrolling="no" 
                                    frameborder="0" 
                                    allowfullscreen="true" 
                                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                            </iframe>
                        </div>
                        <div class="p-3 text-center">
                            <a href="<?php echo htmlspecialchars($fb_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fab fa-facebook me-2"></i>Open in New Tab
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($cms_content['footer']['twitter_url']['content_value'])): ?>
                    <div class="social-card twitter">
                        <div class="social-header twitter">
                            <i class="fab fa-twitter"></i>
                            <h3>Twitter/X</h3>
                        </div>
                        <div class="social-iframe-container d-flex align-items-center justify-content-center">
                            <div class="text-center p-4">
                                <i class="fab fa-twitter fa-4x mb-3" style="color: #1DA1F2;"></i>
                                <p class="mb-3">Twitter/X does not support iframe embedding.</p>
                                <a href="<?php echo htmlspecialchars($cms_content['footer']['twitter_url']['content_value']); ?>" target="_blank" class="btn btn-primary">
                                    <i class="fab fa-twitter me-2"></i>Visit Our Twitter/X
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($cms_content['footer']['instagram_url']['content_value'])): ?>
                    <div class="social-card instagram">
                        <div class="social-header instagram">
                            <i class="fab fa-instagram"></i>
                            <h3>Instagram</h3>
                        </div>
                        <div class="social-iframe-container d-flex align-items-center justify-content-center">
                            <div class="text-center p-4">
                                <i class="fab fa-instagram fa-4x mb-3" style="color: #E4405F;"></i>
                                <p class="mb-3">Instagram does not support iframe embedding.</p>
                                <a href="<?php echo htmlspecialchars($cms_content['footer']['instagram_url']['content_value']); ?>" target="_blank" class="btn btn-danger">
                                    <i class="fab fa-instagram me-2"></i>Visit Our Instagram
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($cms_content['footer']['youtube_url']['content_value'])): ?>
                    <div class="social-card youtube">
                        <div class="social-header youtube">
                            <i class="fab fa-youtube"></i>
                            <h3>YouTube</h3>
                        </div>
                        <div class="social-iframe-container">
                            <?php
                            $yt_url = $cms_content['footer']['youtube_url']['content_value'];
                            // Extract YouTube channel ID from URL
                            preg_match('/channel\/([a-zA-Z0-9_-]+)/', $yt_url, $channel_matches);
                            preg_match('/\/c\/([a-zA-Z0-9_-]+)/', $yt_url, $c_matches);
                            preg_match('/\/user\/([a-zA-Z0-9_-]+)/', $yt_url, $user_matches);
                            
                            $channel_id = $channel_matches[1] ?? $c_matches[1] ?? $user_matches[1] ?? '';
                            ?>
                            <?php if ($channel_id): ?>
                                <iframe src="https://www.youtube.com/embed?listType=user_uploads&list=<?php echo htmlspecialchars($channel_id); ?>" 
                                        width="100%" 
                                        height="100%" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen>
                                </iframe>
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <div class="text-center p-4">
                                        <i class="fab fa-youtube fa-4x mb-3" style="color: #FF0000;"></i>
                                        <p class="mb-3">Unable to embed YouTube channel.</p>
                                        <a href="<?php echo htmlspecialchars($yt_url); ?>" target="_blank" class="btn btn-danger">
                                            <i class="fab fa-youtube me-2"></i>Visit Our YouTube
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 text-center">
                            <a href="<?php echo htmlspecialchars($yt_url); ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                <i class="fab fa-youtube me-2"></i>Open in New Tab
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($cms_content['footer']['linkedin_url']['content_value'])): ?>
                    <div class="social-card linkedin">
                        <div class="social-header linkedin">
                            <i class="fab fa-linkedin"></i>
                            <h3>LinkedIn</h3>
                        </div>
                        <div class="social-iframe-container d-flex align-items-center justify-content-center">
                            <div class="text-center p-4">
                                <i class="fab fa-linkedin fa-4x mb-3" style="color: #0A66C2;"></i>
                                <p class="mb-3">LinkedIn does not support iframe embedding.</p>
                                <a href="<?php echo htmlspecialchars($cms_content['footer']['linkedin_url']['content_value']); ?>" target="_blank" class="btn btn-primary" style="background-color: #0A66C2; border-color: #0A66C2;">
                                    <i class="fab fa-linkedin me-2"></i>Visit Our LinkedIn
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($cms_content['footer']['facebook_url']['content_value']) && 
                      empty($cms_content['footer']['twitter_url']['content_value']) && 
                      empty($cms_content['footer']['instagram_url']['content_value']) && 
                      empty($cms_content['footer']['youtube_url']['content_value']) && 
                      empty($cms_content['footer']['linkedin_url']['content_value'])): ?>
                <div class="no-social">
                    <i class="fas fa-plug"></i>
                    <h3>No Social Media Links Configured</h3>
                    <p>Please configure your social media URLs in the admin settings to display them here.</p>
                    <a href="auth/login.php" class="btn btn-theme">Go to Admin Settings</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h4><i class="fas fa-graduation-cap me-2"></i>Kidzenia Kindergarten</h4>
                    <p>Where learning begins with joy. We provide a nurturing environment for your child's early education and development.</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php#home" class="text-white-50">Home</a></li>
                        <li class="mb-2"><a href="index.php#programs" class="text-white-50">Programs</a></li>
                        <li class="mb-2"><a href="index.php#gallery" class="text-white-50">Gallery</a></li>
                        <li class="mb-2"><a href="social_media.php" class="text-white-50">Social Media</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-white-50">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Connect With Us</h5>
                    <div class="social-icons">
                        <?php if (!empty($cms_content['footer']['facebook_url']['content_value'])): ?>
                            <a href="<?php echo htmlspecialchars($cms_content['footer']['facebook_url']['content_value']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($cms_content['footer']['twitter_url']['content_value'])): ?>
                            <a href="<?php echo htmlspecialchars($cms_content['footer']['twitter_url']['content_value']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($cms_content['footer']['instagram_url']['content_value'])): ?>
                            <a href="<?php echo htmlspecialchars($cms_content['footer']['instagram_url']['content_value']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($cms_content['footer']['youtube_url']['content_value'])): ?>
                            <a href="<?php echo htmlspecialchars($cms_content['footer']['youtube_url']['content_value']); ?>" target="_blank"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Kidzenia Kindergarten. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
