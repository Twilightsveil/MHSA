<?php
session_start();
require_once 'db/connection.php';

if ($_SESSION['role'] !== 'student') die(json_encode(['success'=>false]));

$counselor_id = $_POST['counselor_id'];
$student_id   = $_SESSION['user_id'];
$datetime     = $_POST['datetime'];
$reason       = isset($_POST['reason']) ? $_POST['reason'] : '';

// Prevent double-booking (optional but recommended)
$check = $conn->prepare("SELECT 1 FROM appointments WHERE counselor_id = ? AND appointment_desc = ?");
$check->execute([$counselor_id, $datetime]);
if ($check->fetch()) {
    echo json_encode(['success' => false]);
    exit;
}


// Insert with status 'pending', correct columns: appointment_date for datetime, appointment_desc for reason
$stmt = $conn->prepare("INSERT INTO appointments (counselor_id, student_id, appointment_date, appointment_desc, status) VALUES (?, ?, ?, ?, 'pending')");
$success = $stmt->execute([$counselor_id, $student_id, $datetime, $reason]);

// Add notification to counselor session
if ($success) {
    // Find counselor session file (simulate, since sessions are per user)
    // For demo: store notifications in a file per counselor
    $notif_file = __DIR__ . "/../sessions/counselor_{$counselor_id}_notifs.json";
    $notifs = file_exists($notif_file) ? json_decode(file_get_contents($notif_file), true) : [];
    $student_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'A student';
    $notifs[] = "$student_name booked an appointment on $datetime.";
    file_put_contents($notif_file, json_encode($notifs));
}

echo json_encode(['success' => $success]);
?>