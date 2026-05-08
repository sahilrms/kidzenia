<?php
session_start();

// Simple test authentication (bypass database for testing)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Simple test credentials
    if ($username == 'admin' && $password == 'admin123') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['full_name'] = 'System Administrator';
        $_SESSION['user_role'] = 'admin';
        $_SESSION['profile_image'] = '';
        
        header("Location: ../admin/");
        exit();
    } elseif ($username == 'teacher' && $password == 'teacher123') {
        $_SESSION['user_id'] = 2;
        $_SESSION['username'] = 'teacher';
        $_SESSION['full_name'] = 'Test Teacher';
        $_SESSION['user_role'] = 'teacher';
        $_SESSION['profile_image'] = '';
        
        header("Location: ../dashboard.php");
        exit();
    } elseif ($username == 'parent' && $password == 'parent123') {
        $_SESSION['user_id'] = 3;
        $_SESSION['username'] = 'parent';
        $_SESSION['full_name'] = 'Test Parent';
        $_SESSION['user_role'] = 'parent';
        $_SESSION['profile_image'] = '';
        
        header("Location: ../dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login - Kidzenia Kindergarten</title>
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
        .test-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .credential-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
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
                            <i class="fas fa-graduation-cap" style="font-size: 3rem;"></i>
                        </div>
                        <h2>Kidzenia Kindergarten</h2>
                        <p class="mb-0">Simple Test Login</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login-form">
                        <h3 class="mb-4">Test Login</h3>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
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
                            
                            <button type="submit" class="btn btn-primary btn-login mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>
                        
                        <div class="test-credentials">
                            <h5 class="mb-3">Test Credentials:</h5>
                            
                            <div class="credential-item">
                                <strong>Admin:</strong><br>
                                Username: <code>admin</code><br>
                                Password: <code>admin123</code>
                            </div>
                            
                            <div class="credential-item">
                                <strong>Teacher:</strong><br>
                                Username: <code>teacher</code><br>
                                Password: <code>teacher123</code>
                            </div>
                            
                            <div class="credential-item">
                                <strong>Parent:</strong><br>
                                Username: <code>parent</code><br>
                                Password: <code>parent123</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
