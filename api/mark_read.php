<?php
session_start();
require_once '../db/connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$from = $data['from'] ?? '';
$my_id = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

if (!$from) die();

$from_role = $my_role === 'student' ? 'counselor' : 'student';

$stmt = $conn->prepare("
    UPDATE messages 
    SET is_read = 1 
    WHERE receiver_id = ? 
      AND receiver_role = ? 
      AND sender_id = ? 
      AND sender_role = ?
      AND is_read = 0
");
$stmt->execute([$my_id, $my_role, $from, $from_role]);

echo json_encode(['success' => true]);
?>