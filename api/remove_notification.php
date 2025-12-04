<?php
// api/remove_notification.php
session_start();
require_once '../db/connection.php';

$counselor_id = $_SESSION['user_id'] ?? 0;
$index = $_POST['index'] ?? -1;

$notif_file = __DIR__ . "/../sessions/counselor_{$counselor_id}_notifs.json";
if (file_exists($notif_file)) {
    $notifs = json_decode(file_get_contents($notif_file), true) ?: [];
    if (isset($notifs[$index])) {
        unset($notifs[$index]);
        $notifs = array_values($notifs); // reindex
        file_put_contents($notif_file, json_encode($notifs));
    }
}
echo json_encode(['success' => true]);
?>