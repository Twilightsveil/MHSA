<?php
require_once '../db/connection.php';
session_start();
if ($_SESSION['role'] !== 'counselor') exit('Unauthorized');

$data = $_POST;
$id = $data['student_id'] ?? null;

unset($data['student_id']);

if ($id) {
    // Update
    $keys = array_keys($data);
    $set = implode(', ', array_map(fn($k) => "$k = ?", $keys));
    $stmt = $conn->prepare("UPDATE student SET $set WHERE student_id = ?");
    $params = array_values($data);
    $params[] = $id;
    $stmt->execute($params);
} else {
    // Insert
    $cols = implode(', ', array_keys($data));
    $placeholders = str_repeat('?,', count($data) - 1) . '?';
    $stmt = $conn->prepare("INSERT INTO student ($cols) VALUES ($placeholders)");
    $stmt->execute(array_values($data));
}

header("Location: ../students.php");