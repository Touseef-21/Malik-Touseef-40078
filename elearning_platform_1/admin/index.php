<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || getUserType() != 'admin') {
    redirect('../login.php');
}

$admin = getCurrentUser();
$page_title = "Admin Dashboard";
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
                <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
                <a href="manage_courses.php"><i class="fas fa-book"></i> Courses</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-cog"></i> Admin Dashboard</h1>
            <p>Welcome back, <?php echo $admin['Full_Name']; ?>!</p>
        </div>

        <!-- Quick Stats -->
        <div class="dashboard-grid">
            <?php
            // Get statistics
            $stats_sql = [
                'students' => "SELECT COUNT(*) as count FROM STUDENT",
                'instructors' => "SELECT COUNT(*) as count FROM INSTRUCTOR WHERE Status = 'active'",
                'courses' => "SELECT COUNT(*) as count FROM COURSE WHERE Status = 'active'",
                'enrollments' => "SELECT COUNT(*) as count FROM ENROLLMENT WHERE Status = 'active'",
                'payments' => "SELECT SUM(Amount) as total FROM PAYMENT WHERE Status = 'completed'",
                'pending_instructors' => "SELECT COUNT(*) as count FROM INSTRUCTOR WHERE Status = 'pending'"
            ];
            
            $stats = [];
            foreach ($stats_sql as $key => $sql) {
                $result = $conn->query($sql);
                $stats[$key] = $result->fetch_assoc();
            }
            ?>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Total Students</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['students']['count']; ?></h2>
                    <p>Active learners</p>
                    <a href="manage_users.php?type=student" class="btn-small">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Instructors</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['instructors']['count']; ?></h2>
                    <p>Active instructors</p>
                    <a href="manage_users.php?type=instructor" class="btn-small">View All</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> Courses</h3>
                </div>
                <div class="card-body">
                    <h2><?php echo $stats['courses']['count']; ?></h2>
                    <p>Active courses</p>
                    <a href="manage_courses.php" class="btn-small">Manage</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-money-bill-wave"></i> Revenue</h3>
                </div>
                <div class="card-body">
                    <h2>Rs. <?php echo number_format($stats['payments']['total'] ?? 0, 2); ?></h2>
                    <p>Total revenue</p>
                    <a href="reports.php?type=payments" class="btn-small">Details</a>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="dashboard-card wide">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Recent Activities</h3>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get recent enrollments
                        $sql = "SELECT e.Enroll_Date, s.Name as StudentName, c.Title as CourseTitle 
                               FROM ENROLLMENT e 
                               JOIN STUDENT s ON e.FK_Student_ID = s.Student_ID 
                               JOIN COURSE c ON e.FK_Course_ID = c.Course_ID 
                               ORDER BY e.Enroll_Date DESC LIMIT 5";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . date('M d, H:i', strtotime($row['Enroll_Date'])) . "</td>";
                                echo "<td>" . $row['StudentName'] . "</td>";
                                echo "<td><span class='badge badge-success'>Enrollment</span></td>";
                                echo "<td>Enrolled in " . $row['CourseTitle'] . "</td>";
                                echo "</tr>";
                            }
                        }
                        
                        // Get recent payments
                        $sql = "SELECT p.Date, s.Name as StudentName, p.Amount, c.Title as CourseTitle 
                               FROM PAYMENT p 
                               JOIN STUDENT s ON p.FK_Student_ID = s.Student_ID 
                               JOIN COURSE c ON p.FK_Course_ID = c.Course_ID 
                               WHERE p.Status = 'completed' 
                               ORDER BY p.Date DESC LIMIT 5";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . date('M d, H:i', strtotime($row['Date'])) . "</td>";
                                echo "<td>" . $row['StudentName'] . "</td>";
                                echo "<td><span class='badge badge-primary'>Payment</span></td>";
                                echo "<td>Rs. " . number_format($row['Amount'], 2) . " for " . $row['CourseTitle'] . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Actions -->
        <?php if ($stats['pending_instructors']['count'] > 0): ?>
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Pending Approvals</h3>
            </div>
            <div class="card-body">
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong><?php echo $stats['pending_instructors']['count']; ?> instructor applications pending</strong>
                        <p>Review and approve instructor registrations</p>
                        <a href="manage_users.php?type=pending" class="btn-primary btn-small">Review Now</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="../js/script.js"></script>
    <style>
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-primary {
            background: #cce5ff;
            color: #004085;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
    </style>
</body>
</html>