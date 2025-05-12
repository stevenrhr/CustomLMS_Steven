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

$id = $_POST['id'];
$description = $_POST['description'];

$stmt = $conn->prepare("UPDATE checklist_items SET description = ? WHERE id = ?");
$stmt->bind_param("si", $description, $id);
$stmt->execute();
$stmt->close();

echo "Success";
