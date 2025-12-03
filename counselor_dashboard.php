<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    header("Location: login.php");
    exit();
}

$counselor_id = $_SESSION['user_id'];
$counselor_name = $_SESSION['fullname'];
$firstName = explode(' ', $counselor_name)[0]; 
$today = date('Y-m-d');

// Load notifications from file (if any) and merge into session
$notif_file = __DIR__ . "/sessions/counselor_{$counselor_id}_notifs.json";
if (file_exists($notif_file)) {
    $file_notifs = json_decode(file_get_contents($notif_file), true) ?: [];
    if (!isset($_SESSION['counselor_notifications'])) $_SESSION['counselor_notifications'] = [];
    $_SESSION['counselor_notifications'] = array_merge($_SESSION['counselor_notifications'], $file_notifs);
    file_put_contents($notif_file, json_encode([])); // clear file after loading
}

// Fetch today's appointments (pending only)
$stmt = $conn->prepare("
    SELECT a.*, s.fname, s.lname, s.mi, s.student_id 
    FROM appointments a 
    JOIN student s ON a.student_id = s.student_id 
    WHERE a.counselor_id = ? AND DATE(a.appointment_date) = ? AND (a.status IS NULL OR a.status = 'pending')
    ORDER BY a.appointment_date
");
$stmt->execute([$counselor_id, $today]);
$today_appointments = $stmt->fetchAll();

// Fetch all appointments for calendar (show status)
$stmt = $conn->prepare("
    SELECT a.appointment_date, a.appointment_desc, s.fname, s.lname, s.mi, a.appointment_id, a.status
    FROM appointments a 
    JOIN student s ON a.student_id = s.student_id 
    WHERE a.counselor_id = ?
");
$stmt->execute([$counselor_id]);
$all_appointments = $stmt->fetchAll();

// Define current page for sidebar active state
$current_page = 'dashboard'; 
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
</head>
<body>

<div class="navbar">
    <div class="logo">Counselor Portal</div>
    <div class="nav-right" style="display: flex; align-items: center; gap: 20px;">
        <!-- Notification Bell -->
        <div style="position: relative;">
            <button id="notifBtn" onclick="toggleNotifDropdown(event)" style="background:none;border:none;cursor:pointer;position:relative;">
                <i class="fas fa-bell fa-lg"></i>
                <?php if (!empty($_SESSION['counselor_notifications'])): ?>
                    <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:white;font-size:12px;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;">
                        <?= count($_SESSION['counselor_notifications']) ?>
                    </span>
                <?php endif; ?>
            </button>
            <div id="notifDropdown" class="profile-dropdown" style="min-width:260px;right:0;left:auto;display:none;position:absolute;z-index:1001;">
                <div style="padding:10px 20px;font-weight:bold;border-bottom:1px solid #eee;">Notifications</div>
                <ul style="max-height:300px;overflow-y:auto;padding:0;margin:0;list-style:none;">
                    <?php if (!empty($_SESSION['counselor_notifications'])): ?>
                        <?php foreach ($_SESSION['counselor_notifications'] as $i => $notif): ?>
                            <li style="padding:12px 20px;border-bottom:1px solid #f5f5f5;">
                                <?= htmlspecialchars($notif) ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li style="padding:12px 20px;color:#888;">No notifications</li>
                    <?php endif; ?>
                </ul>
                <?php if (!empty($_SESSION['counselor_notifications'])): ?>
                <form method="post" style="margin:0;text-align:center;">
                    <input type="hidden" name="clear_counselor_notifications" value="1">
                    <button type="submit" style="background:none;border:none;color:#6f42c1;cursor:pointer;padding:10px 0;font-size:14px;">Clear All</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <!-- Profile Button -->
        <button id="profileBtn" class="profile-btn" onclick="toggleProfileDropdown(event)" aria-controls="profileDropdown" aria-expanded="false" aria-label="Toggle profile menu">
            <div class="avatar"><i class="fas fa-user-tie"></i></div>
        </button>
        <div id="profileDropdown" class="profile-dropdown" aria-hidden="true">
            <div class="profile-row" style="display:flex;align-items:center;gap:15px;padding:15px 20px;border-bottom:1px solid var(--purple-lightest);">
                <div class="avatar" style="width:40px;height:40px;flex-shrink:0;"><i class="fas fa-user-tie"></i></div>
                <div class="info">
                    <div><?= htmlspecialchars($counselor_name) ?></div>
                    <small>Counselor</small>
                </div>
            </div>
            <ul>
                <li><a href="counselor_profile.php" style="padding:10px 20px;display:block;text-decoration:none;color:var(--text-dark);font-size:15px;">
                    <i class="fas fa-user-circle" style="margin-right:10px;"></i> My Profile
                </a></li>
                <li><a href="logout.php" style="padding:10px 20px;display:block;text-decoration:none;color:var(--danger);font-size:15px;border-top:1px solid #f5f5f5;">
                    <i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Logout
                </a></li>
            </ul>
        </div>
    </div>
</div>
<?php
// Clear notifications if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_counselor_notifications'])) {
    $_SESSION['counselor_notifications'] = [];
    // Optionally reload to update UI
    echo "<script>location.href=location.href;</script>";
}
?>
</head>
<script>
// Toggle notification dropdown
function toggleNotifDropdown(e) {
    e.stopPropagation();
    var dd = document.getElementById('notifDropdown');
    dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    document.addEventListener('click', function handler(ev) {
        if (!dd.contains(ev.target) && ev.target.id !== 'notifBtn') {
            dd.style.display = 'none';
            document.removeEventListener('click', handler);
        }
    });
}
</script>
<div class="dashboard-container">
    
    <aside class="sidebar">
        <h2>Main Menu</h2>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="counselor_dashboard.php" class="<?= ($current_page == 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a></li>
                <li><a href="counselor_appointments.php" class="<?= ($current_page == 'appointments') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> Appointments
                    <span class="badge"><?= count($today_appointments) ?></span> 
                </a></li>
                <li><a href="counselor_chat.php" class="<?= ($current_page == 'chat') ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i> Chat
                </a></li>
            </ul>
        </nav>
        <h2 style="margin-top:20px;">Account</h2>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="counselor_profile.php" class="<?= ($current_page == 'profile') ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i> Profile Settings
                </a></li>
                <li><a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a></li>
            </ul>
        </nav>
    </aside>
    <div class="main-content">
        
        <div class="page-title" style="text-align:left;">
            <h1 style="font-size:32px;color:var(--purple-dark);">Welcome back, <?= htmlspecialchars($firstName) ?>.</h1>
            <p style="color:var(--text-light);font-size:18px;">Overview of your schedule and student activity.</p>
        </div>
        
        <div class="dashboard-content">
            <div class="card-grid">
                
                <div class="widget">
                    <h3><i class="fas fa-clock"></i> Today's Appointments (<?= count($today_appointments) ?>)</h3>
                    <?php if (empty($today_appointments)): ?>
                        <p style="color:var(--text-light);">No appointments scheduled for today.</p>
                    <?php else: ?>
                        <div class="appointment-list">
                            <?php foreach ($today_appointments as $apt): 
                                $name = trim("{$apt['fname']} {$apt['mi']} {$apt['lname']}");
                                $time = date('g:i A', strtotime($apt['appointment_date']));
                                $desc = htmlspecialchars($apt['appointment_desc']);
                            ?>
                            <div class="appointment-item">
                                <div class="details">
                                    <strong><?= $time ?></strong>
                                    <small><?= htmlspecialchars($name) ?></small><br>
                                    <span style="color:#888; font-size:13px;">Reason: <?= $desc ?></span>
                                </div>
                                <div class="actions">
                                    <button class="action-btn chat-btn" onclick="openChatModal('<?= $apt['student_id'] ?>', '<?= htmlspecialchars($name) ?>')">
                                        <i class="fas fa-comment-dots"></i> Chat
                                    </button>
                                    <button class="action-btn approve-btn" style="background:#27ae60;color:#fff;" onclick="approveAppointment('<?= $apt['appointment_id'] ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="widget" style="padding: 15px;">
                    <h3 style="padding:15px 15px 10px 15px;margin-bottom:10px;"><i class="fas fa-calendar-alt"></i> Appointment Calendar</h3>
                    <div id="calendar" style="margin-top: 10px;"></div>
                </div>

            </div>
        </div>

    </div>
    </div>
<!-- Appointment Detail Modal (Counselor) -->
<div class="modal" id="appointmentDetailModal">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close-modal" onclick="document.getElementById('appointmentDetailModal').style.display='none'">×</span>
        <h3 style="text-align:center; margin-bottom:20px; color:var(--purple-dark);">Appointment Details</h3>
        <div style="text-align:center; margin-bottom:25px;">
            <div style="width:90px; height:90px; background:var(--primary); color:white; border-radius:50%; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; font-size:36px; font-weight:bold;">
                <span id="detailInitials"></span>
            </div>
            <h4 style="margin:10px 0; color:var(--text-dark);" id="detailStudent"></h4>
        </div>
        <div style="background:#f8f9fa; padding:15px; border-radius:12px; margin:15px 0;">
            <p style="margin:8px 0;"><strong>Date & Time:</strong> <span id="detailDateTime" style="color:var(--primary);"></span></p>
            <p style="margin:8px 0;"><strong>Reason:</strong> <span id="detailReason"></span></p>
        </div>
        <div style="text-align:center; margin-top:25px;">
            <button class="btn" style="background:#27ae60; color:white;" onclick="approveAppointmentModal()">
                Approve Appointment
            </button>
        </div>
    </div>
</div>
<script>
let currentAppointmentId = null;
function approveAppointmentModal() {
    if (!currentAppointmentId || !confirm('Approve this appointment?')) return;
    fetch('api/approve_appointment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'appointment_id=' + encodeURIComponent(currentAppointmentId)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert('Appointment approved');
            document.getElementById('appointmentDetailModal').style.display = 'none';
            location.reload();
        } else alert('Failed to approve');
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
    let calendar;
    
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
            height: 'auto',
            slotMinTime: '08:00:00',
            slotMaxTime: '18:00:00',
            editable: true,
            events: [
                <?php foreach ($all_appointments as $apt): 
                    $name = trim("{$apt['fname']} {$apt['mi']} {$apt['lname']}");
                    $title = "{$name} - " . htmlspecialchars($apt['appointment_desc']);
                    $color = ($apt['status'] === 'approved') ? '#27ae60' : 'var(--primary)';
                    $initials = strtoupper(($apt['fname'][0] ?? 'S') . ($apt['lname'][0] ?? 'T'));
                    $datetime = date('F j, Y \a\t g:i A', strtotime($apt['appointment_date']));
                ?>
                {
                    id: '<?= $apt['appointment_id'] ?>',
                    title: '<?= $title ?>',
                    start: '<?= $apt['appointment_date'] ?>',
                    color: '<?= $color ?>',
                    textColor: 'white',
                    extendedProps: {
                        appointment_id: '<?= $apt['appointment_id'] ?>',
                        student: '<?= htmlspecialchars($name) ?>',
                        initials: '<?= $initials ?>',
                        datetime: '<?= $datetime ?>',
                        reason: '<?= htmlspecialchars($apt['appointment_desc']) ?>',
                        status: '<?= $apt['status'] ?>'
                    }
                },
                <?php endforeach; ?>
            ],
            eventContent: function(arg) {
                var status = arg.event.extendedProps.status;
                var dot = '';
                if (status === 'approved') {
                    dot = '<span style="display:inline-block;width:12px;height:12px;background:#27ae60;border-radius:50%;margin-right:6px;vertical-align:middle;"></span>';
                }
                var time = arg.timeText ? arg.timeText + ' ' : '';
                var title = arg.event.title.split(' - ')[0];
                return {
                    html: '<span style="display:flex;align-items:center;">' + dot + '<span>' + time + title + '</span></span>'
                };
            },
            // Event Handlers for Edit/Drag
            eventClick: function(info) {
                // Use extendedProps for student details
                const props = info.event.extendedProps || {};
                document.getElementById('detailInitials').textContent = props.initials || 'ST';
                document.getElementById('detailStudent').textContent = props.student || info.event.title || '';
                document.getElementById('detailDateTime').textContent = props.datetime || (info.event.start ? new Date(info.event.start).toLocaleString() : '-');
                document.getElementById('detailReason').textContent = props.reason || '-';
                currentAppointmentId = props.appointment_id || info.event.id;
                document.getElementById('appointmentDetailModal').style.display = 'flex';
            },
        });
        calendar.render();
    });

    // Helper functions for profile dropdown
    function toggleProfileDropdown(e) {
        e.stopPropagation();
        const dd = document.getElementById('profileDropdown');
        if (!dd) return;
        const isHidden = dd.getAttribute('aria-hidden') !== 'false';
        dd.setAttribute('aria-hidden', isHidden ? 'false' : 'true');
    }
    document.addEventListener('click', (e) => {
        const dd = document.getElementById('profileDropdown');
        const btn = document.getElementById('profileBtn');
        if (dd && dd.getAttribute('aria-hidden') === 'false' && !dd.contains(e.target) && !btn.contains(e.target)) {
            dd.setAttribute('aria-hidden', 'true');
        }
    });

    // Placeholder functions you should define in JS/counselor.js
    function openChatModal(studentId, studentName) { console.log(`Opening chat with ${studentName} (${studentId})`); }
    function approveAppointment(appointmentId) {
        if (!confirm('Approve this appointment?')) return;
        fetch('api/approve_appointment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'appointment_id=' + encodeURIComponent(appointmentId)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Appointment approved!');
                location.reload();
            } else {
                alert('Failed to approve: ' + (data.message || 'Unknown error'));
            }
        });
    }

</script>
<script src="js/counselor.js"></script>
</body>
</html>