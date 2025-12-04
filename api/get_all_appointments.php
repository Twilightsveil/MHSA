<?php
session_start();
require_once '../db/connection.php';
if ($_SESSION['role'] !== 'counselor') exit(json_encode([]));

$counselor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT a.*, 
           CONCAT(TRIM(CONCAT(s.fname, ' ', IFNULL(CONCAT(s.mi,'.'), ''), ' ', s.lname))) as student_name,
           s.student_id
    FROM appointments a 
    JOIN student s ON a.student_id = s.student_id 
    WHERE a.counselor_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$counselor_id]);
echo json_encode(['appointments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);