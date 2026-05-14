<?php if (!isset($hide_header)): ?>
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            E-Learning Platform
        </a>
        <div class="nav-menu">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
            <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
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
<?php endif; ?>