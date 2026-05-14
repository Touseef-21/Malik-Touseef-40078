<?php
require_once '../config.php';

// Check if user is instructor
if (!isLoggedIn() || getUserType() != 'instructor') {
    redirect('../login.php');
}

$instructor = getCurrentUser();
$instructor_id = $_SESSION['user_id'];
$page_title = "Create Course";

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $level = sanitize($_POST['level']);
    $price = sanitize($_POST['price']);
    $duration = sanitize($_POST['duration']);
    $status = sanitize($_POST['status']);
    
    // Validation
    if (empty($title) || empty($description) || empty($category)) {
        $error = 'Please fill in all required fields';
    } elseif ($price < 0) {
        $error = 'Price cannot be negative';
    } elseif ($duration <= 0) {
        $error = 'Duration must be greater than 0';
    } else {
        // Insert course
        $sql = "INSERT INTO COURSE (Title, Description, Category, Level, Price, Duration, FK_Instructor_ID, Status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssddis", $title, $description, $category, $level, $price, $duration, $instructor_id, $status);
        
        if ($stmt->execute()) {
            $course_id = $conn->insert_id;
            $success = 'Course created successfully!';
            
            // Add default module if needed
            if (isset($_POST['add_default_module']) && $_POST['add_default_module'] == 'yes') {
                $module_sql = "INSERT INTO COURSE_MODULE (Module_Title, Module_Description, Module_Order, FK_Course_ID) 
                              VALUES ('Introduction', 'Course introduction and overview', 1, ?)";
                $module_stmt = $conn->prepare($module_sql);
                $module_stmt->bind_param("i", $course_id);
                $module_stmt->execute();
            }
            
            // Redirect to edit page
            header("refresh:2;url=edit_course.php?id=" . $course_id);
        } else {
            $error = 'Failed to create course: ' . $conn->error;
        }
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
    <style>
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section h3 {
            color: #667eea;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .preview-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        .preview-course {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
    </style>
</head>
<body>
    <!-- Instructor Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                Instructor Portal
            </a>
            <div class="nav-menu">
                <span class="user-greeting">
                    <i class="fas fa-chalkboard-teacher"></i> <?php echo $instructor['Name']; ?>
                </span>
                <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my_courses.php"><i class="fas fa-book"></i> My Courses</a>
                <a href="create_course.php" class="active"><i class="fas fa-plus"></i> Create Course</a>
                <a href="grade_assignments.php"><i class="fas fa-tasks"></i> Grade Assignments</a>
                <a href="../dashboard.php"><i class="fas fa-exchange-alt"></i> Main Site</a>
                <a href="../logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-plus-circle"></i> Create New Course</h1>
            <p>Design and publish your own course on our platform</p>
        </div>

        <!-- Display Messages -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="createCourseForm">
            <!-- Basic Information -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Course Title *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               placeholder="e.g., Web Development Bootcamp" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="Computer Science" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                            <option value="Web Development" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Web Development') ? 'selected' : ''; ?>>Web Development</option>
                            <option value="Data Science" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Data Science') ? 'selected' : ''; ?>>Data Science</option>
                            <option value="Business" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Business') ? 'selected' : ''; ?>>Business</option>
                            <option value="Design" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Design') ? 'selected' : ''; ?>>Design</option>
                            <option value="Marketing" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="description">Course Description *</label>
                    <textarea id="description" name="description" class="form-control" 
                              placeholder="Describe what students will learn in this course..." 
                              required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
            </div>

            <!-- Course Details -->
            <div class="form-section">
                <h3><i class="fas fa-cog"></i> Course Details</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="level">Course Level</label>
                        <select id="level" name="level" class="form-control">
                            <option value="Beginner" <?php echo (isset($_POST['level']) && $_POST['level'] == 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo (isset($_POST['level']) && $_POST['level'] == 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo (isset($_POST['level']) && $_POST['level'] == 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (Rs.)</label>
                        <input type="number" id="price" name="price" class="form-control" 
                               step="0.01" min="0" 
                               value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '0.00'; ?>">
                        <small>Set to 0 for a free course</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration (hours)</label>
                        <input type="number" id="duration" name="duration" class="form-control" 
                               min="1" max="500" 
                               value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : '10'; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Course Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft (Not published)</option>
                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active (Published)</option>
                    </select>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="add_default_module" name="add_default_module" value="yes" checked>
                    <label for="add_default_module">Add default "Introduction" module</label>
                </div>
            </div>

            <!-- Course Structure (Optional) -->
            <div class="form-section">
                <h3><i class="fas fa-sitemap"></i> Course Structure (Optional)</h3>
                <p>You can add modules and content after creating the course.</p>
                
                <div id="modules-container">
                    <!-- Modules will be added here dynamically -->
                </div>
                
                <button type="button" id="add-module-btn" class="btn-outline">
                    <i class="fas fa-plus"></i> Add Module
                </button>
            </div>

            <!-- Preview -->
            <div class="preview-section">
                <h3><i class="fas fa-eye"></i> Course Preview</h3>
                <div class="preview-course" id="course-preview">
                    <h3 id="preview-title">Course Title</h3>
                    <p id="preview-description">Course description will appear here...</p>
                    <div class="preview-details">
                        <p><strong>Category:</strong> <span id="preview-category">-</span></p>
                        <p><strong>Level:</strong> <span id="preview-level">Beginner</span></p>
                        <p><strong>Price:</strong> Rs. <span id="preview-price">0.00</span></p>
                        <p><strong>Duration:</strong> <span id="preview-duration">10</span> hours</p>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <button type="submit" class="btn-primary btn-block">
                            <i class="fas fa-save"></i> Create Course
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" onclick="saveAsDraft()" class="btn-outline btn-block">
                            <i class="fas fa-file-alt"></i> Save as Draft
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <a href="my_courses.php" class="btn-secondary btn-block" style="text-align: center; display: block;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="../js/script.js"></script>
    <script>
        // Preview update
        const form = document.getElementById('createCourseForm');
        const previewFields = {
            title: document.getElementById('preview-title'),
            description: document.getElementById('preview-description'),
            category: document.getElementById('preview-category'),
            level: document.getElementById('preview-level'),
            price: document.getElementById('preview-price'),
            duration: document.getElementById('preview-duration')
        };
        
        // Update preview on input
        form.addEventListener('input', function(e) {
            const field = e.target;
            const previewId = 'preview-' + field.name;
            
            if (previewFields[field.name]) {
                previewFields[field.name].textContent = field.value || field.options[field.selectedIndex]?.text || '-';
            }
            
            // Special handling for price
            if (field.name === 'price') {
                const price = parseFloat(field.value) || 0;
                previewFields.price.textContent = price.toFixed(2);
            }
        });
        
        // Save as draft
        function saveAsDraft() {
            document.getElementById('status').value = 'draft';
            form.submit();
        }
        
        // Add module functionality
        let moduleCount = 0;
        document.getElementById('add-module-btn').addEventListener('click', function() {
            moduleCount++;
            const container = document.getElementById('modules-container');
            
            const moduleDiv = document.createElement('div');
            moduleDiv.className = 'module-item';
            moduleDiv.innerHTML = `
                <div class="module-header">
                    <h4>Module ${moduleCount}</h4>
                    <button type="button" class="btn-danger btn-small remove-module">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="form-group">
                    <label>Module Title</label>
                    <input type="text" class="form-control" placeholder="Enter module title">
                </div>
                <div class="form-group">
                    <label>Module Description</label>
                    <textarea class="form-control" rows="3" placeholder="Describe this module"></textarea>
                </div>
            `;
            
            container.appendChild(moduleDiv);
            
            // Add remove functionality
            moduleDiv.querySelector('.remove-module').addEventListener('click', function() {
                moduleDiv.remove();
            });
        });
        
        // Initialize form validation
        form.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const category = document.getElementById('category').value;
            
            if (!title || !description || !category) {
                e.preventDefault();
                alert('Please fill in all required fields (Title, Description, Category)');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>