<?php
session_start();
require_once '../db/connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$appointment_id = $_POST['appointment_id'] ?? 0;
if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

// Approve the appointment (set a status column, assumed to exist)
$stmt = $conn->prepare("UPDATE appointments SET status = 'approved' WHERE appointment_id = ?");
$success = $stmt->execute([$appointment_id]);

if ($success) {
    // Get student ID for this appointment
    $stmt2 = $conn->prepare("SELECT student_id FROM appointments WHERE appointment_id = ?");
    $stmt2->execute([$appointment_id]);
    $student_id = $stmt2->fetchColumn();
    if ($student_id) {
        // Store notification in file for student
        $notif_file = __DIR__ . "/../sessions/student_{$student_id}_notifs.json";
        $notifs = file_exists($notif_file) ? json_decode(file_get_contents($notif_file), true) : [];
        $counselor_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Your counselor';
        $notifs[] = "$counselor_name approved your appointment.";
        file_put_contents($notif_file, json_encode($notifs));
    }
}

echo json_encode(['success' => $success]);
?>
