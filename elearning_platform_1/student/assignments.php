<?php
require_once '../config.php';

// Check if user is student
if (!isLoggedIn() || getUserType() != 'student') {
    redirect('../login.php');
}

$student = getCurrentUser();
$student_id = $_SESSION['user_id'];
$page_title = "Assignments";
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
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
                <a href="assignments.php" class="active"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-tasks"></i> Assignments</h1>
            <p>View and submit your course assignments</p>
            
            <!-- Filter Tabs -->
            <div class="tabs">
                <a href="?filter=pending" class="tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="?filter=submitted" class="tab <?php echo $filter == 'submitted' ? 'active' : ''; ?>">
                    <i class="fas fa-paper-plane"></i> Submitted
                </a>
                <a href="?filter=graded" class="tab <?php echo $filter == 'graded' ? 'active' : ''; ?>">
                    <i class="fas fa-check"></i> Graded
                </a>
                <a href="?filter=all" class="tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All
                </a>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="assignments-list">
            <?php
            // Build query based on filter
            $sql = "SELECT a.*, c.Title as CourseTitle, s.Grade, s.Status as SubmissionStatus,
                   s.Submission_Date, s.Feedback 
                   FROM ASSIGNMENT a 
                   JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                   JOIN ENROLLMENT e ON a.FK_Course_ID = e.FK_Course_ID 
                   LEFT JOIN SUBMISSION s ON a.Assign_ID = s.FK_Assign_ID AND s.FK_Student_ID = ?
                   WHERE e.FK_Student_ID = ?";
            
            $params = [$student_id, $student_id];
            
            if ($filter == 'pending') {
                $sql .= " AND (s.Sub_ID IS NULL OR s.Status = 'missing') AND a.Due_Date >= CURDATE()";
            } elseif ($filter == 'submitted') {
                $sql .= " AND s.Sub_ID IS NOT NULL AND s.Status IN ('submitted', 'late')";
            } elseif ($filter == 'graded') {
                $sql .= " AND s.Sub_ID IS NOT NULL AND s.Status = 'graded'";
            }
            
            $sql .= " ORDER BY a.Due_Date ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $due_date = date('M d, Y', strtotime($row['Due_Date']));
                    $days_left = floor((strtotime($row['Due_Date']) - time()) / (60 * 60 * 24));
                    $is_late = $days_left < 0 && !$row['Submission_Date'];
                    $is_submitted = !empty($row['Submission_Date']);
                    ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <h3><?php echo htmlspecialchars($row['Title']); ?></h3>
                            <span class="course-tag"><?php echo $row['CourseTitle']; ?></span>
                        </div>
                        
                        <div class="assignment-body">
                            <p><?php echo htmlspecialchars($row['Description']); ?></p>
                            
                            <div class="assignment-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Due: <?php echo $due_date; ?></span>
                                    <?php if ($days_left >= 0): ?>
                                        <small>(<?php echo $days_left; ?> days left)</small>
                                    <?php else: ?>
                                        <small class="text-danger">(Overdue)</small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="meta-item">
                                    <i class="fas fa-star"></i>
                                    <span>Max Score: <?php echo $row['Max_Score']; ?></span>
                                </div>
                                
                                <?php if ($is_submitted): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-paper-plane"></i>
                                        <span>Submitted: <?php echo date('M d, Y', strtotime($row['Submission_Date'])); ?></span>
                                    </div>
                                    
                                    <?php if ($row['Grade'] !== null): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Grade: <?php echo $row['Grade']; ?>/<?php echo $row['Max_Score']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="assignment-status">
                                <?php if ($is_submitted): ?>
                                    <?php if ($row['Grade'] !== null): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Graded
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-clock"></i> Under Review
                                        </span>
                                    <?php endif; ?>
                                <?php elseif ($is_late): ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-circle"></i> Late
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="assignment-footer">
                            <?php if (!$is_submitted): ?>
                                <a href="submit_assignment.php?id=<?php echo $row['Assign_ID']; ?>" 
                                   class="btn-primary <?php echo $is_late ? 'btn-danger' : ''; ?>">
                                    <i class="fas fa-upload"></i> 
                                    <?php echo $is_late ? 'Submit Late' : 'Submit Assignment'; ?>
                                </a>
                            <?php else: ?>
                                <a href="view_submission.php?id=<?php echo $row['Assign_ID']; ?>" 
                                   class="btn-outline">
                                    <i class="fas fa-eye"></i> View Submission
                                </a>
                                <?php if ($row['Grade'] !== null): ?>
                                    <a href="#" class="btn-success">
                                        <i class="fas fa-chart-bar"></i> View Grade
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <a href="course.php?id=<?php 
                                $course_sql = "SELECT Course_ID FROM ASSIGNMENT WHERE Assign_ID = ?";
                                $course_stmt = $conn->prepare($course_sql);
                                $course_stmt->bind_param("i", $row['Assign_ID']);
                                $course_stmt->execute();
                                $course_result = $course_stmt->get_result();
                                $course = $course_result->fetch_assoc();
                                echo $course['Course_ID'];
                            ?>" class="btn-secondary">
                                <i class="fas fa-book"></i> Go to Course
                            </a>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="empty-state">';
                echo '<i class="fas fa-tasks fa-3x"></i>';
                echo '<h3>No assignments found</h3>';
                echo '<p>You have no ' . $filter . ' assignments at the moment.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <style>
        .assignments-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .assignment-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .course-tag {
            background: #f8f9fa;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .assignment-body {
            margin-bottom: 1.5rem;
        }
        
        .assignment-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }
        
        .meta-item i {
            color: #667eea;
        }
        
        .assignment-status {
            margin: 1rem 0;
        }
        
        .badge-info {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .text-danger {
            color: #e74c3c;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .assignment-footer {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
    </style>
</body>
</html>