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
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                 background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 9999; }
        .modal.active { display: flex; }

        .booking-steps { display: flex; justify-content: center; gap: 25px; margin: 25px 0; font-weight: 600; }
        .step { padding: 10px 20px; border-radius: 50px; background: #f0e6ff; color: #8e44ad; }
        .step.active { background: #8e44ad; color: white; font-weight: 800; }

        .counselor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin: 20px; }
        .counselor-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);
                          cursor: pointer; transition: all 0.3s; border: 3px solid transparent; text-align: left; }
        .counselor-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(139,89,182,0.25); }
        .counselor-card.selected { border-color: #8e44ad; background: #faf5ff; }
        .counselor-photo { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 4px solid #D8BEE5; }

        .calendar-container { height: 520px; margin: 20px; background: white; border-radius: 16px; padding: 10px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); }

        .chat-header, .chat-container, .chat-message { /* your existing styles */ }
        .close-modal { position: absolute; top: 15px; right: 25px; font-size: 34px; cursor: pointer; color: #aaa; z-index: 10; }
        .close-modal:hover { color: #000; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">Student Portal • <?= htmlspecialchars($name) ?></div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="dashboard-content">
    <div class="page-title">
        <h1>Hello, <?= $firstName ?>!</h1>
        <p>We're here to support you anytime</p>
    </div>

    <div class="action-cards">
        <div class="action-card" onclick="openBookingModal()">
            <i class="fa-solid fa-calendar-plus"></i>
            <h3>Book Appointment</h3>
            <p>Schedule a confidential session</p>
        </div>
        <div class="action-card" onclick="openEmergencyChat()">
            <i class="fa-solid fa-comment-medical"></i>
            <h3>I'm Not Okay</h3>
            <p>Instant private chat with a counselor</p>
        </div>
        <div class="action-card" onclick="location.href='resources.php'">
            <i class="fa-solid fa-heart"></i>
            <h3>Mental Health Resources</h3>
            <p>Tips, hotlines & support</p>
        </div>
    </div>

    <div class="card-grid">
        <div class="widget">
            <h3>My Schedule</h3>
            <div id="calendar"></div>
            <p style="margin-top:10px; font-size:14px; color:#8e44ad;">Click any time slot to book</p>
        </div>

        <div class="widget">
            <h3>My Upcoming Appointments</h3>
            <div style="margin-top:15px;">
                <?php foreach ($events as $e):
                    $cname = trim($e['fname'] . ' ' . ($e['mi'] ? $e['mi'].'. ' : '') . ' ' . $e['lname']);
                    $dt = new DateTime(explode("\n", $e['appointment_desc'])[0] ?? $e['appointment_desc']);
                    $reason = explode("\nReason: ", $e['appointment_desc'])[1] ?? 'Counseling Session';
                ?>
                <div class="appointment-item" style="background:#f8f5ff; border-radius:12px; padding:15px; margin-bottom:15px;">
                    <div><strong><?= $dt->format('M j, Y • g:i A') ?></strong><br>
                        <small><?= htmlspecialchars($cname) ?> • <?= htmlspecialchars($reason) ?></small>
                    </div>
                    <div><span style="color:#27ae60; font-weight:bold;">Confirmed</span></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($events)): ?><p>No upcoming appointments yet.</p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="emergency">In Crisis? Call Hopeline PH: <strong>0917-558-4673</strong></div>
</div>

<!-- BOOKING MODAL: Counselor → Calendar → Reason -->
<div class="modal" id="bookingModal">
    <div class="modal-content" style="max-width:820px; max-height:92vh; overflow-y:auto; position:relative;">
        <span class="close-modal" onclick="closeBookingModal()">×</span>

        <div class="booking-steps">
            <div class="step active" id="step1">1. Choose Counselor</div>
            <div class="step" id="step2">2. Pick Time</div>
            <div class="step" id="step3">3. Reason</div>
        </div>

        <!-- Step 1: Choose Counselor -->
        <div id="step1Content">
            <h3 style="text-align:center; margin:20px 0;">Who would you like to meet with?</h3>
            <div class="counselor-grid" id="counselorGrid">
                <!-- Loaded via JS -->
            </div>
        </div>

        <!-- Step 2: Calendar (perfectly fitted) -->
        <div id="step2Content" style="display:none;">
            <div style="text-align:center; margin:20px 0;">
                <img id="selectedCounselorPhoto" src="" style="width:90px; height:90px; border-radius:50%; object-fit:cover; margin-bottom:12px;">
                <h3>Schedule with <span id="selectedCounselorName"></span></h3>
                <p style="color:#8e44ad; font-weight:600;" id="selectedCounselorTitle"></p>
            </div>
            <div class="calendar-container">
                <div id="counselorCalendar"></div>
            </div>
            <div style="text-align:center; margin:20px 0;">
                <button class="btn" onclick="backToCounselors()">← Back</button>
            </div>
        </div>

        <!-- Step 3: Reason -->
        <div id="step3Content" style="display:none; padding:20px;">
            <h3 style="text-align:center; margin-bottom:20px;">Tell us more</h3>
            <div style="display:flex; align-items:center; gap:20px; margin-bottom:20px;">
                <img id="finalPhoto" src="" style="width:80px; height:80px; border-radius:50%; object-fit:cover;">
                <div>
                    <strong id="finalName"></strong><br>
                    <small style="color:#8e44ad;">Counseling Session</small>
                </div>
            </div>
            <textarea id="reasonField" rows="6" placeholder="Briefly share what you'd like to talk about... (optional)"
                      style="width:100%; padding:16px; border-radius:12px; border:1px solid #D8BEE5; resize:none; font-family:inherit;"></textarea>
            <div style="text-align:center; margin-top:25px;">
                <button class="btn" onclick="backToCalendar()">← Back</button>
                <button class="btn" style="background:#8e44ad; color:white; margin-left:12px;" onclick="confirmBooking()">Confirm & Book</button>
            </div>
        </div>
    </div>
</div>

<!-- EMERGENCY CHAT -->
<div class="modal" id="emergencyChatModal">
    <div class="modal-content" style="max-width:520px;" onclick="event.stopPropagation()">
        <div class="chat-header">
            <div class="counselor-avatar">
                <i class="fa-solid fa-user-tie" style="font-size:28px; color:#4b2b63;"></i>
            </div>
            <div class="counselor-info">
                <h4>Dr. Emily Carter</h4>
                <small>Senior Counselor • Available Now</small>
            </div>
            <span class="close-modal" onclick="closeModal('emergencyChatModal')">×</span>
        </div>
        <div id="emergencyMessages" class="chat-container"></div>
        <div style="display:flex; gap:10px; padding:15px; background:white;">
            <input type="text" id="emergencyInput" placeholder="Type your message..." onkeypress="if(event.key==='Enter') sendEmergencyMessage()" style="flex:1; padding:14px; border-radius:12px; border:1px solid #D8BEE5;">
            <button onclick="sendEmergencyMessage()" style="padding:14px 20px; background:#8e44ad; color:white; border:none; border-radius:12px;">Send</button>
        </div>
    </div>
</div>

<!-- APPOINTMENT CHAT -->
<div class="modal" id="appointmentChatModal">
    <div class="modal-content" style="max-width:520px;" onclick="event.stopPropagation()">
        <div class="chat-header">
            <div class="counselor-avatar">
                <i class="fa-solid fa-user-tie" style="font-size:28px; color:#4b2b63;"></i>
            </div>
            <div class="counselor-info">
                <h4 id="chatCounselorName">Dr. Joshua Cruz</h4>
                <small id="chatCounselorTitle">Senior Counselor</small>
            </div>
            <span class="close-modal" onclick="closeModal('appointmentChatModal')">×</span>
        </div>
        <div id="appointmentMessages" class="chat-container"></div>
        <div style="display:flex; gap:10px; padding:15px; background:white;">
            <input type="text" id="appointmentInput" placeholder="Type your message..." onkeypress="if(event.key==='Enter') sendAppointmentMessage()" style="flex:1; padding:14px; border-radius:12px; border:1px solid #D8BEE5;">
            <button onclick="sendAppointmentMessage()" style="padding:14px 20px; background:#8e44ad; color:white; border:none; border-radius:12px;">Send</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="js/booking.js"></script>
</body>
</html>