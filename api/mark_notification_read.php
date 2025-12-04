<?php
// api/mark_notifications_read.php
session_start();
require_once '../db/connection.php';

$counselor_id = $_SESSION['user_id'] ?? 0;
$notif_file = __DIR__ . "/../sessions/counselor_{$counselor_id}_notifs.json";

if (file_exists($notif_file)) {
    $notifs = json_decode(file_get_contents($notif_file), true) ?: [];
    foreach ($notifs as &$n) {
        if (is_array($n)) $n['read'] = true;
    }
    file_put_contents($notif_file, json_encode($notifs));
}

$_SESSION['counselor_notifications'] = $notifs;
echo json_encode(['success' => true]);
?>