<?php
require_once '../db/connection.php';
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM student WHERE student_id = ?");
$stmt->execute([$id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));