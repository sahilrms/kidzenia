<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requirements - Preschool Management System</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #2c3e50;
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
        
        .requirements-section {
            padding: 80px 0;
        }
        
        .category-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: all 0.3s;
            height: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .category-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .category-icon::before {
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
        
        .category-card.core .category-icon { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); }
        .category-card.admin .category-icon { background: linear-gradient(135deg, var(--success-color) 0%, #20c997 100%); }
        .category-card.website .category-icon { background: linear-gradient(135deg, var(--info-color) 0%, #00b4d8 100%); }
        .category-card.cms .category-icon { background: linear-gradient(135deg, var(--warning-color) 0%, #f77f00 100%); }
        .category-card.communication .category-icon { background: linear-gradient(135deg, var(--danger-color) 0%, #f72585 100%); }
        .category-card.technical .category-icon { background: linear-gradient(135deg, #7209b7 0%, #560bad 100%); }
        
        .category-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
            text-align: center;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .feature-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li i {
            color: var(--primary-color);
            margin-right: 12px;
            margin-top: 2px;
            width: 20px;
            flex-shrink: 0;
        }
        
        .feature-content {
            flex-grow: 1;
        }
        
        .feature-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        
        .feature-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .priority-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: auto;
            flex-shrink: 0;
        }
        
        .priority-badge.critical { background: #dc3545; color: white; }
        .priority-badge.high { background: #fd7e14; color: white; }
        .priority-badge.medium { background: #ffc107; color: #212529; }
        .priority-badge.low { background: #28a745; color: white; }
        
        .implementation-phase {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .phase-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .phase-items {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .phase-item {
            background: white;
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.85rem;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        
        .comparison-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .comparison-table table {
            margin: 0;
        }
        
        .comparison-table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        
        .comparison-table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .check-icon {
            color: var(--success-color);
            font-size: 1.2rem;
        }
        
        .cross-icon {
            color: var(--danger-color);
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0 40px;
            }
            
            .requirements-section {
                padding: 60px 0;
            }
            
            .category-card {
                padding: 20px;
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
                        Preschool Management System Requirements
                    </h1>
                    <p class="lead mb-4">Comprehensive feature requirements for a modern preschool management system with integrated website and CMS functionality</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-light btn-lg">
                            <i class="fas fa-home me-2"></i>View Demo
                        </a>
                        <a href="readme.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Features Guide
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Requirements Section -->
    <section class="requirements-section">
        <div class="container">
            <!-- Core Management Features -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="category-card core">
                        <div class="category-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        <h3 class="category-title">Core Management Features</h3>
                        <ul class="feature-list">
                            <li>
                                <i class="fas fa-user-graduate"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Student Management</div>
                                    <div class="feature-description">Complete student profiles with enrollment, demographics, medical info, and academic records</div>
                                </div>
                                <span class="priority-badge critical">Critical</span>
                            </li>
                            <li>
                                <i class="fas fa-chalkboard-teacher"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Teacher & Staff Management</div>
                                    <div class="feature-description">Staff profiles, qualifications, assignments, schedules, and performance tracking</div>
                                </div>
                                <span class="priority-badge critical">Critical</span>
                            </li>
                            <li>
                                <i class="fas fa-door-open"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Class & Room Management</div>
                                    <div class="feature-description">Class scheduling, capacity management, room assignments, and teacher-student ratios</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-calendar-check"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Attendance Tracking</div>
                                    <div class="feature-description">Daily attendance, reporting, absentee tracking, and automated notifications</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-clipboard-list"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Curriculum Management</div>
                                    <div class="feature-description">Lesson planning, curriculum mapping, learning outcomes, and progress tracking</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-graduation-cap"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Progress Reports</div>
                                    <div class="feature-description">Student assessments, report cards, developmental milestones, and parent conferences</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Administrative Features -->
                <div class="col-lg-6 mb-4">
                    <div class="category-card admin">
                        <div class="category-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3 class="category-title">Administrative Features</h3>
                        <ul class="feature-list">
                            <li>
                                <i class="fas fa-users-cog"></i>
                                <div class="feature-content">
                                    <div class="feature-title">User Management</div>
                                    <div class="feature-description">Role-based access control, permissions, user accounts, and authentication</div>
                                </div>
                                <span class="priority-badge critical">Critical</span>
                            </li>
                            <li>
                                <i class="fas fa-credit-card"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Fee Management</div>
                                    <div class="feature-description">Tuition fees, payment processing, invoices, financial reports, and scholarship management</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-file-invoice-dollar"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Billing & Invoicing</div>
                                    <div class="feature-description">Automated billing, payment reminders, late fees, and financial analytics</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-chart-line"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Analytics & Reporting</div>
                                    <div class="feature-description">Enrollment statistics, attendance reports, financial dashboards, and KPI tracking</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-warehouse"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Inventory Management</div>
                                    <div class="feature-description">Supplies tracking, equipment management, procurement, and maintenance scheduling</div>
                                </div>
                                <span class="priority-badge low">Low</span>
                            </li>
                            <li>
                                <i class="fas fa-calendar-alt"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Academic Calendar</div>
                                    <div class="feature-description">School year planning, holidays, events, exam schedules, and important dates</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Website & CMS Features -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="category-card website">
                        <div class="category-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h3 class="category-title">Website Features</h3>
                        <ul class="feature-list">
                            <li>
                                <i class="fas fa-home"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Homepage & Landing Pages</div>
                                    <div class="feature-description">Professional design, school branding, call-to-action, and responsive layout</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-info-circle"></i>
                                <div class="feature-content">
                                    <div class="feature-title">About Us Section</div>
                                    <div class="feature-description">School history, mission, vision, facilities, and staff introductions</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-book"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Programs & Curriculum</div>
                                    <div class="feature-description">Age-appropriate programs, curriculum details, learning methodologies</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-images"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Photo Gallery</div>
                                    <div class="feature-description">School activities, events, classroom photos, and student achievements</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-calendar"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Events Calendar</div>
                                    <div class="feature-description">Upcoming events, school calendar, parent-teacher meetings, holidays</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-envelope"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Contact & Inquiry Forms</div>
                                    <div class="feature-description">Contact information, inquiry forms, admission applications, location maps</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- CMS Features -->
                <div class="col-lg-6 mb-4">
                    <div class="category-card cms">
                        <div class="category-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3 class="category-title">CMS Features</h3>
                        <ul class="feature-list">
                            <li>
                                <i class="fas fa-edit"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Content Management</div>
                                    <div class="feature-description">Page creation, editing, publishing, and content organization</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-newspaper"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Blog/News Management</div>
                                    <div class="feature-description">News articles, announcements, blog posts, and content scheduling</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-images"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Media Management</div>
                                    <div class="feature-description">Image uploads, video management, file organization, and media library</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-sitemap"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Menu Management</div>
                                    <div class="feature-description">Navigation menus, page hierarchy, and site structure management</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-palette"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Theme & Template System</div>
                                    <div class="feature-description">Customizable themes, templates, color schemes, and branding options</div>
                                </div>
                                <span class="priority-badge low">Low</span>
                            </li>
                            <li>
                                <i class="fas fa-search"></i>
                                <div class="feature-content">
                                    <div class="feature-title">SEO Management</div>
                                    <div class="feature-description">Meta tags, sitemaps, URL optimization, and search engine tools</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Communication & Technical Features -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="category-card communication">
                        <div class="category-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="category-title">Communication Features</h3>
                        <ul class="feature-list">
                            <li>
                                <i class="fas fa-bell"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Notification System</div>
                                    <div class="feature-description">Real-time alerts, push notifications, SMS integration, and email alerts</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-bullhorn"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Announcement System</div>
                                    <div class="feature-description">School announcements, emergency alerts, targeted messaging, and broadcasts</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-envelope"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Messaging System</div>
                                    <div class="feature-description">Internal messaging, parent-teacher communication, and group messaging</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-mobile-alt"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Mobile App Integration</div>
                                    <div class="feature-description">Mobile app for parents, push notifications, and on-the-go access</div>
                                </div>
                                <span class="priority-badge low">Low</span>
                            </li>
                            <li>
                                <i class="fas fa-video"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Video Conferencing</div>
                                    <div class="feature-description">Virtual meetings, parent-teacher conferences, and online classes</div>
                                </div>
                                <span class="priority-badge low">Low</span>
                            </li>
                            <li>
                                <i class="fas fa-share-alt"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Social Media Integration</div>
                                    <div class="feature-description">Social media sharing, school profiles, and community engagement</div>
                                </div>
                                <span class="priority-badge low">Low</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Technical Requirements -->
                <div class="col-lg-6 mb-4">
                    <div class="category-card technical">
                        <div class="category-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <h3 class="category-title">Technical Requirements</h3>
                        <ul class="feature-list">
                            <li>
                                <i class="fas fa-database"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Database Management</div>
                                    <div class="feature-description">Secure database, data backup, recovery systems, and data integrity</div>
                                </div>
                                <span class="priority-badge critical">Critical</span>
                            </li>
                            <li>
                                <i class="fas fa-lock"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Security Features</div>
                                    <div class="feature-description">Data encryption, secure authentication, GDPR compliance, and access controls</div>
                                </div>
                                <span class="priority-badge critical">Critical</span>
                            </li>
                            <li>
                                <i class="fas fa-mobile"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Responsive Design</div>
                                    <div class="feature-description">Mobile-friendly interface, cross-browser compatibility, and adaptive layouts</div>
                                </div>
                                <span class="priority-badge high">High</span>
                            </li>
                            <li>
                                <i class="fas fa-tachometer-alt"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Performance Optimization</div>
                                    <div class="feature-description">Fast loading times, caching systems, and performance monitoring</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-cloud"></i>
                                <div class="feature-content">
                                    <div class="feature-title">Cloud Hosting</div>
                                    <div class="feature-description">Scalable hosting, CDN integration, and reliable uptime</div>
                                </div>
                                <span class="priority-badge medium">Medium</span>
                            </li>
                            <li>
                                <i class="fas fa-plug"></i>
                                <div class="feature-content">
                                    <div class="feature-title">API Integration</div>
                                    <div class="feature-description">Third-party integrations, payment gateways, and external services</div>
                                </div>
                                <span class="priority-badge low">Low</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Implementation Phases -->
            <div class="row">
                <div class="col-12">
                    <div class="category-card">
                        <h3 class="category-title mb-4">
                            <i class="fas fa-project-diagram me-2"></i>
                            Implementation Phases
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="implementation-phase">
                                    <div class="phase-title">Phase 1: Core Foundation</div>
                                    <div class="phase-items">
                                        <div class="phase-item">User Management</div>
                                        <div class="phase-item">Student Registration</div>
                                        <div class="phase-item">Class Management</div>
                                        <div class="phase-item">Basic Attendance</div>
                                        <div class="phase-item">Website Framework</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="implementation-phase">
                                    <div class="phase-title">Phase 2: Enhanced Features</div>
                                    <div class="phase-items">
                                        <div class="phase-item">Fee Management</div>
                                        <div class="phase-item">Progress Reports</div>
                                        <div class="phase-item">Communication System</div>
                                        <div class="phase-item">CMS Functionality</div>
                                        <div class="phase-item">Photo Gallery</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="implementation-phase">
                                    <div class="phase-title">Phase 3: Advanced Features</div>
                                    <div class="phase-items">
                                        <div class="phase-item">Mobile App</div>
                                        <div class="phase-item">Advanced Analytics</div>
                                        <div class="phase-item">Video Conferencing</div>
                                        <div class="phase-item">Inventory Management</div>
                                        <div class="phase-item">API Integration</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Comparison -->
            <div class="row">
                <div class="col-12">
                    <div class="category-card">
                        <h3 class="category-title mb-4">
                            <i class="fas fa-balance-scale me-2"></i>
                            Essential vs Nice-to-Have Features
                        </h3>
                        
                        <div class="comparison-table">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Feature Category</th>
                                        <th>Essential (MVP)</th>
                                        <th>Enhanced (Version 2)</th>
                                        <th>Advanced (Future)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Student Management</strong></td>
                                        <td><i class="fas fa-check check-icon"></i> Basic profiles, enrollment</td>
                                        <td><i class="fas fa-check check-icon"></i> Progress tracking, reports</td>
                                        <td><i class="fas fa-check check-icon"></i> AI recommendations</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Communication</strong></td>
                                        <td><i class="fas fa-check check-icon"></i> Basic messaging</td>
                                        <td><i class="fas fa-check check-icon"></i> Push notifications</td>
                                        <td><i class="fas fa-check check-icon"></i> Video conferencing</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Website</strong></td>
                                        <td><i class="fas fa-check check-icon"></i> Information pages</td>
                                        <td><i class="fas fa-check check-icon"></i> Dynamic CMS</td>
                                        <td><i class="fas fa-check check-icon"></i> E-commerce integration</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Financial</strong></td>
                                        <td><i class="fas fa-check check-icon"></i> Basic fee tracking</td>
                                        <td><i class="fas fa-check check-icon"></i> Online payments</td>
                                        <td><i class="fas fa-check check-icon"></i> Advanced financial analytics</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Mobile</strong></td>
                                        <td><i class="fas fa-times cross-icon"></i> Responsive web only</td>
                                        <td><i class="fas fa-check check-icon"></i> Mobile app</td>
                                        <td><i class="fas fa-check check-icon"></i> Full mobile suite</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="text-center">
                        <h3 class="mb-4">Ready to Build Your Preschool Management System?</h3>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-eye me-2"></i>View Current Implementation
                            </a>
                            <a href="readme.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-book me-2"></i>Technical Documentation
                            </a>
                            <a href="auth/simple_login.php" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Test Admin Panel
                            </a>
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
                Preschool Management System Requirements
            </p>
            <p class="mb-0 small text-muted">
                Comprehensive feature planning for modern educational institutions
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

        // Observe all category cards
        document.querySelectorAll('.category-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
