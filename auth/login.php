<?php
require_once '../config/config.php';

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-form {
            padding: 3rem;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 16px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            transition: transform 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .school-info {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="row g-0">
                <div class="col-lg-6">
                    <div class="login-header">
                        <div class="logo">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h2>Kidzenia Kindergarten</h2>
                        <p class="mb-0">Where Learning Begins with Joy</p>
                        
                        <div class="school-info">
                            <h5><i class="fas fa-clock me-2"></i>School Hours</h5>
                            <p>9:00 AM - 12:30 PM</p>
                            <h5><i class="fas fa-phone me-2"></i>Contact</h5>
                            <p>+1234567890</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login-form">
                        <h3 class="mb-4">Welcome Back!</h3>
                        
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
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                            
                            <div class="text-center">
                                <p class="mb-0">Default Login: admin / admin123</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
