<?php
require_once 'config.php';
$page_title = "About Us - E-Learning Platform";
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
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>About E-Learning Platform</h1>
            <p>Empowering learners worldwide with quality education</p>
        </div>
        
        <div class="about-content">
            <div class="about-section">
                <h2><i class="fas fa-rocket"></i> Our Mission</h2>
                <p>Our mission is to make quality education accessible to everyone, everywhere. We believe that learning should be flexible, engaging, and relevant to today's world.</p>
            </div>
            
            <div class="about-section">
                <h2><i class="fas fa-eye"></i> Our Vision</h2>
                <p>To become the world's leading e-learning platform, transforming education through technology and innovation.</p>
            </div>
            
            <div class="about-section">
                <h2><i class="fas fa-history"></i> Our Story</h2>
                <p>Founded in 2024, E-Learning Platform started as a small project by passionate educators and technologists. Today, we serve thousands of students worldwide with hundreds of courses across various disciplines.</p>
            </div>
            
            <div class="about-section">
                <h2><i class="fas fa-chart-line"></i> Our Impact</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <h3>1500+</h3>
                        <p>Students Empowered</p>
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
            
            <div class="about-section">
                <h2><i class="fas fa-users"></i> Meet Our Team</h2>
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3>Sir Waseem</h3>
                        <p>Project Supervisor</p>
                        <p>MS Software Engineering</p>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3>Ilham Nawaz</h3>
                        <p>Project Developer</p>
                        <p>Student ID: 40089</p>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3>Expert Instructors</h3>
                        <p>Industry Professionals</p>
                        <p>PhD & MSc Holders</p>
                    </div>
                </div>
            </div>
            
            <div class="about-section">
                <h2><i class="fas fa-graduation-cap"></i> Our Values</h2>
                <div class="values-grid">
                    <div class="value-item">
                        <i class="fas fa-handshake"></i>
                        <h3>Quality</h3>
                        <p>We maintain the highest standards in course content and delivery.</p>
                    </div>
                    
                    <div class="value-item">
                        <i class="fas fa-users"></i>
                        <h3>Community</h3>
                        <p>We foster a supportive learning community where everyone can grow.</p>
                    </div>
                    
                    <div class="value-item">
                        <i class="fas fa-lightbulb"></i>
                        <h3>Innovation</h3>
                        <p>We continuously improve our platform with the latest technologies.</p>
                    </div>
                    
                    <div class="value-item">
                        <i class="fas fa-globe"></i>
                        <h3>Accessibility</h3>
                        <p>We believe education should be available to all, regardless of location.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
</body>
</html>