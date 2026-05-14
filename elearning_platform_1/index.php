<?php
require_once 'config.php';
$page_title = "E-Learning Platform - Home";
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
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                E-Learning Platform
            </a>
            <div class="nav-menu">
                <a href="index.php" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="#courses"><i class="fas fa-book"></i> Courses</a>
                <a href="#features"><i class="fas fa-star"></i> Features</a>
                <a href="#about"><i class="fas fa-info-circle"></i> About</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn-outline"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-outline"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn-primary"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Learn Anytime, Anywhere</h1>
                <p>Access high-quality courses from expert instructors. Boost your career with our comprehensive e-learning platform.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn-primary btn-large">Get Started</a>
                    <a href="#courses" class="btn-outline btn-large">Browse Courses</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1553877522-43269d4ea984" alt="Online Learning">
            </div>
        </div>
    </section>

    <!-- Featured Courses -->
    <section id="courses" class="section">
        <div class="container">
            <h2 class="section-title">Featured Courses</h2>
            <div class="courses-grid">
                <?php
                $sql = "SELECT c.*, i.Name as InstructorName, 
                       COUNT(e.Enroll_ID) as Enrollments 
                       FROM COURSE c 
                       JOIN INSTRUCTOR i ON c.FK_Instructor_ID = i.Instructor_ID 
                       LEFT JOIN ENROLLMENT e ON c.Course_ID = e.FK_Course_ID 
                       WHERE c.Status = 'active' 
                       GROUP BY c.Course_ID 
                       ORDER BY c.Created_Date DESC 
                       LIMIT 6";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        ?>
                        <div class="course-card">
                            <div class="course-image">
                                <img src="<?php echo $row['Thumbnail'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3'; ?>" alt="<?php echo $row['Title']; ?>">
                                <span class="course-level"><?php echo $row['Level']; ?></span>
                            </div>
                            <div class="course-content">
                                <h3><?php echo $row['Title']; ?></h3>
                                <p class="course-instructor">
                                    <i class="fas fa-user"></i> <?php echo $row['InstructorName']; ?>
                                </p>
                                <p class="course-description">
                                    <?php echo substr($row['Description'], 0, 100) . '...'; ?>
                                </p>
                                <div class="course-meta">
                                    <span><i class="fas fa-clock"></i> <?php echo $row['Duration']; ?> hrs</span>
                                    <span><i class="fas fa-users"></i> <?php echo $row['Enrollments']; ?> students</span>
                                </div>
                                <div class="course-footer">
                                    <span class="course-price">Rs. <?php echo number_format($row['Price'], 2); ?></span>
                                    <a href="course_details.php?id=<?php echo $row['Course_ID']; ?>" class="btn-primary btn-small">View Course</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>No courses available at the moment.</p>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Platform Features -->
    <section id="features" class="section bg-light">
        <div class="container">
            <h2 class="section-title">Why Choose Our Platform?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Expert Instructors</h3>
                    <p>Learn from industry professionals with years of experience.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>Hands-on Projects</h3>
                    <p>Apply your knowledge with real-world projects and assignments.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Certification</h3>
                    <p>Receive certificates upon course completion to boost your resume.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Community Support</h3>
                    <p>Connect with peers and instructors through discussion forums.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>1500+</h3>
                    <p>Active Students</p>
                </div>
                <div class="stat-item">
                    <h3>50+</h3>
                    <p>Expert Instructors</p>
                </div>
                <div class="stat-item">
                    <h3>100+</h3>
                    <p>Courses Available</p>
                </div>
                <div class="stat-item">
                    <h3>95%</h3>
                    <p>Satisfaction Rate</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>E-Learning Platform</h3>
                    <p>Empowering learners worldwide with quality education.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="index.php">Home</a>
                    <a href="#courses">Courses</a>
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact</a>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-envelope"></i> info@elearning.com</p>
                    <p><i class="fas fa-phone"></i> +92 300 1234567</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 E-Learning Platform. All rights reserved. | Created by Ilham Nawaz (ID: 40089)</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>