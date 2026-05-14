<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || getUserType() != 'admin') {
    redirect('../login.php');
}

$admin = getCurrentUser();
$page_title = "Reports & Analytics";

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['type']) ? $_GET['type'] : 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - E-Learning Platform</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="manage_courses.php"><i class="fas fa-book"></i> Courses</a>
                <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>
            
            <!-- Report Type Tabs -->
            <div class="tabs">
                <a href="?type=overview" class="tab <?php echo $report_type == 'overview' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Overview
                </a>
                <a href="?type=users" class="tab <?php echo $report_type == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="?type=courses" class="tab <?php echo $report_type == 'courses' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Courses
                </a>
                <a href="?type=payments" class="tab <?php echo $report_type == 'payments' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill-wave"></i> Payments
                </a>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="filter-bar">
            <form method="GET" action="">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                <div class="form-group">
                    <label>From:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group">
                    <label>To:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
                <button type="button" onclick="window.print()" class="btn-secondary">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="export_report.php?type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn-success">
                    <i class="fas fa-file-export"></i> Export CSV
                </a>
            </form>
        </div>

        <!-- Report Content -->
        <div class="report-content">
            <?php if ($report_type == 'overview'): ?>
                <!-- Overview Report -->
                <div class="dashboard-grid">
                    <?php
                    // Get overview statistics
                    $stats = [];
                    
                    // Total Revenue
                    $sql = "SELECT SUM(Amount) as total FROM PAYMENT 
                           WHERE Status = 'completed' 
                           AND Date BETWEEN ? AND ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $start_date, $end_date);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;
                    
                    // New Users
                    $sql = "SELECT COUNT(*) as count FROM STUDENT 
                           WHERE Registration_Date BETWEEN ? AND ?
                           UNION
                           SELECT COUNT(*) FROM INSTRUCTOR 
                           WHERE Joining_Date BETWEEN ? AND ? AND Status = 'active'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stats['new_users'] = 0;
                    while ($row = $result->fetch_assoc()) {
                        $stats['new_users'] += $row['count'];
                    }
                    
                    // Course Enrollments
                    $sql = "SELECT COUNT(*) as count FROM ENROLLMENT 
                           WHERE Enroll_Date BETWEEN ? AND ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $start_date, $end_date);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stats['enrollments'] = $result->fetch_assoc()['count'];
                    
                    // Completed Courses
                    $sql = "SELECT COUNT(*) as count FROM ENROLLMENT 
                           WHERE Status = 'completed' 
                           AND Completion_Date BETWEEN ? AND ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $start_date, $end_date);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stats['completions'] = $result->fetch_assoc()['count'];
                    ?>
                    
                    <div class="dashboard-card">
                        <div class="card-body">
                            <h3>Total Revenue</h3>
                            <h2>Rs. <?php echo number_format($stats['revenue'], 2); ?></h2>
                            <p>From <?php echo date('M d', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-body">
                            <h3>New Users</h3>
                            <h2><?php echo $stats['new_users']; ?></h2>
                            <p>Students & Instructors</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-body">
                            <h3>Course Enrollments</h3>
                            <h2><?php echo $stats['enrollments']; ?></h2>
                            <p>New enrollments</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-body">
                            <h3>Course Completions</h3>
                            <h2><?php echo $stats['completions']; ?></h2>
                            <p>Courses completed</p>
                        </div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Revenue Trend</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="250"></canvas>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>User Growth</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="userGrowthChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Top Courses -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Top Performing Courses</h3>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Instructor</th>
                                    <th>Enrollments</th>
                                    <th>Revenue</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT c.Title, i.Name as Instructor, 
                                       COUNT(e.Enroll_ID) as Enrollments,
                                       SUM(p.Amount) as Revenue
                                       FROM COURSE c 
                                       JOIN INSTRUCTOR i ON c.FK_Instructor_ID = i.Instructor_ID 
                                       LEFT JOIN ENROLLMENT e ON c.Course_ID = e.FK_Course_ID 
                                       LEFT JOIN PAYMENT p ON c.Course_ID = p.FK_Course_ID AND p.Status = 'completed'
                                       WHERE c.Status = 'active' 
                                       GROUP BY c.Course_ID 
                                       ORDER BY Enrollments DESC 
                                       LIMIT 10";
                                $result = $conn->query($sql);
                                
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>{$row['Title']}</td>";
                                    echo "<td>{$row['Instructor']}</td>";
                                    echo "<td>{$row['Enrollments']}</td>";
                                    echo "<td>Rs. " . number_format($row['Revenue'] ?? 0, 2) . "</td>";
                                    echo "<td>4.5 <i class='fas fa-star' style='color: #f39c12;'></i></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($report_type == 'users'): ?>
                <!-- Users Report -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>User Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <h4><?php
                                $sql = "SELECT COUNT(*) as count FROM STUDENT";
                                echo $conn->query($sql)->fetch_assoc()['count'];
                                ?></h4>
                                <p>Total Students</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php
                                $sql = "SELECT COUNT(*) as count FROM INSTRUCTOR WHERE Status = 'active'";
                                echo $conn->query($sql)->fetch_assoc()['count'];
                                ?></h4>
                                <p>Active Instructors</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php
                                $sql = "SELECT COUNT(*) as count FROM INSTRUCTOR WHERE Status = 'pending'";
                                echo $conn->query($sql)->fetch_assoc()['count'];
                                ?></h4>
                                <p>Pending Instructors</p>
                            </div>
                        </div>
                        
                        <canvas id="userTypeChart" height="200"></canvas>
                    </div>
                </div>
                
            <?php elseif ($report_type == 'courses'): ?>
                <!-- Courses Report -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Course Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <h4><?php
                                $sql = "SELECT COUNT(*) as count FROM COURSE";
                                echo $conn->query($sql)->fetch_assoc()['count'];
                                ?></h4>
                                <p>Total Courses</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php
                                $sql = "SELECT COUNT(*) as count FROM COURSE WHERE Status = 'active'";
                                echo $conn->query($sql)->fetch_assoc()['count'];
                                ?></h4>
                                <p>Active Courses</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php
                                $sql = "SELECT AVG(Progress_Percentage) as avg FROM ENROLLMENT WHERE Status = 'active'";
                                echo number_format($conn->query($sql)->fetch_assoc()['avg'] ?? 0, 1);
                                ?>%</h4>
                                <p>Avg Progress</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($report_type == 'payments'): ?>
                <!-- Payments Report -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Payment Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <h4>Rs. <?php
                                $sql = "SELECT SUM(Amount) as total FROM PAYMENT WHERE Status = 'completed'";
                                echo number_format($conn->query($sql)->fetch_assoc()['total'] ?? 0, 2);
                                ?></h4>
                                <p>Total Revenue</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php
                                $sql = "SELECT COUNT(*) as count FROM PAYMENT WHERE Status = 'completed'";
                                echo $conn->query($sql)->fetch_assoc()['count'];
                                ?></h4>
                                <p>Successful Payments</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php
                                $sql = "SELECT COUNT(*) as count FROM PAYMENT WHERE Status = 'pending'";
                                echo $conn->query($sql)->fetch_assoc()['count'];
                                ?></h4>
                                <p>Pending Payments</p>
                            </div>
                        </div>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT p.*, s.Name as StudentName, c.Title as CourseTitle 
                                       FROM PAYMENT p 
                                       JOIN STUDENT s ON p.FK_Student_ID = s.Student_ID 
                                       JOIN COURSE c ON p.FK_Course_ID = c.Course_ID 
                                       WHERE p.Date BETWEEN ? AND ? 
                                       ORDER BY p.Date DESC";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("ss", $start_date, $end_date);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . date('M d, Y', strtotime($row['Date'])) . "</td>";
                                    echo "<td>{$row['StudentName']}</td>";
                                    echo "<td>{$row['CourseTitle']}</td>";
                                    echo "<td>Rs. " . number_format($row['Amount'], 2) . "</td>";
                                    echo "<td>{$row['Method']}</td>";
                                    echo "<td><span class='status-badge {$row['Status']}'>{$row['Status']}</span></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($report_type == 'overview'): ?>
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue (Rs.)',
                        data: [50000, 75000, 60000, 90000, 85000, 95000],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
            // User Growth Chart
            const userCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'New Users',
                        data: [150, 200, 180, 250, 220, 300],
                        backgroundColor: '#27ae60'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            <?php elseif ($report_type == 'users'): ?>
            // User Type Chart
            const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
            new Chart(userTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Students', 'Active Instructors', 'Pending Instructors'],
                    datasets: [{
                        data: [1500, 50, 5],
                        backgroundColor: ['#667eea', '#27ae60', '#f39c12']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            <?php endif; ?>
        });
    </script>
    <style>
        .filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .filter-bar form {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-bar .form-group {
            margin: 0;
        }
        
        .filter-bar label {
            margin-right: 0.5rem;
            font-weight: 500;
        }
        
        .filter-bar input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .report-content {
            margin-top: 1.5rem;
        }
    </style>
</body>
</html>