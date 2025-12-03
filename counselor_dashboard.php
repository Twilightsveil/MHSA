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

<style>
   

    /* MAIN CONTENT */
    .main-content {
        margin-left: 80px;
        padding: 25px;
        width: calc(100% - 250px);
    }

    .page-title h1 {
        margin: 0;
        font-size: 32px;
        color: var(--purple-dark);
    }

    /* FLEX LAYOUT: Calendar LEFT, Appointments RIGHT */
    .dashboard-flex {
        display:flex;
        justify-content: space-between;
        gap: 20px;
        margin-top: 25px;
    }

    /* CALENDAR BOX */
    .calendar-container {
        flex: 1;
        min-width: 650px;
        background: var(--white);
        padding: 18px;
        border-radius: 10px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    }

    /* APPOINTMENT CARD */
    .appointment-card {
        width: 330px;
        background: var(--white);
        padding: 18px;
        border-radius: 10px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        height: fit-content;
        position: sticky;
        top: 20px;
    }

    .appointment-card h3 {
        margin-top: 0;
        color: var(--purple-dark);
    }

    .appointment-item {
        padding: 12px 0;
        border-bottom: 1px solid #eee;
    }

    .appointment-item:last-child {
        border-bottom: none;
    }

    #calendar {
        margin-top: 15px;
    }
</style>
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

    <div class="page-title">
        <h1>Welcome back, <?= htmlspecialchars($firstName) ?>.</h1>
        <p style="color:var(--text-light);font-size:18px;">Overview of your schedule and student activity.</p>
    </div>

    <!-- FLEX: Calendar LEFT | Appointments RIGHT -->
    <div class="dashboard-flex">

        <!-- LEFT: CALENDAR -->
        <div class="calendar-container">
            <h3><i class="fas fa-calendar-alt"></i> Appointment Calendar</h3>
            <div id="calendar"></div>
        </div>

        <!-- RIGHT: TODAY'S APPOINTMENTS -->
        <div class="appointment-card">
            <h3><i class="fas fa-clock"></i> Today's Appointments (<?= count($today_appointments) ?>)</h3>

            <?php if (empty($today_appointments)): ?>
                <p style="color:var(--text-light);">No appointments scheduled for today.</p>
            <?php else: ?>
                <?php foreach ($today_appointments as $apt): 
                    $name = trim("{$apt['fname']} {$apt['mi']} {$apt['lname']}");
                    $time = date('g:i A', strtotime($apt['appointment_desc']));
                ?>
                <div class="appointment-item">
                    <strong><?= $time ?></strong><br>
                    <small><?= htmlspecialchars($name) ?></small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>








document.addEventListener('DOMContentLoaded', () => {
    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
        height: '100%',
        selectable: true,

        select: function(info) {
            const selectedDate = info.startStr.split('T')[0];
            selectedCounselor = null;
            selectedSlot = null;

            openBookingModal();
            document.getElementById('datePicker').value = selectedDate;

            const nextBtn = document.getElementById('counselorNextBtn');
            nextBtn.onclick = null;
            nextBtn.onclick = function() {
                nextStep(2);
                setTimeout(() => {
                    const picker = document.getElementById('datePicker');
                    if (picker.value === selectedDate) {
                        picker.dispatchEvent(new Event('change'));
                    }
                }, 100);
            };
        },

        events: [
            <?php foreach($events as $e): 
                $cname = trim($e['fname'] . ' ' . ($e['mi'] ? $e['mi'].'.' : '') . ' ' . $e['lname']);
                $formatted = date('F j, Y \a\t g:i A', strtotime($e['appointment_date']));
            ?>
            {
                title: 'Session with <?= htmlspecialchars($cname) ?>',
                start: '<?= $e['appointment_date'] ?>',
                color: '#8e44ad',
                textColor: 'white',
                extendedProps: {
                    appointment_id: <?= (int)$e['appointment_id'] ?>,
                    counselor: '<?= htmlspecialchars($cname) ?>',
                    initials: '<?= strtoupper($e['fname'][0] . $e['lname'][0]) ?>',
                    datetime: '<?= $formatted ?>',
                    reason: '<?= htmlspecialchars($e['Appointment_desc'] ?? 'Not specified') ?>'
                }
            },
            <?php endforeach; ?>
        ],

        eventClick: function(info) {
            currentAppointmentId = info.event.extendedProps.appointment_id;
            document.getElementById('detailInitials').textContent = info.event.extendedProps.initials;
            document.getElementById('detailCounselor').textContent = info.event.extendedProps.counselor;
            document.getElementById('detailDateTime').textContent = info.event.extendedProps.datetime;
            document.getElementById('detailReason').textContent = info.event.extendedProps.reason;
            document.getElementById('appointmentDetailModal').style.display = 'flex';
        }
    });
    calendar.render();
});















<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const calendarEl = document.getElementById("calendar");

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "timeGridWeek",
        height: "auto",
        slotMinTime: "08:00:00",
        slotMaxTime: "18:00:00",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay"
        },
        events: [
            <?php foreach ($all_appointments as $apt): 
                $name = trim("{$apt['fname']} {$apt['mi']} {$apt['lname']}");
            ?>
            {
                id: "<?= $apt['appointment_id'] ?>",
                title: "<?= $name ?>",
                start: "<?= $apt['appointment_desc'] ?>",
                color: "#4b3876"
            },
            <?php endforeach; ?>
        ]
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



