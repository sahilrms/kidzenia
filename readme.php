<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kidzenia Kindergarten - Features Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 80px 0 60px;
            text-align: center;
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
        
        .feature-section {
            padding: 80px 0;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .feature-icon::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            opacity: 0.1;
            z-index: -1;
        }
        
        .feature-card.admin .feature-icon { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); }
        .feature-card.student .feature-icon { background: linear-gradient(135deg, var(--success-color) 0%, #20c997 100%); }
        .feature-card.teacher .feature-icon { background: linear-gradient(135deg, var(--info-color) 0%, #00b4d8 100%); }
        .feature-card.parent .feature-icon { background: linear-gradient(135deg, var(--warning-color) 0%, #f77f00 100%); }
        .feature-card.communication .feature-icon { background: linear-gradient(135deg, var(--danger-color) 0%, #f72585 100%); }
        .feature-card.ui .feature-icon { background: linear-gradient(135deg, #7209b7 0%, #560bad 100%); }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li i {
            color: var(--primary-color);
            margin-right: 10px;
            width: 20px;
        }
        
        .demo-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }
        
        .demo-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .tech-stack {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .tech-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .tech-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
            color: white;
        }
        
        .tech-icon.php { background: #777BB4; }
        .tech-icon.mysql { background: #4479A1; }
        .tech-icon.bootstrap { background: #7952B3; }
        .tech-icon.html { background: #E34C26; }
        .tech-icon.js { background: #F7DF1E; color: #333; }
        
        .stats-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        
        .quick-actions {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }
        
        .action-button {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px 20px;
            margin: 5px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .action-button:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .action-button i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0 40px;
            }
            
            .feature-section {
                padding: 60px 0;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">
                        <i class="fas fa-graduation-cap me-3"></i>
                        Kidzenia Kindergarten Management System
                    </h1>
                    <p class="lead mb-4">A comprehensive, modern PHP-based kindergarten school management system with full-featured admin panel, responsive design, and powerful communication tools.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="demo-button">
                            <i class="fas fa-home me-2"></i>Visit Website
                        </a>
                        <a href="auth/simple_login.php" class="demo-button">
                            <i class="fas fa-sign-in-alt me-2"></i>Test Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="feature-section">
        <div class="container">
            <div class="stats-section">
                <h2 class="text-center mb-5">System Overview</h2>
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">12+</div>
                            <div class="stat-label">Core Features</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">3</div>
                            <div class="stat-label">User Roles</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Responsive</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">5</div>
                            <div class="stat-label">Tech Stack</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technology Stack -->
            <div class="tech-stack">
                <h3 class="text-center mb-4">Technology Stack</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="tech-item">
                            <div class="tech-icon php">
                                <i class="fab fa-php"></i>
                            </div>
                            <div>
                                <strong>PHP 8.0+</strong><br>
                                <small class="text-muted">Backend programming language</small>
                            </div>
                        </div>
                        <div class="tech-item">
                            <div class="tech-icon mysql">
                                <i class="fas fa-database"></i>
                            </div>
                            <div>
                                <strong>MySQL</strong><br>
                                <small class="text-muted">Database management system</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="tech-item">
                            <div class="tech-icon bootstrap">
                                <i class="fab fa-bootstrap"></i>
                            </div>
                            <div>
                                <strong>Bootstrap 5</strong><br>
                                <small class="text-muted">Modern CSS framework</small>
                            </div>
                        </div>
                        <div class="tech-item">
                            <div class="tech-icon html">
                                <i class="fab fa-html5"></i>
                            </div>
                            <div>
                                <strong>HTML5 & CSS3</strong><br>
                                <small class="text-muted">Modern web standards</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="feature-section bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Core Features</h2>
            
            <div class="row">
                <!-- Admin Panel Features -->
                <div class="col-lg-6 mb-4">
                    <div class="feature-card admin">
                        <div class="feature-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3 class="feature-title">Admin Panel</h3>
                        <p class="text-muted">Complete administrative control over all school operations</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i>Dashboard with real-time statistics</li>
                            <li><i class="fas fa-check"></i>Student management system</li>
                            <li><i class="fas fa-check"></i>Teacher and staff management</li>
                            <li><i class="fas fa-check"></i>Class and curriculum management</li>
                            <li><i class="fas fa-check"></i>Attendance tracking & reporting</li>
                            <li><i class="fas fa-check"></i>Announcement & notification system</li>
                            <li><i class="fas fa-check"></i>Gallery and media management</li>
                            <li><i class="fas fa-check"></i>Events and activities management</li>
                        </ul>
                        <a href="admin/" class="demo-button">
                            <i class="fas fa-tachometer-alt me-2"></i>View Admin Panel
                        </a>
                    </div>
                </div>

                <!-- Student Management -->
                <div class="col-lg-6 mb-4">
                    <div class="feature-card student">
                        <div class="feature-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="feature-title">Student Management</h3>
                        <p class="text-muted">Comprehensive student information and progress tracking</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i>Complete student profiles</li>
                            <li><i class="fas fa-check"></i>Class assignments & scheduling</li>
                            <li><i class="fas fa-check"></i>Attendance records & statistics</li>
                            <li><i class="fas fa-check"></i>Medical information & allergies</li>
                            <li><i class="fas fa-check"></i>Emergency contact details</li>
                            <li><i class="fas fa-check"></i>Progress tracking & reports</li>
                            <li><i class="fas fa-check"></i>Photo gallery per student</li>
                            <li><i class="fas fa-check"></i>Parent-student linking</li>
                        </ul>
                        <a href="admin/students.php" class="demo-button">
                            <i class="fas fa-users me-2"></i>Manage Students
                        </a>
                    </div>
                </div>

                <!-- Teacher Features -->
                <div class="col-lg-6 mb-4">
                    <div class="feature-card teacher">
                        <div class="feature-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3 class="feature-title">Teacher Dashboard</h3>
                        <p class="text-muted">Empowering teachers with classroom management tools</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i>Class assignment & management</li>
                            <li><i class="fas fa-check"></i>Daily attendance marking</li>
                            <li><i class="fas fa-check"></i>Student progress monitoring</li>
                            <li><i class="fas fa-check"></i>Lesson planning tools</li>
                            <li><i class="fas fa-check"></i>Communication with parents</li>
                            <li><i class="fas fa-check"></i>Classroom resources</li>
                            <li><i class="fas fa-check"></i>Activity scheduling</li>
                            <li><i class="fas fa-check"></i>Performance analytics</li>
                        </ul>
                        <a href="dashboard.php" class="demo-button">
                            <i class="fas fa-desktop me-2"></i>Teacher Dashboard
                        </a>
                    </div>
                </div>

                <!-- Parent Features -->
                <div class="col-lg-6 mb-4">
                    <div class="feature-card parent">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Parent Portal</h3>
                        <p class="text-muted">Keeping parents connected to their child's education</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i>Child's progress tracking</li>
                            <li><i class="fas fa-check"></i>Attendance monitoring</li>
                            <li><i class="fas fa-check"></i>Real-time notifications</li>
                            <li><i class="fas fa-check"></i>School announcements</li>
                            <li><i class="fas fa-check"></i>Photo galleries & events</li>
                            <li><i class="fas fa-check"></i>Teacher communication</li>
                            <li><i class="fas fa-check"></i>Fee & payment tracking</li>
                            <li><i class="fas fa-check"></i>Calendar & schedules</li>
                        </ul>
                        <a href="dashboard.php" class="demo-button">
                            <i class="fas fa-home me-2"></i>Parent Portal
                        </a>
                    </div>
                </div>

                <!-- Communication Features -->
                <div class="col-lg-6 mb-4">
                    <div class="feature-card communication">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="feature-title">Communication System</h3>
                        <p class="text-muted">Seamless communication between school and parents</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i>Real-time notifications</li>
                            <li><i class="fas fa-check"></i>Announcement system</li>
                            <li><i class="fas fa-check"></i>Targeted messaging</li>
                            <li><i class="fas fa-check"></i>"What's New" section</li>
                            <li><i class="fas fa-check"></i>Email notifications</li>
                            <li><i class="fas fa-check"></i>SMS alerts (configurable)</li>
                            <li><i class="fas fa-check"></i>Parent-teacher messaging</li>
                            <li><i class="fas fa-check"></i>Event reminders</li>
                        </ul>
                        <a href="admin/announcements.php" class="demo-button">
                            <i class="fas fa-bullhorn me-2"></i>Manage Communications
                        </a>
                    </div>
                </div>

                <!-- UI/UX Features -->
                <div class="col-lg-6 mb-4">
                    <div class="feature-card ui">
                        <div class="feature-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h3 class="feature-title">Modern UI/UX</h3>
                        <p class="text-muted">Beautiful, intuitive, and responsive design</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i>100% responsive design</li>
                            <li><i class="fas fa-check"></i>Mobile-optimized interface</li>
                            <li><i class="fas fa-check"></i>Modern Bootstrap 5 styling</li>
                            <li><i class="fas fa-check"></i>Smooth animations & transitions</li>
                            <li><i class="fas fa-check"></i>Interactive dashboards</li>
                            <li><i class="fas fa-check"></i>Intuitive navigation</li>
                            <li><i class="fas fa-check"></i>Dark/light theme ready</li>
                            <li><i class="fas fa-check"></i>Accessibility compliant</li>
                        </ul>
                        <a href="index.php" class="demo-button">
                            <i class="fas fa-eye me-2"></i>View Website
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions Section -->
    <section class="feature-section">
        <div class="container">
            <div class="quick-actions">
                <h3 class="text-center mb-4">Quick Access</h3>
                <div class="text-center">
                    <a href="index.php" class="action-button">
                        <i class="fas fa-home"></i>Website Home
                    </a>
                    <a href="auth/simple_login.php" class="action-button">
                        <i class="fas fa-sign-in-alt"></i>Test Login
                    </a>
                    <a href="admin/" class="action-button">
                        <i class="fas fa-tachometer-alt"></i>Admin Panel
                    </a>
                    <a href="dashboard.php" class="action-button">
                        <i class="fas fa-desktop"></i>User Dashboard
                    </a>
                    <a href="contact.php" class="action-button">
                        <i class="fas fa-envelope"></i>Contact Form
                    </a>
                    <a href="setup_database.php" class="action-button">
                        <i class="fas fa-database"></i>Setup Database
                    </a>
                </div>
                
                <div class="mt-4 text-center">
                    <h4>Test Login Credentials</h4>
                    <div class="row justify-content-center mt-3">
                        <div class="col-md-3">
                            <div class="alert alert-info">
                                <strong>Admin</strong><br>
                                <code>admin / admin123</code>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-success">
                                <strong>Teacher</strong><br>
                                <code>teacher / teacher123</code>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-warning">
                                <strong>Parent</strong><br>
                                <code>parent / parent123</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">
                <i class="fas fa-graduation-cap me-2"></i>
                Kidzenia Kindergarten Management System
            </p>
            <p class="mb-0 small text-muted">
                Complete School Management Solution | Built with PHP, MySQL & Bootstrap 5
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for anchor links
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

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
