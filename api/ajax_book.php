<?php
session_start();
require_once 'db/connection.php';

if ($_SESSION['role'] !== 'student') die(json_encode(['success'=>false]));

$counselor_id = $_POST['counselor_id'];
$student_id   = $_SESSION['user_id'];
$datetime     = $_POST['datetime'];

// Prevent double-booking (optional but recommended)
$check = $conn->prepare("SELECT 1 FROM appointments WHERE counselor_id = ? AND appointment_desc = ?");
$check->execute([$counselor_id, $datetime]);
if ($check->fetch()) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO appointments (counselor_id, student_id, appointment_desc) VALUES (?, ?, ?)");
$success = $stmt->execute([$counselor_id, $student_id, $datetime]);

echo json_encode(['success' => $success]);
?>