<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$count = $stmt->fetchColumn();

echo json_encode(['count' => (int)$count]);