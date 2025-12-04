<?php
// appointments.php (student books appointment)
session_start();
require_once '../db/connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$counselor_id = $input['counselor_id'] ?? 0;
$datetime     = $input['datetime'] ?? '';
$reason       = trim($input['reason'] ?? '');
$student_id   = $_SESSION['user_id'];

if (!$counselor_id || !$datetime || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Missing information']);
    exit;
}

// Prevent double booking
$check = $conn->prepare("SELECT 1 FROM appointments WHERE counselor_id = ? AND appointment_date = ?");
$check->execute([$counselor_id, $datetime]);
if ($check->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'This time slot is already taken.']);
    exit;
}

// Insert appointment
$stmt = $conn->prepare("
    INSERT INTO appointments 
    (counselor_id, student_id, appointment_date, appointment_desc, status) 
    VALUES (?, ?, ?, ?, 'pending')
");

$success = $stmt->execute([$counselor_id, $student_id, $datetime, $reason]);

if ($success) {
    $appointment_id = $conn->lastInsertId();

    // Get student name
    $name_stmt = $conn->prepare("SELECT CONCAT(fname, ' ', COALESCE(CONCAT(mi,'.'), ''), ' ', lname) as name FROM student WHERE student_id = ?");
    $name_stmt->execute([$student_id]);
    $student_name = $name_stmt->fetchColumn() ?: 'A student';

    // Create notification for counselor
    $notif_file = __DIR__ . "/../sessions/counselor_{$counselor_id}_notifs.json";
    $notifs = file_exists($notif_file) ? json_decode(file_get_contents($notif_file), true) : [];

    $notifs[] = [
        'type' => 'new_appointment',
        'message' => "$student_name requested an appointment",
        'appointment_id' => $appointment_id,
        'time' => date('M j, g:i A'),
        'read' => false
    ];

    file_put_contents($notif_file, json_encode($notifs));

    echo json_encode(['success' => true, 'message' => 'Appointment booked!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to book appointment']);
}
?>