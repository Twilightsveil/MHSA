<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['counselor', 'student'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$my_role = $_SESSION['role'];
$with_id = $_GET['with'] ?? '';

if (!$with_id) {
    die("<h2 style='text-align:center;margin-top:100px;color:#8e44ad;'>No chat partner selected</h2>");
}

// Fetch partner name
$partner_name = "User";
$initials = "??";
$partner_id = null;

if ($my_role === 'counselor') {
    $stmt = $conn->prepare("SELECT student_id AS id, CONCAT(TRIM(fname), ' ', COALESCE(mi,''), ' ', TRIM(lname)) AS name FROM student WHERE student_id = ?");
} else {
    $stmt = $conn->prepare("SELECT counselor_id AS id, CONCAT(TRIM(fname), ' ', COALESCE(mi,''), ' ', TRIM(lname)) AS name FROM counselor WHERE counselor_id = ?");
}

$stmt->execute([$with_id]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partner) {
    die("<h2 style='text-align:center;margin-top:100px;color:#e74c3c;'>Chat partner not found</h2>");
}

$partner_id = $partner['id'];
$partner_name = trim(preg_replace('/\s+/', ' ', $partner['name']));
$initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $partner_name), 0, 2));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?= htmlspecialchars($partner_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #8e44ad;
            --sent-bg: #8e44ad;
            --sent-text: white;
            --received-bg: white;
            --received-text: #000;
            --bg: #e5e5ea;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#e5e5ea; height:100dvh; display:flex; flex-direction:column; overflow:hidden; }

        .chat-header {
            background: var(--primary); color:white; padding:12px 16px;
            display:flex; align-items:center; gap:12px; position:sticky; top:0; z-index:10;
            box-shadow:0 1px 5px rgba(0,0,0,0.2);
        }
        .back-btn { background:rgba(255,255,255,0.2); width:36px; height:36px; border-radius:50%;
            display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:18px; }
        .back-btn:hover { background:rgba(255,255,255,0.3); }

        .partner-avatar { width:40px; height:40px; background:white; color:var(--primary);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-weight:bold; font-size:16px; }

        .partner-info h3 { font-size:17px; font-weight:600; }
        .partner-info small { font-size:13px; opacity:0.9; }

        .messages-container {
            flex:1; overflow-y:auto; padding:20px 14px; display:flex; flex-direction:column; gap:8px;
            background:var(--bg);
        }

        .message-wrapper {
            max-width:80%; display:flex; flex-direction:column;
        }
        .sent { align-self:flex-end; }
        .received { align-self:flex-start; }

        .message {
            padding:10px 16px; border-radius:18px; line-height:1.4; font-size:15.5px;
            box-shadow:0 1px 2px rgba(0,0,0,0.15);
        }
        .sent .message {
            background:var(--sent-bg); color:var(--sent-text);
            border-bottom-right-radius:4px;
        }
        .received .message {
            background:var(--received-bg); color:var(--received-text);
            border-bottom-left-radius:4px;
        }

        .message-meta {
            font-size:11.5px; opacity:0.8; margin-top:4px; text-align:right;
        }
        .sent .message-meta { color:#e8c3ff; }
        .received .message-meta { color:#666; text-align:left; }

        .chat-input-area {
            padding:12px 16px; background:white; display:flex; align-items:center; gap:12px;
            box-shadow:0 -2px 10px rgba(0,0,0,0.1);
        }
        .message-input {
            flex:1; padding:14px 18px; border:1px solid #ddd; border-radius:25px;
            font-size:16px; outline:none; background:#f9f9f9;
        }
        .message-input:focus { border-color:var(--primary); background:white; }

        .send-btn {
            width:46px; height:46px; background:var(--primary); color:white; border:none;
            border-radius:50%; font-size:18px; cursor:pointer; display:flex;
            align-items:center; justify-content:center;
        }
        .send-btn:hover { background:#6f42c1; }
    </style>
</head>
<body>

<div class="chat-header">
    <div class="back-btn" onclick="history.back()">Back</div>
    <div class="partner-avatar"><?= $initials ?></div>
    <div class="partner-info">
        <h3><?= htmlspecialchars($partner_name) ?></h3>
        <small>Active now</small>
    </div>
</div>

<div class="messages-container" id="messages">
    <div style="text-align:center;color:#888;padding:40px;">Loading messages...</div>
</div>

<div class="chat-input-area">
    <input type="text" class="message-input" id="messageInput" placeholder="Type a message..." autocomplete="off">
    <button class="send-btn" onclick="sendMessage()">Send</button>
</div>

<script>
const myId = '<?= $user_id ?>';
const myRole = '<?= $my_role ?>';
const partnerId = '<?= $partner_id ?>';
let lastMessageId = 0;

function formatTime(date) {
    const d = new Date(date);
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

async function loadMessages() {
    try {
        const res = await fetch(`api/get_messages.php?with=${partnerId}&role=${myRole}&since=${lastMessageId}&t=${Date.now()}`);
        const messages = await res.json();
        if (messages.length === 0) return;

        const container = document.getElementById('messages');
        const shouldScroll = container.scrollHeight - container.scrollTop - container.clientHeight < 150;

        const fragment = document.createDocumentFragment();
        messages.forEach(msg => {
            // THIS LINE IS THE ONLY ONE THAT MATTERS
            const isSent = parseInt(msg.sender_id) === parseInt(myId);

            const wrapper = document.createElement('div');
            wrapper.className = `message-wrapper ${isSent ? 'sent' : 'received'}`;
            wrapper.innerHTML = `
                <div class="message">
                    ${msg.message.replace(/\n/g, '<br>')}
                    <div class="message-meta">
                        ${formatTime(msg.sent_at)}
                        ${isSent ? ` · ${msg.is_read == 1 ? 'Seen' : (msg.is_read == 2 ? 'Delivered' : 'Sent')}` : ''}
                    </div>
                </div>
            `;
            fragment.appendChild(wrapper);
            if (msg.id > lastMessageId) lastMessageId = msg.id;
        });

        container.appendChild(fragment);
        if (shouldScroll) container.scrollTop = container.scrollHeight;

        // Mark as read
        if (messages.some(m => parseInt(m.sender_id) === parseInt(partnerId) && m.is_read == 0)) {
            fetch('api/mark_read.php', { 
                method: 'POST', 
                headers: {'Content-Type':'application/json'}, 
                body: JSON.stringify({from: partnerId}) 
            });
        }

        const loading = container.querySelector('div');
        if (loading && loading.textContent.includes('Loading')) loading.remove();

    } catch (e) { console.error(e); }
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const msg = input.value.trim();
    if (!msg) return;

    fetch('api/send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ to: partnerId, message: msg, role: myRole })
    }).then(() => {
        input.value = '';
        loadMessages();
    });
}

document.getElementById('messageInput').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

setInterval(loadMessages, 2500);
loadMessages();
</script>

</body>
</html>