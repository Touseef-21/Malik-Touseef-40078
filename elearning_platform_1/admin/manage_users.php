<?php
require_once '../config.php';

// Check if user is admin
if (!isLoggedIn() || getUserType() != 'admin') {
    redirect('../login.php');
}

$admin = getCurrentUser();
$page_title = "Manage Users";
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Handle user actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = $_GET['id'] ?? 0;
    $user_type = $_GET['user_type'] ?? '';
    
    if ($action == 'delete' && $user_id && $user_type) {
        $table = ($user_type == 'student') ? 'STUDENT' : 'INSTRUCTOR';
        $id_field = ($user_type == 'student') ? 'Student_ID' : 'Instructor_ID';
        
        $sql = "DELETE FROM $table WHERE $id_field = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete user";
        }
        
        header("Location: manage_users.php?type=$type");
        exit();
    }
    
    if ($action == 'approve' && $user_id) {
        $sql = "UPDATE INSTRUCTOR SET Status = 'active' WHERE Instructor_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Instructor approved successfully";
        } else {
            $_SESSION['error'] = "Failed to approve instructor";
        }
        
        header("Location: manage_users.php?type=pending");
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
                <a href="manage_users.php" class="active"><i class="fas fa-users"></i> Users</a>
                <a href="manage_courses.php"><i class="fas fa-book"></i> Courses</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-users-cog"></i> Manage Users</h1>
            
            <!-- User Type Tabs -->
            <div class="tabs">
                <a href="?type=all" class="tab <?php echo $type == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> All Users
                </a>
                <a href="?type=student" class="tab <?php echo $type == 'student' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
                <a href="?type=instructor" class="tab <?php echo $type == 'instructor' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i> Instructors
                </a>
                <a href="?type=pending" class="tab <?php echo $type == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                    <?php
                    $pending_count = $conn->query("SELECT COUNT(*) as count FROM INSTRUCTOR WHERE Status = 'pending'")->fetch_assoc()['count'];
                    if ($pending_count > 0): ?>
                    <span class="badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
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

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" action="">
                <input type="hidden" name="type" value="<?php echo $type; ?>">
                <input type="text" name="search" placeholder="Search users..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="dashboard-card">
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Build query based on type
                        $where = "";
                        $params = [];
                        $types = "";
                        
                        if ($type == 'student') {
                            $sql = "SELECT Student_ID as id, Name, Email, Phone, 'student' as type, 
                                   Status, Registration_Date as reg_date 
                                   FROM STUDENT WHERE 1=1";
                        } elseif ($type == 'instructor') {
                            $sql = "SELECT Instructor_ID as id, Name, Email, Phone, 'instructor' as type, 
                                   Status, Joining_Date as reg_date 
                                   FROM INSTRUCTOR WHERE Status = 'active'";
                        } elseif ($type == 'pending') {
                            $sql = "SELECT Instructor_ID as id, Name, Email, Phone, 'instructor' as type, 
                                   Status, Qualification, Joining_Date as reg_date 
                                   FROM INSTRUCTOR WHERE Status = 'pending'";
                        } else {
                            $sql = "SELECT Student_ID as id, Name, Email, Phone, 'student' as type, 
                                   Status, Registration_Date as reg_date 
                                   FROM STUDENT 
                                   UNION 
                                   SELECT Instructor_ID as id, Name, Email, Phone, 'instructor' as type, 
                                   Status, Joining_Date as reg_date 
                                   FROM INSTRUCTOR WHERE 1=1";
                        }
                        
                        // Add search filter
                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                            $search = "%" . $_GET['search'] . "%";
                            if (strpos($sql, 'WHERE') !== false) {
                                $sql .= " AND (Name LIKE ? OR Email LIKE ?)";
                            } else {
                                $sql .= " WHERE (Name LIKE ? OR Email LIKE ?)";
                            }
                            $params[] = $search;
                            $params[] = $search;
                            $types .= "ss";
                        }
                        
                        $sql .= " ORDER BY reg_date DESC";
                        
                        // Prepare and execute
                        $stmt = $conn->prepare($sql);
                        if (!empty($params)) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['Name']); ?></strong>
                                        <?php if ($type == 'pending'): ?>
                                        <br><small>Qualification: <?php echo htmlspecialchars($row['Qualification']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['type'] == 'student' ? 'badge-primary' : 'badge-success'; ?>">
                                            <?php echo ucfirst($row['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $row['Status']; ?>">
                                            <?php echo ucfirst($row['Status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['reg_date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($type == 'pending'): ?>
                                                <a href="?action=approve&id=<?php echo $row['id']; ?>&type=pending" 
                                                   class="btn-success btn-small" 
                                                   onclick="return confirm('Approve this instructor?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($row['type'] == 'student'): ?>
                                                <a href="student_details.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn-primary btn-small">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?action=delete&id=<?php echo $row['id']; ?>&user_type=<?php echo $row['type']; ?>&type=<?php echo $type; ?>" 
                                               class="btn-danger btn-small"
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center'>No users found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                
                <!-- Pagination (simplified) -->
                <div class="pagination">
                    <a href="#" class="page-link disabled"><i class="fas fa-chevron-left"></i></a>
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">3</a>
                    <a href="#" class="page-link"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <style>
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            border-bottom: 2px solid #eee;
        }
        
        .tab {
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            color: #666;
            border-bottom: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            font-weight: 500;
        }
        
        .tab:hover {
            color: #667eea;
            background: #f8f9fa;
        }
        
        .tab .badge {
            background: #e74c3c;
            color: white;
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        
        .search-bar {
            margin: 1.5rem 0;
        }
        
        .search-bar form {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-bar input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-bar button {
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #666;
        }
        
        .page-link.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .page-link.disabled {
            color: #999;
            cursor: not-allowed;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</body>
</html>