<?php
require_once '../db/connection.php';
header('Content-Type: application/json');

try {
    $stmt = $conn->query("SELECT counselor_id, fname, lname, mi, title, department, bio, phone, email FROM counselor ORDER BY lname");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
?>