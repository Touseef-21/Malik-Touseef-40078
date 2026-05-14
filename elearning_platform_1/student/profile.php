<?php
require_once '../config.php';

// Check if user is student
if (!isLoggedIn() || getUserType() != 'student') {
    redirect('../login.php');
}

$student = getCurrentUser();
$student_id = $_SESSION['user_id'];
$page_title = "My Profile";

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $address = sanitize($_POST['address']);
    
    // Validation
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        $sql = "UPDATE STUDENT SET 
               Name = ?, 
               Phone = ?, 
               Date_of_Birth = ?, 
               Address = ? 
               WHERE Student_ID = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $phone, $date_of_birth, $address, $student_id);
        
        if ($stmt->execute()) {
            $success = 'Profile updated successfully';
            // Update session
            $_SESSION['user_name'] = $name;
            // Refresh student data
            $student = getCurrentUser();
        } else {
            $error = 'Failed to update profile: ' . $conn->error;
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = sanitize($_POST['current_password']);
    $new_password = sanitize($_POST['new_password']);
    $confirm_password = sanitize($_POST['confirm_password']);
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($current_password !== $student['Password']) {
        $error = 'Current password is incorrect';
    } else {
        $sql = "UPDATE STUDENT SET Password = ? WHERE Student_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_password, $student_id);
        
        if ($stmt->execute()) {
            $success = 'Password changed successfully';
        } else {
            $error = 'Failed to change password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - E-Learning Platform</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Student Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                Student Portal
            </a>
            <div class="nav-menu">
                <span class="user-greeting">
                    <i class="fas fa-user-graduate"></i> <?php echo $student['Name']; ?>
                </span>
                <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a>
                <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Manage your personal information and account settings</p>
        </div>

        <!-- Display Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Profile Information -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['Name']); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" id="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['Email']); ?>" 
                                   disabled>
                            <small>Email cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['Phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_of_birth"><i class="fas fa-birthday-cake"></i> Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['Date_of_Birth'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address"><i class="fas fa-home"></i> Address</label>
                            <textarea id="address" name="address" class="form-control" 
                                      rows="3"><?php echo htmlspecialchars($student['Address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Member Since</label>
                            <p><?php echo date('F d, Y', strtotime($student['Registration_Date'])); ?></p>
                        </div>
                        
                        <button type="submit" class="btn-primary btn-block">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Security -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-shield-alt"></i> Account Security</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" placeholder="Enter current password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password"><i class="fas fa-key"></i> New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" placeholder="Enter new password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-key"></i> Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" placeholder="Confirm new password">
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-primary btn-block">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Learning Statistics -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Learning Statistics</h3>
                </div>
                <div class="card-body">
                    <?php
                    // Get learning statistics
                    $stats_sql = [
                        'total_courses' => "SELECT COUNT(*) as count FROM ENROLLMENT WHERE FK_Student_ID = ?",
                        'active_courses' => "SELECT COUNT(*) as count FROM ENROLLMENT WHERE FK_Student_ID = ? AND Status = 'active'",
                        'completed_courses' => "SELECT COUNT(*) as count FROM ENROLLMENT WHERE FK_Student_ID = ? AND Status = 'completed'",
                        'avg_progress' => "SELECT AVG(Progress_Percentage) as avg FROM ENROLLMENT WHERE FK_Student_ID = ? AND Status = 'active'",
                        'total_assignments' => "SELECT COUNT(*) as count FROM SUBMISSION WHERE FK_Student_ID = ?",
                        'avg_grade' => "SELECT AVG(Grade) as avg FROM SUBMISSION WHERE FK_Student_ID = ? AND Grade IS NOT NULL"
                    ];
                    
                    $stats = [];
                    foreach ($stats_sql as $key => $sql) {
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $student_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $stats[$key] = $result->fetch_assoc();
                    }
                    ?>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <h4><?php echo $stats['total_courses']['count']; ?></h4>
                            <p>Total Courses</p>
                        </div>
                        <div class="stat-item">
                            <h4><?php echo $stats['completed_courses']['count']; ?></h4>
                            <p>Completed</p>
                        </div>
                        <div class="stat-item">
                            <h4><?php echo number_format($stats['avg_progress']['avg'] ?? 0, 1); ?>%</h4>
                            <p>Avg Progress</p>
                        </div>
                        <div class="stat-item">
                            <h4><?php echo number_format($stats['avg_grade']['avg'] ?? 0, 1); ?></h4>
                            <p>Avg Grade</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Account Actions</h3>
                </div>
                <div class="card-body">
                    <div class="action-buttons">
                        <a href="learning_history.php" class="btn-action">
                            <i class="fas fa-history"></i> Learning History
                        </a>
                        <a href="certificates.php" class="btn-action">
                            <i class="fas fa-certificate"></i> My Certificates
                        </a>
                        <a href="notifications.php" class="btn-action">
                            <i class="fas fa-bell"></i> Notification Settings
                        </a>
                        <a href="delete_account.php" class="btn-action btn-danger">
                            <i class="fas fa-trash"></i> Delete Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>