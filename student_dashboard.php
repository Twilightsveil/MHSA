<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['fullname'];
$firstName = explode(' ', $name)[0];
$student_id = $_SESSION['user_id'];

// Load appointments for main calendar
$appointments = $conn->prepare("
    SELECT a.appointment_desc, c.fname, c.lname, c.mi 
    FROM appointments a 
    JOIN counselor c ON a.counselor_id = c.counselor_id 
    WHERE a.student_id = ?
");
$appointments->execute([$student_id]);
$events = $appointments->fetchAll();

// Define current page for sidebar active state
$current_page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal • <?= htmlspecialchars($name) ?></title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <style>
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.6); 
            justify-content: center; 
            align-items: center; 
            z-index: 999; 
        }
        .modal-content { 
            background: white; 
            padding: 30px; 
            border-radius: 20px; 
            position: relative; 
            max-width: 500px; 
            width: 90%; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">Student Portal</div>
    <div class="nav-right">
        <button id="profileBtn" class="profile-btn" onclick="toggleProfileDropdown(event)" aria-controls="profileDropdown" aria-expanded="false" aria-label="Toggle profile menu">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
        </button>
        
        <div id="profileDropdown" class="profile-dropdown" aria-hidden="true">
            <div class="profile-row" style="display:flex;align-items:center;gap:15px;padding:15px 20px;border-bottom:1px solid var(--purple-lightest);">
                <div class="avatar" style="width:40px;height:40px;flex-shrink:0;"><i class="fas fa-user-graduate"></i></div>
                <div class="info">
                    <div><?= htmlspecialchars($name) ?></div>
                    <small>Student</small>
                </div>
            </div>
            <ul>
                <li><a href="student_profile.php" style="padding:10px 20px;display:block;text-decoration:none;color:var(--text-dark);font-size:15px;">
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
                <li><a href="student_dashboard.php" class="<?= ($current_page == 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a></li>
                <li><a href="student_appointments.php" class="<?= ($current_page == 'appointments') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> My Appointments
                </a></li>
                <li><a href="#" onclick="openBookingModal()" style="color: var(--primary); font-weight: 700;">
                    <i class="fas fa-plus-circle" style="color:var(--primary);"></i> Book New Session
                </a></li>
            </ul>
        </nav>
        <h2 style="margin-top:20px;">Account</h2>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="student_profile.php" class="<?= ($current_page == 'profile') ? 'active' : ''; ?>">
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
            <h1 style="font-size:32px;color:var(--purple-dark);">Hello, <?= htmlspecialchars($firstName) ?>.</h1>
            <p style="color:var(--text-light);font-size:18px;">How can we help you today?</p>
        </div>
        
        <div class="emergency">
            Need urgent help? Call the Crisis Hotline: 0917-558-HELP (4357) or 
            <button class="btn small" style="background:white; color:var(--danger); margin-left:15px; padding: 5px 12px; transform:none; box-shadow:none;" onclick="openModal('emergencyChatModal')">
                <i class="fas fa-headset"></i> Chat Now
            </button>
        </div>
        
        <div class="dashboard-content">
            <div class="card-grid">
                
                <div class="widget">
                    <h3><i class="fas fa-toolbox"></i> Tools & Actions</h3>
                    <div style="display:flex; flex-direction:column; gap:15px;">
                        <button class="btn" onclick="openBookingModal()">
                            <i class="fas fa-calendar-plus" style="margin-right: 10px;"></i> Request New Appointment
                        </button>
                        <button class="btn btn-secondary" onclick="openModal('emergencyChatModal')" style="box-shadow: none !important;">
                            <i class="fas fa-headset" style="margin-right: 10px;"></i> Emergency/Immediate Chat
                        </button>
                    </div>
                </div>

                <div class="widget" style="padding: 15px;">
                    <h3 style="padding:15px 15px 10px 15px;margin-bottom:10px;"><i class="fas fa-calendar-alt"></i> My Appointments Calendar</h3>
                    <div id="calendar" style="margin-top: 10px;"></div>
                </div>
            </div>
        </div>

    </div>
    </div>
<div class="modal" id="bookingModal">
    <div class="modal-content" onclick="event.stopPropagation()">
        <span class="close-modal" onclick="closeBookingModal()">×</span>
        
        <h3 style="text-align:center; margin-bottom: 0;">Book a Counseling Session</h3>
        <div class="booking-steps">
            <span class="step active" id="step1">1. Counselor</span>
            <span class="step" id="step2">2. Date & Time</span>
            <span class="step" id="step3">3. Reason & Confirm</span>
        </div>
        
        <div id="step1Content">
            <p style="text-align:center; color:var(--text-light); margin-bottom: 20px;">Choose a counselor to view their availability.</p>
            <div id="counselorGrid" class="card-grid counselor-grid">
                </div>
            <button class="btn" style="width:100%;" id="counselorNextBtn" disabled onclick="nextStep(2)">Next: Choose Date</button>
        </div>

        <div id="step2Content" style="display:none;">
            <p style="text-align:center; color:var(--text-light); margin-bottom: 20px;">Select an available time slot below.</p>
            <div id="dateTimeCalendar" class="calendar-container"></div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                <button class="btn btn-secondary small" onclick="backToCounselors()">Back</button>
                <div id="selectedTimeDisplay" style="font-weight:700; color:var(--primary);"></div>
                <button class="btn small" id="nextBtn" disabled onclick="nextStep(3)">Next: Finalize</button>
            </div>
        </div>
        
        <div id="step3Content" style="display:none;">
            <h4 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Confirmation</h4>
            <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
                <img id="finalPhoto" src="" alt="Counselor Photo" class="counselor-photo" style="width: 50px; height: 50px;">
                <div>
                    <strong>Counselor:</strong> <span id="finalName"></span><br>
                    <strong>Time:</strong> <span id="finalDateTime"></span>
                </div>
            </div>
            <div class="input-field">
                <label for="reasonField">Reason for Visit (Required)</label>
                <textarea id="reasonField" rows="4" placeholder="Briefly describe what you would like to discuss..."></textarea>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:20px;">
                <button class="btn btn-secondary" onclick="backToTimeSelection()">Back</button>
                <button class="btn" onclick="confirmBooking()">Confirm Appointment</button>
            </div>
        </div>

    </div>
</div>

<div class="modal" id="emergencyChatModal">
    <div class="modal-content">
        <div class="chat-header">
            <div class="counselor-avatar">
                <i class="fa-solid fa-headset"></i>
            </div>
            <div class="counselor-info">
                <h4>Crisis Support</h4>
                <small style="color:var(--danger);">Immediate help available</small>
            </div>
            <span class="close-modal" onclick="closeModal('emergencyChatModal')">×</span>
        </div>
        <div id="emergencyMessages" class="chat-container">
            <p style="text-align:center; padding-top:20px; color:var(--text-light);">You are connecting to an immediate support counselor. Please wait or start typing.</p>
        </div>
        <div style="display:flex; gap:10px; padding:15px; background:white; border-radius: 0 0 20px 20px;">
            <input type="text" id="emergencyInput" placeholder="Type your message..." onkeypress="if(event.key==='Enter') sendEmergencyMessage()" style="flex:1;">
            <button onclick="sendEmergencyMessage()" class="btn small" style="padding:10px 18px;">Send</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
    // Placeholder functions for modal/dropdown logic
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    function sendEmergencyMessage() { alert("Sending emergency message placeholder."); }

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

    // FullCalendar Initialization - using existing PHP logic
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', 
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            height: 'auto',
            selectable: true,
            select: function (info) {
                // openBookingModal(); // Removed direct call to avoid confusion
            },
            events: [
                <?php foreach ($events as $event): 
                    $c_name = trim("{$event['fname']} {$event['mi']} {$event['lname']}");
                ?>
                { 
                    title: 'Session with <?= htmlspecialchars($c_name) ?>', 
                    start: '<?= $event['appointment_desc'] ?>',
                    color: 'var(--primary)'
                },
                <?php endforeach; ?>
            ]
        });
        calendar.render();
    });

    // Placeholder functions for booking.js that are needed in the HTML
    function openBookingModal() { document.getElementById('bookingModal').style.display = 'flex'; /* Load counselors via AJAX here */ }
    function closeBookingModal() { document.getElementById('bookingModal').style.display = 'none'; }
    function nextStep(step) { console.log("Next step in booking flow called."); }
    function backToCounselors() { console.log("Back to counselors called."); }
    function backToTimeSelection() { console.log("Back to time selection called."); }
    function confirmBooking() { console.log("Booking confirmed called."); }

</script>
<script src="js/booking.js"></script>
<script src="js/student.js"></script>
</body>
</html>