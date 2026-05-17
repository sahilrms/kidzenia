<?php
require_once '../config/config.php';
require_once '../config/app_settings.php';

// Load school settings
try {
    $database = new Database();
    $db = $database->getConnection();
    $school_settings = load_app_settings($db);
} catch (Exception $e) {
    $school_settings = app_settings_defaults();
}

// Check if user is already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect(SITE_URL . 'admin/');
    } else {
        redirect(SITE_URL . 'dashboard.php');
    }
}

// Process login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM users WHERE username = :username AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            // Update last login
            $update_query = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $user['id']);
            $update_stmt->execute();
            
            flash_message('success', 'Welcome back, ' . $user['full_name'] . '!');
            
            // Redirect based on role
            if ($user['role'] == 'admin') {
                redirect(SITE_URL . 'admin/');
            } else {
                redirect(SITE_URL . 'dashboard.php');
            }
        } else {
            flash_message('error', 'Invalid username or password!');
        }
    } catch(PDOException $exception) {
        flash_message('error', 'Login failed. Please try again.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kidzenia Kindergarten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 1000px;
            width: 95%;
            margin: 20px;
        }
        .login-header {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/></svg>');
            background-size: 100px 100px;
            opacity: 0.3;
        }
        .school-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border-radius: 50%;
            background: white;
            padding: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        .school-name {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .school-tagline {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        .school-info {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            position: relative;
            z-index: 1;
        }
        .school-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        .school-info-item:last-child {
            margin-bottom: 0;
        }
        .school-info-item i {
            width: 30px;
            font-size: 1.1rem;
            margin-right: 10px;
        }
        .school-info-item a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }
        .school-info-item a:hover {
            opacity: 0.8;
        }
        .login-form {
            padding: 3rem;
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid #e8e8e8;
            padding: 14px 18px;
            font-size: 16px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.15);
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e8e8e8;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #4a90e2;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        .input-group:focus-within .input-group-text {
            border-color: #4a90e2;
        }
        .btn-login {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            color: white;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.4);
        }
        .form-check-input:checked {
            background-color: #4a90e2;
            border-color: #4a90e2;
        }
        .welcome-text {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .welcome-subtext {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .login-container {
                margin: 10px;
            }
            .login-form {
                padding: 2rem;
            }
            .school-name {
                font-size: 1.8rem;
            }
            .school-logo {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="row g-0">
                <div class="col-lg-6">
                    <div class="login-header">
                        <img src="<?php echo SITE_URL; ?>uploads/homepage/1778232759_kidzenia_logo.jpg" alt="<?php echo htmlspecialchars($school_settings['school_name']); ?> Logo" class="school-logo" onerror="this.src='https://via.placeholder.com/120?text=Logo';">
                        <h1 class="school-name"><?php echo htmlspecialchars($school_settings['school_name']); ?></h1>
                        <p class="school-tagline">Where Learning Begins with Joy</p>
                        
                        <div class="school-info">
                            <div class="school-info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($school_settings['school_address']); ?></span>
                            </div>
                            <div class="school-info-item">
                                <i class="fas fa-phone"></i>
                                <a href="tel:<?php echo app_phone_link($school_settings['school_phone']); ?>"><?php echo htmlspecialchars($school_settings['school_phone']); ?></a>
                            </div>
                            <div class="school-info-item">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:<?php echo htmlspecialchars($school_settings['school_email']); ?>"><?php echo htmlspecialchars($school_settings['school_email']); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login-form">
                        <h3 class="welcome-text">Welcome Back!</h3>
                        <p class="welcome-subtext">Please sign in to access your account</p>
                        
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
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label for="username" class="form-label fw-semibold">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                                <a href="#" onclick="alert('Please contact the school administrator to reset your password')" class="text-decoration-none">Forgot Password?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-login mb-4">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                            
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
