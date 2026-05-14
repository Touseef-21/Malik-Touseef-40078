<?php
require_once '../config.php';

// Check if user is student
if (!isLoggedIn() || getUserType() != 'student') {
    redirect('../login.php');
}

$student = getCurrentUser();
$page_title = "Student Dashboard";
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
                <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a>
                <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Student Dashboard</h1>
            <p>Welcome back, <?php echo $student['Name']; ?>! Continue your learning journey.</p>
        </div>

        <!-- Quick Stats -->
        <div class="dashboard-grid">
            <?php
            // Get student statistics
            $student_id = $_SESSION['user_id'];
            
            $stats_sql = [
                'active_courses' => "SELECT COUNT(*) as count FROM ENROLLMENT WHERE FK_Student_ID = ? AND Status = 'active'",
                'completed_courses' => "SELECT COUNT(*) as count FROM ENROLLMENT WHERE FK_Student_ID = ? AND Status = 'completed'",
                'pending_assignments' => "SELECT COUNT(DISTINCT a.Assign_ID) as count 
                                        FROM ASSIGNMENT a 
                                        JOIN ENROLLMENT e ON a.FK_Course_ID = e.FK_Course_ID 
                                        WHERE e.FK_Student_ID = ? 
                                        AND a.Due_Date >= CURDATE() 
                                        AND a.Assign_ID NOT IN (
                                            SELECT FK_Assign_ID FROM SUBMISSION WHERE FK_Student_ID = ?
                                        )",
                'avg_progress' => "SELECT AVG(Progress_Percentage) as avg FROM ENROLLMENT WHERE FK_Student_ID = ? AND Status = 'active'"
            ];
            
            $stats = [];
            foreach ($stats_sql as $key => $sql) {
                $stmt = $conn->prepare($sql);
                if ($key == 'pending_assignments') {
                    $stmt->bind_param("ii", $student_id, $student_id);
                } else {
                    $stmt->bind_param("i", $student_id);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $stats[$key] = $result->fetch_assoc();
            }
            ?>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> Active Courses</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['active_courses']['count']; ?></h2>
                    <p>Courses you're currently enrolled in</p>
                    <a href="my_courses.php" class="btn-small">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-tasks"></i> Pending Assignments</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['pending_assignments']['count']; ?></h2>
                    <p>Assignments to complete</p>
                    <a href="assignments.php" class="btn-small">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Average Progress</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo number_format($stats['avg_progress']['avg'] ?? 0, 1); ?>%</h2>
                    <p>Overall course progress</p>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-certificate"></i> Certificates</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['completed_courses']['count']; ?></h2>
                    <p>Courses completed</p>
                    <a href="certificates.php" class="btn-small">View</a>
                </div>
            </div>
        </div>

        <!-- Continue Learning -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-play-circle"></i> Continue Learning</h3>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT c.*, e.Progress_Percentage 
                       FROM ENROLLMENT e 
                       JOIN COURSE c ON e.FK_Course_ID = c.Course_ID 
                       WHERE e.FK_Student_ID = ? AND e.Status = 'active' 
                       ORDER BY e.Enroll_Date DESC 
                       LIMIT 3";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<div class="courses-grid">';
                    while($row = $result->fetch_assoc()) {
                        ?>
                        <div class="course-card">
                            <div class="course-image">
                                <img src="<?php echo $row['Thumbnail'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3'; ?>" alt="<?php echo $row['Title']; ?>">
                            </div>
                            <div class="course-content">
                                <h3><?php echo $row['Title']; ?></h3>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $row['Progress_Percentage']; ?>%">
                                        <?php echo $row['Progress_Percentage']; ?>%
                                    </div>
                                </div>
                                <div class="course-footer">
                                    <a href="course.php?id=<?php echo $row['Course_ID']; ?>" class="btn-primary btn-small">Continue</a>
                                    <a href="course_materials.php?id=<?php echo $row['Course_ID']; ?>" class="btn-outline btn-small">Materials</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    echo '</div>';
                } else {
                    echo '<p>You are not enrolled in any courses yet. <a href="../index.php#courses">Browse courses</a> to get started.</p>';
                }
                ?>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT * FROM NOTIFICATION 
                       WHERE User_ID = ? AND User_Type = 'student' 
                       ORDER BY Created_Date DESC 
                       LIMIT 5";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $icon = $row['Is_Read'] ? 'fa-envelope-open' : 'fa-envelope';
                        $class = $row['Is_Read'] ? '' : 'unread';
                        ?>
                        <div class="notification-item <?php echo $class; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <div>
                                <h4><?php echo $row['Title']; ?></h4>
                                <p><?php echo $row['Message']; ?></p>
                                <small><?php echo date('M d, H:i', strtotime($row['Created_Date'])); ?></small>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p>No notifications at the moment.</p>';
                }
                ?>
                <a href="notifications.php" class="btn-outline btn-block">View All Notifications</a>
            </div>
        </div>

        <!-- Upcoming Deadlines -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-alt"></i> Upcoming Deadlines</h3>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT a.*, c.Title as CourseTitle 
                       FROM ASSIGNMENT a 
                       JOIN ENROLLMENT e ON a.FK_Course_ID = e.FK_Course_ID 
                       JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                       WHERE e.FK_Student_ID = ? 
                       AND a.Due_Date >= CURDATE() 
                       AND a.Assign_ID NOT IN (
                           SELECT FK_Assign_ID FROM SUBMISSION WHERE FK_Student_ID = ?
                       )
                       ORDER BY a.Due_Date ASC 
                       LIMIT 5";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $student_id, $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $due_date = date('M d, Y', strtotime($row['Due_Date']));
                        $days_left = floor((strtotime($row['Due_Date']) - time()) / (60 * 60 * 24));
                        ?>
                        <div class="assignment-item">
                            <h4><?php echo $row['Title']; ?></h4>
                            <p><strong>Course:</strong> <?php echo $row['CourseTitle']; ?></p>
                            <p><strong>Due:</strong> <?php echo $due_date; ?> 
                            (<?php echo $days_left > 0 ? $days_left . ' days left' : 'Today'; ?>)</p>
                            <a href="submit_assignment.php?id=<?php echo $row['Assign_ID']; ?>" class="btn-primary btn-small">Submit</a>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p>No upcoming deadlines. Great job!</p>';
                }
                ?>
                <a href="assignments.php" class="btn-outline btn-block">View All Assignments</a>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>