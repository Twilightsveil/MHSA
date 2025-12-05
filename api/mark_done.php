<?php
session_start();
require_once '../db/connection.php';

if ($_SESSION['role'] !== 'counselor') {
    echo json_encode(['success' => false]);
    exit;
}

$id = $_POST['appointment_id'] ?? 0;

$stmt = $conn->prepare("UPDATE appointments SET status = 'done' WHERE appointment_id = ? AND counselor_id = ?");
$success = $stmt->execute([$id, $_SESSION['user_id']]);

if ($success) {
    // Get student ID and name
    $stmt = $conn->prepare("SELECT student_id FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$id]);
    $student_id = $stmt->fetchColumn();

    if ($student_id) {
        $notif_file = __DIR__ . "/../sessions/student_{$student_id}_notifs.json";
        $notifs = file_exists($notif_file) ? json_decode(file_get_contents($notif_file), true) : [];

        $notifs[] = [
            'type' => 'session_complete',
            'message' => "Your session with {$_SESSION['fullname']} is complete!",
            'details' => "Please share your feedback",
            'appointment_id' => $id,
            'time' => date('M j, g:i A'),
            'read' => false
        ];

        file_put_contents($notif_file, json_encode($notifs));
    }
}

echo json_encode(['success' => $success]);
?>