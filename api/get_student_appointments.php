<?php
session_start();
require_once '../db/connection.php';
if (!isset($_SESSION['user_id'])) exit(json_encode(['appointments' => []]));

$student_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT a.*, c.fname, c.lname, c.mi,
           CONCAT(c.fname, ' ', COALESCE(CONCAT(c.mi, '.'), ''), ' ', c.lname) as counselor_name,
           f.rating IS NOT NULL as feedback_given
    FROM appointments a
    JOIN counselor c ON a.counselor_ID = c.counselor_id
    LEFT JOIN feedback f ON a.appointment_id = f.appointment_id
    WHERE a.student_Id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$student_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['appointments' => $appointments]);