<?php
require_once '../config.php';

// Check if user is instructor
if (!isLoggedIn() || getUserType() != 'instructor') {
    redirect('../login.php');
}

$instructor = getCurrentUser();
$instructor_id = $_SESSION['user_id'];
$page_title = "My Courses";

// Handle course actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $course_id = $_GET['id'] ?? 0;
    
    if ($action == 'delete' && $course_id) {
        // Check if course belongs to this instructor
        $check_sql = "SELECT Course_ID FROM COURSE WHERE Course_ID = ? AND FK_Instructor_ID = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $course_id, $instructor_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $sql = "DELETE FROM COURSE WHERE Course_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $course_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Course deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete course";
            }
        } else {
            $_SESSION['error'] = "Course not found or access denied";
        }
        
        header("Location: my_courses.php");
        exit();
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
                <a href="my_courses.php" class="active"><i class="fas fa-book"></i> My Courses</a>
                <a href="create_course.php"><i class="fas fa-plus"></i> Create Course</a>
                <a href="grade_assignments.php"><i class="fas fa-tasks"></i> Grade Assignments</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-book-open"></i> My Courses</h1>
            <p>Manage all your created courses</p>
            
            <a href="create_course.php" class="btn-primary">
                <i class="fas fa-plus"></i> Create New Course
            </a>
        </div>

        <!-- Display Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Courses Grid -->
        <div class="courses-grid">
            <?php
            $sql = "SELECT c.*, COUNT(e.Enroll_ID) as StudentCount 
                   FROM COURSE c 
                   LEFT JOIN ENROLLMENT e ON c.Course_ID = e.FK_Course_ID 
                   WHERE c.FK_Instructor_ID = ? 
                   GROUP BY c.Course_ID 
                   ORDER BY c.Created_Date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $instructor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    ?>
                    <div class="course-card">
                        <div class="course-image">
                            <img src="<?php echo $row['Thumbnail'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3'; ?>" alt="<?php echo $row['Title']; ?>">
                            <span class="course-status <?php echo $row['Status']; ?>">
                                <?php echo ucfirst($row['Status']); ?>
                            </span>
                        </div>
                        <div class="course-content">
                            <h3><?php echo htmlspecialchars($row['Title']); ?></h3>
                            <p class="course-description">
                                <?php echo substr(htmlspecialchars($row['Description']), 0, 100) . '...'; ?>
                            </p>
                            
                            <!-- Course Stats -->
                            <div class="course-stats">
                                <div class="stat-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $row['StudentCount']; ?> students</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Rs. <?php echo number_format($row['Price'], 2); ?></span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $row['Duration']; ?> hours</span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="course-actions">
                                <a href="edit_course.php?id=<?php echo $row['Course_ID']; ?>" class="btn-primary btn-small">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="course_students.php?id=<?php echo $row['Course_ID']; ?>" class="btn-outline btn-small">
                                    <i class="fas fa-users"></i> Students
                                </a>
                                <a href="course_assignments.php?id=<?php echo $row['Course_ID']; ?>" class="btn-outline btn-small">
                                    <i class="fas fa-tasks"></i> Assignments
                                </a>
                                <a href="?action=delete&id=<?php echo $row['Course_ID']; ?>" 
                                   class="btn-danger btn-small"
                                   onclick="return confirm('Are you sure you want to delete this course?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="empty-state">';
                echo '<i class="fas fa-book-open fa-3x"></i>';
                echo '<h3>No courses yet</h3>';
                echo '<p>You haven\'t created any courses. Create your first course to get started!</p>';
                echo '<a href="create_course.php" class="btn-primary">Create Your First Course</a>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <style>
        .course-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            color: white;
        }
        
        .course-status.active {
            background: #27ae60;
        }
        
        .course-status.draft {
            background: #f39c12;
        }
        
        .course-status.archived {
            background: #95a5a6;
        }
        
        .course-stats {
            display: flex;
            justify-content: space-between;
            margin: 1rem 0;
            padding: 1rem 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .stat-item i {
            color: #667eea;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-item span {
            font-size: 0.9rem;
            color: #666;
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
    </style>
</body>
</html>