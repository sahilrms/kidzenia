<?php
require_once 'config/config.php';

// Get latest announcements
try {
    $database = new Database();
    $db = $database->getConnection();
    
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
    $announcements = [];
    $events = [];
    $gallery_images = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kidzenia Kindergarten - Where Learning Begins with Joy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --text-dark: #2c3e50;
            --text-light: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 100px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }
        
        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 2px;
        }
        
        .announcement-card {
            background: white;
            border-left: 4px solid var(--primary-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .announcement-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .gallery-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            height: 200px;
            margin-bottom: 20px;
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        
        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.7) 100%);
            display: flex;
            align-items: flex-end;
            padding: 15px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }
        
        .event-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--accent-color);
        }
        
        .event-date {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            min-width: 60px;
        }
        
        .footer {
            background: linear-gradient(135deg, var(--text-dark) 0%, #34495e 100%);
            color: white;
            padding: 50px 0 30px;
        }
        
        .footer-widget h5 {
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        
        .footer-widget ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-widget ul li {
            margin-bottom: 10px;
        }
        
        .footer-widget ul li a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-widget ul li a:hover {
            color: var(--accent-color);
        }
        
        .stats-counter {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0 40px;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .feature-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>Kidzenia
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#programs">Programs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#events">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="readme.php">Features Guide</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-gradient ms-2" href="auth/login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Where Learning Begins with Joy</h1>
                    <p class="lead mb-4">Welcome to Kidzenia Kindergarten, where we nurture young minds through play-based learning, creativity, and comprehensive development.</p>
                    <div class="d-flex gap-3">
                        <a href="#programs" class="btn btn-light btn-lg">Explore Programs</a>
                        <a href="#contact" class="btn btn-outline-light btn-lg">Contact Us</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://picsum.photos/seed/kindergarten/600/400" alt="Happy Kids" class="img-fluid rounded-3">
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stats-counter">150+</div>
                    <p class="text-muted">Happy Students</p>
                </div>
                <div class="col-md-3">
                    <div class="stats-counter">20+</div>
                    <p class="text-muted">Experienced Teachers</p>
                </div>
                <div class="col-md-3">
                    <div class="stats-counter">10+</div>
                    <p class="text-muted">Years of Excellence</p>
                </div>
                <div class="col-md-3">
                    <div class="stats-counter">100%</div>
                    <p class="text-muted">Parent Satisfaction</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="about">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Why Choose Kidzenia?</h2>
                <p class="lead text-muted">We provide the best learning environment for your child</p>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <h4>Play-Based Learning</h4>
                        <p class="text-muted">We believe children learn best through play and hands-on experiences.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Small Class Sizes</h4>
                        <p class="text-muted">Personalized attention with our low student-to-teacher ratio.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h4>Creative Curriculum</h4>
                        <p class="text-muted">Innovative teaching methods that foster creativity and critical thinking.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Safe Environment</h4>
                        <p class="text-muted">Child-safe facilities with comprehensive security measures.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>Loving Teachers</h4>
                        <p class="text-muted">Caring educators who nurture each child's unique potential.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h4>Global Perspective</h4>
                        <p class="text-muted">Preparing children for a diverse and interconnected world.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Programs Section -->
    <section class="py-5 bg-light" id="programs">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Programs</h2>
                <p class="lead text-muted">Age-appropriate learning programs for every stage</p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-baby"></i>
                            </div>
                            <h5>Nursery</h5>
                            <p class="text-muted">2-3 years</p>
                            <p>Gentle introduction to learning through play and exploration.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-child"></i>
                            </div>
                            <h5>LKG</h5>
                            <p class="text-muted">3-4 years</p>
                            <p>Building foundational skills through structured activities.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-book"></i>
                            </div>
                            <h5>UKG</h5>
                            <p class="text-muted">4-5 years</p>
                            <p>Developing literacy and numeracy skills through engaging activities.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h5>Prep</h5>
                            <p class="text-muted">5-6 years</p>
                            <p>Preparing for primary school with advanced learning concepts.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Latest Announcements</h2>
                <p class="lead text-muted">Stay updated with important school news</p>
            </div>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-card">
                                <h5><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-calendar me-2"></i><?php echo format_date($announcement['publish_date']); ?>
                                    <?php if ($announcement['author_name']): ?>
                                        <span class="ms-3"><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($announcement['author_name']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-bullhorn fa-3x mb-3"></i>
                            <p>No announcements at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="py-5 bg-light" id="gallery">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Gallery</h2>
                <p class="lead text-muted">Catch a glimpse of our happy learning environment</p>
            </div>
            <div class="row">
                <?php if (!empty($gallery_images)): ?>
                    <?php foreach ($gallery_images as $image): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="gallery-item">
                                <img src="uploads/gallery/<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>">
                                <div class="gallery-overlay">
                                    <div class="text-white">
                                        <h6><?php echo htmlspecialchars($image['title']); ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="gallery-item">
                                <img src="https://picsum.photos/seed/kid<?php echo $i; ?>/400/300" alt="Gallery Image <?php echo $i; ?>">
                                <div class="gallery-overlay">
                                    <div class="text-white">
                                        <h6>Learning Activity <?php echo $i; ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section class="py-5" id="events">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Upcoming Events</h2>
                <p class="lead text-muted">Join us for exciting school events</p>
            </div>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                            <div class="event-card">
                                <div class="d-flex">
                                    <div class="event-date me-3">
                                        <div class="fw-bold"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                                        <div class="small"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5><?php echo htmlspecialchars($event['title']); ?></h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-clock me-2"></i><?php echo $event['event_time'] ? format_time($event['event_time']) : 'All Day'; ?>
                                            <?php if ($event['location']): ?>
                                                <span class="ms-3"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($event['location']); ?></span>
                                            <?php endif; ?>
                                        </p>
                                        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                            <p>No upcoming events at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5 bg-light" id="contact">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Get in Touch</h2>
                <p class="lead text-muted">We'd love to hear from you</p>
            </div>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5>Address</h5>
                        <p class="text-muted">123 Education Street<br>Learning City, 12345</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Phone</h5>
                        <p class="text-muted">+1234567890<br>+0987654321</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email</h5>
                        <p class="text-muted">info@kidzenia.com<br>admissions@kidzenia.com</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-widget">
                        <h4><i class="fas fa-graduation-cap me-2"></i>Kidzenia Kindergarten</h4>
                        <p>Where learning begins with joy. We provide a nurturing environment for your child's early education and development.</p>
                        <div class="mt-3">
                            <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                            <a href="#" class="text-white"><i class="fab fa-youtube fa-lg"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="footer-widget">
                        <h5>Quick Links</h5>
                        <ul>
                            <li><a href="#about">About Us</a></li>
                            <li><a href="#programs">Programs</a></li>
                            <li><a href="#gallery">Gallery</a></li>
                            <li><a href="#events">Events</a></li>
                            <li><a href="#contact">Contact</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="footer-widget">
                        <h5>Office Hours</h5>
                        <ul>
                            <li>Monday - Friday: 8:00 AM - 4:00 PM</li>
                            <li>Saturday: 9:00 AM - 1:00 PM</li>
                            <li>Sunday: Closed</li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr class="bg-light">
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
