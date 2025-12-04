<?php
session_start();
require_once '../db/connection.php';
if ($_SESSION['role'] !== 'counselor') exit(json_encode(['success'=>false]));

$id = $_POST['appointment_id'];
$new = $_POST['new_datetime'];

$stmt = $conn->prepare("UPDATE appointments SET appointment_date = ? WHERE appointment_id = ? AND counselor_id = ?");
$success = $stmt->execute([$new, $id, $_SESSION['user_id']]);

echo json_encode(['success' => $success, 'message' => $success ? 'OK' : 'Invalid date or not your appointment']);