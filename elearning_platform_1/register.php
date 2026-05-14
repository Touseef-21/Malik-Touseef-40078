<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = sanitize($_POST['password']);
    $confirm_password = sanitize($_POST['confirm_password']);
    $user_type = sanitize($_POST['user_type']);
    $qualification = isset($_POST['qualification']) ? sanitize($_POST['qualification']) : '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email already exists
        $check_sql = "SELECT Email FROM STUDENT WHERE Email = ? 
                     UNION 
                     SELECT Email FROM INSTRUCTOR WHERE Email = ? 
                     UNION 
                     SELECT Email FROM ADMIN WHERE Email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sss", $email, $email, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Determine table based on user type
            switch ($user_type) {
                case 'student':
                    $table = 'STUDENT';
                    $sql = "INSERT INTO STUDENT (Name, Email, Phone, Password, Registration_Date) 
                           VALUES (?, ?, ?, ?, NOW())";
                    break;
                    
                case 'instructor':
                    $table = 'INSTRUCTOR';
                    $sql = "INSERT INTO INSTRUCTOR (Name, Email, Phone, Qualification, Password, Joining_Date, Status) 
                           VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
                    break;
                    
                default:
                    $error = 'Invalid user type';
                    break;
            }
            
            if (!$error) {
                // Prepare and execute insert statement
                $stmt = $conn->prepare($sql);
                
                if ($user_type == 'student') {
                    $stmt->bind_param("ssss", $name, $email, $phone, $password);
                } else {
                    $stmt->bind_param("sssss", $name, $email, $phone, $qualification, $password);
                }
                
                if ($stmt->execute()) {
                    // Get the new user ID
                    $new_user_id = $conn->insert_id;
                    
                    // Set session and redirect
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    // Add welcome notification
                    $notification_sql = "INSERT INTO NOTIFICATION (User_ID, User_Type, Title, Message, Type) 
                                       VALUES (?, ?, 'Welcome to E-Learning Platform', 'Your account has been created successfully. Start exploring courses!', 'system')";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $notification_stmt->bind_param("is", $new_user_id, $user_type);
                    $notification_stmt->execute();
                    
                    $success = 'Registration successful! Redirecting to dashboard...';
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=dashboard.php");
                } else {
                    $error = 'Registration failed: ' . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - E-Learning Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function toggleInstructorFields() {
            const userType = document.getElementById('user_type').value;
            const instructorFields = document.getElementById('instructor_fields');
            
            if (userType === 'instructor') {
                instructorFields.style.display = 'block';
            } else {
                instructorFields.style.display = 'none';
            }
        }
        
        function validatePassword() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const passwordMatch = document.getElementById('password_match');
            
            if (password === confirmPassword && password !== '') {
                passwordMatch.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                passwordMatch.className = 'password-match valid';
                return true;
            } else {
                passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                passwordMatch.className = 'password-match invalid';
                return false;
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('password_strength');
            const strengthText = document.getElementById('password_strength_text');
            
            let strength = 0;
            
            // Check password length
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 25;
            
            // Check for numbers
            if (/\d/.test(password)) strength += 25;
            
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            // Update strength text and color
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#e74c3c';
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#e74c3c';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#f39c12';
                strengthText.textContent = 'Medium';
                strengthText.style.color = '#f39c12';
            } else {
                strengthBar.style.backgroundColor = '#27ae60';
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#27ae60';
            }
        }
    </script>
    <style>
        .password-match {
            margin-top: 5px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .password-match.valid {
            color: #27ae60;
        }
        
        .password-match.invalid {
            color: #e74c3c;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 5px;
            background: #eee;
            border-radius: 3px;
            margin: 5px 0;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .strength-text {
            font-size: 0.8rem;
            color: #666;
        }
        
        .form-tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #eee;
        }
        
        .form-tab {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .form-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            font-weight: 500;
        }
        
        .form-tab:hover {
            color: #667eea;
            background: #f8f9fa;
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
        }
        
        .register-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .register-info h4 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .register-info ul {
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .register-info li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-user-plus"></i> Create Account</h1>
                <p>Join our learning community</p>
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
            
            <div class="form-tabs">
                <button type="button" class="form-tab active" onclick="showFormSection('student')">Student</button>
                <button type="button" class="form-tab" onclick="showFormSection('instructor')">Instructor</button>
            </div>
            
            <div class="register-info">
                <h4><i class="fas fa-info-circle"></i> Why Join Us?</h4>
                <ul>
                    <li>Access to hundreds of courses</li>
                    <li>Learn from expert instructors</li>
                    <li>Earn certificates upon completion</li>
                    <li>Connect with fellow learners</li>
                    <li>Flexible learning schedule</li>
                </ul>
            </div>
            
            <form method="POST" action="" id="registerForm" onsubmit="return validateForm()">
                <!-- Hidden field for user type -->
                <input type="hidden" name="user_type" id="user_type" value="student">
                
                <!-- Common Fields -->
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           placeholder="Enter your full name" 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email address" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                    <small style="color: #666;">We'll never share your email with anyone else.</small>
                </div>
                
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           placeholder="e.g., 03001234567" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <!-- Student Section (Default) -->
                <div id="student_section" class="form-section active">
                    <div class="form-group">
                        <label for="student_password"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="student_password" name="password" class="form-control" 
                               placeholder="Create a strong password" 
                               onkeyup="checkPasswordStrength()" required>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="password_strength"></div>
                            </div>
                            <div class="strength-text">
                                Strength: <span id="password_strength_text">None</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                        <input type="password" id="student_confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Re-enter your password" 
                               onkeyup="validatePassword()" required>
                        <div id="password_match" class="password-match"></div>
                    </div>
                </div>
                
                <!-- Instructor Section -->
                <div id="instructor_section" class="form-section">
                    <div class="form-group">
                        <label for="qualification"><i class="fas fa-graduation-cap"></i> Qualification *</label>
                        <input type="text" id="qualification" name="qualification" class="form-control" 
                               placeholder="e.g., PhD in Computer Science, MSc in Data Science" 
                               value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>">
                        <small style="color: #666;">Please provide your highest academic qualification</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="instructor_password"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="instructor_password" name="password" class="form-control" 
                               placeholder="Create a strong password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="instructor_confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                        <input type="password" id="instructor_confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Re-enter your password" required>
                    </div>
                    
                    <div class="alert">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Instructor Registration Note:</strong><br>
                            Your account will be reviewed by our admin team. 
                            You'll receive an email once your account is approved.
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a> *
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </div>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function showFormSection(type) {
            // Update tabs
            document.querySelectorAll('.form-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.form-tab[onclick*="${type}"]`).classList.add('active');
            
            // Update hidden field
            document.getElementById('user_type').value = type;
            
            // Show/hide sections
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(`${type}_section`).classList.add('active');
            
            // Update password field IDs for validation
            if (type === 'student') {
                document.getElementById('student_password').name = 'password';
                document.getElementById('student_confirm_password').name = 'confirm_password';
            } else {
                document.getElementById('instructor_password').name = 'password';
                document.getElementById('instructor_confirm_password').name = 'confirm_password';
            }
        }
        
        function validatePassword() {
            const password = document.getElementById('student_password').value;
            const confirmPassword = document.getElementById('student_confirm_password').value;
            const passwordMatch = document.getElementById('password_match');
            
            if (password === confirmPassword && password !== '') {
                passwordMatch.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                passwordMatch.className = 'password-match valid';
                return true;
            } else {
                passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                passwordMatch.className = 'password-match invalid';
                return false;
            }
        }
        
        function validateForm() {
            const userType = document.getElementById('user_type').value;
            const terms = document.getElementById('terms');
            
            // Check terms agreement
            if (!terms.checked) {
                alert('Please agree to the Terms of Service and Privacy Policy');
                return false;
            }
            
            // For student registration, validate password match
            if (userType === 'student') {
                if (!validatePassword()) {
                    alert('Passwords do not match. Please check and try again.');
                    return false;
                }
            }
            
            return true;
        }
        
        // Initialize form
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial state
            showFormSection('student');
            
            // Add real-time password validation
            const passwordFields = document.querySelectorAll('input[type="password"]');
            passwordFields.forEach(field => {
                field.addEventListener('input', function() {
                    if (document.getElementById('user_type').value === 'student') {
                        validatePassword();
                        checkPasswordStrength();
                    }
                });
            });
        });
    </script>
</body>
</html>