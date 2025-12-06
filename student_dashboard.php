<?php
session_start();
require_once 'db/connection.php';

// --- Authentication and Setup ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['fullname'];
$firstName = explode(' ', $name)[0];
$student_id = $_SESSION['user_id'];

// --- Notification Logic: Load and clear file-based notifications ---
$notif_file = __DIR__ . "/sessions/student_{$student_id}_notifs.json";
if (file_exists($notif_file)) {
    $file_notifs = json_decode(file_get_contents($notif_file), true) ?: [];
    if (!isset($_SESSION['student_notifications'])) $_SESSION['student_notifications'] = [];
    $_SESSION['student_notifications'] = array_merge($_SESSION['student_notifications'], $file_notifs);
    // Clear the file after loading into the session
    file_put_contents($notif_file, json_encode([]));
}

// --- Database Query: Load Appointments for FullCalendar ---
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

// --- Clear Notifications Action ---
if (isset($_GET['clear_notifications'])) {
    // Attempt to delete the notifications file (though it should be empty now)
    $notif_file_to_clear = __DIR__ . "/sessions/student_{$student_id}_notifs.json";
    if (file_exists($notif_file_to_clear)) unlink($notif_file_to_clear);
    
    // Clear the session array
    $_SESSION['student_notifications'] = [];
    
    // Redirect to clear the GET parameter
    header("Location: student_portal.php");
    exit;
}
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
    /* Define the new Color Palette */
    :root {
        --primary: #ff00ddff; /* Aqua/Teal - Main Action Color */
        --primary-dark: #ff00ddff; /* Darker Teal */
        --secondary: #ff00ddff; /* Dark Blue - Text/Headers */
        --danger: #ef4444; /* Red - For emergency/cancel */
        --resource-color: #2ecc71; /* Green for resources */
        --background-light: #fcfeff; /* Light Gray/Blue background */
        --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
        --text-dark: var(--secondary);
        --text-light: #64748b;
    }

        body { 
            background: var(--background-light); 
            margin: 0; 
            font-family: 'Inter', 'Segoe UI', sans-serif; /* A modern font stack */
            color: var(--text-dark);
        }
        
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
        .logo { 
            font-weight: 800; 
            font-size: 24px; 
            color: var(--secondary); 
        }
        .main-content { max-width: 1200px; margin: 40px auto; padding: 0 20px; 
        }

        .page-title {
            text-align: center;
            margin: 0 0 50px;
        }
        .page-title h1 {
            font-size: 40px;
            color: var(--secondary);
            margin: 0;
            font-weight: 800;
        }
        .page-title p {
            color: var(--text-light);
            font-size: 18px;
            margin-top: 5px;
        }
        .card h3 {
            color: var(--success);
        }
        
        /* Buttons */
        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
            border: none;
            color: white;
            background: var(--primary);
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #cbd5e1;
            color: var(--text-dark);
        }
        .btn-secondary:hover {
            background: #94a3b8;
            color: white;
        }

        /* Profile Dropdown Styling */
        .profile-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }
        .avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .profile-dropdown {
            position: absolute;
            right: 20px;
            top: 70px;
            background: white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-radius: 16px;
            width: 200px;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            pointer-events: none;
            /* Override existing profile-dropdown styles from internal or external CSS if necessary */
        }
        .profile-dropdown[aria-hidden="false"] {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .profile-dropdown .profile-row {
            background: var(--primary-dark) !important;
            color: white;
            padding: 15px 20px !important;
            border-radius: 16px 16px 0 0 !important;
        }
        .profile-dropdown .info-name {
            font-weight: 600;
            font-size: 16px;
        }
        .profile-dropdown small {
            color: #e0f7fa;
        }
        .profile-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .profile-dropdown li a {
             padding:10px 20px;
             display:block;
             text-decoration:none;
             color:var(--text-dark);
             font-size:15px;
             transition: background 0.3s ease, color 0.3s ease;
        }
        .profile-dropdown li a:hover {
            background: var(--background-light);
            color: var(--primary);
        }
        .profile-dropdown li:last-child a:hover {
            border-radius: 0 0 16px 16px; 
        }

        /* Notification Styling */
        #notifDropdown {
            box-shadow: 0 15px 40px rgba(0,0,0,0.18) !important;
            border-radius:16px !important;
            border:1px solid #e2e8f0 !important;
        }
        #notifDropdown > div:first-child {
            background:linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
        }
        .notif-item {
            background:#ffffff !important;
        }
        .notif-item:hover {
            background:#f0f9ff !important;
        }
        
        /* Action Cards */
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        .card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border-left: 5px solid transparent; /* New design touch */
            display: flex;
            flex-direction: column;
            justify-content: space-between; 
            height: 100%; 
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 188, 212, 0.2); /* Highlight shadow */
            border-left: 5px solid var(--primary);
        }
        .card i {
            font-size: 55px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        .card h3 { 
            margin: 10px 0 10px; 
            color: var(--text-dark); 
            font-size: 24px; 
        }
        .card p { 
            color: var(--text-light); 
            margin-bottom: 25px; 
            font-size: 16px;
            flex-grow: 1;
        }
       .action-cards .card:nth-child(1):hover {
            border-left: 5px solid var(--info);
            box-shadow: 0 15px 30px rgba(52, 152, 219, 0.2); 
        }
        .action-cards .card:nth-child(2):hover {
            border-left: 5px solid var(--resource-color);

            box-shadow: 0 15px 30px rgba(39, 174, 96, 0.2);
        }
        .action-cards .card:nth-child(3):hover {
            border-left: 5px solid var(--danger);
            box-shadow: 0 15px 30px rgba(231, 76, 60, 0.2); 
        }
        
        /* Card-specific overrides for color consistency */
        .action-cards .card:nth-child(1) i { color: var(--info); }
        .action-cards .card:nth-child(1) h3 { color: var(--info); }
        .action-cards .card:nth-child(1) .btn { background: var(--info); }
        .action-cards .card:nth-child(2) i { color: var(--resource-color); }
        .action-cards .card:nth-child(2) h3 { color: var(--resource-color); } 
        .action-cards .card:nth-child(3) i { color: var(--danger); }
        .action-cards .card:nth-child(3) h3 { color: var(--danger); }
        .action-cards .card:nth-child(2) .btn { background: var(--resource-color); }
        .action-cards .card:nth-child(3) .btn { background: var(--danger); }

        /* Big Calendar */
        #calendar-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin: 0 auto 60px;
        }
        #calendar-container h2 {
            text-align: center;
            color: var(--secondary);
            margin-bottom: 25px;
            font-size: 30px;
            font-weight: 700;
        }
        #calendar { height: 720px !important; }

        /* FullCalendar overrides for new UI */
        .fc-button-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            border-radius: 8px !important;
        }
        .fc-button-primary:hover, .fc-button-primary:not(:disabled):active, .fc-button-primary:focus {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
            box-shadow: none !important;
        }
        .fc-toolbar-title {
            font-size: 1.8em !important;
            font-weight: 700 !important;
            color: var(--secondary) !important;
        }
        .fc-daygrid-event {
            border-radius: 6px;
            padding: 5px 8px;
            font-weight: 500;
        }
        
        /* Floating Chat Button */
        .floating-chat {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--danger); /* High contrast for floating chat */
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.4);
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .floating-chat:hover {
            transform: scale(1.1);
            background: #c0392b;
        }
        .floating-chat .badge {
            background: #ffc107; /* Warning yellow for unread */
            color: #1f2937;
            border: 2px solid white;
        }

        /* Modals */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; 
            z-index: 10000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }
        .modal-content { 
            background-color: #fefefe;
            margin: auto;
            border: 1px solid #888;
            border-radius: 20px; 
            padding: 30px 40px; 
            position: relative;
            max-width: 90%; 
        }
        .close-modal { 
            color: #aaa;
            float: right;
            font-size: 36px;
            font-weight: bold;
            position: absolute;
            top: 15px; 
            right: 25px; 
            cursor: pointer;
            color: #94a3b8;
        }
        .close-modal:hover,
        .close-modal:focus {
            color: var(--danger);
            text-decoration: none;
            cursor: pointer;
        }

        /* Booking Modal Steps */
        .booking-steps .step {
            padding: 8px 15px;
            border-radius: 20px;
            color: var(--text-light);
            font-weight: 500;
            background: #e2e8f0;
            transition: all 0.3s ease;
        }
        .booking-steps .step.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 188, 212, 0.3);
        }

        /* Counselor Card in Modal */
        .counselor-card {
            border: 2px solid #e2e8f0 !important;
            border-radius: 16px !important;
            transition: all 0.3s ease;
        }
        .counselor-card:hover {
            box-shadow: 0 4px 15px rgba(0, 188, 212, 0.2);
        }
        /* Style for the *selected* counselor card */
        .counselor-card.selected {
            border-color: var(--primary) !important;
            background: #e0f7fa !important;
        }
        .counselor-card.selected > div:first-child {
             background: var(--primary) !important;
        }

        /* Time Slots in Modal */
        #timeSlots div div {
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            border: 2px solid #e2e8f0 !important;
            transition: all 0.2s ease;
        }
        #timeSlots div div:hover {
            background: #d1f2f8 !important;
        }
        /* Style for the *selected* time slot */
        #timeSlots div div.selected {
            background: var(--primary) !important;
            color: white;
            border-color: var(--primary) !important;
        }
        
        /* Emergency Chat Modal */
        #emergencyChatModal .modal-content {
            padding: 0 !important;
        }
        #emergencyChatModal .chat-header {
            background: var(--danger) !important;
            border-radius: 16px 16px 0 0 !important;
        }
        #emergencyInput {
            border: 2px solid var(--danger) !important;
            border-radius: 16px !important;
        }

        /* Appointment Detail Modal */
        #appointmentDetailModal div[style*='background:#e0f7fa'] {
            background: #e0f7fa !important;
            border-left: 5px solid var(--primary);
        }
        #appointmentDetailModal strong {
            color: var(--secondary);
        }

        /* Custom FullCalendar Nav Spacing (as requested by user) */
        .fc-header-toolbar {
            /* This targets the entire header area */
            align-items: center;
            display: flex;
            justify-content: space-between;
        }
        .fc-toolbar-chunk:first-child {
            /* This is usually where the Prev/Next/Today buttons are */
            display: flex;
            align-items: center;
        }
        .fc-toolbar-chunk:first-child .fc-button-group {
            /* Targeting the Prev/Next button group */
            margin-right: 15px; /* Adds space between the arrows and 'Today' if it exists */
        }
        .fc-prev-button {
            margin-right: 4px; /* Space between the two arrow buttons */
        }

    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">Student Portal</div>
    <div class="nav-right" style="display: flex; align-items: center; gap: 20px;">
        <div style="position: relative;">
            <button id="notifBtn" onclick="toggleNotifDropdown(event)" style="background:none;border:none;cursor:pointer;position:relative;font-size:24px;color:var(--text-dark);">
                <i class="fas fa-bell"></i>
                <?php if (!empty($_SESSION['student_notifications'])): ?>
                    <span style="position:absolute;top:-8px;right:-8px;background:var(--danger);color:white;font-size:11px;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;">
                        <?= count($_SESSION['student_notifications']) ?>
                    </span>
                <?php endif; ?>
            </button>

            <div id="notifDropdown" style="display:none;position:absolute;right:0;top:50px;background:white;box-shadow:0 15px 40px rgba(0,0,0,0.18);border-radius:16px;min-width:380px;max-height:80vh;overflow:hidden;z-index:1001;border:1px solid #eee;">
                <div style="padding:18px 22px;font-weight:bold;color:white;border-radius:16px 16px 0 0; background:linear-gradient(135deg, var(--primary), var(--primary-dark));">
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
                            <div class="notif-item" style="padding:18px 22px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:16px;background:white;transition:all 0.3s ease;">
                                <div style="width:48px;height:48px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:var(--text-dark);font-size:15px;line-height:1.4;">
                                        <?= htmlspecialchars($msg) ?>
                                    </div>
                                    <?php if ($details): ?>
                                        <div style="color:var(--text-light);font-size:14px;margin-top:4px;">
                                            <?= htmlspecialchars($details) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="color:var(--text-light);font-size:13px;margin-top:6px;">
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
                    <a href="?clear_notifications=1" style="color:var(--primary);font-weight:500;font-size:14px;">Clear all notifications</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <button id="profileBtn" class="profile-btn" onclick="toggleProfileDropdown(event)">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
        </button>
           <div id="profileDropdown" class="profile-dropdown" aria-hidden="true">
                <div class="profile-row" style="display:flex;align-items:center;gap:15px;padding:12px 10px;border-radius:16px 16px 0 0;background: var(--primary-dark);">
                    <div class="avatar" style=" width:20px;height:20px; background:white; color:var(--primary-dark);"><i class="fas fa-user-graduate"></i></div>
                    <div class="info">
                        <div class="info-name"><?= htmlspecialchars($name) ?></div>
                        <small>Student</small>
                    </div>
                </div>
                <ul>
                    <li><a href="student_profile.php" style="border-bottom: 1px solid #f1f5f9;">
                        <i class="fas fa-user-circle" style="margin-right:10px;"></i> My Profile
                    </a></li>
                    <li><a href="logout.php" style="color:var(--danger);font-size:15px;">
                        <i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Logout
                    </a></li>
                </ul>
            </div>
    </div>
</div>

<div class="main-content">

    <div class="page-title">
        <h1>Hello, <?= htmlspecialchars($firstName) ?>!</h1>
        <p>We're here to support your mental health journey</p>
    </div>

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
            <button class="btn" onclick="window.location='resources.php'" style="background:var(--resource-color);">Explore Resources</button>
        </div>

        <div class="card">
            <i class="fas fa-headset"></i>
            <h3>Emergency Support</h3>
            <p>Immediate help when you need it most</p>
            <button class="btn" style="background:var(--danger);" onclick="openModal('emergencyChatModal')">Chat Now</button>
        </div>
    </div>
    
    <h2 style="text-align: center;"><i class="fas fa-calendar-alt"></i> My Appointments Calendar</h2>
<div id="calendar-container">
    <div style="display:flex; justify-content:flex-end; align-items:center; margin-bottom:20px;">
        <button class="btn" onclick="openStudentAppointmentsPanel()" style="padding:12px 24px; font-size:16px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: 0 4px 10px rgba(0, 188, 212, 0.4);">
            <i class="fas fa-list-ul"></i> View All Appointments
        </button>
    </div>
    <div id="calendar"></div>
</div>

</div>

<div class="floating-chat" id="chatFloatBtn" onclick="openChatWithCounselor()">
    <i class="fas fa-comment-medical"></i>
    <div class="badge" id="unreadBadge" style="display:none;">0</div>
</div>

<div class="modal" id="bookingModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeBookingModal()">×</span>
        <h3 style="text-align:center;margin-bottom:0;color:var(--secondary);">Book a Counseling Session</h3>
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
            <input type="date" id="datePicker" style="width:100%;padding:12px;border-radius:12px;border:2px solid #e2e8f0;margin-bottom:15px;">
            <div id="timeSlots" style="max-height:280px;overflow-y:auto;padding:10px;background:#f9f9f9;border-radius:12px;"></div>
            <div style="display:flex;justify-content:space-between;margin-top:20px;">
                <button class="btn btn-secondary" onclick="nextStep(1)">Back</button>
                <button class="btn" id="nextToConfirm" disabled>Next: Confirm</button>
            </div>
        </div>

        <div id="step3Content" style="display:none;">
            <h4>Confirmation</h4>
            <div style="display:flex;align-items:center;gap:15px;margin:20px 0; background:#e0f7fa; padding:15px; border-radius:12px;">
                <div id="finalPhoto" style="width:60px;height:60px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:20px;"></div>
                <div>
                    <strong>Counselor:</strong> <span id="finalName"></span><br>
                    <strong>Time:</strong> <span id="finalDateTime"></span>
                </div>
            </div>
            <textarea id="reasonField" rows="4" placeholder="Briefly describe what you'd like to discuss..." style="width:100%;padding:12px;border-radius:12px;border:2px solid #e2e8f0;"></textarea>
            <div style="display:flex;justify-content:space-between;margin-top:20px;">
                <button class="btn btn-secondary" onclick="nextStep(2)">Back</button>
                <button class="btn" onclick="confirmBooking()">Confirm Appointment</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="emergencyChatModal">
    <div class="modal-content" style="height: 90vh; max-width: 500px; display:flex; flex-direction:column; padding:0;">
        <div class="chat-header" style="background:var(--danger);color:white;padding:15px 25px;border-radius:16px 16px 0 0;text-align:center;position:relative;">
            <h4 style="margin:0;">Crisis Support • AI Assistant</h4>
            <span class="close-modal" onclick="closeModal('emergencyChatModal')" style="color:white; right: 15px;">×</span>
        </div>
        <div id="emergencyMessages" class="chat-container" style="flex:1;overflow-y:auto;padding:20px;background:#fff8f8;"></div>
        <div style="padding:15px;background:white;border-top:1px solid #eee;display:flex;gap:10px;">
            <input type="text" id="emergencyInput" placeholder="I'm here to help. How are you feeling?" style="flex:1;padding:12px;border-radius:16px;border:2px solid var(--danger);">
            <button class="btn small" style="background:var(--danger);" onclick="sendEmergencyMessage()">Send</button>
        </div>
    </div>
</div>

<div class="modal" id="appointmentDetailModal">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close-modal" onclick="document.getElementById('appointmentDetailModal').style.display='none'">×</span>
        <h3 style="text-align:center; margin-bottom:20px; color:var(--secondary);">Appointment Details</h3>
        <div style="text-align:center; margin-bottom:25px;">
            <div id="detailInitials" style="width:90px; height:90px; background:var(--primary); color:white; border-radius:50%; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; font-size:36px; font-weight:bold;"></div>
            <h4 style="margin:10px 0; color:var(--text-dark);" id="detailCounselor"></h4>
        </div>
        <div style="background:#e0f7fa; padding:15px; border-radius:12px; margin:15px 0; border-left: 5px solid var(--primary);">
            <p style="margin:8px 0;"><strong>Date & Time:</strong> <span id="detailDateTime" style="color:var(--primary-dark);"></span></p>
            <p style="margin:8px 0;"><strong>Reason:</strong> <span id="detailReason"></span></p>
            <input type="hidden" id="detailAppointmentId">
        </div>
        <div style="text-align:center; margin-top:25px;">
            <button class="btn" id="cancelBtn" style="background:var(--danger); color:white;" onclick="cancelAppointment()">
                Cancel Appointment
            </button>
            <p id="statusMessage" style="color:var(--primary); font-weight: bold; margin-top: 15px; display: none;"></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
// Global state
let selectedCounselor = null;
let selectedSlot = null;
let currentAppointmentId = null;
let calendar = null; // To hold the FullCalendar instance

// Helper to open/close generic modals (used for emergency chat)
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// --- Emergency Chat Logic ---
function sendEmergencyMessage() {
    const input = document.getElementById('emergencyInput');
    const message = input.value.trim();
    if (!message) return;

    const messagesContainer = document.getElementById('emergencyMessages');
    
    // User message display
    const userMsg = document.createElement('div');
    userMsg.style = "text-align:right; margin-bottom:15px;";
    userMsg.innerHTML = `<span style="background:var(--danger); color:white; padding:10px 15px; border-radius:15px 15px 0 15px; max-width:70%; display:inline-block;">${message}</span>`;
    messagesContainer.appendChild(userMsg);

    input.value = '';
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    // AI simulated response
    setTimeout(() => {
        const aiMsg = document.createElement('div');
        aiMsg.style = "text-align:left; margin-bottom:15px;";
        aiMsg.innerHTML = `<span style="background:#e2e8f0; color:var(--text-dark); padding:10px 15px; border-radius:15px 15px 15px 0; max-width:70%; display:inline-block;">Thank you for reaching out. Please take a deep breath. Can you tell me one thing you are feeling right now? If you are in immediate danger, please call emergency services.</span>`;
        messagesContainer.appendChild(aiMsg);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 1000);
}

// --- Notification & Profile Dropdown Logic ---
function toggleNotifDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('notifDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// Close dropdown if clicking outside
document.addEventListener('click', () => {
    const dropdown = document.getElementById('notifDropdown');
    if (dropdown) dropdown.style.display = 'none';
});

function toggleProfileDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('profileDropdown');
    const isHidden = dropdown.getAttribute('aria-hidden') === 'true';
    dropdown.setAttribute('aria-hidden', !isHidden);
}

// Close dropdown if clicking outside
document.addEventListener('click', () => {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) dropdown.setAttribute('aria-hidden', true);
});

// --- Appointment Booking Logic ---

function openBookingModal() {
    document.getElementById('bookingModal').style.display = 'flex';
    // Reset state and go to step 1
    selectedCounselor = null;
    selectedSlot = null;
    document.getElementById('counselorNextBtn').disabled = true;
    document.getElementById('datePicker').value = '';
    document.getElementById('timeSlots').innerHTML = '<p style="text-align:center;color:var(--text-light);padding:20px;">Please select a date.</p>';
    document.getElementById('nextToConfirm').disabled = true;
    document.getElementById('reasonField').value = '';
    
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
    
    if (n === 2) {
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('datePicker').min = today;
        document.getElementById('datePicker').value = today;
        // Load slots for today by default
        if (selectedCounselor) {
             loadTimeSlots(selectedCounselor.id, today);
        }
    }
    if (n === 3) {
        // Update confirmation details
        document.getElementById('finalName').textContent = selectedCounselor.name;
        document.getElementById('finalDateTime').textContent = selectedSlot.date + ' at ' + selectedSlot.time;
        document.getElementById('finalPhoto').textContent = selectedCounselor.initials;
    }
}

function loadCounselors() {
    const grid = document.getElementById('counselorGrid');
    grid.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-light);">Loading...</p>';

    fetch('api/get_counselors.php')
        .then(r => r.json())
        .then(counselors => {
            if (counselors.error) {
                 grid.innerHTML = `<p style="text-align:center;padding:40px;color:var(--danger);">${counselors.error}</p>`;
                 return;
            }

            let html = '';
            counselors.forEach(c => {
                const initials = (c.fname[0] + c.lname[0]).toUpperCase();
                const name = [c.fname, c.mi ? c.mi + '.' : '', c.lname].filter(Boolean).join(' ');
                html += `<div class="counselor-card" data-id="${c.counselor_id}" data-name="${name}" data-initials="${initials}"
                    style="border:2px solid #e2e8f0;border-radius:16px;padding:20px;text-align:center;cursor:pointer;background:white;">
                    <div style="width:70px;height:70px;background:var(--primary);color:white;border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:24px;">
                        ${initials}
                    </div>
                    <h4>${name}</h4>
                    <small style="color:var(--text-light);">${c.title || 'Guidance Counselor'}</small>
                </div>`;
            });
            grid.innerHTML = html;

            document.querySelectorAll('#counselorGrid .counselor-card').forEach(card => {
                card.addEventListener('click', function() {
                    // Deselect all
                    document.querySelectorAll('#counselorGrid .counselor-card').forEach(el => el.classList.remove('selected'));
                    // Select current
                    this.classList.add('selected');

                    selectedCounselor = {
                        id: this.dataset.id,
                        name: this.dataset.name,
                        initials: this.dataset.initials
                    };
                    document.getElementById('counselorNextBtn').disabled = false;
                });
            });

            document.getElementById('counselorNextBtn').onclick = () => nextStep(2);
        })
        .catch(e => {
            console.error("Error loading counselors:", e);
             grid.innerHTML = '<p style="text-align:center;padding:40px;color:var(--danger);">Failed to load counselors.</p>';
        });
}

function loadTimeSlots(counselorId, date) {
    const timeSlotsDiv = document.getElementById('timeSlots');
    timeSlotsDiv.innerHTML = '<p style="text-align:center;color:var(--text-light);padding:20px;">Loading time slots...</p>';
    selectedSlot = null;
    document.getElementById('nextToConfirm').disabled = true;

    fetch(`api/get_slots.php?counselor_id=${counselorId}&date=${date}`)
        .then(r => r.json())
        .then(slots => {
            if (slots.error) {
                timeSlotsDiv.innerHTML = `<p style="text-align:center;color:var(--danger);padding:20px;">${slots.error}</p>`;
                return;
            }
            if (slots.length === 0) {
                timeSlotsDiv.innerHTML = '<p style="text-align:center;color:var(--text-light);padding:20px;">No available slots on this date.</p>';
                return;
            }

            let html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:10px;">';
            slots.forEach(slot => {
                html += `<div class="time-slot" data-time="${slot.time}" data-date="${date}">${slot.time}</div>`;
            });
            html += '</div>';
            timeSlotsDiv.innerHTML = html;

            document.querySelectorAll('#timeSlots .time-slot').forEach(slotDiv => {
                slotDiv.addEventListener('click', function() {
                    // Deselect all
                    document.querySelectorAll('#timeSlots .time-slot').forEach(el => el.classList.remove('selected'));
                    // Select current
                    this.classList.add('selected');

                    selectedSlot = {
                        date: this.dataset.date,
                        time: this.dataset.time
                    };
                    document.getElementById('nextToConfirm').disabled = false;
                });
            });

            document.getElementById('nextToConfirm').onclick = () => nextStep(3);
        })
        .catch(e => {
            console.error("Error loading time slots:", e);
             timeSlotsDiv.innerHTML = '<p style="text-align:center;color:var(--danger);padding:20px;">Failed to load time slots.</p>';
        });
}

// Event listener for date picker change
document.getElementById('datePicker').addEventListener('change', function() {
    if (selectedCounselor) {
        loadTimeSlots(selectedCounselor.id, this.value);
    }
});


function confirmBooking() {
    const reason = document.getElementById('reasonField').value.trim();
    if (!reason || !selectedCounselor || !selectedSlot) {
        alert("Please complete all steps: select a counselor, a date/time, and provide a reason.");
        return;
    }

    const bookingData = new URLSearchParams();
    bookingData.append('counselor_id', selectedCounselor.id);
    bookingData.append('appointment_date', selectedSlot.date);
    bookingData.append('appointment_time', selectedSlot.time);
    bookingData.append('appointment_desc', reason);

    fetch('api/book_appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: bookingData
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert("Appointment booked successfully! Your counselor will confirm soon.");
            closeBookingModal();
            window.location.reload(); // Reload the page to update the calendar
        } else {
            alert("Booking failed: " + (result.error || "An unknown error occurred."));
        }
    })
    .catch(e => {
        console.error("Booking error:", e);
        alert("An error occurred while confirming your appointment.");
    });
}

// --- FullCalendar Initialization ---

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
            <?php foreach ($events as $event):
                $color = 'var(--primary)'; // default
                if ($event['status'] === 'Approved') $color = 'var(--success)';
                else if ($event['status'] === 'Pending') $color = 'var(--warning)';
                else if ($event['status'] === 'Cancelled') $color = 'var(--danger)';
            ?>
            {
                title: "<?= htmlspecialchars($event['Appointment_desc']) ?> (<?= htmlspecialchars($event['fname'].' '.$event['lname']) ?>)",
                start: "<?= $event['appointment_date'] ?>",
                color: "<?= $color ?>"
            },
            <?php endforeach; ?>
        ]
    });
    calendar.render();
});


// --- Appointment Cancellation Logic ---

function cancelAppointment() {
    const appointmentId = document.getElementById('detailAppointmentId').value;
    if (!confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
        return;
    }

    const data = new URLSearchParams();
    data.append('appointment_id', appointmentId);

    fetch('api/cancel_appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: data
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('Appointment successfully cancelled.');
            // Close modal and reload page to update the calendar
            document.getElementById('appointmentDetailModal').style.display = 'none';
            window.location.reload();
        } else {
            alert('Cancellation failed: ' + (result.error || 'An unknown error occurred.'));
        }
    })
    .catch(e => {
        console.error('Cancellation error:', e);
        alert('An error occurred while cancelling the appointment.');
    });
}

// Function placeholder for View All Appointments and Chat
function openStudentAppointmentsPanel() {
    // Implement logic to open a panel/page showing a list of all appointments
    alert("Opening list of all appointments... (Functionality to be implemented)");
}
function openChatWithCounselor() {
     // Implement logic to open a persistent chat window with the last counselor or a main chat room
    alert("Opening persistent chat with counselor... (Functionality to be implemented)");
}

</script>
</body>
</html>
