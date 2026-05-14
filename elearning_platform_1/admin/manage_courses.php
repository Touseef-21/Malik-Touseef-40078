<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || getUserType() != 'admin') {
    redirect('../login.php');
}

$admin = getCurrentUser();
$page_title = "Manage Courses";

// Handle course actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $course_id = $_GET['id'] ?? 0;
    
    if ($action == 'delete' && $course_id) {
        $sql = "DELETE FROM COURSE WHERE Course_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete course";
        }
        
        header("Location: manage_courses.php");
        exit();
    }
    
    if ($action == 'status' && $course_id) {
        $new_status = $_GET['status'] ?? 'active';
        $sql = "UPDATE COURSE SET Status = ? WHERE Course_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $course_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course status updated";
        } else {
            $_SESSION['error'] = "Failed to update course status";
        }
        
        header("Location: manage_courses.php");
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
    <!-- Admin Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                Admin Panel
            </a>
            <div class="nav-menu">
                <span class="user-greeting">
                    <i class="fas fa-user-shield"></i> <?php echo $admin['Username']; ?>
                </span>
                <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
                <a href="manage_courses.php" class="active"><i class="fas fa-book"></i> Courses</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-book-open"></i> Manage Courses</h1>
            <p>View, edit, and manage all courses on the platform</p>
            
            <a href="add_course.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add New Course
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

        <!-- Course Statistics -->
        <div class="dashboard-grid">
            <?php
            $stats_sql = [
                'total_courses' => "SELECT COUNT(*) as count FROM COURSE",
                'active_courses' => "SELECT COUNT(*) as count FROM COURSE WHERE Status = 'active'",
                'total_enrollments' => "SELECT COUNT(*) as count FROM ENROLLMENT",
                'total_revenue' => "SELECT SUM(Amount) as total FROM PAYMENT WHERE Status = 'completed'"
            ];
            
            foreach ($stats_sql as $key => $sql) {
                $result = $conn->query($sql);
                $$key = $result->fetch_assoc();
            }
            ?>
            
            <div class="dashboard-card">
                <div class="card-body">
                    <h3>Total Courses</h3>
                    <h2><?php echo $total_courses['count']; ?></h2>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-body">
                    <h3>Active Courses</h3>
                    <h2><?php echo $active_courses['count']; ?></h2>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-body">
                    <h3>Total Enrollments</h3>
                    <h2><?php echo $total_enrollments['count']; ?></h2>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-body">
                    <h3>Course Revenue</h3>
                    <h2>Rs. <?php echo number_format($total_revenue['total'] ?? 0, 2); ?></h2>
                </div>
            </div>
        </div>

        <!-- Courses Table -->
        <div class="dashboard-card">
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course Title</th>
                            <th>Instructor</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Enrollments</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT c.*, i.Name as InstructorName, 
                               COUNT(e.Enroll_ID) as EnrollmentCount 
                               FROM COURSE c 
                               JOIN INSTRUCTOR i ON c.FK_Instructor_ID = i.Instructor_ID 
                               LEFT JOIN ENROLLMENT e ON c.Course_ID = e.FK_Course_ID 
                               GROUP BY c.Course_ID 
                               ORDER BY c.Created_Date DESC";
                        
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td>#<?php echo $row['Course_ID']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['Title']); ?></strong>
                                        <br><small><?php echo substr(htmlspecialchars($row['Description']), 0, 50) . '...'; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['InstructorName']); ?></td>
                                    <td>
                                        <span class="badge"><?php echo $row['Category'] ?? 'Uncategorized'; ?></span>
                                    </td>
                                    <td>Rs. <?php echo number_format($row['Price'], 2); ?></td>
                                    <td><?php echo $row['EnrollmentCount']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $row['Status']; ?>">
                                            <?php echo ucfirst($row['Status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['Created_Date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_course.php?id=<?php echo $row['Course_ID']; ?>" 
                                               class="btn-primary btn-small">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            
                                            <?php if ($row['Status'] == 'active'): ?>
                                                <a href="?action=status&id=<?php echo $row['Course_ID']; ?>&status=draft" 
                                                   class="btn-warning btn-small"
                                                   onclick="return confirm('Archive this course?')">
                                                    <i class="fas fa-archive"></i> Archive
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=status&id=<?php echo $row['Course_ID']; ?>&status=active" 
                                                   class="btn-success btn-small"
                                                   onclick="return confirm('Activate this course?')">
                                                    <i class="fas fa-check"></i> Activate
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?action=delete&id=<?php echo $row['Course_ID']; ?>" 
                                               class="btn-danger btn-small"
                                               onclick="return confirm('Are you sure you want to delete this course?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center'>No courses found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <style>
        .btn-warning {
            background: #f39c12;
            color: white;
        }
    </style>
</body>
</html>