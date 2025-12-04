<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['messages' => []]);
    exit;
}

$user_id = $_SESSION['user_id'];
$with = (int)$_GET['with'];
$last = (int)$_GET['last'];

$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
       AND id > ?
    ORDER BY sent_at ASC
");
$stmt->execute([$user_id, $with, $with, $user_id, $last]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark as read
$conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
    ->execute([$with, $user_id]);

echo json_encode(['messages' => $messages]);