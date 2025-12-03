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

// Fetch today's appointments
$stmt = $conn->prepare("
    SELECT a.*, s.fname, s.lname, s.mi, s.student_id 
    FROM appointments a 
    JOIN student s ON a.student_id = s.student_id 
    WHERE a.counselor_id = ? AND DATE(a.appointment_desc) = ?
    ORDER BY a.appointment_desc
");
$stmt->execute([$counselor_id, $today]);
$today_appointments = $stmt->fetchAll();

// Fetch all appointments for calendar
$stmt = $conn->prepare("
    SELECT a.appointment_desc, s.fname, s.lname, s.mi, a.appointment_id
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
    <div class="nav-right">
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
    function completeAppointment(appointmentId) { console.log(`Completing appointment ${appointmentId}`); }

</script>
<script src="js/counselor.js"></script>
</body>
</html>



