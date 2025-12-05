<?php
session_start();
require_once '../db/connection.php';

if ($_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$appt_id = (int)$_POST['appointment_id'];
$rating  = (int)$_POST['rating'];
$comment = trim($_POST['comment'] ?? '');

if ($appt_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Verify this appointment belongs to the student and is marked "done"
$stmt = $conn->prepare("
    SELECT a.counselor_id, f.feedback_id 
    FROM appointments a 
    LEFT JOIN feedback f ON a.appointment_id = f.appointment_id 
    WHERE a.appointment_id = ? AND a.student_id = ? AND a.status = 'done'
");
$stmt->execute([$appt_id, $_SESSION['user_id']]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Invalid or already submitted']);
    exit;
}

if ($row['feedback_id']) {
    echo json_encode(['success' => false, 'message' => 'Feedback already submitted']);
    exit;
}

// Insert feedback
$insert = $conn->prepare("
    INSERT INTO feedback (appointment_id, student_id, counselor_id, rating, comment) 
    VALUES (?, ?, ?, ?, ?)
");
$success = $insert->execute([
    $appt_id,
    $_SESSION['user_id'],
    $row['counselor_id'],
    $rating,
    $comment
]);

echo json_encode(['success' => $success]);
?>