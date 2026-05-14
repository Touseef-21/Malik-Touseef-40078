-- ============================================
-- E-LEARNING PLATFORM DATABASE SYSTEM
-- Combined Database Schema + Sample Data
-- Created by: Ilham Nawaz (ID: 40089)
-- ============================================

-- Disable foreign key checks to avoid issues during import
SET FOREIGN_KEY_CHECKS = 0;

-- Create Database
DROP DATABASE IF EXISTS elearning_platform_1;
CREATE DATABASE elearning_platform_1;
USE elearning_platform_1;

-- ============================================
-- DATABASE SCHEMA
-- ============================================

-- 1. STUDENT Table
CREATE TABLE student (
    Student_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(50) NOT NULL,
    Email VARCHAR(50) UNIQUE NOT NULL,
    Phone VARCHAR(20),
    Password VARCHAR(255) NOT NULL,
    Date_of_Birth DATE,
    Address TEXT,
    Profile_Image VARCHAR(255),
    Registration_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    Last_Login TIMESTAMP NULL
);

-- 2. INSTRUCTOR Table
CREATE TABLE instructor (
    Instructor_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(50) NOT NULL,
    Email VARCHAR(50) UNIQUE NOT NULL,
    Phone VARCHAR(20),
    Qualification VARCHAR(100),
    Specialization VARCHAR(100),
    Bio TEXT,
    Password VARCHAR(255) NOT NULL,
    Profile_Image VARCHAR(255),
    Joining_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    Last_Login TIMESTAMP NULL
);

-- 3. COURSE Table
CREATE TABLE course (
    Course_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(100) NOT NULL,
    Description TEXT,
    Category VARCHAR(50),
    Level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
    Price DECIMAL(10,2) DEFAULT 0.00,
    Duration INT COMMENT 'Duration in hours',
    Thumbnail VARCHAR(255),
    FK_Instructor_ID INT,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    FOREIGN KEY (FK_Instructor_ID) REFERENCES instructor(Instructor_ID) ON DELETE SET NULL
);

-- 4. COURSE_MODULE Table
CREATE TABLE course_module (
    Module_ID INT PRIMARY KEY AUTO_INCREMENT,
    Module_Title VARCHAR(100) NOT NULL,
    Module_Description TEXT,
    Module_Order INT,
    FK_Course_ID INT NOT NULL,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FK_Course_ID) REFERENCES course(Course_ID) ON DELETE CASCADE
);

-- 5. MODULE_CONTENT Table
CREATE TABLE module_content (
    Content_ID INT PRIMARY KEY AUTO_INCREMENT,
    Content_Title VARCHAR(100) NOT NULL,
    Content_Type ENUM('video', 'pdf', 'quiz', 'article', 'assignment') NOT NULL,
    Content_URL VARCHAR(255),
    Duration INT COMMENT 'Duration in minutes',
    FK_Module_ID INT NOT NULL,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FK_Module_ID) REFERENCES course_module(Module_ID) ON DELETE CASCADE
);

-- 6. ENROLLMENT Table
CREATE TABLE enrollment (
    Enroll_ID INT PRIMARY KEY AUTO_INCREMENT,
    FK_Student_ID INT NOT NULL,
    FK_Course_ID INT NOT NULL,
    Enroll_Date DATE,
    Completion_Date DATE NULL,
    Progress_Percentage INT DEFAULT 0,
    Status ENUM('active', 'completed', 'dropped', 'pending') DEFAULT 'active',
    FOREIGN KEY (FK_Student_ID) REFERENCES student(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (FK_Course_ID) REFERENCES course(Course_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (FK_Student_ID, FK_Course_ID)
);

-- 7. ASSIGNMENT Table
CREATE TABLE assignment (
    Assign_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(100) NOT NULL,
    Description TEXT,
    FK_Course_ID INT NOT NULL,
    Due_Date DATE,
    Max_Score INT DEFAULT 100,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FK_Course_ID) REFERENCES course(Course_ID) ON DELETE CASCADE
);

-- 8. SUBMISSION Table
CREATE TABLE submission (
    Sub_ID INT PRIMARY KEY AUTO_INCREMENT,
    FK_Assign_ID INT NOT NULL,
    FK_Student_ID INT NOT NULL,
    File_Link VARCHAR(255),
    Submission_Date DATE,
    Grade DECIMAL(5,2) NULL,
    Feedback TEXT,
    Status ENUM('submitted', 'graded', 'late', 'missing') DEFAULT 'submitted',
    FOREIGN KEY (FK_Assign_ID) REFERENCES assignment(Assign_ID) ON DELETE CASCADE,
    FOREIGN KEY (FK_Student_ID) REFERENCES student(Student_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_submission (FK_Assign_ID, FK_Student_ID)
);

-- 9. PAYMENT Table
CREATE TABLE payment (
    Payment_ID INT PRIMARY KEY AUTO_INCREMENT,
    FK_Student_ID INT NOT NULL,
    FK_Course_ID INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    Date DATE,
    Method ENUM('Credit Card', 'Debit Card', 'Bank Transfer', 'EasyPaisa', 'JazzCash', 'Cash') DEFAULT 'Credit Card',
    Status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    Transaction_ID VARCHAR(100) UNIQUE,
    Receipt_URL VARCHAR(255),
    FOREIGN KEY (FK_Student_ID) REFERENCES student(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (FK_Course_ID) REFERENCES course(Course_ID) ON DELETE CASCADE
);

-- 10. QUIZ Table
CREATE TABLE quiz (
    Quiz_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(100) NOT NULL,
    Description TEXT,
    FK_Course_ID INT NOT NULL,
    Total_Questions INT DEFAULT 10,
    Passing_Score INT DEFAULT 70,
    Time_Limit INT COMMENT 'Time limit in minutes',
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FK_Course_ID) REFERENCES course(Course_ID) ON DELETE CASCADE
);

-- 11. QUIZ_QUESTION Table
CREATE TABLE quiz_question (
    Question_ID INT PRIMARY KEY AUTO_INCREMENT,
    FK_Quiz_ID INT NOT NULL,
    Question_Text TEXT NOT NULL,
    Option_A VARCHAR(255),
    Option_B VARCHAR(255),
    Option_C VARCHAR(255),
    Option_D VARCHAR(255),
    Correct_Answer CHAR(1) CHECK (Correct_Answer IN ('A', 'B', 'C', 'D')),
    Points INT DEFAULT 1,
    FOREIGN KEY (FK_Quiz_ID) REFERENCES quiz(Quiz_ID) ON DELETE CASCADE
);

-- 12. QUIZ_RESULT Table
CREATE TABLE quiz_result (
    Result_ID INT PRIMARY KEY AUTO_INCREMENT,
    FK_Quiz_ID INT NOT NULL,
    FK_Student_ID INT NOT NULL,
    Score INT,
    Total_Questions INT,
    Attempt_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Time_Taken INT COMMENT 'Time taken in seconds',
    FOREIGN KEY (FK_Quiz_ID) REFERENCES quiz(Quiz_ID) ON DELETE CASCADE,
    FOREIGN KEY (FK_Student_ID) REFERENCES student(Student_ID) ON DELETE CASCADE
);

-- 13. DISCUSSION Table
CREATE TABLE discussion (
    Discussion_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(200) NOT NULL,
    Content TEXT NOT NULL,
    FK_Course_ID INT NOT NULL,
    FK_Student_ID INT,
    FK_Instructor_ID INT,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Is_Closed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (FK_Course_ID) REFERENCES course(Course_ID) ON DELETE CASCADE,
    FOREIGN KEY (FK_Student_ID) REFERENCES student(Student_ID) ON DELETE SET NULL,
    FOREIGN KEY (FK_Instructor_ID) REFERENCES instructor(Instructor_ID) ON DELETE SET NULL
);

-- 14. DISCUSSION_REPLY Table
CREATE TABLE discussion_reply (
    Reply_ID INT PRIMARY KEY AUTO_INCREMENT,
    FK_Discussion_ID INT NOT NULL,
    Reply_Text TEXT NOT NULL,
    FK_Student_ID INT,
    FK_Instructor_ID INT,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FK_Discussion_ID) REFERENCES discussion(Discussion_ID) ON DELETE CASCADE,
    FOREIGN KEY (FK_Student_ID) REFERENCES student(Student_ID) ON DELETE SET NULL,
    FOREIGN KEY (FK_Instructor_ID) REFERENCES instructor(Instructor_ID) ON DELETE SET NULL
);

-- 15. ADMIN Table
CREATE TABLE admin (
    Admin_ID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(50) UNIQUE NOT NULL,
    Full_Name VARCHAR(50),
    Role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Last_Login TIMESTAMP NULL
);

-- 16. NOTIFICATION Table
CREATE TABLE notification (
    Notification_ID INT PRIMARY KEY AUTO_INCREMENT,
    User_ID INT NOT NULL,
    User_Type ENUM('student', 'instructor', 'admin') NOT NULL,
    Title VARCHAR(100),
    Message TEXT,
    Type ENUM('enrollment', 'assignment', 'payment', 'system', 'announcement') DEFAULT 'system',
    Is_Read BOOLEAN DEFAULT FALSE,
    Created_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Link VARCHAR(255)
);

-- 17. CERTIFICATE Table
CREATE TABLE certificate (
    Certificate_ID INT PRIMARY KEY AUTO_INCREMENT,
    FK_Student_ID INT NOT NULL,
    FK_Course_ID INT NOT NULL,
    Certificate_Number VARCHAR(50) UNIQUE NOT NULL,
    Issue_Date DATE,
    Expiry_Date DATE NULL,
    Certificate_URL VARCHAR(255),
    FOREIGN KEY (FK_Student_ID) REFERENCES student(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (FK_Course_ID) REFERENCES course(Course_ID) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_student_email ON student(Email);
CREATE INDEX idx_instructor_email ON instructor(Email);
CREATE INDEX idx_course_instructor ON course(FK_Instructor_ID);
CREATE INDEX idx_enrollment_student ON enrollment(FK_Student_ID);
CREATE INDEX idx_enrollment_course ON enrollment(FK_Course_ID);
CREATE INDEX idx_payment_student ON payment(FK_Student_ID);
CREATE INDEX idx_assignment_course ON assignment(FK_Course_ID);
CREATE INDEX idx_submission_student ON submission(FK_Student_ID);

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Insert Admin Users
INSERT INTO admin (Username, Password, Email, Full_Name, Role) VALUES
('superadmin', 'password123', 'admin@elearning.com', 'System Administrator', 'super_admin'),
('waseem', 'password123', 'waseem@elearning.com', 'Sir Waseem', 'admin');

-- Insert Sample Instructors
INSERT INTO instructor (Name, Email, Phone, Qualification, Specialization, Bio, Password, Status) VALUES
('Dr. Ali Khan', 'ali.khan@university.edu', '0300-1234567', 'PhD in Computer Science', 'Database Systems', 'Senior Professor with 15 years of teaching experience', 'password123', 'active'),
('Prof. Sarah Ahmed', 'sarah.ahmed@tech.com', '0312-9876543', 'MSc in Data Science', 'Machine Learning', 'Data Scientist with industry experience', 'password123', 'active'),
('Mr. Waseem Akram', 'waseem.akram@coding.com', '0333-5555555', 'MS Software Engineering', 'Web Development', 'Full-stack developer and instructor', 'password123', 'active'),
('Dr. Fatima Malik', 'fatima.malik@ai.edu', '0301-4444444', 'PhD in Artificial Intelligence', 'AI & Neural Networks', 'AI researcher and educator', 'password123', 'active');

-- Insert Sample Students
INSERT INTO student (Name, Email, Phone, Password, Date_of_Birth, Address) VALUES
('Ilham Nawaz', 'ilham.nawaz@student.com', '0301-1111111', 'password123', '2000-05-15', 'Lahore, Pakistan'),
('Ayesha Malik', 'ayesha.malik@student.com', '0322-2222222', 'password123', '2001-08-22', 'Karachi, Pakistan'),
('Bilal Ahmed', 'bilal.ahmed@student.com', '0333-3333333', 'password123', '1999-03-10', 'Islamabad, Pakistan'),
('Zainab Khan', 'zainab.khan@student.com', '0344-4444444', 'password123', '2002-11-30', 'Peshawar, Pakistan'),
('Usman Ali', 'usman.ali@student.com', '0355-5555555', 'password123', '2000-07-18', 'Faisalabad, Pakistan');

-- Insert Sample Courses
INSERT INTO course (Title, Description, Category, Level, Price, Duration, FK_Instructor_ID, Status) VALUES
('Database Management Systems', 'Learn SQL, normalization, database design, and administration. Covers MySQL, PostgreSQL, and database optimization techniques.', 'Computer Science', 'Intermediate', 5000.00, 40, 1, 'active'),
('Web Development Bootcamp', 'Full-stack web development course covering HTML, CSS, JavaScript, React, Node.js, and MongoDB. Build real-world projects.', 'Web Development', 'Beginner', 8000.00, 60, 3, 'active'),
('Python Programming Masterclass', 'Python from beginner to advanced level. Includes data structures, OOP, web scraping, APIs, and automation.', 'Programming', 'Beginner', 3000.00, 45, 2, 'active'),
('Machine Learning Fundamentals', 'Introduction to ML algorithms, data preprocessing, model training, and evaluation using Python.', 'Data Science', 'Advanced', 7000.00, 50, 4, 'active'),
('Mobile App Development', 'Build iOS and Android apps using React Native and Flutter. Includes publishing to app stores.', 'Mobile Development', 'Intermediate', 6000.00, 55, 3, 'active');

-- Insert Course Modules
INSERT INTO course_module (Module_Title, Module_Description, Module_Order, FK_Course_ID) VALUES
('Introduction to Databases', 'Understanding basic database concepts', 1, 1),
('SQL Fundamentals', 'Learn basic to advanced SQL queries', 2, 1),
('Database Design', 'ER Diagrams and Normalization', 3, 1),
('HTML & CSS Basics', 'Web page structure and styling', 1, 2),
('JavaScript Programming', 'Client-side scripting', 2, 2),
('React Fundamentals', 'Building UI components', 3, 2);

-- Insert Sample Enrollments
INSERT INTO enrollment (FK_Student_ID, FK_Course_ID, Enroll_Date, Status, Progress_Percentage) VALUES
(1, 1, '2024-01-15', 'active', 60),
(1, 2, '2024-01-20', 'active', 30),
(2, 1, '2024-01-10', 'active', 75),
(3, 3, '2024-01-25', 'active', 90),
(4, 4, '2024-02-01', 'active', 45),
(5, 2, '2024-02-05', 'active', 20);

-- Insert Sample Assignments
INSERT INTO assignment (Title, Description, FK_Course_ID, Due_Date, Max_Score) VALUES
('Database Design Project', 'Design a normalized database for an e-commerce platform with at least 5 tables', 1, '2024-03-15', 100),
('SQL Queries Practice', 'Write complex SQL queries for given business scenarios', 1, '2024-03-10', 100),
('Build a Portfolio Website', 'Create a responsive portfolio website using HTML/CSS', 2, '2024-03-20', 100),
('Python Data Analysis', 'Analyze dataset using pandas and matplotlib', 3, '2024-03-18', 100),
('ML Model Implementation', 'Implement a classification model on given dataset', 4, '2024-03-25', 100);

-- Insert Sample Submissions
INSERT INTO submission (FK_Assign_ID, FK_Student_ID, File_Link, Submission_Date, Grade, Status) VALUES
(1, 1, 'https://drive.google.com/file/ilham_db_project.pdf', '2024-03-14', 85.5, 'graded'),
(2, 1, 'https://drive.google.com/file/ilham_sql_queries.pdf', '2024-03-09', 92.0, 'graded'),
(1, 2, 'https://drive.google.com/file/ayesha_db_project.pdf', '2024-03-13', 88.0, 'graded'),
(3, 1, 'https://drive.google.com/file/ilham_portfolio.zip', '2024-03-19', NULL, 'submitted');

-- Insert Sample Payments
INSERT INTO payment (FK_Student_ID, FK_Course_ID, Amount, Date, Method, Status, Transaction_ID) VALUES
(1, 1, 5000.00, '2024-01-15', 'Credit Card', 'completed', 'TXN20240115001'),
(1, 2, 8000.00, '2024-01-20', 'Bank Transfer', 'completed', 'TXN20240120001'),
(2, 1, 5000.00, '2024-01-10', 'EasyPaisa', 'completed', 'TXN20240110001'),
(3, 3, 3000.00, '2024-01-25', 'JazzCash', 'completed', 'TXN20240125001'),
(4, 4, 7000.00, '2024-02-01', 'Credit Card', 'completed', 'TXN20240201001');

-- Insert Sample Quizzes
INSERT INTO quiz (Title, Description, FK_Course_ID, Total_Questions, Passing_Score, Time_Limit) VALUES
('SQL Fundamentals Quiz', 'Test your SQL knowledge', 1, 10, 70, 30),
('HTML & CSS Quiz', 'Basic web development concepts', 2, 15, 65, 45),
('Python Basics Quiz', 'Python syntax and concepts', 3, 12, 75, 35);

-- Insert Sample Quiz Questions
INSERT INTO quiz_question (FK_Quiz_ID, Question_Text, Option_A, Option_B, Option_C, Option_D, Correct_Answer, Points) VALUES
(1, 'What does SQL stand for?', 'Structured Query Language', 'Simple Query Language', 'Structured Question Language', 'Sequential Query Language', 'A', 1),
(1, 'Which command is used to retrieve data from a database?', 'GET', 'SELECT', 'RETRIEVE', 'FETCH', 'B', 1),
(2, 'What does HTML stand for?', 'Hyper Text Markup Language', 'High Tech Modern Language', 'Hyper Transfer Markup Language', 'Home Tool Markup Language', 'A', 1);

-- Insert Sample Quiz Results
INSERT INTO quiz_result (FK_Quiz_ID, FK_Student_ID, Score, Total_Questions, Time_Taken) VALUES
(1, 1, 8, 10, 1200),
(1, 2, 9, 10, 1400);

-- Insert Sample Discussions
INSERT INTO discussion (Title, Content, FK_Course_ID, FK_Student_ID, Created_Date) VALUES
('Help with Assignment 1', 'I am having trouble understanding the normalization part of assignment 1. Can someone help?', 1, 1, '2024-03-10 10:30:00'),
('Course Materials', 'When will the lecture videos for week 3 be uploaded?', 2, 2, '2024-03-12 14:20:00'),
('Project Ideas', 'Looking for project partners for the final project. Interested in e-commerce database design.', 1, 3, '2024-03-08 16:45:00');

-- Insert Sample Discussion Replies
INSERT INTO discussion_reply (FK_Discussion_ID, Reply_Text, FK_Instructor_ID, Created_Date) VALUES
(1, 'Normalization involves organizing data to reduce redundancy. Check the lecture slides for examples of 1NF, 2NF, and 3NF.', 1, '2024-03-10 11:45:00'),
(2, 'Week 3 videos will be uploaded by tomorrow. They cover React hooks and state management.', 3, '2024-03-12 15:30:00');

-- Insert Sample Notifications
INSERT INTO notification (User_ID, User_Type, Title, Message, Type, Is_Read) VALUES
(1, 'student', 'Assignment Graded', 'Your Database Design Project has been graded. You scored 85.5/100', 'assignment', FALSE),
(1, 'student', 'Payment Confirmed', 'Payment for Web Development course confirmed successfully', 'payment', TRUE),
(2, 'student', 'New Course Material', 'New videos uploaded for Database Management course', 'announcement', FALSE);

-- Insert Sample Certificates
INSERT INTO certificate (FK_Student_ID, FK_Course_ID, Certificate_Number, Issue_Date, Certificate_URL) VALUES
(3, 3, 'CERT-202403-PYT001', '2024-03-28', 'https://certificates.elearning.com/cert123456.pdf');

-- Additional Sample Data
INSERT INTO student (Name, Email, Phone, Password, Date_of_Birth, Address) VALUES
('Sara Khan', 'sara.khan@student.com', '0306-6666666', 'password123', '2001-09-14', 'Rawalpindi, Pakistan'),
('Ahmed Raza', 'ahmed.raza@student.com', '0307-7777777', 'password123', '1998-12-05', 'Multan, Pakistan');

INSERT INTO enrollment (FK_Student_ID, FK_Course_ID, Enroll_Date, Status, Progress_Percentage) VALUES
(6, 1, '2024-02-10', 'active', 40),
(7, 2, '2024-02-15', 'active', 25);

INSERT INTO payment (FK_Student_ID, FK_Course_ID, Amount, Date, Method, Status, Transaction_ID) VALUES
(6, 1, 5000.00, '2024-02-10', 'JazzCash', 'completed', 'TXN20240210001'),
(7, 2, 8000.00, '2024-02-15', 'Credit Card', 'completed', 'TXN20240215001');

-- ============================================
-- TEST DATA VERIFICATION
-- ============================================

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Test Queries to Verify Data
SELECT '============================================' as '';
SELECT 'DATABASE SETUP COMPLETE' as '';
SELECT '============================================' as '';
SELECT '' as '';
SELECT CONCAT('Database: elearning_platform_1') as '';
SELECT CONCAT('Created: ', NOW()) as '';
SELECT '' as '';
SELECT 'Sample Data Inserted:' as '';
SELECT CONCAT('- ', COUNT(*), ' Admin Users') as '' FROM admin;
SELECT CONCAT('- ', COUNT(*), ' Instructors') as '' FROM instructor;
SELECT CONCAT('- ', COUNT(*), ' Students') as '' FROM student;
SELECT CONCAT('- ', COUNT(*), ' Courses') as '' FROM course;
SELECT CONCAT('- ', COUNT(*), ' Enrollments') as '' FROM enrollment;
SELECT CONCAT('- ', COUNT(*), ' Payments') as '' FROM payment;
SELECT '' as '';
SELECT 'Demo Credentials:' as '';
SELECT 'Student: ilham.nawaz@student.com / password123' as '';
SELECT 'Instructor: ali.khan@university.edu / password123' as '';
SELECT 'Admin: admin@elearning.com / password123' as '';
SELECT '' as '';
SELECT 'Test Query Results:' as '';
SELECT CONCAT('Student Count: ', COUNT(*)) as '' FROM student;
SELECT CONCAT('Course Count: ', COUNT(*)) as '' FROM course;
SELECT '============================================' as '';