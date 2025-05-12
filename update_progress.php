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

$student_id = $_POST['student_id'];
$checklist_item_id = $_POST['checklist_item_id'];
$completed = $_POST['completed'];
$comment = $_POST['comment'];
echo "<script>console.log('$student_id, $checklist_item_id, $completed, $comment');</script>";
// Check if the progress record exists
$result = $conn->query("SELECT * FROM student_progress WHERE student_id = $student_id AND checklist_item_id = $checklist_item_id");
if ($result->num_rows > 0) {
    // Update existing record
    $stmt = $conn->prepare("UPDATE student_progress SET completed = '$completed' , comment = '$comment' WHERE student_id ='$student_id' AND checklist_item_id = '$checklist_item_id'");
} else {
    // Insert new record
    $stmt = $conn->prepare("INSERT INTO student_progress (student_id, checklist_item_id, completed, comment) VALUES ('$student_id', '$checklist_item_id', '$completed', '$comment')");
}

$stmt->execute();
$stmt->close();
$conn->close();

echo "Progress updated successfully.";
?>
