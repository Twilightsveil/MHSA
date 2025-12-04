<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$sender_id = $_SESSION['user_id'];
$receiver_id = (int)$data['receiver_id'];
$message = trim($data['message']);

if (empty($message)) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$sender_id, $receiver_id, $message]);

echo json_encode(['success' => true]);