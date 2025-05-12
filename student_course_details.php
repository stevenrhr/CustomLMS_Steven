<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
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

$course_id = $_GET['course_id'];
$student_id = $_SESSION['user_id'];
$weeks = 12;

// Fetch course weeks content
$course_weeks = [];
for ($week = 1; $week <= $weeks; $week++) {
    $result = $conn->query("SELECT * FROM course_weeks WHERE course_id = $course_id AND week = $week");
    $row = $result->fetch_assoc();
    $course_weeks[$week] = $row ? $row : [
        'explanation' => ''
    ];
}

// Fetch checklist items
$checklist_items = [];
for ($week = 1; $week <= $weeks; $week++) {
    $result = $conn->query("SELECT * FROM checklist_items WHERE course_id = $course_id AND week = $week");
    while ($row = $result->fetch_assoc()) {
        $checklist_items[$week][$row['category']][] = $row;
    }
}

// Fetch student progress
$student_progress = [];
$result = $conn->query("SELECT * FROM student_progress WHERE student_id = $student_id");
while ($row = $result->fetch_assoc()) {
    $student_progress[$row['checklist_item_id']] = $row['completed'];
}

$student_comment = [];
$result = $conn->query("SELECT * FROM student_progress WHERE student_id = $student_id");
while ($row = $result->fetch_assoc()) {
    $student_comment[$row['checklist_item_id']] = $row['comment'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Course Details</title>
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
                    <a class="nav-link" href="student_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="student_courses.php">Courses</a>
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
        <h4>Course: <?php echo htmlspecialchars($course_id); ?></h4>
        <div class="accordion" id="accordionExample">
            <?php for ($week = 1; $week <= $weeks; $week++): ?>
                <div class="card">
                    <div class="card-header" id="heading<?php echo $week; ?>">
                        <h4 class="mb-0">
                            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?php echo $week; ?>" aria-expanded="true" aria-controls="collapse<?php echo $week; ?>">
                                Week <?php echo $week; ?>
                            </button>
                        </h4>
                    </div>
                    <div id="collapse<?php echo $week; ?>" class="collapse" aria-labelledby="heading<?php echo $week; ?>" data-parent="#accordionExample">
                        <div class="card-body">
                            <h5>Explanation</h5>
                            <p><?php echo htmlspecialchars($course_weeks[$week]['explanation']); ?></p>
                            <h5>Nilai Agama dan Budi Pekerti</h5>
                            <ul>
                                <?php $result = $conn->query("SELECT * FROM checklist_items WHERE course_id = $course_id AND week = $week AND category=1");
                                if ($result->num_rows > 0) { foreach ($checklist_items[$week]['agama_budi_pekerti'] as $item): ?>
                                    <li>
                                        <span><?php echo htmlspecialchars($item['description']); ?></span>
                                        <?php if (isset($student_progress[$item['id']]) && $student_progress[$item['id']]): ?>
                                            <span class="badge badge-success">Done</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Not Done</span>
                                        <?php endif; ?>
                                        <span>Comment: <?php echo htmlspecialchars($student_comment[$item['id']]); ?></span>
                                    </li>
                                <?php endforeach;} else {?>
                                    <li>Empty</li>
                                <?php } ?> 
                            </ul>
                            <h5>Jati Diri</h5>
                            <ul>
                                <?php $result = $conn->query("SELECT * FROM checklist_items WHERE course_id = $course_id AND week = $week AND category=2");
                                if ($result->num_rows > 0) { foreach ($checklist_items[$week]['jati_diri'] as $item): ?>
                                    <li>
                                        <span><?php echo htmlspecialchars($item['description']); ?></span>
                                        <?php if (isset($student_progress[$item['id']]) && $student_progress[$item['id']]): ?>
                                            <span class="badge badge-success">Done</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Not Done</span>
                                        <?php endif; ?>
                                        <span>Comment: <?php echo htmlspecialchars($student_comment[$item['id']]); ?></span>
                                        </li>
                                <?php endforeach;} else {?>
                                    <li>Empty</li>
                                <?php } ?>
                            </ul>
                            <h5>Dasar Literasi dan STEAM</h5>
                            <ul>
                                <?php $result = $conn->query("SELECT * FROM checklist_items WHERE course_id = $course_id AND week = $week AND category=3");
                                if ($result->num_rows > 0) { foreach ($checklist_items[$week]['literasi_steam'] as $item): ?>
                                    <li>
                                        <span><?php echo htmlspecialchars($item['description']); ?></span>
                                        <?php if (isset($student_progress[$item['id']]) && $student_progress[$item['id']]): ?>
                                            <span class="badge badge-success">Done</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Not Done</span>
                                        <?php endif; ?>
                                        <span>Comment: <?php echo htmlspecialchars($student_comment[$item['id']]); ?></span>
                                        </li>
                                <?php endforeach;} else {?>
                                    <li>Empty</li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
