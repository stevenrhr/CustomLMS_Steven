<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
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

// Fetch all courses taught by the teacher
$teacher_id = $_SESSION['user_id'];
$courses = [];
$result = $conn->query("SELECT id, name FROM courses WHERE teacher_id = $teacher_id");
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Fetch students in a selected course
$students = [];
$selected_course_id = isset($_GET['course_id']) ? $_GET['course_id'] : (isset($courses[0]['id']) ? $courses[0]['id'] : null);
if ($selected_course_id) {
    $result = $conn->query("SELECT users.id, users.username FROM users JOIN student_courses ON users.id = student_courses.student_id WHERE student_courses.course_id = $selected_course_id");
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch checklist items for the selected course
$checklist_items = [];
for ($week = 1; $week <= 12; $week++) {
    $result = $conn->query("SELECT * FROM checklist_items WHERE course_id = $selected_course_id AND week = $week");
    while ($row = $result->fetch_assoc()) {
        $checklist_items[$week][$row['category']][] = $row;
    }
}

// Fetch student progress
$student_progress = [];
$student_comment = [];
if ($selected_course_id && !empty($students)) {
    foreach ($students as $student) {
        $student_id = $student['id'];
        $student_progress[$student_id] = [];
        $student_comment[$student_id] = [];
        $result = $conn->query("SELECT * FROM student_progress WHERE student_id = $student_id");
        while ($row = $result->fetch_assoc()) {
            $student_progress[$student_id][$row['checklist_item_id']] = $row['completed'];
            $student_comment[$student_id][$row['checklist_item_id']] = $row['comment'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Assess Students</title>
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
                    <a class="nav-link" href="teacher_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="teacher_courses.php">Courses</a>
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
        <h1>Assess Students</h1>
        <div class="form-group">
            <label for="courseSelect">Select Course</label>
            <select class="form-control" id="courseSelect" onchange="window.location.href='teacher_assess.php?course_id=' + this.value">
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>" <?php if ($selected_course_id == $course['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($course['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($selected_course_id && !empty($students)): ?>
            <div class="accordion" id="accordionExample">
                <?php foreach ($students as $student): ?>
                    <div class="card">
                        <div class="card-header" id="heading<?php echo $student['id']; ?>">
                            <h2 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?php echo $student['id']; ?>" aria-expanded="true" aria-controls="collapse<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['username']); ?>
                                </button>
                            </h2>
                        </div>
                        <div id="collapse<?php echo $student['id']; ?>" class="collapse" aria-labelledby="heading<?php echo $student['id']; ?>" data-parent="#accordionExample">
                            <div class="card-body">
                                <?php for ($week = 1; $week <= 12; $week++): ?>
                                    <h4>Week <?php echo $week; ?></h4>
                                    <?php foreach (['agama_budi_pekerti', 'jati_diri', 'literasi_steam'] as $category): ?>
                                        <h5><?php echo ucwords(str_replace('_', ' ', $category)); ?></h5>
                                        <ul>
                                            <?php if (isset($checklist_items[$week][$category])): ?>
                                                <?php foreach ($checklist_items[$week][$category] as $item): ?>
                                                    <li>
                                                        <?php 
                                                            $std_check = $student['id'].$item['id'];
                                                            $com = $std_check."comm"; 
                                                            $check = $std_check."check";
                                                            $prev = $std_check."prev";
                                                        ?>
                                                        <div id="<?php echo $std_check; ?>">
                                                            <span><?php echo htmlspecialchars($item['description']); ?></span>
                                                            <input id="<?php echo $prev; ?>" readonly disabled type="checkbox" <?php if (isset($student_progress[$student['id']][$item['id']]) && $student_progress[$student['id']][$item['id']]) echo 'checked'; ?>>
                                                            <br>              
                                                            <select id="<?php echo $check; ?>">
                                                                <option value="0">No</option>
                                                                <option value="1">Yes</option>
                                                            </select>
                                                            <input type="text" id="<?php echo $com; ?>">
                                                            <input type="submit" onclick="updateProgress(<?php echo $student['id']; ?>, <?php echo $item['id']; ?>, document.getElementById('<?php echo $check; ?>').value, document.getElementById('<?php echo $com; ?>').value, <?php echo $std_check; ?>)">
                                                            <br>
                                                            <span>Last Comment: <?php echo htmlspecialchars($student_comment[$student['id']][$item['id']] ?? 'Is Empty'); ?></span>                                                    
                                                        </div>
                                                        </li>
                                                    <hr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    <?php endforeach; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No students found for this course.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script>
        function updateProgress(student_id, checklist_item_id, completed, comment, refresh) {
            $.post('update_progress.php', {student_id: student_id, checklist_item_id: checklist_item_id, completed: completed, comment:comment}, function(data) {
                console.log(data);
                alert("done");
                let ref="#"+refresh
                $(ref).load(document.URL +  ' '+ref);
                return false;   
            });
        }
    </script>
</body>
</html>
