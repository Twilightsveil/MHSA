
let selectedDateTime = null;

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '17:00:00',
        height: 'auto',
        selectable: true,
        selectOverlap: false,
        select: function (info) {
            selectedDateTime = info.startStr;
            document.getElementById('bookingDateTime').value = info.startStr.slice(0, 16);
            document.getElementById('bookingModal').style.display = 'flex';
        },
        events: [
            { title: 'You - Academic Stress', start: '2025-11-20T10:00:00', color: '#27ae60' },
            { title: 'You - Family Concerns', start: '2025-11-25T14:30:00', color: '#f39c12' }
        ]
    });
    calendar.render();
});

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

function submitBooking() {
    const counselor = document.getElementById('counselorSelect').value;
    const duration = document.getElementById('durationSelect').value;
    const reason = document.getElementById('visitReason').value.trim();

    if (!reason) {
        alert('Please enter your reason for visit.');
        return;
    }

    alert(`Appointment Request Sent!\n\nCounselor: ${counselor}\nDate & Time: ${document.getElementById('bookingDateTime').value.replace('T', ' ')}\nDuration: ${duration}\nReason: ${reason}\n\nYou will receive a confirmation soon.`);

    // Visual feedback: add to calendar
    const calendarApi = document.querySelector('#calendar')._calendarApi;
    calendarApi.addEvent({
        title: `You - ${reason.substring(0, 15)}${reason.length > 15 ? '...' : ''}`,
        start: selectedDateTime,
        color: '#f39c12'
    });

    closeBookingModal();
    document.getElementById('visitReason').value = '';
}

let chatInterval = null;

function openCounselorChatModal() {
    document.getElementById('counselorChatModal').style.display = 'flex';
    loadCounselorMessages();
    updateUnreadBadge();

    // Poll every 5 seconds
    chatInterval = setInterval(() => {
        loadCounselorMessages();
        updateUnreadBadge();
    }, 5000);
}

function closeCounselorChat() {
    document.getElementById('counselorChatModal').style.display = 'none';
    if (chatInterval) clearInterval(chatInterval);
}

function loadCounselorMessages() {
    fetch('api/chat_counselor.php?action=get')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('counselorMessages');
            let html = '';
            data.messages.forEach(msg => {
                const isStudent = msg.sender_type === 'student' && msg.sender_id == <?= $student_id ?>;
                const align = isStudent ? 'flex-end' : 'flex-start';
                const bg = isStudent ? 'var(--primary)' : '#e9ecef';
                const color = isStudent ? 'white' : 'black';

                html += `
                <div style="margin:8px 0;display:flex;justify-content:${align};">
                    <div style="max-width:70%;background:${bg};color:${color};padding:10px 15px;border-radius:18px;">
                        <small style="opacity:0.8;">${msg.sender_type === 'student' ? 'You' : 'Counselor'}</small><br>
                        ${msg.message.replace(/\n/g, '<br>')}
                        <div style="font-size:10px;margin-top:5px;opacity:0.7;">
                            ${new Date(msg.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        </div>
                    </div>
                </div>`;
            });
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        });
}

function sendCounselorMessage() {
    const input = document.getElementById('counselorInput');
    const message = input.value.trim();
    if (!message) return;

    fetch('api/chat_counselor.php?action=send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
    }).then(() => {
        input.value = '';
        loadCounselorMessages();
    });
}

// Update unread badge
function updateUnreadBadge() {
    const badge = document.getElementById('unreadBadge');
    fetch('api/chat_counselor.php?action=get')
        .then(r => r.json())
        .then(data => {
            const unread = data.messages.filter(m => 
                m.sender_type === 'counselor'
            ).length;
            badge.textContent = unread > 9 ? '9+' : unread;
            badge.style.display = unread > 0 ? 'flex' : 'none';
        });
}

// Enter key to send
document.getElementById('counselorInput')?.addEventListener('keypress', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendCounselorMessage();
    }
});

const aiResponses = [
    "I'm really sorry you're feeling this way. You're not alone — I'm here with you.",
    "That sounds really heavy. Can you tell me more about what's going on?",
    "It's okay to feel overwhelmed. Would it help to talk about what happened today?",
    "You're doing the right thing by reaching out. I'm listening.",
    "Take a slow breath with me: in for 4... hold... out for 4. You're safe here.",
    "I'm really glad you messaged. What’s been the hardest part for you?",
    "You matter. Your feelings are valid. I'm here as long as you need.",
    "Would you like some grounding techniques or just someone to listen right now?"
];

function sendEmergencyMessage() {
    const input = document.getElementById('emergencyInput');
    const msg = input.value.trim();
    if (!msg) return;

    const container = document.getElementById('emergencyMessages');

    // Add user message
    container.innerHTML += `
        <div style="text-align:right;margin:10px 0;">
            <div style="display:inline-block;background:#e74c3c;color:white;padding:10px 16px;border-radius:18px;max-width:80%;">${msg}</div>
        </div>`;

    input.value = '';

    // Simulate AI typing
    container.innerHTML += `<div style="text-align:left;color:#888;font-style:italic;">AI is typing...</div>`;
    container.scrollTop = container.scrollHeight;

    setTimeout(() => {
        container.lastElementChild.remove();
        const reply = aiResponses[Math.floor(Math.random() * aiResponses.length)];
        container.innerHTML += `
            <div style="text-align:left;margin:10px 0;">
                <div style="display:inline-block;background:#f1f1f1;padding:10px 16px;border-radius:18px;max-width:80%;">
                    <strong>Support AI:</strong><br>${reply}
                </div>
            </div>`;
        container.scrollTop = container.scrollHeight;
    }, 1000 + Math.random() * 1500);
}

// Allow Enter to send
document.getElementById('emergencyInput')?.addEventListener('keypress', e => {
    if (e.key === 'Enter') {
        sendEmergencyMessage();
    }
});