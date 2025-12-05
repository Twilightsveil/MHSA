<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    header("Location: login.php");
    exit();
}

$counselor_id = $_SESSION['user_id'];
$counselor_name = $_SESSION['fullname'];
$firstName = explode(' ', trim($counselor_name))[0];

// Notif handler
$notif_file = __DIR__ . "/sessions/counselor_{$counselor_id}_notifs.json";
if (file_exists($notif_file)) {
    $notifications = json_decode(file_get_contents($notif_file), true) ?: [];
    $_SESSION['counselor_notifications'] = $notifications;
} else {
    $_SESSION['counselor_notifications'] = $_SESSION['counselor_notifications'] ?? [];
}

// All appointments for calendar
$stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.appointment_desc, a.status,
           s.fname, s.lname, s.mi, s.student_id
    FROM appointments a 
    JOIN student s ON a.student_id = s.student_id 
    WHERE a.counselor_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$counselor_id]);
$all_appointments = $stmt->fetchAll();

// Pending badge count
$pending = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE counselor_id = ? AND (status IS NULL OR status = 'pending')");
$pending->execute([$counselor_id]);
$pending_count = $pending->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Dashboard • Guidance Office</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />

    <style>
        #counselorAllAppointmentsPanel {
            position: fixed;
            top: 0;
            right: -520px;
            width: 500px;
            height: 100vh;
            background: white;
            box-shadow: -15px 0 50px rgba(0,0,0,0.3);
            z-index: 1100;
            transition: right 0.45s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', sans-serif;
        }
        #counselorAllAppointmentsPanel.open { right: 0; }

        #allApptHeader {
            background: linear-gradient(135deg, #8e44ad, #9b59b6);
            color: white;
            padding: 22px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        #allApptHeader h3 { margin: 0; font-size: 24px; font-weight: 600; }
        #allApptHeader .close-panel {
            font-size: 36px;
            cursor: pointer;
            opacity: 0.9;
            transition: 0.3s;
        }
        #allApptHeader .close-panel:hover { opacity: 1; transform: rotate(90deg); }

        #allApptBody {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .appt-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-left: 6px solid #8e44ad;
            transition: all 0.3s ease;
        }
        .appt-card:hover { transform: translateY(-6px); box-shadow: 0 15px 35px rgba(0,0,0,0.18); }

        .appt-card.pending   { border-left-color: #e67e22; }
        .appt-card.approved  { border-left-color: #27ae60; }
        .appt-card.done      { border-left-color: #3498db; opacity: 0.95; }

        .appt-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .appt-date { font-weight: bold; color: #2c3e50; font-size: 15px; }
        .appt-status {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: bold;
            color: white;
        }
        .pending .appt-status   { background: #e67e22; }
        .approved .appt-status  { background: #27ae60; }
        .done .appt-status      { background: #3498db; }

        .appt-student-info {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 14px 0;
        }
        .appt-student-initials {
            width: 56px; height: 56px;
            background: #8e44ad;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        .appt-reason {
            background: #f5f0ff;
            padding: 12px;
            border-radius: 12px;
            font-size: 14.5px;
            margin: 12px 0;
        }

        .appt-actions {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
        }
        .appt-actions button {
            padding: 11px;
            border: none;
            border-radius: 14px;
            font-weight: bold;
            font-size: 13.5px;
            cursor: pointer;
            transition: 0.3s;
        }
        .appt-actions .chat      { background: #3498db; color: white; }
        .appt-actions .reschedule{ background: #f39c12; color: white; }
        .appt-actions .done      { background: #27ae60; color: white; }
        .appt-actions button:disabled { background: #95a5a6; cursor: not-allowed; }

        #allApptOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1099;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">Counselor Portal</div>
    <div class="nav-right" style="display:flex;align-items:center;gap:20px;">
        <div style="position:relative;">
            <button id="notifBtn" onclick="toggleNotifDropdown(event)" style="background:none;border:none;cursor:pointer;font-size:22px;">
                <i class="fas fa-bell"></i>
                <?php if (!empty($_SESSION['counselor_notifications'])): ?>
                    <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:white;font-size:11px;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <?= count($_SESSION['counselor_notifications']) ?>
                    </span>
                <?php endif; ?>
            </button>

            <div id="notifDropdown" style="display:none;position:absolute;right:0;top:55px;background:white;box-shadow:0 15px 40px rgba(0,0,0,0.18);border-radius:16px;min-width:400px;max-height:85vh;overflow:hidden;z-index:1001;border:1px solid #eee;">
    <div style="padding:18px 22px;font-weight:bold;border-bottom:1px solid #eee;background:linear-gradient(135deg,#8e44ad,#9b59b6);color:white;border-radius:16px 16px 0 0;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:18px;">Notifications</span>
            <span style="background:rgba(255,255,255,0.25);padding:4px 10px;border-radius:20px;font-size:13px;">
                <?= count($_SESSION['counselor_notifications'] ?? []) ?> new
            </span>
        </div>
    </div>

    <div style="max-height:460px;overflow-y:auto;">
        <?php if (!empty($_SESSION['counselor_notifications'])): ?>
            <?php foreach ($_SESSION['counselor_notifications'] as $index => $n): 
                $msg = is_array($n) ? $n['message'] : $n;
                $appt_id = is_array($n) ? ($n['appointment_id'] ?? 0) : 0;
                $time = is_array($n) ? ($n['time'] ?? 'Just now') : 'Just now';
                $student_name = explode(' requested', $msg)[0] ?? 'A student';
            ?>
                <div class="notif-item" data-id="<?= $appt_id ?>" data-index="<?= $index ?>" 
                     style="padding:18px 22px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:16px;background:#faf8ff;transition:all 0.3s ease;border-left:4px solid #8e44ad;">
                    
                    <div style="width:48px;height:48px;background:#8e44ad;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:18px;flex-shrink:0;">
                        <?= strtoupper(substr($student_name, 0, 2)) ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:600;color:#2c3e50;font-size:15px;line-height:1.4;">
                            <?= htmlspecialchars($student_name) ?> 
                            <span style="font-weight:400;color:#7f8c8d;">requested an appointment</span>
                        </div>
                        <div style="color:#95a5a6;font-size:13px;margin-top:4px;">
                            <i class="fas fa-clock"></i> <?= $time ?>
                        </div>
                    </div>

                    <?php if ($appt_id): ?>
                        <button onclick="approveAndRemove(<?= $appt_id ?>, <?= $index ?>, this)" 
                                style="padding:10px 20px;background:#27ae60;color:white;border:none;border-radius:12px;font-weight:600;font-size:14px;cursor:pointer;transition:0.3s;min-width:90px;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding:80px 30px;text-align:center;color:#bdc3c7;">
                <i class="fas fa-bell-slash fa-3x mb-3"></i>
                <div style="font-size:16px;font-weight:500;">All caught up!</div>
                <div style="font-size:14px;margin-top:8px;">No pending requests</div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['counselor_notifications'])): ?>
    <div style="padding:12px 22px;background:#f8f9fa;text-align:center;border-top:1px solid #eee;">
        <a href="javascript:void(0)" onclick="clearAllNotifications()" style="color:#8e44ad;font-size:14px;font-weight:500;">Clear all notifications</a>
    </div>
    <?php endif; ?>
</div>
        </div>

        <button id="profileBtn" onclick="toggleProfileDropdown(event)" style="background:none;border:none;cursor:pointer;">
            <div style="width:40px;height:40px;background:#8e44ad;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;">
                <i class="fas fa-user-tie"></i>
            </div>
        </button>
        <div id="profileDropdown" class="profile-dropdown" aria-hidden="true" style="position:absolute;right:20px;top:70px;background:white;box-shadow:0 10px 30px rgba(0,0,0,0.2);border-radius:12px;width:240px;z-index:1001;">
            <div style="padding:16px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;background:#8e44ad;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($counselor_name) ?></div>
                    <small style="color:#8e44ad;">Counselor</small>
                </div>
            </div>
            <ul style="margin:0;padding:0;list-style:none;">
                <li><a href="counselor_profile.php" style="display:block;padding:12px 20px;text-decoration:none;color:#333;">My Profile</a></li>
                <li><a href="logout.php" style="display:block;padding:12px 20px;color:#e74c3c;text-decoration:none;">Logout</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="dashboard-container">
    <aside class="sidebar">
        <h2>Main Menu</h2>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="counselor_dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li>
                    <a href="javascript:void(0)" onclick="openAllAppointmentsPanel()">
                        <i class="fas fa-calendar-check"></i> Appointments
                        <?php if ($pending_count > 0): ?>
                            <span class="badge"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </nav>
        <h2 style="margin-top:30px;">Account</h2>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="counselor_profile.php"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
                <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <div class="main-content">
        <div class="page-title">
            <h1>Welcome back, <?= htmlspecialchars($firstName) ?>.</h1>
            <p>Overview of your schedule and student activity.</p>
        </div>

        <div class="dashboard-content">
            <div class="widget">
                <h3 style="padding:15px 20px 10px;margin:0 0 15px;"><i class="fas fa-calendar-alt"></i> Appointment Calendar</h3>
                <div id="calendar" style="background:white;border-radius:12px;padding:15px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="appointmentDetailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1200;align-items:center;justify-content:center;">
    <div class="modal-content" style="background:white;border-radius:18px;max-width:500px;width:90%;padding:30px;position:relative;">
        <span onclick="this.closest('.modal').style.display='none'" style="position:absolute;top:15px;right:20px;font-size:32px;cursor:pointer;color:#aaa;">×</span>
        <h3 style="text-align:center;color:#8e44ad;margin-bottom:20px;">Appointment Details</h3>
        <div style="text-align:center;margin:20px 0;">
            <div style="width:90px;height:90px;background:#8e44ad;color:white;border-radius:50%;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:bold;">
                <span id="detailInitials"></span>
            </div>
            <h4 id="detailStudent" style="margin:10px 0;"></h4>
        </div>
        <div style="background:#f8f9fa;padding:18px;border-radius:14px;">
            <p style="margin:10px 0;"><strong>Date & Time:</strong> <span id="detailDateTime" style="color:#8e44ad;"></span></p>
            <p style="margin:10px 0;"><strong>Reason:</strong> <span id="detailReason"></span></p>
        </div>
        <div style="text-align:center;margin-top:25px;">
            <button onclick="approveAppointment(currentAppointmentId)" style="padding:12px 30px;background:#27ae60;color:white;border:none;border-radius:12px;font-weight:bold;cursor:pointer;">
                Approve Appointment
            </button>
        </div>
    </div>
</div>

<!-- Appointments panel -->
<div id="counselorAllAppointmentsPanel">
    <div id="allApptHeader">
        <h3>All Appointments</h3>
        <div style="display:flex;align-items:center;gap:15px;">
            <input type="text" id="apptSearch" placeholder="Search student..." style="padding:12px 16px;border-radius:14px;border:1px solid #ddd;width:240px;font-size:15px;">
            <span class="close-panel" onclick="closeAllAppointmentsPanel()">×</span>
        </div>
    </div>
    <div id="allApptBody">
        <div style="text-align:center;padding:100px 20px;color:#888;">
            <i class="fas fa-spinner fa-spin fa-4x"></i><br><br>
            <h3>Loading appointments...</h3>
        </div>
    </div>
</div>
<div id="allApptOverlay" onclick="closeAllAppointmentsPanel()"></div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
let currentAppointmentId = null;
let calendar;

// Dropdowns
function toggleNotifDropdown(e) { e.stopPropagation(); const d = document.getElementById('notifDropdown'); d.style.display = d.style.display === 'block' ? 'none' : 'block'; }
function toggleProfileDropdown(e) { e.stopPropagation(); const d = document.getElementById('profileDropdown'); d.setAttribute('aria-hidden', d.getAttribute('aria-hidden') === 'true' ? 'false' : 'true'); }
document.addEventListener('click', () => {
    document.getElementById('notifDropdown').style.display = 'none';
    document.getElementById('profileDropdown').setAttribute('aria-hidden', 'true');
});

// Calendar
document.addEventListener('DOMContentLoaded', () => {
    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        height: 'auto',
        events: [
            <?php foreach ($all_appointments as $a):
                $name = trim("{$a['fname']} " . ($a['mi'] ? $a['mi'].'.' : '') . " {$a['lname']}");
                $title = "$name - " . htmlspecialchars($a['appointment_desc']);
                $color = $a['status'] === 'approved' ? '#27ae60' : ($a['status'] === 'done' ? '#3498db' : '#8e44ad');
                $initials = strtoupper(($a['fname'][0]??'S') . ($a['lname'][0]??'T'));
                $dt = date('F j, Y \a\t g:i A', strtotime($a['appointment_date']));
            ?>
            {
                id: '<?= $a['appointment_id'] ?>',
                title: '<?= $title ?>',
                start: '<?= $a['appointment_date'] ?>',
                color: '<?= $color ?>',
                textColor: 'white',
                extendedProps: {
                    appointment_id: '<?= $a['appointment_id'] ?>',
                    student: '<?= htmlspecialchars($name) ?>',
                    initials: '<?= $initials ?>',
                    datetime: '<?= $dt ?>',
                    reason: '<?= htmlspecialchars($a['appointment_desc']) ?>',
                    status: '<?= $a['status'] ?? 'pending' ?>'
                }
            },
            <?php endforeach; ?>
        ],
        eventClick: function(i) {
            const p = i.event.extendedProps;
            document.getElementById('detailInitials').textContent = p.initials;
            document.getElementById('detailStudent').textContent = p.student;
            document.getElementById('detailDateTime').textContent = p.datetime;
            document.getElementById('detailReason').textContent = p.reason;
            currentAppointmentId = p.appointment_id;
            document.getElementById('appointmentDetailModal').style.display = 'flex';
        }
    });
    calendar.render();
});

// Panel
function openAllAppointmentsPanel() {
    fetch('api/get_all_appointments.php')
        .then(r => r.json())
        .then(d => {
            const body = document.getElementById('allApptBody');
            if (!d.appointments || d.appointments.length === 0) {
                body.innerHTML = `<div style="text-align:center;padding:120px;color:#888;"><i class="fas fa-calendar-times fa-4x"></i><h3>No appointments</h3></div>`;
                return;
            }
            let html = '';
            d.appointments.forEach(a => {
                const name = a.student_name;
                const date = new Date(a.appointment_date);
                const fmt = date.toLocaleString('en-US', { weekday:'short', month:'short', day:'numeric', hour:'numeric', minute:'2-digit' });
                const status = a.status || 'pending';
                const initials = name.split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase();

                html += `
                <div class="appt-card ${status}">
                    <div class="appt-header">
                        <div class="appt-date"><i class="fas fa-calendar"></i> ${fmt}</div>
                        <div class="appt-status">${status.charAt(0).toUpperCase() + status.slice(1)}</div>
                    </div>
                    <div class="appt-student-info">
                        <div class="appt-student-initials">${initials}</div>
                        <div><strong>${name}</strong><br><small>Student</small></div>
                    </div>
                    <div class="appt-reason"><strong>Reason:</strong> ${a.appointment_desc || 'Not specified'}</div>
                    <div class="appt-actions">
                        <button class="chat" onclick="window.open('chat.php?with=<?= $a['student_id'] ?>', '_blank')">
                            Chat
                        </button>
                        <button class="reschedule" onclick="rescheduleAppointment('${a.appointment_id}', '${name}')"><i class="fas fa-clock"></i> Reschedule</button>
                        ${status !== 'done' ? `<button class="done" onclick="markAsDone('${a.appointment_id}')"><i class="fas fa-check-circle"></i> Done</button>` :
                        `<button disabled><i class="fas fa-check-circle"></i> Completed</button>`}
                    </div>
                </div>`;
            });
            body.innerHTML = html;
        });

    document.getElementById('counselorAllAppointmentsPanel').classList.add('open');
    document.getElementById('allApptOverlay').style.display = 'block';
}

function closeAllAppointmentsPanel() {
    document.getElementById('counselorAllAppointmentsPanel').classList.remove('open');
    document.getElementById('allApptOverlay').style.display = 'none';
}

// Search
document.getElementById('apptSearch').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('.appt-card').forEach(c => {
        c.style.display = c.textContent.toLowerCase().includes(term) ? 'block' : 'none';
    });
});

// Actions
function approveAppointment(id) {
    if (!id || !confirm('Approve this appointment?')) return;
    fetch('api/approve_appointment.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'appointment_id='+id})
        .then(r=>r.json()).then(d=>{ if(d.success){ alert('Approved!'); location.reload(); } });
}

function markAsDone(id) {
    if (!confirm('Mark as completed?')) return;
    fetch('api/mark_done.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'appointment_id='+id})
        .then(r=>r.json()).then(d=>{ if(d.success){ alert('Done!'); openAllAppointmentsPanel(); } });
}

function rescheduleAppointment(id, name) {
    const dt = prompt(`Reschedule for ${name}:\nEnter date & time (YYYY-MM-DD HH:MM)`, '');
    if (!dt) return;
    fetch('api/reschedule.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`appointment_id=${id}&new_datetime=${dt}`})
        .then(r=>r.json()).then(d=>{ if(d.success){ alert('Rescheduled!'); openAllAppointmentsPanel(); location.reload(); } else alert('Invalid date'); });
}

function openChatModal(id, name) { alert(`Chat with ${name} (ID: ${id})`); }

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllAppointmentsPanel(); });

function approveAndRemove(appointment_id, notification_index, button) {
    if (!confirm('Approve this appointment request?')) return;

    // Visual feedback
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;

    fetch('api/approve_appointment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'appointment_id=' + appointment_id
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const item = button.closest('.notif-item');
            
            // Smooth fade out + slide up
            item.style.transition = 'all 0.4s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(-100%)';
            item.style.height = item.offsetHeight + 'px';
            item.style.height = '0';
            item.style.padding = '0';
            item.style.margin = '0';
            item.style.border = '0';

            setTimeout(() => {
                item.remove();

                // Update badge count
                const badge = document.querySelector('#notifBtn span');
                if (badge) {
                    let count = parseInt(badge.textContent) - 1;
                    if (count <= 0) badge.remove();
                    else badge.textContent = count;
                }

                // If no more notifications
                if (document.querySelectorAll('.notif-item').length === 0) {
                    document.querySelector('#notifDropdown > div:nth-child(2)').innerHTML = `
                        <div style="padding:80px 30px;text-align:center;color:#bdc3c7;">
                            <i class="fas fa-bell-slash fa-3x mb-3"></i>
                            <div style="font-size:16px;font-weight:500;">All caught up!</div>
                        </div>
                    `;
                }
            }, 400);

            // Optional: Remove from file too
            fetch('api/remove_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'index=' + notification_index
            });
        } else {
            alert('Error approving appointment');
            button.innerHTML = 'Approve';
            button.disabled = false;
        }
    });
}

function clearAllNotifications() {
    if (!confirm('Clear all notifications?')) return;
    fetch('api/clear_notifications.php', { method: 'POST' })
        .then(() => location.reload());
}


</script>
</body>
</html>