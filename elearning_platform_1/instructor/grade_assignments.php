<?php
require_once '../config.php';

// Check if user is instructor
if (!isLoggedIn() || getUserType() != 'instructor') {
    redirect('../login.php');
}

$instructor = getCurrentUser();
$instructor_id = $_SESSION['user_id'];
$page_title = "Grade Assignments";

// Handle grading
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = sanitize($_POST['submission_id']);
    $grade = sanitize($_POST['grade']);
    $feedback = sanitize($_POST['feedback']);
    
    if (empty($grade) || $grade < 0) {
        $error = 'Please enter a valid grade';
    } else {
        $sql = "UPDATE SUBMISSION SET 
               Grade = ?, 
               Feedback = ?, 
               Status = 'graded' 
               WHERE Sub_ID = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsi", $grade, $feedback, $submission_id);
        
        if ($stmt->execute()) {
            $success = 'Assignment graded successfully!';
            
            // Get submission details for notification
            $notify_sql = "SELECT s.FK_Student_ID, a.Title as AssignmentTitle 
                          FROM SUBMISSION s 
                          JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                          WHERE s.Sub_ID = ?";
            $notify_stmt = $conn->prepare($notify_sql);
            $notify_stmt->bind_param("i", $submission_id);
            $notify_stmt->execute();
            $notify_result = $notify_stmt->get_result();
            $submission = $notify_result->fetch_assoc();
            
            // Add notification for student
            if ($submission) {
                $notification_sql = "INSERT INTO NOTIFICATION 
                                   (User_ID, User_Type, Title, Message, Type) 
                                   VALUES (?, 'student', 'Assignment Graded', 
                                   'Your assignment \"{$submission['AssignmentTitle']}\" has been graded. You scored {$grade}/100', 
                                   'assignment')";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("i", $submission['FK_Student_ID']);
                $notification_stmt->execute();
            }
        } else {
            $error = 'Failed to grade assignment: ' . $conn->error;
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
                <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a>
                <a href="create_course.php"><i class="fas fa-plus"></i> Create Course</a>
                <a href="grade_assignments.php" class="active"><i class="fas fa-tasks"></i> Grade Assignments</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-check-double"></i> Grade Assignments</h1>
            <p>Review and grade student submissions</p>
        </div>

        <!-- Display Messages -->
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

        <!-- Pending Submissions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-inbox"></i> Pending Submissions</h3>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT s.*, a.Title as AssignmentTitle, a.Max_Score, 
                       stu.Name as StudentName, stu.Email as StudentEmail,
                       c.Title as CourseTitle 
                       FROM SUBMISSION s 
                       JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                       JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                       JOIN STUDENT stu ON s.FK_Student_ID = stu.Student_ID 
                       WHERE c.FK_Instructor_ID = ? AND s.Status = 'submitted' 
                       ORDER BY s.Submission_Date ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $instructor_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        ?>
                        <div class="submission-card">
                            <div class="submission-header">
                                <h4><?php echo htmlspecialchars($row['AssignmentTitle']); ?></h4>
                                <span class="badge badge-info">Pending Review</span>
                            </div>
                            
                            <div class="submission-details">
                                <p><strong>Student:</strong> <?php echo htmlspecialchars($row['StudentName']); ?> (<?php echo $row['StudentEmail']; ?>)</p>
                                <p><strong>Course:</strong> <?php echo htmlspecialchars($row['CourseTitle']); ?></p>
                                <p><strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($row['Submission_Date'])); ?></p>
                                <p><strong>Max Score:</strong> <?php echo $row['Max_Score']; ?></p>
                                
                                <?php if ($row['File_Link']): ?>
                                    <p><strong>Submission File:</strong> 
                                       <a href="<?php echo $row['File_Link']; ?>" target="_blank" class="btn-small">
                                           <i class="fas fa-download"></i> Download
                                       </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Grading Form -->
                            <div class="grading-form">
                                <form method="POST" action="">
                                    <input type="hidden" name="submission_id" value="<?php echo $row['Sub_ID']; ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="grade_<?php echo $row['Sub_ID']; ?>">Grade (0-<?php echo $row['Max_Score']; ?>)</label>
                                            <input type="number" id="grade_<?php echo $row['Sub_ID']; ?>" 
                                                   name="grade" class="form-control" 
                                                   min="0" max="<?php echo $row['Max_Score']; ?>" 
                                                   step="0.1" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="feedback_<?php echo $row['Sub_ID']; ?>">Feedback</label>
                                            <textarea id="feedback_<?php echo $row['Sub_ID']; ?>" 
                                                      name="feedback" class="form-control" 
                                                      rows="3" placeholder="Provide constructive feedback..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="grade_submission" class="btn-primary">
                                        <i class="fas fa-check"></i> Submit Grade
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '<i class="fas fa-check-circle fa-3x"></i>';
                    echo '<h3>All caught up!</h3>';
                    echo '<p>No pending submissions to grade at the moment.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Recently Graded -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Recently Graded</h3>
            </div>
            <div class="card-body">
                <?php
                $sql = "SELECT s.*, a.Title as AssignmentTitle, a.Max_Score, 
                       stu.Name as StudentName, c.Title as CourseTitle 
                       FROM SUBMISSION s 
                       JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                       JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                       JOIN STUDENT stu ON s.FK_Student_ID = stu.Student_ID 
                       WHERE c.FK_Instructor_ID = ? AND s.Status = 'graded' 
                       ORDER BY s.Submission_Date DESC 
                       LIMIT 10";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $instructor_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<table class="data-table">';
                    echo '<thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Grade</th>
                                <th>Graded On</th>
                            </tr>
                          </thead>
                          <tbody>';
                    
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['AssignmentTitle']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['StudentName']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['CourseTitle']) . '</td>';
                        echo '<td><strong>' . $row['Grade'] . '/' . $row['Max_Score'] . '</strong></td>';
                        echo '<td>' . date('M d, Y', strtotime($row['Submission_Date'])) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p>No graded assignments yet.</p>';
                }
                ?>
            </div>
        </div>

        <!-- Grading Statistics -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> Grading Statistics</h3>
            </div>
            <div class="card-body">
                <?php
                $stats_sql = [
                    'total_submissions' => "SELECT COUNT(*) as count FROM SUBMISSION s 
                                           JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                                           JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                                           WHERE c.FK_Instructor_ID = ?",
                    'pending_submissions' => "SELECT COUNT(*) as count FROM SUBMISSION s 
                                             JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                                             JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                                             WHERE c.FK_Instructor_ID = ? AND s.Status = 'submitted'",
                    'graded_submissions' => "SELECT COUNT(*) as count FROM SUBMISSION s 
                                            JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                                            JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                                            WHERE c.FK_Instructor_ID = ? AND s.Status = 'graded'",
                    'avg_grade' => "SELECT AVG(s.Grade) as avg FROM SUBMISSION s 
                                   JOIN ASSIGNMENT a ON s.FK_Assign_ID = a.Assign_ID 
                                   JOIN COURSE c ON a.FK_Course_ID = c.Course_ID 
                                   WHERE c.FK_Instructor_ID = ? AND s.Status = 'graded'"
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
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <h4><?php echo $stats['total_submissions']['count']; ?></h4>
                        <p>Total Submissions</p>
                    </div>
                    <div class="stat-item">
                        <h4><?php echo $stats['pending_submissions']['count']; ?></h4>
                        <p>Pending Review</p>
                    </div>
                    <div class="stat-item">
                        <h4><?php echo $stats['graded_submissions']['count']; ?></h4>
                        <p>Graded</p>
                    </div>
                    <div class="stat-item">
                        <h4><?php echo number_format($stats['avg_grade']['avg'] ?? 0, 1); ?></h4>
                        <p>Average Grade</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <style>
        .submission-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .submission-details {
            margin-bottom: 1.5rem;
        }
        
        .submission-details p {
            margin: 0.5rem 0;
            color: #555;
        }
        
        .grading-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
        }
        
        .empty-state i {
            color: #27ae60;
            margin-bottom: 1rem;
        }
    </style>
</body>
</html>