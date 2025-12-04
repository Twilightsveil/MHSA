<?php
session_start();
$counselor_id = $_SESSION['user_id'] ?? 0;
$notif_file = __DIR__ . "/../sessions/counselor_{$counselor_id}_notifs.json";
if (file_exists($notif_file)) unlink($notif_file);
echo json_encode(['success' => true]);
?>