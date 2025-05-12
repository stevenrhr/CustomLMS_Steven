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

$course_id = $_GET['course_id'];
$weeks = 12;

// Fetch course weeks content
$course_weeks = [];
for ($week = 1; $week <= $weeks; $week++) {
    $result = $conn->query("SELECT * FROM course_weeks WHERE course_id = $course_id AND week = $week");
    $row = $result->fetch_assoc();
    $course_weeks[$week] = $row ? $row : [
        'explanation' => '',
        'agama_budi_pekerti' => json_encode([]),
        'jati_diri' => json_encode([]),
        'literasi_steam' => json_encode([])
    ];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $week = $_POST['week'];
    $explanation = $_POST['explanation'];
    $result = $conn->query("SELECT * FROM course_weeks WHERE course_id = '$course_id' AND week = '$week'");
    if ($result->num_rows > 0){
        $stmt = $conn->prepare("UPDATE course_weeks SET explanation = '$explanation' WHERE course_id = '$course_id' AND week = '$week'");
    } else {
        $stmt = $conn->prepare("INSERT INTO course_weeks (course_id, week, explanation) VALUES ('$course_id', '$week', '$explanation')");
    }
    $stmt->execute();
    $stmt->close();
    header("Location: teacher_course_details.php?course_id=$course_id");
}

// Fetch checklist items
$checklist_items = [];
for ($week = 1; $week <= $weeks; $week++) {
    $result = $conn->query("SELECT * FROM checklist_items WHERE course_id = $course_id AND week = $week");
    while ($row = $result->fetch_assoc()) {
        $checklist_items[$week][$row['category']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Course Details</title>
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
                            <form action="teacher_course_details.php?course_id=<?php echo $course_id; ?>" method="post">
                                <input type="hidden" name="week" value="<?php echo $week; ?>">
                                <div class="form-group">
                                    <label for="explanation">Explanation</label>
                                    <textarea class="form-control" id="explanation" name="explanation" rows="3"><?php echo htmlspecialchars($course_weeks[$week]['explanation']); ?></textarea>
                                </div>
                               
                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>
                            <h3>Checklist Items</h3>
                            <div>
                                <button type="button" class="btn btn-success" onclick="addChecklistItem(<?php echo $week; ?>)">Add Checklist Item</button>
                            </div>
                            <div id="checklistItems<?php echo $week; ?>">
                                <?php if (isset($checklist_items[$week])): ?>
                                    <?php foreach ($checklist_items[$week] as $category => $items): ?>
                                        <h4><?php echo ucfirst(str_replace('_', ' ', $category)); ?></h4>
                                        <ul>
                                            <?php foreach ($items as $item): ?>
                                                <li>
                                                    <span><?php echo htmlspecialchars($item['description']); ?></span>
                                                    <button type="button" class="btn btn-warning btn-sm" onclick="editChecklistItem(<?php echo $item['id']; ?>, <?php echo $week; ?>)">Edit</button>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeChecklistItem(<?php echo $item['id']; ?>, <?php echo $week; ?>)">Remove</button>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        function addChecklistItem(week) {
            let description = prompt("Enter the checklist item description:");
            if (description) {
                let checkCategory = 1;                
                while(checkCategory==1){
                    let category = prompt("Pick 1-3 (1. agama_budi_pekerti, 2. jati_diri, 3. literasi_steam):");
                    if(category == 1 || category == 2 || category == 3){
                        $.post('add_checklist_item.php', {course_id: <?php echo $course_id; ?>, week: week, category: category, description: description}, function(data) {
                        location.reload()});
                        checkCategory = 0;
                    } else {
                        alert("Error: insert 1,2,3");
                    }
                }
                
            }
        }

        function editChecklistItem(id, week) {
            let description = prompt("Edit the checklist item description:");
            if (description) {
                $.post('edit_checklist_item.php', {id: id, description: description}, function(data) {
                    alert(`Checklist Removed on week ${week}`);
                    location.reload();
                });
            }
        }

        function removeChecklistItem(id, week) {
            if (confirm("Are you sure you want to remove this checklist item?")) {
                alert(`Checklist Changed on week ${week}`);
                $.post('remove_checklist_item.php', {id: id}, function(data) {
                    location.reload();
                });
            }
        }
    </script>
</body>
</html>
