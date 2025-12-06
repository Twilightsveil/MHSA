<?php
// api/approve_appointment.php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['appointment_id'] ?? $_POST['appointment_id'] ?? 0;

if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'No appointment ID']);
    exit;
}

// Get appointment + student info
$stmt = $conn->prepare("
    SELECT a.*, s.student_id, CONCAT(s.fname, ' ', COALESCE(CONCAT(s.mi,'.'), ''), ' ', s.lname) as student_name
    FROM appointments a
    JOIN student s ON a.student_id = s.student_id
    WHERE a.appointment_id = ?
");
$stmt->execute([$appointment_id]);
$appt = $stmt->fetch();

if (!$appt) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit;
}

// Update status to approved
$update = $conn->prepare("UPDATE appointments SET status = 'approved' WHERE appointment_id = ?");
$success = $update->execute([$appointment_id]);

// Debugging: Log the success of the database update
error_log("Appointment ID: $appointment_id - Update Success: " . ($success ? 'true' : 'false'));

if ($success) {
    // === SEND NOTIFICATION TO STUDENT ===
    $student_id = $appt['student_id'];
    $student_name = $appt['student_name'];
    $counselor_name = $_SESSION['fullname'];
    $datetime = date('M j \a\t g:i A', strtotime($appt['appointment_date']));

    $notif_file = __DIR__ . "/../sessions/student_{$student_id}_notifs.json";
    $notifs = file_exists($notif_file) ? json_decode(file_get_contents($notif_file), true) : [];

    $notifs[] = [
        'type' => 'appointment_approved',
        'message' => "Your appointment with {$counselor_name} has been approved!",
        'details' => "Date: {$datetime}",
        'time' => date('M j, g:i A'),
        'read' => false
    ];

    file_put_contents($notif_file, json_encode($notifs));

    // Optional: Remove from counselor's pending list (clean up)
    $counselor_notif_file = __DIR__ . "/../sessions/counselor_{$_SESSION['user_id']}_notifs.json";
    if (file_exists($counselor_notif_file)) {
        $cnotifs = json_decode(file_get_contents($counselor_notif_file), true) ?: [];
        $cnotifs = array_filter($cnotifs, fn($n) => ($n['appointment_id'] ?? 0) != $appointment_id);
        file_put_contents($counselor_notif_file, json_encode(array_values($cnotifs)));
    }

    // Refresh the calendar by sending a signal to the frontend
    echo json_encode(['success' => true, 'message' => 'Appointment approved successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to approve appointment.']);
}
?>