<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
$user_type = getUserType();
$page_title = "Dashboard - E-Learning Platform";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                E-Learning Dashboard
            </a>
            <div class="nav-menu">
                <span class="user-greeting">
                    <i class="fas fa-user-circle"></i> 
                    <?php echo $_SESSION['user_name']; ?> (<?php echo ucfirst($user_type); ?>)
                </span>
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo $_SESSION['user_name']; ?>!</h1>
            <p><?php echo ucfirst($user_type); ?> Dashboard</p>
        </div>

        <!-- Dashboard Content Based on User Type -->
        <?php if ($user_type == 'student'): ?>
            <!-- Student Dashboard -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> My Courses</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT c.*, e.Enroll_Date, e.Progress_Percentage 
                               FROM ENROLLMENT e 
                               JOIN COURSE c ON e.FK_Course_ID = c.Course_ID 
                               WHERE e.FK_Student_ID = ? AND e.Status = 'active'";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="course-item">
                                    <h4><?php echo $row['Title']; ?></h4>
                                    <p>Enrolled: <?php echo date('M d, Y', strtotime($row['Enroll_Date'])); ?></p>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $row['Progress_Percentage']; ?>%">
                                            <?php echo $row['Progress_Percentage']; ?>%
                                        </div>
                                    </div>
                                    <a href="student/course.php?id=<?php echo $row['Course_ID']; ?>" class="btn-small">Continue Learning</a>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p>You are not enrolled in any courses yet.</p>";
                            echo "<a href='index.php#courses' class='btn-primary'>Browse Courses</a>";
                        }
                        ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-tasks"></i> Pending Assignments</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT a.*, c.Title as CourseTitle 
                               FROM ASSIGNMENT a 
                               JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                               JOIN ENROLLMENT e ON c.Course_ID = e.FK_Course_ID 
                               WHERE e.FK_Student_ID = ? 
                               AND a.Due_Date >= CURDATE() 
                               AND a.Assign_ID NOT IN (
                                   SELECT FK_Assign_ID FROM SUBMISSION WHERE FK_Student_ID = ?
                               )";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
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
                                    <a href="student/submit_assignment.php?id=<?php echo $row['Assign_ID']; ?>" class="btn-small">Submit</a>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p>No pending assignments. Great job!</p>";
                        }
                        ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Progress Overview</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calculate overall progress
                        $sql = "SELECT AVG(Progress_Percentage) as AvgProgress 
                               FROM ENROLLMENT 
                               WHERE FK_Student_ID = ? AND Status = 'active'";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $progress = $result->fetch_assoc();
                        
                        // Get completed courses
                        $sql_completed = "SELECT COUNT(*) as Completed 
                                         FROM ENROLLMENT 
                                         WHERE FK_Student_ID = ? AND Status = 'completed'";
                        $stmt = $conn->prepare($sql_completed);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $completed = $stmt->get_result()->fetch_assoc();
                        ?>
                        <div class="stats-summary">
                            <div class="stat-item">
                                <h4><?php echo number_format($progress['AvgProgress'] ?? 0, 1); ?>%</h4>
                                <p>Average Progress</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php echo $completed['Completed'] ?? 0; ?></h4>
                                <p>Courses Completed</p>
                            </div>
                        </div>
                        <div class="progress-bar large">
                            <div class="progress" style="width: <?php echo $progress['AvgProgress'] ?? 0; ?>%">
                                <?php echo number_format($progress['AvgProgress'] ?? 0, 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> Notifications</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT * FROM NOTIFICATION 
                               WHERE User_ID = ? AND User_Type = 'student' 
                               ORDER BY Created_Date DESC LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
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
                            echo "<p>No new notifications.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>

        <?php elseif ($user_type == 'instructor'): ?>
            <!-- Instructor Dashboard -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> My Courses</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT c.*, COUNT(e.Enroll_ID) as StudentCount 
                               FROM COURSE c 
                               LEFT JOIN ENROLLMENT e ON c.Course_ID = e.FK_Course_ID 
                               WHERE c.FK_Instructor_ID = ? 
                               GROUP BY c.Course_ID 
                               ORDER BY c.Created_Date DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="course-item">
                                    <h4><?php echo $row['Title']; ?></h4>
                                    <p>Students: <?php echo $row['StudentCount']; ?></p>
                                    <p>Status: <span class="status-badge <?php echo $row['Status']; ?>">
                                        <?php echo ucfirst($row['Status']); ?>
                                    </span></p>
                                    <a href="instructor/manage_course.php?id=<?php echo $row['Course_ID']; ?>" class="btn-small">Manage</a>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p>You haven't created any courses yet.</p>";
                            echo "<a href='instructor/create_course.php' class='btn-primary'>Create Course</a>";
                        }
                        ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-tasks"></i> Submissions to Grade</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT s.*, a.Title as AssignmentTitle, 
                               stu.Name as StudentName, c.Title as CourseTitle 
                               FROM SUBMISSION s 
                               JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                               JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                               JOIN STUDENT stu ON s.FK_Student_ID = stu.Student_ID 
                               WHERE c.FK_Instructor_ID = ? AND s.Status = 'submitted' 
                               ORDER BY s.Submission_Date ASC 
                               LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="submission-item">
                                    <h4><?php echo $row['AssignmentTitle']; ?></h4>
                                    <p><strong>Student:</strong> <?php echo $row['StudentName']; ?></p>
                                    <p><strong>Course:</strong> <?php echo $row['CourseTitle']; ?></p>
                                    <p>Submitted: <?php echo date('M d, Y', strtotime($row['Submission_Date'])); ?></p>
                                    <a href="instructor/grade_submission.php?id=<?php echo $row['Sub_ID']; ?>" class="btn-small">Grade Now</a>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p>No submissions to grade at the moment.</p>";
                        }
                        ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Course Statistics</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT 
                               COUNT(DISTINCT c.Course_ID) as TotalCourses,
                               SUM(e.StudentCount) as TotalStudents,
                               AVG(e.StudentCount) as AvgStudents
                               FROM COURSE c 
                               LEFT JOIN (
                                   SELECT FK_Course_ID, COUNT(*) as StudentCount 
                                   FROM ENROLLMENT 
                                   GROUP BY FK_Course_ID
                               ) e ON c.Course_ID = e.FK_Course_ID 
                               WHERE c.FK_Instructor_ID = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $stats = $result->fetch_assoc();
                        ?>
                        <div class="stats-summary">
                            <div class="stat-item">
                                <h4><?php echo $stats['TotalCourses'] ?? 0; ?></h4>
                                <p>Total Courses</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php echo $stats['TotalStudents'] ?? 0; ?></h4>
                                <p>Total Students</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php echo number_format($stats['AvgStudents'] ?? 0, 1); ?></h4>
                                <p>Avg Students/Course</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-comments"></i> Recent Discussions</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT d.*, c.Title as CourseTitle 
                               FROM DISCUSSION d 
                               JOIN COURSE c ON d.FK_Course_ID = c.Course_ID 
                               WHERE c.FK_Instructor_ID = ? 
                               ORDER BY d.Created_Date DESC 
                               LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="discussion-item">
                                    <h4><?php echo $row['Title']; ?></h4>
                                    <p><strong>Course:</strong> <?php echo $row['CourseTitle']; ?></p>
                                    <small><?php echo date('M d, H:i', strtotime($row['Created_Date'])); ?></small>
                                    <a href="instructor/discussion.php?id=<?php echo $row['Discussion_ID']; ?>" class="btn-small">View</a>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p>No recent discussions.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>

        <?php elseif ($user_type == 'admin'): ?>
            <!-- Admin Dashboard -->
            <div class="dashboard-grid">
                <div class="dashboard-card wide">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> System Overview</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get system statistics
                        $stats = [];
                        
                        // Total Students
                        $sql = "SELECT COUNT(*) as count FROM STUDENT";
                        $result = $conn->query($sql);
                        $stats['students'] = $result->fetch_assoc()['count'];
                        
                        // Total Instructors
                        $sql = "SELECT COUNT(*) as count FROM INSTRUCTOR WHERE Status = 'active'";
                        $result = $conn->query($sql);
                        $stats['instructors'] = $result->fetch_assoc()['count'];
                        
                        // Total Courses
                        $sql = "SELECT COUNT(*) as count FROM COURSE WHERE Status = 'active'";
                        $result = $conn->query($sql);
                        $stats['courses'] = $result->fetch_assoc()['count'];
                        
                        // Total Enrollments
                        $sql = "SELECT COUNT(*) as count FROM ENROLLMENT WHERE Status = 'active'";
                        $result = $conn->query($sql);
                        $stats['enrollments'] = $result->fetch_assoc()['count'];
                        
                        // Total Revenue
                        $sql = "SELECT SUM(Amount) as total FROM PAYMENT WHERE Status = 'completed'";
                        $result = $conn->query($sql);
                        $stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;
                        
                        // Recent Users
                        $sql = "SELECT Name, Email, 'student' as type, Registration_Date as date 
                               FROM STUDENT 
                               UNION 
                               SELECT Name, Email, 'instructor' as type, Joining_Date as date 
                               FROM INSTRUCTOR 
                               ORDER BY date DESC LIMIT 5";
                        $recent_users = $conn->query($sql);
                        ?>
                        
                        <div class="stats-grid admin-stats">
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <h3><?php echo $stats['students']; ?></h3>
                                <p>Total Students</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <h3><?php echo $stats['instructors']; ?></h3>
                                <p>Active Instructors</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-book"></i>
                                <h3><?php echo $stats['courses']; ?></h3>
                                <p>Active Courses</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-user-graduate"></i>
                                <h3><?php echo $stats['enrollments']; ?></h3>
                                <p>Active Enrollments</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-money-bill-wave"></i>
                                <h3>Rs. <?php echo number_format($stats['revenue'], 2); ?></h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Recent Users</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($recent_users->num_rows > 0) {
                            while($row = $recent_users->fetch_assoc()) {
                                ?>
                                <div class="user-item">
                                    <div class="user-info">
                                        <h4><?php echo $row['Name']; ?></h4>
                                        <p><?php echo $row['Email']; ?></p>
                                        <small><?php echo ucfirst($row['type']); ?> • 
                                            <?php echo date('M d', strtotime($row['date'])); ?></small>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p>No users found.</p>";
                        }
                        ?>
                        <a href="admin/manage_users.php" class="btn-primary btn-block">Manage All Users</a>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-cog"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <a href="admin/manage_courses.php" class="btn-action">
                                <i class="fas fa-book"></i> Manage Courses
                            </a>
                            <a href="admin/manage_instructors.php" class="btn-action">
                                <i class="fas fa-chalkboard-teacher"></i> Manage Instructors
                            </a>
                            <a href="admin/manage_payments.php" class="btn-action">
                                <i class="fas fa-credit-card"></i> View Payments
                            </a>
                            <a href="admin/reports.php" class="btn-action">
                                <i class="fas fa-chart-bar"></i> Generate Reports
                            </a>
                            <a href="admin/settings.php" class="btn-action">
                                <i class="fas fa-sliders-h"></i> System Settings
                            </a>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> System Notifications</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT * FROM NOTIFICATION 
                               WHERE User_Type = 'admin' 
                               ORDER BY Created_Date DESC LIMIT 5";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="notification-item <?php echo $row['Is_Read'] ? '' : 'unread'; ?>">
                                    <i class="fas fa-bell"></i>
                                    <div>
                                        <h4><?php echo $row['Title']; ?></h4>
                                        <p><?php echo $row['Message']; ?></p>
                                        <small><?php echo date('M d, H:i', strtotime($row['Created_Date'])); ?></small>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p>No system notifications.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 E-Learning Platform. All rights reserved. | 
                   Logged in as: <?php echo $_SESSION['user_name']; ?> (<?php echo ucfirst($user_type); ?>) | 
                   <a href="logout.php">Logout</a></p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>