<?php
session_start();
require_once '../db/connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$counselor_id = $input['counselor_id'] ?? 0;
$datetime     = $input['datetime'] ?? '';  // e.g., "12-15-2025 14:30:00"
$reason       = trim($input['reason'] ?? '');
$student_id   = $_SESSION['user_id'];

if (!$counselor_id || !$datetime || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Missing information']);
    exit;
}

// Prevent double booking
$check = $conn->prepare("SELECT 1 FROM appointments WHERE counselor_ID = ? AND appointment_date = ?");
$check->execute([$counselor_id, $datetime]);
if ($check->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'This time slot is already taken.']);
    exit;
}

// Insert into correct columns

// Insert with status 'pending'
$stmt = $conn->prepare("
    INSERT INTO appointments 
    (counselor_ID, student_Id, appointment_date, Appointment_desc, status) 
    VALUES (?, ?, ?, ?, 'pending')
");

$success = $stmt->execute([
    $counselor_id,
    $student_id,
    $datetime,           // Clean: 2025-12-15 14:30:00
    $reason              // Only reason goes here
]);

echo json_encode(['success' => $success, 'message' => $success ? 'Booked!' : 'Failed']);
?>