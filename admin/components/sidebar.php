<?php
// Common sidebar component for all admin pages
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="components/sidebar.css">

<nav class="sidebar">
    <div class="p-3">
        <h4 class="text-center mb-4">
            <i class="fas fa-graduation-cap me-2"></i>Kidzenia
        </h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'homepage_cms.php' ? 'active' : ''; ?>" href="homepage_cms.php">
                    <i class="fas fa-home me-2"></i>Homepage CMS
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'students.php' ? 'active' : ''; ?>" href="students.php">
                    <i class="fas fa-user-graduate me-2"></i>Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'teachers.php' ? 'active' : ''; ?>" href="teachers.php">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Teachers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'classes.php' ? 'active' : ''; ?>" href="classes.php">
                    <i class="fas fa-school me-2"></i>Classes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'attendance.php' ? 'active' : ''; ?>" href="attendance.php">
                    <i class="fas fa-calendar-check me-2"></i>Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                    <i class="fas fa-bullhorn me-2"></i>Announcements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'events.php' ? 'active' : ''; ?>" href="events.php">
                    <i class="fas fa-calendar-alt me-2"></i>Events
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'gallery.php' ? 'active' : ''; ?>" href="gallery.php">
                    <i class="fas fa-images me-2"></i>Gallery
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                    <i class="fas fa-envelope me-2"></i>Messages
                    <?php if (isset($unread_notifications) && $unread_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../readme.php" target="_blank">
                    <i class="fas fa-info-circle me-2"></i>Features Guide
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
