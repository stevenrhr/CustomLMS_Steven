<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$db = 'lms_db';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$course_id = $_POST['course_id'];
$week = $_POST['week'];
$category = $_POST['category'];
$description = $_POST['description'];

$stmt = $conn->prepare("INSERT INTO checklist_items (course_id, week, category, description) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $course_id, $week, $category, $description);
$stmt->execute();
$stmt->close();

echo "Success";
