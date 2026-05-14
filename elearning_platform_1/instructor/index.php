<?php
require_once '../config.php';

// Check if user is instructor
if (!isLoggedIn() || getUserType() != 'instructor') {
    redirect('../login.php');
}

$instructor = getCurrentUser();
$instructor_id = $_SESSION['user_id'];
$page_title = "Instructor Dashboard";
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
    <!-- Instructor Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                Instructor Portal
            </a>
            <div class="nav-menu">
                <span class="user-greeting">
                    <i class="fas fa-chalkboard-teacher"></i> <?php echo $instructor['Name']; ?>
                </span>
                <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a>
                <a href="create_course.php"><i class="fas fa-plus"></i> Create Course</a>
                <a href="grade_assignments.php"><i class="fas fa-tasks"></i> Grade Assignments</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-chalkboard-teacher"></i> Instructor Dashboard</h1>
            <p>Welcome back, <?php echo $instructor['Name']; ?>! Manage your courses and students.</p>
        </div>

        <!-- Quick Stats -->
        <div class="dashboard-grid">
            <?php
            // Get instructor statistics
            $stats_sql = [
                'total_courses' => "SELECT COUNT(*) as count FROM COURSE WHERE FK_Instructor_ID = ?",
                'active_courses' => "SELECT COUNT(*) as count FROM COURSE WHERE FK_Instructor_ID = ? AND Status = 'active'",
                'total_students' => "SELECT COUNT(DISTINCT e.FK_Student_ID) as count 
                                   FROM ENROLLMENT e 
                                   JOIN COURSE c ON e.FK_Course_ID = c.Course_ID 
                                   WHERE c.FK_Instructor_ID = ? AND e.Status = 'active'",
                'pending_grading' => "SELECT COUNT(*) as count 
                                    FROM SUBMISSION s 
                                    JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                                    JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                                    WHERE c.FK_Instructor_ID = ? AND s.Status = 'submitted'"
            ];
            
            $stats = [];
            foreach ($stats_sql as $key => $sql) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $instructor_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $stats[$key] = $result->fetch_assoc();
            }
            ?>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> Total Courses</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['total_courses']['count']; ?></h2>
                    <p>Courses created</p>
                    <a href="my_courses.php" class="btn-small">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Total Students</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['total_students']['count']; ?></h2>
                    <p>Active students</p>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-tasks"></i> Pending Grading</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['pending_grading']['count']; ?></h2>
                    <p>Submissions to grade</p>
                    <a href="grade_assignments.php" class="btn-small">Grade Now</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Earnings</h3>
                </div>
                <div class="card-body">
                    <h2>Rs. <?php
                    $sql = "SELECT SUM(p.Amount) as total 
                           FROM PAYMENT p 
                           JOIN COURSE c ON p.FK_Course_ID = c.Course_ID 
                           WHERE c.FK_Instructor_ID = ? AND p.Status = 'completed'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $instructor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $earnings = $result->fetch_assoc();
                    echo number_format($earnings['total'] ?? 0, 2);
                    ?></h2>
                    <p>Total revenue</p>
                </div>
            </div>
        </div>

        <!-- Recent Courses -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Recent Courses</h3>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT c.*, COUNT(e.Enroll_ID) as StudentCount 
                       FROM COURSE c 
                       LEFT JOIN ENROLLMENT e ON c.Course_ID = e.FK_Course_ID 
                       WHERE c.FK_Instructor_ID = ? 
                       GROUP BY c.Course_ID 
                       ORDER BY c.Created_Date DESC 
                       LIMIT 5";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $instructor_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<table class="data-table">';
                    echo '<thead>
                            <tr>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Students</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>';
                    
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($row['Title']) . '</strong></td>';
                        echo '<td><span class="status-badge ' . $row['Status'] . '">' . ucfirst($row['Status']) . '</span></td>';
                        echo '<td>' . $row['StudentCount'] . '</td>';
                        echo '<td>' . date('M d, Y', strtotime($row['Created_Date'])) . '</td>';
                        echo '<td>
                                <a href="edit_course.php?id=' . $row['Course_ID'] . '" class="btn-small">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                              </td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p>You haven\'t created any courses yet.</p>';
                    echo '<a href="create_course.php" class="btn-primary">Create Your First Course</a>';
                }
                ?>
            </div>
        </div>

        <!-- Recent Submissions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-inbox"></i> Recent Submissions</h3>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT s.*, a.Title as AssignmentTitle, stu.Name as StudentName, 
                       c.Title as CourseTitle 
                       FROM SUBMISSION s 
                       JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                       JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                       JOIN STUDENT stu ON s.FK_Student_ID = stu.Student_ID 
                       WHERE c.FK_Instructor_ID = ? 
                       ORDER BY s.Submission_Date DESC 
                       LIMIT 5";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $instructor_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        ?>
                        <div class="submission-item">
                            <div class="submission-info">
                                <h4><?php echo htmlspecialchars($row['AssignmentTitle']); ?></h4>
                                <p><strong>Student:</strong> <?php echo htmlspecialchars($row['StudentName']); ?></p>
                                <p><strong>Course:</strong> <?php echo htmlspecialchars($row['CourseTitle']); ?></p>
                                <p><strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($row['Submission_Date'])); ?></p>
                            </div>
                            <div class="submission-actions">
                                <?php if ($row['Status'] == 'submitted'): ?>
                                    <a href="grade_submission.php?id=<?php echo $row['Sub_ID']; ?>" 
                                       class="btn-primary btn-small">
                                        <i class="fas fa-check"></i> Grade
                                    </a>
                                <?php else: ?>
                                    <span class="badge badge-success">Graded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p>No recent submissions.</p>';
                }
                ?>
                <a href="grade_assignments.php" class="btn-outline btn-block">View All Submissions</a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="action-buttons">
                    <a href="create_course.php" class="btn-action">
                        <i class="fas fa-plus-circle"></i> Create New Course
                    </a>
                    <a href="my_courses.php" class="btn-action">
                        <i class="fas fa-edit"></i> Manage Courses
                    </a>
                    <a href="grade_assignments.php" class="btn-action">
                        <i class="fas fa-check-double"></i> Grade Assignments
                    </a>
                    <a href="students.php" class="btn-action">
                        <i class="fas fa-user-graduate"></i> View Students
                    </a>
                    <a href="analytics.php" class="btn-action">
                        <i class="fas fa-chart-bar"></i> View Analytics
                    </a>
                    <a href="profile.php" class="btn-action">
                        <i class="fas fa-user-cog"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <style>
        .submission-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .submission-item:last-child {
            border-bottom: none;
        }
        
        .submission-info h4 {
            margin-bottom: 0.5rem;
        }
        
        .submission-info p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</body>
</html>