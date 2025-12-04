<?php
// api/create_notification.php
session_start();
require_once '../db/connection.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$counselor_id = $input['counselor_id'] ?? 0;
$appointment_id = $input['appointment_id'] ?? 0;
$student_name = $input['student_name'] ?? 'A student';

if (!$counselor_id || !$appointment_id) {
    echo json_encode(['success' => false]);
    exit;
}

// Save notification for counselor
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
echo json_encode(['success' => true]);
?>