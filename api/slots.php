<?php
require_once '../db/connection.php';
header('Content-Type: application/json');

$counselor_id = $_GET['counselor_id'] ?? 0;
$date = $_GET['date'] ?? '';

if (!$counselor_id || !$date) {
    echo json_encode([]);
    exit;
}

// Generate time slots from 8:00 AM to 5:00 PM (30-minute intervals)
$slots = [];
$start = strtotime("$date 08:00");
$end   = strtotime("$date 17:00");

while ($start < $end) {
    $time = date('h:i A', $start);
    $datetime = date('Y-m-d H:i:s', $start);

    // Check if slot is taken
    $check = $conn->prepare("SELECT 1 FROM appointments WHERE counselor_id = ? AND appointment_date = ?");
    $check->execute([$counselor_id, $datetime]);
    $taken = $check->fetchColumn();

    $slots[] = [
        'time' => $time,
        'datetime' => $datetime,
        'taken' => (bool)$taken
    ];

    $start = strtotime('+30 minutes', $start);
}

echo json_encode($slots);
?>