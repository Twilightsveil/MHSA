<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['with'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$chat_with_id = (int)$_GET['with'];
$role = $_SESSION['role']; // 'student' or 'counselor'

// Get chat partner info
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT counselor_id as id, CONCAT(fname, ' ', COALESCE(mi,''), ' ', lname) as name FROM counselor WHERE counselor_id = ?");
} else {
    $stmt = $conn->prepare("SELECT student_id as id, CONCAT(fname, ' ', COALESCE(mi,''), ' ', lname) as name FROM student WHERE student_id = ?");
}
$stmt->execute([$chat_with_id]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partner) {
    die("User not found.");
}

$partner_name = $partner['name'];
$partner_id = $partner['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?= htmlspecialchars($partner_name) ?></title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f0f2f5; height: 100vh; display: flex; flex-direction: column; }
        .chat-header {
            background: #8e44ad;
            color: white;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .message {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 10px 14px;
            border-radius: 18px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        .sent {
            background: #8e44ad;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        .received {
            background: white;
            color: #333;
            margin-right: auto;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 4px;
        }
        .chat-input {
            padding: 15px;
            background: white;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
        }
        #messageInput {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 15px;
            outline: none;
        }
        #sendBtn {
            width: 48px;
            height: 48px;
            background: #8e44ad;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="chat-header">
    <i class="fas fa-arrow-left" onclick="history.back()" style="cursor:pointer;margin-right:15px;"></i>
    <?= htmlspecialchars($partner_name) ?>
</div>

<div class="chat-messages" id="messagesContainer">
    <!-- Messages loaded here -->
</div>

<div class="chat-input">
    <input type="text" id="messageInput" placeholder="Type a message..." autocomplete="off">
    <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
</div>

<script>
const currentUserId = <?= $current_user_id ?>;
const chatWithId = <?= $partner_id ?>;
const messagesContainer = document.getElementById('messagesContainer');
let lastMessageId = 0;

function loadMessages() {
    fetch(`api/get_messages.php?with=${chatWithId}&last=${lastMessageId}`)
        .then(r => r.json())
        .then(data => {
            data.messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'message ' + (msg.sender_id == currentUserId ? 'sent' : 'received');
                div.innerHTML = `
                    ${msg.message}
                    <div class="message-time">${new Date(msg.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                `;
                messagesContainer.appendChild(div);
                lastMessageId = Math.max(lastMessageId, msg.id);
            });
            if (data.messages.length > 0) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            updateUnreadBadge();
        });
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const msg = input.value.trim();
    if (!msg) return;

    fetch('api/send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            receiver_id: chatWithId,
            message: msg
        })
    }).then(() => {
        input.value = '';
        loadMessages();
    });
}

function updateUnreadBadge() {
    // Optional: update floating badge on other tabs
    if (window.opener || parent !== window) {
        // If opened in modal, notify parent
        try { parent.postMessage({type: 'updateChatBadge'}, '*'); } catch(e) {}
    }
}

// Load messages every 2 seconds
setInterval(loadMessages, 2000);
loadMessages();

// Send on Enter or button
document.getElementById('messageInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') sendMessage();
});
document.getElementById('sendBtn').addEventListener('click', sendMessage);
</script>
</body>
</html>