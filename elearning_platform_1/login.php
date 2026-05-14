<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $user_type = sanitize($_POST['user_type']);
    
    // Determine table and ID field based on user type
    switch ($user_type) {
        case 'student':
            $table = 'STUDENT';
            $id_field = 'Student_ID';
            $name_field = 'Name';
            break;
        case 'instructor':
            $table = 'INSTRUCTOR';
            $id_field = 'Instructor_ID';
            $name_field = 'Name';
            break;
        case 'admin':
            $table = 'ADMIN';
            $id_field = 'Admin_ID';
            $name_field = 'Username';
            break;
        default:
            $error = 'Invalid user type';
    }
    
    if (!$error) {
        // Query the database
        $sql = "SELECT * FROM $table WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // In production, use password_verify() with hashed passwords
            if ($password === $user['Password']) { // Simple check for demo
                // Set session variables
                $_SESSION['user_id'] = $user[$id_field];
                $_SESSION['user_type'] = $user_type;
                $_SESSION['user_name'] = $user[$name_field];
                $_SESSION['user_email'] = $user['Email'];
                
                // Update last login
                $update_sql = "UPDATE $table SET Last_Login = NOW() WHERE $id_field = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $_SESSION['user_id']);
                $update_stmt->execute();
                
                // Redirect to dashboard
                redirect('dashboard.php');
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Learning Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-sign-in-alt"></i> Login</h1>
                <p>Access your learning dashboard</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="user_type"><i class="fas fa-user-tag"></i> Login As</label>
                    <select id="user_type" name="user_type" class="form-control" required>
                        <option value="">Select User Type</option>
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="forgot_password.php">Forgot your password?</a></p>
                <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
            </div>
            
            <div class="demo-credentials">
                <h4>Demo Credentials:</h4>
                <p><strong>Student:</strong> ilham.nawaz@student.com / password123</p>
                <p><strong>Instructor:</strong> ali.khan@university.edu / password123</p>
                <p><strong>Admin:</strong> admin@elearning.com / password123</p>
            </div>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>