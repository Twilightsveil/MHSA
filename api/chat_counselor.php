<?php
session_start();
require_once '../db/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'send') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO chat_messages (sender_type, sender_id, message) VALUES ('student', ?, ?)");
    $stmt->execute([$student_id, $message]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get') {
    // Get last 50 messages (or from a specific time)
    $stmt = $conn->prepare("
        SELECT sender_type, sender_id, message, sent_at 
        FROM chat_messages 
        ORDER BY sent_at DESC LIMIT 50
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reverse to show oldest first
    $messages = array_reverse($messages);

    echo json_encode(['messages' => $messages]);
    exit;
}