<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    header("Location: login.php");
    exit();
}

$counselor_id = $_SESSION['user_id'];
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
    SELECT a.appointment_desc, s.fname, s.lname, s.mi, s.student_id
    FROM appointments a 
    JOIN student s ON a.student_id = s.student_id 
    WHERE a.counselor_id = ?
");
$stmt->execute([$counselor_id]);
$all_appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Dashboard • Guidance Office</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet'/>
</head>
<body>

<div class="navbar">
    <div class="left-group" style="display:flex;align-items:center;gap:12px">
        <button id="profileBtn" class="profile-btn" onclick="toggleProfileDropdown(event)">
            <span class="avatar"><i class="fa-solid fa-user"></i></span>
        </button>
        <div id="profileDropdown" class="profile-dropdown" aria-hidden="true">
            <div class="profile-row" style="padding:12px;border-bottom:1px solid #f0e6ff;">
                <div class="avatar" style="width:48px;height:48px;border-radius:8px;background:linear-gradient(135deg,#D8BEE5,#b88ed9);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="info">
                    <div style="font-weight:700"><?= htmlspecialchars($_SESSION['fullname']) ?></div>
                    <small style="color:#8e44ad">Counselor</small>
                </div>
            </div>
            <a href="counselor_profile.php" class="profile-item">View Profile</a>
            <a href="logout.php" class="profile-item">Logout</a>
        </div>
        <div class="logo">Counselor • <?= htmlspecialchars($_SESSION['fullname']) ?></div>
    </div>
</div>

<div class="dashboard-content">
    <div class="page-title">
        <h1>Welcome back, <?= explode(' ', $_SESSION['fullname'])[0] ?>!</h1>
        <p>Today is <strong><?= date('l, F j, Y') ?></strong></p>
    </div>

    <div class="card-grid">
        <div class="widget">
            <h3>Today's Schedule</h3>
            <?php if (empty($today_appointments)): ?>
                <p>No appointments today.</p>
            <?php else: ?>
                <?php foreach ($today_appointments as $i => $apt): 
                    $name = trim("{$apt['fname']} {$apt['mi']} {$apt['lname']}");
                    $time = date('h:i A', strtotime($apt['appointment_desc']));
                ?>
                <div class="appointment-item" data-id="<?= $apt['appointment_id'] ?>">
                    <div>
                        <strong id="time-<?= $apt['appointment_id'] ?>"><?= $time ?></strong> • <?= htmlspecialchars($name) ?><br>
                        <small>ID: <?= $apt['student_id'] ?></small>
                    </div>
                    <div class="actions">
                        <button class="action-btn done-btn" onclick="markStatus(<?= $apt['appointment_id'] ?>, 'done')">Done</button>
                        <button class="action-btn monitoring-btn" onclick="markStatus(<?= $apt['appointment_id'] ?>, 'monitoring')">Monitoring</button>
                        <button class="action-btn reschedule-btn" onclick="openFollowUpFromList(<?= $apt['appointment_id'] ?>, '<?= htmlspecialchars($name) ?>')">Follow Up</button>
                        <button class="action-btn chat-btn" onclick="openChatModal('<?= htmlspecialchars($name) ?>', '<?= $apt['student_id'] ?>')">
                            Chat
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="widget">
            <h3>Appointment Calendar</h3>
            <div id="calendar"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        height: 'auto',
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        editable: true,
        events: [
            <?php foreach ($all_appointments as $apt): 
                $name = trim("{$apt['fname']} {$apt['mi']} {$apt['lname']}");
                $title = "$name - Counseling";
            ?>
            { 
                id: '<?= $apt['appointment_id'] ?>', 
                title: '<?= $title ?>', 
                start: '<?= $apt['appointment_desc'] ?>',
                color: '#9b59b6'
            },
            <?php endforeach; ?>
        ],
        eventClick: function(info) {
            openEditModal(info.event);
        }
    });
    calendar.render();
});
</script>
<script src="js/counselor.js"></script>
</body>
</html>