<?php
require_once 'db/connection.php';
$stmt = $conn->query("SELECT counselor_id, fname, lname, title, photo, bio FROM counselor ORDER BY lname");
echo json_encode($stmt->fetchAll());
?>