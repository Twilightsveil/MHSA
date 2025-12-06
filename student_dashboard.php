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

// Load notifications
$notif_file = __DIR__ . "/sessions/student_{$student_id}_notifs.json";
if (file_exists($notif_file)) {
    $file_notifs = json_decode(file_get_contents($notif_file), true) ?: [];
    if (!isset($_SESSION['student_notifications'])) $_SESSION['student_notifications'] = [];
    $_SESSION['student_notifications'] = array_merge($_SESSION['student_notifications'], $file_notifs);
    file_put_contents($notif_file, json_encode([]));
}

// Load appointments
$appointments = $conn->prepare("
    SELECT 
        a.appointment_id,
        a.appointment_date, 
        a.Appointment_desc, 
        a.status,
        c.fname, c.lname, c.mi 
    FROM appointments a 
    JOIN counselor c ON a.counselor_ID = c.counselor_id 
    WHERE a.student_Id = ?
");
$appointments->execute([$student_id]);
$events = $appointments->fetchAll(PDO::FETCH_ASSOC);
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
        body { background: #f8f9fa; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo { font-weight: bold; font-size: 24px; color: var(--primary); }
        .main-content { max-width: 1200px; margin: 30px auto; padding: 20px; }

        .page-title {
            text-align: center;
            margin: 30px 0 40px;
        }
        .page-title h1 {
            font-size: 38px;
            color: var(--purple-dark);
            margin: 0;
        }
        .page-title p {
            color: var(--text-light);
            font-size: 19px;
        }

        /* Action Cards */
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        .card {
            background: white;
            padding: 35px 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(142,68,173,0.15);
        }
        .card i {
            font-size: 50px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .action-cards .card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;    
            height: 100%;                        
        }

        .action-cards .card > * {
            flex-shrink: 0;                      
        }

        .action-cards .card .btn {
            margin-top: auto;                   
            align-self: center;                  
            width: 80%;                          
            max-width: 200px;
        }
        
        .card h3 { margin: 15px 0 10px; color: var(--text-dark); font-size: 22px; }
        .card p { color: var(--text-light); margin-bottom: 20px; }

        /* Big Calendar */
        #calendar-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 35px rgba(0,0,0,0.1);
            margin: 0 auto 60px;
        }
        #calendar-container h2 {
            text-align: center;
            color: var(--purple-dark);
            margin-bottom: 20px;
            font-size: 28px;
        }
        #calendar { height: 720px !important; }

        /* Floating Chat Button */
        .floating-chat {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 65px;
            height: 65px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .floating-chat:hover {
            transform: scale(1.15);
            background: #6f42c1;
        }
        .floating-chat .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            font-size: 12px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid white;
        }

        /* Modals */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.7); 
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
            box-shadow: 0 20px 50px rgba(0,0,0,0.3); 
        }
        .close-modal { 
            position: absolute; 
            top: 15px; 
            right: 20px; 
            font-size: 32px; 
            cursor: pointer; 
            color: #aaa; 
        }
        .close-modal:hover { color: #000; }
    </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo">Student Portal</div>
    <div class="nav-right" style="display: flex; align-items: center; gap: 20px;">
        <!-- Notification Bell -->
        <div style="position: relative;">
    <button id="notifBtn" onclick="toggleNotifDropdown(event)" style="background:none;border:none;cursor:pointer;position:relative;font-size:22px;color:#333;">
        <i class="fas fa-bell"></i>
        <?php if (!empty($_SESSION['student_notifications'])): ?>
            <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:white;font-size:11px;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;">
                <?= count($_SESSION['student_notifications']) ?>
            </span>
        <?php endif; ?>
    </button>

    <!-- DROPDOWN -->
    <div id="notifDropdown" style="display:none;position:absolute;right:0;top:50px;background:white;box-shadow:0 15px 40px rgba(0,0,0,0.18);border-radius:16px;min-width:380px;max-height:80vh;overflow:hidden;z-index:1001;border:1px solid #eee;">
        <div style="padding:18px 22px;font-weight:bold;background:linear-gradient(135deg,#8e44ad,#9b59b6);color:white;border-radius:16px 16px 0 0;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:18px;">Notifications</span>
                <span style="background:rgba(255,255,255,0.25);padding:5px 12px;border-radius:20px;font-size:13px;">
                    <?= count($_SESSION['student_notifications'] ?? []) ?> new
                </span>
            </div>
        </div>

        <div style="max-height:460px;overflow-y:auto;">
            <?php if (!empty($_SESSION['student_notifications'])): ?>
                <?php foreach ($_SESSION['student_notifications'] as $n): 
                    $msg = is_array($n) ? $n['message'] : $n;
                    $details = is_array($n) ? ($n['details'] ?? '') : '';
                    $time = is_array($n) ? ($n['time'] ?? 'Just now') : 'Just now';
                ?>
                    <div class="notif-item" style="padding:18px 22px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:16px;background:#faf8ff;transition:all 0.3s ease;">
                        <div style="width:48px;height:48px;background:#27ae60;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600;color:#2c3e50;font-size:15px;line-height:1.4;">
                                <?= htmlspecialchars($msg) ?>
                            </div>
                            <?php if ($details): ?>
                                <div style="color:white;font-size:14px;margin-top:4px;">
                                    <?= htmlspecialchars($details) ?>
                                </div>
                            <?php endif; ?>
                            <div style="color:#95a5a6;font-size:13px;margin-top:6px;">
                                <i class="fas fa-clock"></i> <?= $time ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:80px 30px;text-align:center;color:#bdc3c7;">
                    <i class="fas fa-bell-slash fa-3x mb-3"></i>
                    <div style="font-size:16px;font-weight:500;">All caught up!</div>
                    <div style="font-size:14px;margin-top:8px;">No new notifications</div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($_SESSION['student_notifications'])): ?>
        <div style="padding:14px 22px;background:#f8f9fa;text-align:center;border-top:1px solid #eee;">
            <a href="?clear_notifications=1" style="color:#8e44ad;font-weight:500;font-size:14px;">Clear all notifications</a>
        </div>
        <?php endif; ?>
    </div>
</div>
        </div>
        <!-- Profile Button -->
        <button id="profileBtn" class="profile-btn" onclick="toggleProfileDropdown(event)">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
        </button>
         <div id="profileDropdown" class="profile-dropdown" aria-hidden="true">
            <div class="profile-row" style="display:flex;align-items:center;gap:15px;padding:12px 10px;border-radius:16px 16px 0 0;background: #8e44ad;">
                <div class="avatar" style=" width:20px;height:20px;"><i class="fas fa-user-graduate"></i></div>
                <div class="info">
                    <div class="info-name"><?= htmlspecialchars($name) ?></div>
                    <small>Student</small>
                </div>
            </div>
            <ul>
                <li><a href="student_profile.php" style="padding:10px 20px;display:block;text-decoration:none;color:var(--text-dark);font-size:15px;">
                    <i class="fas fa-user-circle" style="margin-right:10px;"></i> My Profile
                <li><a href="logout.php" style="padding:10px 20px;display:block;text-decoration:none;color:var(--danger);font-size:15px;border-top:1px solid #f5f5f5;">
                    <i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Logout
                </a></li>
            </ul>
        </div>
    </div>
</div>
<?php
// Clear notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_student_notifications'])) {
    $_SESSION['student_notifications'] = [];
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

<!-- Main Content -->
<div class="main-content">

    <div class="page-title">
        <h1>Hello, <?= htmlspecialchars($firstName) ?>!</h1>
        <p>We're here to support your mental health journey</p>
    </div>

    <!-- 3 Action Cards -->
    <div class="action-cards">
        <div class="card">
            <i class="fas fa-calendar-plus"></i>
            <h3>Book an Appointment</h3>
            <p>Schedule a one-on-one session with a counselor</p>
            <button class="btn" onclick="openBookingModal()">Book Now</button>
        </div>

        <div class="card">
            <i class="fas fa-book-open"></i>
            <h3>Mental Health Resources</h3>
            <p>Articles, videos, and self-help tools</p>
            <button class="btn" onclick="window.location='resources.php'">Explore Resources</button>
        </div>

        <div class="card">
            <i class="fas fa-headset"></i>
            <h3>Emergency Support</h3>
            <p>Immediate help when you need it most</p>
            <button class="btn" style="background:#e74c3c;" onclick="openModal('emergencyChatModal')">Chat Now</button>
        </div>
    </div>

   <!-- Calendar -->
    <h2 style="text-align: center;"><i class="fas fa-calendar-alt"></i> My Appointments Calendar</h2>
<div id="calendar-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <button class="btn" onclick="openStudentAppointmentsPanel()" style="padding:12px 24px; font-size:16px;">
            <i class="fas fa-list-ul"></i> View All Appointments
        </button>
    </div>
    <div id="calendar"></div>
</div>

</div>

<!-- Floating Chat Button -->
<div class="floating-chat" id="chatFloatBtn" onclick="openChatWithCounselor()">
    <i class="fas fa-comment-medical"></i>
    <div class="badge" id="unreadBadge">0</div>
</div>

<!-- Booking Modal -->
<div class="modal" id="bookingModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeBookingModal()">×</span>
        <h3 style="text-align:center;margin-bottom:0;">Book a Counseling Session</h3>
        <div class="booking-steps" style="display:flex;justify-content:center;gap:20px;margin:20px 0;">
            <span class="step active">1. Counselor</span>
            <span class="step">2. Date & Time</span>
            <span class="step">3. Reason & Confirm</span>
        </div>

        <div id="step1Content">
            <p style="text-align:center;color:var(--text-light);margin:20px 0;">Choose a counselor:</p>
            <div id="counselorGrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:20px 0;"></div>
            <button class="btn" id="counselorNextBtn" disabled style="width:100%;">Next: Choose Date</button>
        </div>

        <div id="step2Content" style="display:none;">
            <p style="text-align:center;color:var(--text-light);margin:20px 0;">Select date and time:</p>
            <input type="date" id="datePicker" style="width:100%;padding:12px;border-radius:12px;border:1px solid #ddd;margin-bottom:15px;">
            <div id="timeSlots" style="max-height:280px;overflow-y:auto;padding:10px;background:#f9f9f9;border-radius:12px;"></div>
            <div style="display:flex;justify-content:space-between;margin-top:20px;">
                <button class="btn btn-secondary" onclick="nextStep(1)">Back</button>
                <button class="btn" id="nextToConfirm" disabled>Next: Confirm</button>
            </div>
        </div>

        <div id="step3Content" style="display:none;">
            <h4>Confirmation</h4>
            <div style="display:flex;align-items:center;gap:15px;margin:20px 0;">
                <div id="finalPhoto" style="width:60px;height:60px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:20px;"></div>
                <div>
                    <strong>Counselor:</strong> <span id="finalName"></span><br>
                    <strong>Time:</strong> <span id="finalDateTime"></span>
                </div>
            </div>
            <textarea id="reasonField" rows="4" placeholder="Briefly describe what you'd like to discuss..." style="width:100%;padding:12px;border-radius:12px;border:2px solid #eee;"></textarea>
            <div style="display:flex;justify-content:space-between;margin-top:20px;">
                <button class="btn btn-secondary" onclick="nextStep(2)">Back</button>
                <button class="btn" onclick="confirmBooking()">Confirm Appointment</button>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Chat Modal -->
<div class="modal" id="emergencyChatModal">
    <div class="modal-content" style="height: 90vh; max-width: 500px; display:flex; flex-direction:column;">
        <div class="chat-header" style="background:#e74c3c;color:white;padding:15px;border-radius:20px 20px 0 0;text-align:center;position:relative;">
            <h4 style="margin:0;">Crisis Support • AI Assistant</h4>
            <span class="close-modal" onclick="closeModal('emergencyChatModal')" style="color:white;">×</span>
        </div>
        <div id="emergencyMessages" class="chat-container" style="flex:1;overflow-y:auto;padding:20px;background:#fff8f8;"></div>
        <div style="padding:15px;background:white;border-top:1px solid #eee;display:flex;gap:10px;">
            <input type="text" id="emergencyInput" placeholder="I'm here to help. How are you feeling?" style="flex:1;padding:12px;border-radius:12px;border:1px solid #ddd;">
            <button class="btn small" style="background:#e74c3c;" onclick="sendEmergencyMessage()">Send</button>
        </div>
    </div>
</div>

<!-- Appointment Detail Modal -->
<div class="modal" id="appointmentDetailModal">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close-modal" onclick="document.getElementById('appointmentDetailModal').style.display='none'">×</span>
        <h3 style="text-align:center; margin-bottom:20px; color:var(--purple-dark);">Appointment Details</h3>
        <div style="text-align:center; margin-bottom:25px;">
            <div style="width:90px; height:90px; background:var(--primary); color:white; border-radius:50%; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; font-size:36px; font-weight:bold;">
                <span id="detailInitials"></span>
            </div>
            <h4 style="margin:10px 0; color:var(--text-dark);" id="detailCounselor"></h4>
        </div>
        <div style="background:#f8f9fa; padding:15px; border-radius:12px; margin:15px 0;">
            <p style="margin:8px 0;"><strong>Date & Time:</strong> <span id="detailDateTime" style="color:var(--primary);"></span></p>
            <p style="margin:8px 0;"><strong>Reason:</strong> <span id="detailReason"></span></p>
        </div>
        <div style="text-align:center; margin-top:25px;">
            <button class="btn" style="background:#e74c3c; color:white;" onclick="cancelAppointment()">
                Cancel Appointment
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
// Global state
let selectedCounselor = null;
let selectedSlot = null;
let currentAppointmentId = null;

function openBookingModal() {
    document.getElementById('bookingModal').style.display = 'flex';
    nextStep(1);
    loadCounselors();
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

function nextStep(n) {
    document.querySelectorAll('#step1Content, #step2Content, #step3Content').forEach(el => el.style.display = 'none');
    document.getElementById('step' + n + 'Content').style.display = 'block';
    document.querySelectorAll('.booking-steps .step').forEach((s,i) => s.classList.toggle('active', i === n-1));
}

function loadCounselors() {
    const grid = document.getElementById('counselorGrid');
    grid.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-light);">Loading...</p>';

    fetch('api/get_counselors.php')
        .then(r => r.json())
        .then(counselors => {
            let html = '';
            counselors.forEach(c => {
                const initials = (c.fname[0] + c.lname[0]).toUpperCase();
                const name = [c.fname, c.mi ? c.mi + '.' : '', c.lname].filter(Boolean).join(' ');
                html += `<div class="counselor-card" data-id="${c.counselor_id}" data-name="${name}" data-initials="${initials}"
                    style="border:2px solid #eee;border-radius:16px;padding:20px;text-align:center;cursor:pointer;background:white;">
                    <div style="width:70px;height:70px;background:var(--primary);color:white;border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:24px;">
                        ${initials}
                    </div>
                    <h4>${name}</h4>
                    <small>${c.title || 'Guidance Counselor'}</small>
                </div>`;
            });
            grid.innerHTML = html;

            document.querySelectorAll('#counselorGrid .counselor-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('#counselorGrid .counselor-card').forEach(el => {
                        el.style.borderColor = '#eee';
                        el.style.background = 'white';
                    });
                    this.style.borderColor = 'var(--primary)';
                    this.style.background = '#f3e8ff';

                    selectedCounselor = {
                        id: this.dataset.id,
                        name: this.dataset.name,
                        initials: this.dataset.initials
                    };
                    document.getElementById('counselorNextBtn').disabled = false;
                });
            });
        });
}

document.getElementById('datePicker').addEventListener('change', function() {
    if (!this.value || !selectedCounselor) return;

    fetch(`api/slots.php?counselor_id=${selectedCounselor.id}&date=${this.value}`)
        .then(r => r.json())
        .then(slots => {
            const container = document.getElementById('timeSlots');
            let html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;">';
            slots.forEach(s => {
                if (s.taken) {
                    html += `<div style="padding:12px;border-radius:12px;text-align:center;background:#fff;border:2px solid #e74c3c;color:#e74c3c;opacity:0.7;pointer-events:none;">${s.time}</div>`;
                } else {
                    html += `<div style="padding:12px;border-radius:12px;text-align:center;background:#f3e8ff;border:2px solid var(--primary);cursor:pointer;" 
                                onclick="selectSlot('${s.datetime}','${s.time}')">
                                ${s.time}
                             </div>`;
                }
            });
            html += '</div>';
            container.innerHTML = html;
        });
});

function selectSlot(datetime, time) {
    selectedSlot = datetime;
    document.getElementById('nextToConfirm').disabled = false;
    document.querySelectorAll('#timeSlots div div').forEach(el => el.style.background = '#f3e8ff');
    event.target.style.background = 'var(--primary)';
    event.target.style.color = 'white';
}

document.getElementById('nextToConfirm').onclick = () => {
    document.getElementById('finalName').textContent = selectedCounselor.name;
    document.getElementById('finalDateTime').textContent = selectedSlot.replace(' ', 'T').slice(0, 16).replace('T', ' ');
    document.getElementById('finalPhoto').textContent = selectedCounselor.initials;
    nextStep(3);
};

function confirmBooking() {
    const reason = document.getElementById('reasonField').value.trim();
    if (!reason) {
        alert('Please enter a reason for your visit.');
        return;
    }

    fetch('api/appointments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            counselor_id: selectedCounselor.id,
            datetime: selectedSlot,
            reason: reason
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Appointment successfully booked!');
            closeBookingModal();
            location.reload();
        } else {
            alert('Booking failed: ' + res.message);
        }
    });
}

document.getElementById('bookingModal').onclick = function(e) {
    if (e.target === this) closeBookingModal();
};

function toggleProfileDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('profileDropdown');
    dd.setAttribute('aria-hidden', dd.getAttribute('aria-hidden') === 'true' ? 'false' : 'true');
}
document.addEventListener('click', e => {
    const dd = document.getElementById('profileDropdown');
    const btn = document.getElementById('profileBtn');
    if (dd && dd.getAttribute('aria-hidden') === 'false' && !dd.contains(e.target) && !btn.contains(e.target)) {
        dd.setAttribute('aria-hidden', 'true');
    }
});

// FullCalendar
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
                $isApproved = isset($e['status']) && $e['status'] === 'approved';
            ?>
            {
                title: 'Session with <?= htmlspecialchars($cname) ?>',
                start: '<?= $e['appointment_date'] ?>',
                color: <?= $isApproved ? "'#27ae60'" : "'#8e44ad'" ?>,
                textColor: 'white',
                extendedProps: {
                    appointment_id: <?= (int)$e['appointment_id'] ?>,
                    counselor: '<?= htmlspecialchars($cname) ?>',
                    initials: '<?= strtoupper($e['fname'][0] . $e['lname'][0]) ?>',
                    datetime: '<?= $formatted ?>',
                    reason: '<?= htmlspecialchars($e['Appointment_desc'] ?? 'Not specified') ?>',
                    status: '<?= isset($e['status']) ? $e['status'] : '' ?>'
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

function cancelAppointment() {
    if (!currentAppointmentId || !confirm("Are you sure you want to cancel this appointment?")) return;

    fetch('api/cancel_appointments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ appointment_id: currentAppointmentId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert("Appointment cancelled successfully!");
            document.getElementById('appointmentDetailModal').style.display = 'none';
            location.reload();
        } else {
            alert("Failed: " + (res.message || "Unknown error"));
        }
    });
}

<?php
// Clear notifications
if (isset($_GET['clear_notifications'])) {
    $notif_file = __DIR__ . "/sessions/student_{$student_id}_notifs.json";
    if (file_exists($notif_file)) unlink($notif_file);
    $_SESSION['student_notifications'] = [];
    echo "<script>location.href = location.href.split('?')[0];</script>";
}
?>

// Open chat with the student's counselor
async function openChatWithCounselor() {
    try {
        const res = await fetch('api/get_my_counselor.php');
        const data = await res.json();

        if (data.counselor_id) {
            window.location.href = `chat.php?with=${data.counselor_id}`;
        } else {
            alert('No approved appointment found. Please book and get your appointment approved first.');
        }
    } catch (e) {
        alert('Error connecting to chat. Please try again.');
        console.error(e);
    }
}

// Update unread count badge
function updateChatBadge() {
    fetch('api/unread_count.php')
        .then(r => r.json())
        .then(d => {
            const badge = document.getElementById('unreadBadge');
            const count = parseInt(d.count) || 0;
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        })
        .catch(() => {
            // Silent fail if API down
        });
}

// Auto-update badge every 5 seconds
setInterval(updateChatBadge, 5000);
updateChatBadge();

let currentFeedbackApptId = null;

function openStudentAppointmentsPanel() {
    fetch('api/get_student_appointments.php')
        .then(r => r.json())
        .then(data => {
            const body = document.getElementById('studentApptBody');
            if (!data.appointments || data.appointments.length === 0) {
                body.innerHTML = `<div style="text-align:center;padding:120px;color:#888;"><i class="fas fa-calendar-times fa-4x"></i><h3>No appointments yet</h3></div>`;
                return;
            }

            let html = '';
            data.appointments.forEach(a => {
                const initials = (a.counselor_name.match(/\b\w/g) || []).slice(0,2).join('').toUpperCase();
                const date = new Date(a.appointment_date).toLocaleString('en-US', { weekday:'short', month:'short', day:'numeric', hour:'numeric', minute:'2-digit' });
                const statusColor = a.status === 'approved' ? '#27ae60' : a.status === 'done' ? '#3498db' : '#8e44ad';
                const statusText = a.status === 'done' ? 'Completed' : a.status === 'approved' ? 'Approved' : 'Pending';

                html += `
                <div class="appt-card" style="background:white;border-radius:18px;padding:20px;margin-bottom:18px;box-shadow:0 8px 25px rgba(0,0,0,0.12);border-left:6px solid ${statusColor};">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <div style="font-weight:bold;color:#2c3e50;">${date}</div>
                        <span style="padding:6px 14px;border-radius:30px;font-size:13px;font-weight:bold;color:white;background:${statusColor};">${statusText}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;margin:14px 0;">
                        <div style="width:56px;height:56px;background:#8e44ad;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:20px;">
                            ${initials}
                        </div>
                        <div>
                            <strong>${a.counselor_name}</strong><br>
                            <small>Guidance Counselor</small>
                        </div>
                    </div>
                    <div style="background:#f5f0ff;padding:12px;border-radius:12px;font-size:14.5px;">
                        <strong>Reason:</strong> ${a.Appointment_desc || 'Not specified'}
                    </div>
                    ${a.status === 'done' && !a.feedback_given ? `
                    <div style="margin-top:16px;text-align:center;">
                        <button class="btn" style="background:#27ae60;" onclick="openFeedbackModal(${a.appointment_id})">
                            Leave Feedback
                        </button>
                    </div>` : a.status === 'done' ? `<small style="color:#27ae60;display:block;margin-top:12px;text-align:center;"><i class="fas fa-check"></i> Feedback submitted</small>` : ''}
                </div>`;
            });
            body.innerHTML = html;
        });

    document.getElementById('studentAppointmentsPanel').style.right = '0';
    document.getElementById('studentApptOverlay').style.display = 'block';
}

function closeStudentAppointmentsPanel() {
    document.getElementById('studentAppointmentsPanel').style.right = '-520px';
    document.getElementById('studentApptOverlay').style.display = 'none';
}

function openFeedbackModal(appt_id) {
    currentFeedbackApptId = appt_id;
    selectedRating = 0;
    
    // Reset stars
    document.querySelectorAll('.star').forEach(star => {
        star.style.color = '#ddd';
    });
    document.getElementById('ratingText').textContent = 'Tap a star to rate';
    document.getElementById('ratingText').style.color = '#888';
    document.getElementById('feedbackText').value = '';

    // Close the appointments panel + open feedback modal
    closeStudentAppointmentsPanel();
    document.getElementById('feedbackModal').style.display = 'flex';
}

function setRating(rating) {
    selectedRating = rating;
    const stars = document.querySelectorAll('.star');
    const ratingTexts = [
        '', 
        'Very dissatisfied', 
        'Dissatisfied', 
        'Neutral', 
        'Satisfied', 
        'Very satisfied'
    ];
    const ratingColors = ['#e74c3c', '#e67e22', '#f39c12', '#27ae60', '#27ae60'];

    stars.forEach((star, index) => {
        if (index < rating) {
            star.style.color = ratingColors[rating - 1];
        } else {
            star.style.color = '#ddd';
        }
    });

    document.getElementById('ratingText').textContent = ratingTexts[rating];
    document.getElementById('ratingText').style.color = ratingColors[rating - 1];
}


document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('mouseenter', function() {
        if (selectedRating === 0) {
            const value = this.dataset.value;
            document.querySelectorAll('.star').forEach((s, i) => {
                s.style.color = i < value ? '#f39c12' : '#ddd';
            });
        }
    });
});

document.querySelector('.star-rating').addEventListener('mouseleave', function() {
    if (selectedRating === 0) {
        document.querySelectorAll('.star').forEach(s => s.style.color = '#ddd');
        document.getElementById('ratingText').textContent = 'Tap a star to rate';
        document.getElementById('ratingText').style.color = '#888';
    }
});

function submitFeedback() {
    if (selectedRating === 0) {
        alert('Please select a star rating');
        return;
    }

    const comment = document.getElementById('feedbackText').value.trim();

    fetch('api/submit_feedback.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            appointment_id: currentFeedbackApptId,
            rating: selectedRating,
            comment: comment
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Thank you so much for your feedback! It helps us improve.');
            document.getElementById('feedbackModal').style.display = 'none';
            setTimeout(() => openStudentAppointmentsPanel(), 400);
        } else {
            alert('Failed to submit. Please try again.');
        }
    });
}
</script>

<!-- Student Appointments Panel -->
<div id="studentAppointmentsPanel" style="position:fixed;top:0;right:-520px;width:500px;height:100vh;background:white;box-shadow:-15px 0 50px rgba(0,0,0,0.3);z-index:1100;transition:right 0.45s cubic-bezier(0.25,0.8,0.25,1);display:flex;flex-direction:column;font-family:'Segoe UI',sans-serif;">
    <div style="background:linear-gradient(135deg,#8e44ad,#9b59b6);color:white;padding:22px 25px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 4px 15px rgba(0,0,0,0.15);">
        <h3 style="margin:0;font-size:24px;font-weight:600;">My Appointments</h3>
        <span onclick="closeStudentAppointmentsPanel()" style="font-size:36px;cursor:pointer;opacity:0.9;transition:0.3s;" onmouseover="this.style.opacity=1;this.style.transform='rotate(90deg)'" onmouseout="this.style.opacity=0.9;this.style.transform='none'">×</span>
    </div>
    <div style="flex:1;overflow-y:auto;padding:20px;background:#f8f9fa;" id="studentApptBody">
        <div style="text-align:center;padding:100px 20px;color:#888;">
            <i class="fas fa-spinner fa-spin fa-4x"></i><br><br>Loading your appointments...
        </div>
    </div>
</div>
<div id="studentApptOverlay" onclick="closeStudentAppointmentsPanel()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1099;"></div>

<!-- Feedback Modal -->
<div class="modal" id="feedbackModal">
    <div class="modal-content" style="max-width:520px;">
        <span class="close-modal" onclick="document.getElementById('feedbackModal').style.display='none'">×</span>
        <h3 style="text-align:center;color:#8e44ad;margin-bottom:20px;">How was your session?</h3>
        
        <div style="text-align:center;margin:30px 0;">
            <p style="font-size:18px;margin-bottom:25px;color:#444;">
                How satisfied were you with your counseling session?
            </p>
            
            <!-- Star Rating -->
            <div class="star-rating" style="display:flex;justify-content:center;gap:12px;margin:25px 0;font-size:42px;">
                <span class="star" data-value="1" onclick="setRating(1)">★</span>
                <span class="star" data-value="2" onclick="setRating(2)">★</span>
                <span class="star" data-value="3" onclick="setRating(3)">★</span>
                <span class="star" data-value="4" onclick="setRating(4)">★</span>
                <span class="star" data-value="5" onclick="setRating(5)">★</span>
            </div>
            
            <div style="margin:20px 0;">
                <span id="ratingText" style="font-size:19px;color:#8e44ad;font-weight:600;">
                    Tap a star to rate
                </span>
            </div>
        </div>

        <textarea id="feedbackText" placeholder="Share more about your experience (optional, but really helps us improve)..." 
                  style="width:100%;height:130px;padding:16px;border-radius:14px;border:2px solid #eee;font-size:15.5px;margin-bottom:20px;font-family:inherit;"></textarea>
        
        <div style="text-align:center;">
            <button class="btn" onclick="submitFeedback()" style="padding:14px 32px;font-size:16px;">
                Submit Feedback
            </button>
        </div>
    </div>
</div>

</body>
</html>