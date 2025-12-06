<?php
session_start();
require_once '../db/connection.php';

$with = $_GET['with'] ?? '';
$role = $_GET['role'] ?? '';
$since = (int)($_GET['since'] ?? 0);

if (!$with || !$role) die(json_encode([]));

$user_id = $_SESSION['user_id'];
$partner_role = $role === 'student' ? 'counselor' : 'student';

$stmt = $conn->prepare("
    SELECT id, message, sender_id, sender_role, sent_at, is_read
    FROM messages 
    WHERE (
        (sender_id = ? AND receiver_id = ?) OR 
        (sender_id = ? AND receiver_id = ?)
    )
    " . ($since > 0 ? " AND id > ?" : "") . "
    ORDER BY sent_at ASC
");
$params = $since > 0 
    ? [$user_id, $with, $with, $user_id, $since]
    : [$user_id, $with, $with, $user_id];

$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>