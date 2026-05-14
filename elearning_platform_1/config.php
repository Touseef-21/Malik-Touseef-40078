<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'elearning_platform_1');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Base URL
define('BASE_URL', 'http://localhost/elearning_platform_1/');

// Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function sanitize($data) {
    global $conn;
    return htmlspecialchars(stripslashes(trim($data)));
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    
    if ($user_type == 'student') {
        $table = 'STUDENT';
        $id_field = 'Student_ID';
    } elseif ($user_type == 'instructor') {
        $table = 'INSTRUCTOR';
        $id_field = 'Instructor_ID';
    } elseif ($user_type == 'admin') {
        $table = 'ADMIN';
        $id_field = 'Admin_ID';
    } else {
        return null;
    }
    
    $sql = "SELECT * FROM $table WHERE $id_field = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
?>