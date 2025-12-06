<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['counselor_id' => null]);
    exit;
}

$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.counselor_id 
    FROM appointments a 
    JOIN counselor c ON a.counselor_id = c.counselor_id 
    WHERE a.student_id = ? AND a.status = 'approved'
    ORDER BY a.appointment_date DESC 
    LIMIT 1
");
$stmt->execute([$student_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'counselor_id' => $result['counselor_id'] ?? null
]);
?>