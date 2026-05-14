<?php
require_once '../config.php';

// Check if user is student
if (!isLoggedIn() || getUserType() != 'student') {
    redirect('../login.php');
}

$student = getCurrentUser();
$student_id = $_SESSION['user_id'];
$page_title = "My Courses";
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';
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
                <a href="my_courses.php" class="active"><i class="fas fa-book"></i> My Courses</a>
                <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-book"></i> My Courses</h1>
            <p>Manage and access all your enrolled courses</p>
            
            <!-- Filter Tabs -->
            <div class="tabs">
                <a href="?filter=active" class="tab <?php echo $filter == 'active' ? 'active' : ''; ?>">
                    <i class="fas fa-play-circle"></i> Active
                </a>
                <a href="?filter=completed" class="tab <?php echo $filter == 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Completed
                </a>
                <a href="?filter=all" class="tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Courses
                </a>
                <a href="../index.php#courses" class="tab">
                    <i class="fas fa-plus"></i> Browse More
                </a>
            </div>
        </div>

        <!-- Courses Grid -->
        <div class="courses-grid">
            <?php
            // Build query based on filter
            $where = "e.FK_Student_ID = ?";
            $params = [$student_id];
            $types = "i";
            
            if ($filter == 'active') {
                $where .= " AND e.Status = 'active'";
            } elseif ($filter == 'completed') {
                $where .= " AND e.Status = 'completed'";
            }
            
            $sql = "SELECT c.*, e.Enroll_Date, e.Progress_Percentage, e.Status as EnrollmentStatus,
                   i.Name as InstructorName 
                   FROM ENROLLMENT e 
                   JOIN COURSE c ON e.FK_Course_ID = c.Course_ID 
                   JOIN INSTRUCTOR i ON c.FK_Instructor_ID = i.Instructor_ID 
                   WHERE $where 
                   ORDER BY e.Enroll_Date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    ?>
                    <div class="course-card">
                        <div class="course-image">
                            <img src="<?php echo $row['Thumbnail'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3'; ?>" alt="<?php echo $row['Title']; ?>">
                            <?php if ($row['EnrollmentStatus'] == 'completed'): ?>
                                <span class="course-badge badge-success">
                                    <i class="fas fa-check"></i> Completed
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="course-content">
                            <h3><?php echo htmlspecialchars($row['Title']); ?></h3>
                            <p class="course-instructor">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($row['InstructorName']); ?>
                            </p>
                            <p class="course-description">
                                <?php echo substr(htmlspecialchars($row['Description']), 0, 100) . '...'; ?>
                            </p>
                            
                            <!-- Progress -->
                            <?php if ($row['EnrollmentStatus'] == 'active'): ?>
                                <div class="course-progress">
                                    <p>Progress: <?php echo $row['Progress_Percentage']; ?>%</p>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $row['Progress_Percentage']; ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Course Meta -->
                            <div class="course-meta">
                                <span><i class="fas fa-calendar"></i> Enrolled: <?php echo date('M d, Y', strtotime($row['Enroll_Date'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo $row['Duration']; ?> hrs</span>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="course-footer">
                                <?php if ($row['EnrollmentStatus'] == 'active'): ?>
                                    <a href="course.php?id=<?php echo $row['Course_ID']; ?>" class="btn-primary">
                                        <i class="fas fa-play"></i> Continue Learning
                                    </a>
                                    <a href="course_materials.php?id=<?php echo $row['Course_ID']; ?>" class="btn-outline">
                                        <i class="fas fa-folder-open"></i> Materials
                                    </a>
                                <?php else: ?>
                                    <a href="course.php?id=<?php echo $row['Course_ID']; ?>" class="btn-outline">
                                        <i class="fas fa-eye"></i> Review Course
                                    </a>
                                    <?php if ($row['EnrollmentStatus'] == 'completed'): ?>
                                        <a href="certificate.php?course_id=<?php echo $row['Course_ID']; ?>" class="btn-success">
                                            <i class="fas fa-certificate"></i> Certificate
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="empty-state">';
                echo '<i class="fas fa-book-open fa-3x"></i>';
                echo '<h3>No courses found</h3>';
                if ($filter == 'active') {
                    echo '<p>You are not enrolled in any active courses.</p>';
                    echo '<a href="../index.php#courses" class="btn-primary">Browse Courses</a>';
                } else {
                    echo '<p>No courses match your filter.</p>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <style>
        .empty-state {
            text-align: center;
            padding: 3rem;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .course-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .badge-success {
            background: #27ae60;
            color: white;
        }
        
        .course-progress {
            margin: 1rem 0;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
    </style>
</body>
</html>