let calendar;
let currentAppointmentId = 0;

document.addEventListener('DOMContentLoaded', function () {
    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        height: 'auto',
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        editable: true,
        // Events are provided by PHP in the HTML, do not hardcode here
        events: [],
        eventClick: function (info) {
            openEditModal(info.event);
        },
        eventDrop: function (info) {
            const id = info.event.id;
            const newTime = info.event.start.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const timeEl = document.getElementById('time-' + id);
            if (timeEl) timeEl.textContent = newTime;
            showToast('Rescheduled via drag-drop!', 'info');
        }
    });
    calendar.render();
});

function openEditModal(event) {
    currentAppointmentId = event.id;
    const [name, concern] = event.title.split(' - ');

    document.getElementById('modalTitle').textContent = 'Edit Appointment';
    document.getElementById('studentNameInput').value = name || '';
    document.getElementById('concernInput').value = concern || '';
    document.getElementById('datetimeInput').value = event.start.toISOString().slice(0, 16);

    document.getElementById('appointmentAction').onclick = () => updateAppointment(event);
    document.getElementById('extraActions').style.display = 'flex';
    document.getElementById('appointmentModal').style.display = 'flex';
}

function openFollowUpFromList(id, name) {
    currentAppointmentId = id;
    const el = document.getElementById('followupStudentName');
    if (el) el.textContent = name;
    const event = calendar.getEventById(id);
    if (event) {
        document.getElementById('followupDateTime').value = event.start.toISOString().slice(0, 16);
    }
    document.getElementById('followupModal').style.display = 'flex';
}

function confirmFollowUpFromList() {
    const dt = document.getElementById('followupDateTime').value;
    const notes = document.getElementById('followupNotes') ? document.getElementById('followupNotes').value.trim() : '';
    if (!dt) return showToast('Please select a follow-up date and time', 'error');

    const event = calendar.getEventById(currentAppointmentId);
    if (event) {
        const followId = currentAppointmentId + '-followup-' + Date.now();
        calendar.addEvent({
            id: followId,
            title: `${event.title} (Follow-up)`,
            start: dt,
            color: '#3498db'
        });

        showToast('Follow-up scheduled!', 'success');
    } else {
        showToast('Original appointment not found', 'error');
    }

    closeModal('followupModal');
}

function updateAppointment(event) {
    const name = document.getElementById('studentNameInput').value.trim();
    const concern = document.getElementById('concernInput').value.trim();
    if (!name || !concern) return showToast('Please fill all fields', 'error');

    event.setProp('title', `${name} - ${concern}`);
    showToast('Appointment updated!', 'success');
    closeModal('appointmentModal');
}

function deleteAppointment(event) {
    if (confirm('Delete this appointment permanently?')) {
        event.remove();
        showToast('Appointment deleted', 'warning');
        closeModal('appointmentModal');
    }
}

function markStatus(id, status) {
    const el = document.getElementById('status-' + id);
    if (el) {
        el.textContent = status === 'done' ? 'Done' : 'Monitoring';
        el.style.color = status === 'done' ? '#27ae60' : '#e67e22';
        el.style.fontWeight = 'bold';
    }
    showToast(`Marked as ${status === 'done' ? 'Done' : 'Monitoring'}`, 'success');
}

function closeModal(modalId = null) {
    const modals = modalId ? [modalId] : ['appointmentModal', 'followupModal', 'chatModal'];
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'none';
    });
    const extra = document.getElementById('extraActions');
    if (extra) extra.style.display = 'none';
}

function openChatModal(name, id) {
    document.getElementById('chatStudentName').textContent = name;
    document.getElementById('chatStudentId').textContent = id;
    document.getElementById('chatMessages').innerHTML = `
        <div class="msg student">Hi po, medyo nahihirapan po ako ngayon...</div>
        <div class="msg counselor">Salamat sa pag-share. Ano po ang nangyari?</div>
    `;
    document.getElementById('chatModal').style.display = 'flex';
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg) return;
    document.getElementById('chatMessages').innerHTML += `<div class="msg counselor">${msg}</div>`;
    document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
    input.value = '';
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = `
        position:fixed;top:20px;right:20px;z-index:9999;padding:16px 30px;
        background:${type==='error'?'#e74c3c':type==='warning'?'#f39c12':type==='info'?'#3498db':'#27ae60'};
        color:white;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.3);
        font-size:15px;font-weight:600;animation:slideIn 0.4s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}

window.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

function toggleProfileDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('profileDropdown');
    if (!dd) return;
    const isHidden = dd.getAttribute('aria-hidden') !== 'false';
    dd.setAttribute('aria-hidden', isHidden ? 'false' : 'true');
}

function closeProfileDropdown() {
    const dd = document.getElementById('profileDropdown');
    if (!dd) return;
    dd.setAttribute('aria-hidden', 'true');
}

document.addEventListener('click', function (e) {
    const dd = document.getElementById('profileDropdown');
    const btn = document.getElementById('profileBtn');
    if (!dd) return;
    if (dd.getAttribute('aria-hidden') === 'false') {
        if (!dd.contains(e.target) && btn && !btn.contains(e.target)) {
            dd.setAttribute('aria-hidden', 'true');
        }
    }
});