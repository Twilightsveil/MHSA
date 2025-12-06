<?php
session_start();
require_once '../db/connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$sender_id = $_SESSION['user_id'];
$sender_role = $_SESSION['role']; // 'student' or 'counselor'
$receiver_id = $data['to'] ?? '';
$message = trim($data['message'] ?? '');

if (!$receiver_id || !$message || !in_array($sender_role, ['student', 'counselor'])) {
    echo json_encode(['success' => false]);
    exit;
}

// Determine receiver role
$receiver_role = $sender_role === 'student' ? 'counselor' : 'student';

$stmt = $conn->prepare("
    INSERT INTO messages 
    (sender_id, sender_role, receiver_id, receiver_role, message) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$sender_id, $sender_role, $receiver_id, $receiver_role, $message]);

echo json_encode(['success' => true]);
?>