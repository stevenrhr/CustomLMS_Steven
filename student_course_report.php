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

$student_id = $_SESSION['user_id'];

// Fetch all courses attended by the student
$courses = [];
$result = $conn->query("SELECT courses.id, courses.name FROM courses JOIN student_courses ON courses.id = student_courses.course_id WHERE student_courses.student_id = $student_id");
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

$selected_course_id = isset($_GET['course_id']) ? $_GET['course_id'] : (isset($courses[0]['id']) ? $courses[0]['id'] : null);

if ($selected_course_id) {
    // Fetch checklist progress
    $course_progress = [];
    for ($week = 1; $week <= 12; $week++) {
        $course_progress[$week] = [
            'agama_budi_pekerti' => ['total' => 0, 'completed' => 0],
            'jati_diri' => ['total' => 0, 'completed' => 0],
            'literasi_steam' => ['total' => 0, 'completed' => 0]
        ];
        $result = $conn->query("SELECT * FROM checklist_items WHERE course_id = $selected_course_id AND week = $week");
        while ($row = $result->fetch_assoc()) {
            $category = $row['category'];
            $course_progress[$week][$category]['total']++;
            $checklist_item_id = $row['id'];
            $progress_result = $conn->query("SELECT completed FROM student_progress WHERE student_id = $student_id AND checklist_item_id = $checklist_item_id");
            if ($progress_row = $progress_result->fetch_assoc()) {
                if ($progress_row['completed']) {
                    $course_progress[$week][$category]['completed']++;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Course Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <h4>Course Report</h4>
        <form method="GET" action="">
            <div class="form-group">
                <label for="course_id">Select Course</label>
                <select class="form-control" id="course_id" name="course_id" onchange="this.form.submit()">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $selected_course_id == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($selected_course_id): ?>
        <canvas id="courseReportChart" width="400" height="200"></canvas>
        <script>
            const courseProgress = <?php echo json_encode($course_progress); ?>;
            const datasetsA = [];
            const datasetsB = [];
            const datasetsC = [];
            for (let week = 1; week <= 12; week++) {
                const weekData = courseProgress[week];
                var a = weekData['agama_budi_pekerti']['completed'] / weekData['agama_budi_pekerti']['total'] * 100 || 0;
                var b = weekData['jati_diri']['completed'] / weekData['jati_diri']['total'] * 100 || 0;
                var c = weekData['literasi_steam']['completed'] / weekData['literasi_steam']['total'] * 100 || 0;
                datasetsA.push(a);
                datasetsB.push(b);
                datasetsC.push(c);
            }

            
            const labelsNew = Array.from({length: 12}, (_, i) => `Week ${i + 1}`);
            const chartData = {
                labels: labelsNew,
                datasets: [{
                    label: `Agama dan Budi Pekerti`,
                    data: datasetsA,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    barPercentage: 0.8,
                    categoryPercentage: 0.8
                },{
                    label: `Jati Diri`,
                    data: datasetsB,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1,
                    barPercentage: 0.8,
                    categoryPercentage: 0.8
                },{
                    label: `Literasi dan STEAM`,
                    data: datasetsC,
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1,
                    barPercentage: 0.8,
                    categoryPercentage: 0.8
                }]
            };

            new Chart(document.getElementById('courseReportChart'), {
                type: 'bar',
                data: chartData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + "%";
                                }
                            }
                        },
                        responsive: true
                    }
                
                }
            }    
            );
        </script>
        <?php else: ?>
        <p>No courses found for this student.</p>
        <?php endif; ?>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
