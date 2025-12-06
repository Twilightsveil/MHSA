<?php
session_start();
require_once '../db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    echo json_encode([]);
    exit;
}

$counselor_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT DISTINCT
        s.student_id,
        CONCAT(TRIM(s.fname), ' ', COALESCE(s.mi,''), ' ', TRIM(s.lname)) AS name,
        COALESCE(unread.count, 0) AS unread
    FROM appointments a
    JOIN student s ON a.student_id = s.student_id
    LEFT JOIN (
        SELECT sender_id, COUNT(*) AS count 
        FROM messages 
        WHERE receiver_id = ? AND receiver_role = 'counselor' AND is_read = 0
        GROUP BY sender_id
    ) unread ON unread.sender_id = s.student_id
    WHERE a.counselor_id = ? AND a.status = 'approved'
    ORDER BY unread DESC, name ASC
");
$stmt->execute([$counselor_id, $counselor_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($students);
?>