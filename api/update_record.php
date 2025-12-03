<?php
session_start();
require_once '../db/connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? null;

if ($action === 'update') {
    if (!$id || !in_array($type, ['counselor','student'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $allowed = $type === 'counselor'
        ? ['fname','lname','mi','title','department','email','phone','bio']
        : ['fname','lname','mi','course','year','section'];

    $sets = []; $params = ['id' => $id];
    foreach ($_POST as $k => $v) {
        if (in_array($k, $allowed)) {
            $sets[] = "$k = :$k";
            $params[$k] = trim($v);
        }
    }

    if (empty($sets)) {
        echo json_encode(['success' => false, 'message' => 'No changes']);
        exit;
    }

    $table = $type === 'counselor' ? 'counselor' : 'student';
    $pk = $type === 'counselor' ? 'counselor_id' : 'student_id';

    $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `$pk` = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true]);

} elseif ($action === 'add') {
    // Add new user logic here (you can expand this)
    echo json_encode(['success' => true]); // placeholder
}
?>