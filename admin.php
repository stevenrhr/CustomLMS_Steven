<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Include database connection
$host = 'localhost';
$db = 'lms_db';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users
$teachers = [];
$students = [];
$result = $conn->query("SELECT id, username, role FROM users");
while ($row = $result->fetch_assoc()) {
    if ($row['role'] == 'teacher') {
        $teachers[] = $row;
    } elseif ($row['role'] == 'student') {
        $students[] = $row;
    }
}

// Fetch all courses
$courses = [];
$result = $conn->query("SELECT id, name FROM courses");
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Fetch all classes
$classes = [];
$result = $conn->query("SELECT id, name FROM classes");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_teacher'])) {
        // Add new teacher
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = 'teacher';
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $last_education = $_POST['last_education'];
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO teacher_details (teacher_id, name, phone, last_education) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $name, $phone, $last_education);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php?success=add_teacher");
        exit();
    } elseif (isset($_POST['add_student'])) {
        // Add new student
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = 'student';
        $name = $_POST['name'];
        $parent_name = $_POST['parent_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO student_details (user_id, name, parent_name, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $name, $parent_name, $phone, $address);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php?success=add_student");
        exit();
    } elseif (isset($_POST['add_course'])) {
        // Add new course
        $course_name = $_POST['course_name'];
        $teacher_id = $_POST['teacher_id'];
        $stmt = $conn->prepare("INSERT INTO courses (name, teacher_id) VALUES (?, ?)");
        $stmt->bind_param("si", $course_name, $teacher_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php?success=add_course");
        exit();
    } elseif (isset($_POST['add_class'])) {
        // Add new class
        $class_name = $_POST['class_name'];
        $stmt = $conn->prepare("INSERT INTO classes (name) VALUES (?)");
        $stmt->bind_param("s", $class_name);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php?success=add_class");
        exit();
    } elseif (isset($_POST['assign_student_course'])) {
        // Assign student to course
        $course_id = $_POST['course_id'];
        $student_id = $_POST['student_id'];
        $result = $conn->query("SELECT * FROM student_courses WHERE student_id = $student_id AND course_id = $course_id");
        if ($result->num_rows > 0) {
            header("Location: admin.php?success=failed_student_course");
        } else {
            $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_id, $course_id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin.php?success=assign_student_course");
        }
        exit();
    } elseif (isset($_POST['assign_student_class'])) {
        // Assign student to class
        $class_id = $_POST['class_id'];
        $student_id = $_POST['student_id'];
        $result = $conn->query("SELECT * FROM student_classes WHERE student_id = $student_id AND class_id = $class_id");
        if ($result->num_rows > 0) {
            header("Location: admin.php?success=failed_student_class");
        } else {
            $stmt = $conn->prepare("INSERT INTO student_classes (student_id, class_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_id, $class_id);
            $stmt->execute();
            $stmt->close();
            header("Location: admin.php?success=assign_student_class");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#"><img src="images/SchoolLogo.png" alt="logo" width="32" height="auto">Sistem Terpadu</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin.php">Admin Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <h1>Admin Dashboard</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'add_teacher':
                        echo "New teacher added successfully!";
                        break;
                    case 'add_student':
                        echo "New student added successfully!";
                        break;
                    case 'add_course':
                        echo "New course added successfully!";
                        break;
                    case 'add_class':
                        echo "New class added successfully!";
                        break;
                    case 'assign_student_course':
                        echo "Student assigned to course successfully!";
                        break;
                    case 'failed_student_course':
                        echo "ERROR: Student is already assigned to course!";
                        break;
                    case 'assign_student_class':
                        echo "Student assigned to class successfully!";
                        break;
                    case 'failed_student_class':
                        echo "ERROR: Student is already assigned to class!";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <ul class="nav nav-tabs" id="adminTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="teacher-tab" data-toggle="tab" href="#teacher" role="tab" aria-controls="teacher" aria-selected="true">Teacher</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="student-tab" data-toggle="tab" href="#student" role="tab" aria-controls="student" aria-selected="false"> Student</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="class-tab" data-toggle="tab" href="#class" role="tab" aria-controls="class" aria-selected="false">Class</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="course-tab" data-toggle="tab" href="#course" role="tab" aria-controls="course" aria-selected="false">Course</a>
            </li>
        </ul>

        <div class="tab-content" id="adminTabContent">
            <!-- Add Teacher Tab -->
            <div class="tab-pane fade show active" id="teacher" role="tabpanel" aria-labelledby="teacher-tab">
                <!-- Add Teacher Form -->
                <div class="card mb-3 mt-3">
                    <div class="card-header">
                        Add New Teacher
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="last_education">Last Education</label>
                                <input type="text" class="form-control" id="last_education" name="last_education" required>
                            </div>
                            <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Student Tab -->
            <div class="tab-pane fade" id="student" role="tabpanel" aria-labelledby="student-tab">
                <div class="card mb-3">
                    <div class="card-header">
                        Add New Student
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="name">Student Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="parent_name">Parent Name</label>
                                <input type="text" class="form-control" id="parent_name" name="parent_name" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" required></textarea>
                            </div>
                            <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Class Tab -->
            <div class="tab-pane fade" id="class" role="tabpanel" aria-labelledby="class-tab">
                <!-- Add Class Form -->
                <div class="card mb-3 mt-3">
                    <div class="card-header">
                        Add New Class
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="class_name">Class Name</label>
                                <input type="text" class="form-control" id="class_name" name="class_name" required>
                            </div>
                            <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
                        </form>
                    </div>
                </div>

                <!-- Assign Student to Class Form -->
                <div class="card mb-3">
                    <div class="card-header">
                        Assign Student to Class
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="class_id">Select Class</label>
                                <select class="form-control" id="class_id" name="class_id" required>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="student_id">Select Student</label>
                                <select class="form-control" id="student_id" name="student_id" required>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="assign_student_class" class="btn btn-primary">Assign Student to Class</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Course Tab -->
            <div class="tab-pane fade" id="course" role="tabpanel" aria-labelledby="course-tab">
                <!-- Add Course Form -->
                <div class="card mb-3">
                    <div class="card-header">
                        Add New Course
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="course_name">Course Name</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" required>
                            </div>
                            <div class="form-group">
                                <label for="teacher_id">Assign Teacher</label>
                                <select class="form-control" id="teacher_id" name="teacher_id" required>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_course" class="btn btn-primary">Add Course</button>
                        </form>
                    </div>
                </div>

                <!-- Assign Student to Course Form -->
                <div class="card mb-3">
                    <div class="card-header">
                        Assign Student to Course
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="course_id">Select Course</label>
                                <select class="form-control" id="course_id" name="course_id" required>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="student_id">Select Student</label>
                                <select class="form-control" id="student_id" name="student_id" required>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="assign_student_course" class="btn btn-primary">Assign Student to Course</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
