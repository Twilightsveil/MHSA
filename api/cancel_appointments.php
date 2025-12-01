<?php
session_start();
require_once '../db/connection.php';
header('Content-Type: application/json');

if ($_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['appointment_id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ? AND student_Id = ?");
$success = $stmt->execute([$id, $_SESSION['user_id']]);

echo json_encode(['success' => $success]);
?>