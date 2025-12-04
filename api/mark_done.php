<?php
session_start();
require_once '../db/connection.php';
if ($_SESSION['role'] !== 'counselor') exit(json_encode(['success'=>false]));

$id = $_POST['appointment_id'];
$stmt = $conn->prepare("UPDATE appointments SET status = 'done' WHERE appointment_id = ? AND counselor_id = ?");
$success = $stmt->execute([$id, $_SESSION['user_id']]);

echo json_encode(['success' => $success]);