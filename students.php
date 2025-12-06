<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    header("Location: login.php");
    exit();
}

$counselor_name = $_SESSION['fullname'];
$firstName = explode(' ', trim($counselor_name))[0];

// Fetch all students
$stmt = $conn->prepare("SELECT * FROM student ORDER BY lname, fname");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students • Counselor Portal</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        .logo { font-weight: bold; font-size: 24px; color: #8e44ad; }
        .main-content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .page-title {
            text-align: center;
            margin: 20px 0 40px;
        }
        .page-title h1 {
            font-size: 38px;
            color: #4b2b63;
            margin: 0;
        }
        .page-title p {
            color: #666;
            font-size: 18px;
        }

        /* Search & Add Button */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .search-box {
            position: relative;
            width: 1500px;
        }
        .search-box input {
            width: 100%;
            padding: 14px 50px 14px 20px;
            border: 2px solid #e0d4f7;
            border-radius: 16px;
            font-size: 16px;
            background: #faf6ff;
        }
        .search-box i {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #8e44ad;
        }

        /* Student Grid */
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }
        .student-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #8e44ad;
        }
        .student-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(142,68,173,0.15);
        }
        .student-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        .student-avatar {
            width: 70px;
            height: 70px;
            background: #8e44ad;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
        }
        .student-info h3 {
            margin: 0;
            font-size: 20px;
            color: #2c3e50;
        }
        .student-info small {
            color: #8e44ad;
            font-weight: 600;
        }
        .student-details {
            margin-top: 16px;
            color: #555;
            font-size: 15px;
        }
        .student-details p {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .student-details i {
            width: 20px;
            color: #8e44ad;
        }
        .student-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 10px 16px;
            font-size: 14px;
            border-radius: 12px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1200;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4b2b63;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0d4f7;
            border-radius: 12px;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">Counselor Portal</div>
    <div class="nav-right" style="display:flex;align-items:center;gap:20px;">
    <!-- Notification Bell -->
    <div style="position:relative;">
        <button id="notifBtn" onclick="toggleNotifDropdown(event)" style="background:none;border:none;cursor:pointer;font-size:22px;color:#2c3e50;">
            <i class="fas fa-bell"></i>
            <?php if (!empty($_SESSION['counselor_notifications'])): ?>
                <span style="position:absolute;top:-8px;right:-8px;background:#e74c3c;color:white;font-size:11px;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;">
                    <?= count($_SESSION['counselor_notifications']) ?>
                </span>
            <?php endif; ?>
        </button>

        <!-- Notification Dropdown -->
        <div id="notifDropdown" style="display:none;position:absolute;right:0;top:55px;background:white;box-shadow:0 15px 40px rgba(0,0,0,0.18);border-radius:16px;min-width:400px;max-height:85vh;overflow:hidden;z-index:1001;border:1px solid #eee;">
            <div style="padding:18px 22px;font-weight:bold;border-bottom:1px solid #eee;background:linear-gradient(135deg,#8e44ad,#9b59b6);color:white;border-radius:16px 16px 0 0;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:18px;">Notifications</span>
                    <span style="background:rgba(255,255,255,0.25);padding:5px 12px;border-radius:20px;font-size:13px;">
                        <?= count($_SESSION['counselor_notifications'] ?? []) ?> new
                    </span>
                </div>
            </div>
            <div style="max-height:460px;overflow-y:auto;">
                <?php if (!empty($_SESSION['counselor_notifications'])): ?>
                    <?php foreach ($_SESSION['counselor_notifications'] as $n): 
                        $msg = is_array($n) ? $n['message'] : $n;
                        $student_name = explode(' requested', $msg)[0] ?? 'A student';
                        $time = is_array($n) ? ($n['time'] ?? 'Just now') : 'Just now';
                    ?>
                    <div style="padding:18px 22px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:16px;background:#faf8ff;">
                        <div style="width:48px;height:48px;background:#8e44ad;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:18px;">
                            <?= strtoupper(substr($student_name, 0, 2)) ?>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600;color:#2c3e50;font-size:15px;">
                                <?= htmlspecialchars($student_name) ?> requested an appointment
                            </div>
                            <div style="color:#95a5a6;font-size:13px;margin-top:4px;">
                                <i class="fas fa-clock"></i> <?= $time ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding:80px 30px;text-align:center;color:#bdc3c7;">
                        <i class="fas fa-bell-slash fa-3x mb-3"></i>
                        <div style="font-size:16px;font-weight:500;">All caught up!</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Profile Button + Dropdown -->
    <button id="profileBtn" onclick="toggleProfileDropdown(event)" style="background:none;border:none;cursor:pointer;">
        <div style="width:44px;height:44px;background:#8e44ad;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;">
            <i class="fas fa-user-tie"></i>
        </div>
    </button>

    <div id="profileDropdown" class="profile-dropdown" aria-hidden="true" style="position:absolute;right:20px;top:70px;background:white;box-shadow:0 10px 30px rgba(0,0,0,0.2);border-radius:12px;width:260px;z-index:1001;">
        <div style="padding:18px 22px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:14px;">
            <div style="width:50px;height:50px;background:#8e44ad;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <div style="font-weight:600;font-size:16px;"><?= htmlspecialchars($counselor_name) ?></div>
                <small style="color:#8e44ad;font-weight:600;">Guidance Counselor</small>
            </div>
        </div>
        <ul style="margin:0;padding:0;list-style:none;">
            <li><a href="counselor_profile.php" style="display:block;padding:14px 22px;text-decoration:none;color:#333;font-size:15px;">
                My Profile
            </a></li>
            <li><a href="counselor_dashboard.php" style="display:block;padding:14px 22px;text-decoration:none;color:#333;font-size:15px;">
                Dashboard
            </a></li>
            <li><a href="logout.php" style="display:block;padding:14px 22px;color:#e74c3c;text-decoration:none;font-size:15px;">
                Logout
            </a></li>
        </ul>
    </div>
</div>
</div>

<div class="main-content">
    <div class="page-title">
        <h1>All Students</h1>
        <p>Manage student profiles and information</p>
    </div>

    <div class="header-actions">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search students..." onkeyup="filterStudents()">
            <i class="fas fa-search"></i>
        </div>
        <button class="btn" onclick="openAddModal()">
            Add New Student
        </button>
    </div>

    <div class="students-grid" id="studentsGrid">
        <?php foreach ($students as $s): 
            $initials = strtoupper(substr($s['fname'],0,1) . substr($s['lname'],0,1));
        ?>
        <div class="student-card">
            <div class="student-header">
                <div class="student-avatar"><?= $initials ?></div>
                <div class="student-info">
                    <h3><?= htmlspecialchars($s['fname'] . ' ' . $s['lname']) ?></h3>
                    <small>Student ID: <?= $s['student_id'] ?></small>
                </div>
            </div>
            <div class="student-details">
                <p>Email: <?= htmlspecialchars($s['email'] ?? 'Not set') ?></p>
                <p>Grade: <?= htmlspecialchars($s['grade_level'] ?? 'N/A') ?></p>
                <p>Contact: <?= htmlspecialchars($s['contact_no'] ?? 'N/A') ?></p>
            </div>
            <div class="student-actions">
                <button class="btn btn-small" style="background:#3498db;" onclick="openEditModal(<?= $s['student_id'] ?>)">
                    Edit
                </button>
                <button class="btn btn-small" style="background:#8e44ad;" onclick="viewProfile(<?= $s['student_id'] ?>)">
                    View Profile
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="studentModal">
    <div class="modal-content">
        <h2 style="text-align:center;color:#8e44ad;margin-bottom:20px;" id="modalTitle">Add New Student</h2>
        <form id="studentForm" method="POST" action="api/save_student.php">
            <input type="hidden" name="student_id" id="studentId">

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="fname" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lname" required>
            </div>
            <div class="form-group">
                <label>Middle Initial (optional)</label>
                <input type="text" name="mi" maxlength="1">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email">
            </div>
            <div class="form-group">
                <label>Grade Level</label>
                <select name="grade_level">
                    <option value="">Select Grade</option>
                    <?php for($i=7;$i<=12;$i++): ?>
                        <option value="<?= $i ?>"><?= $i ?>th Grade</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_no">
            </div>

            <div style="text-align:center;margin-top:30px;">
                <button type="submit" class="btn" style="padding:14px 40px;font-size:16px;">Save Student</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('studentModal').style.display='none'" style="margin-left:15px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Search
function filterStudents() {
    const term = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.student-card').forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(term) ? 'block' : 'none';
    });
}

// Open Add Modal
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Student';
    document.getElementById('studentForm').reset();
    document.getElementById('studentId').value = '';
    document.getElementById('studentModal').style.display = 'flex';
}

// Open Edit Modal
function openEditModal(id) {
    fetch(`api/get_student.php?id=${id}`)
        .then(r => r.json())
        .then(s => {
            document.getElementById('modalTitle').textContent = 'Edit Student';
            document.getElementById('studentId').value = s.student_id;
            document.querySelector('[name="fname"]').value = s.fname;
            document.querySelector('[name="lname"]').value = s.lname;
            document.querySelector('[name="mi"]').value = s.mi || '';
            document.querySelector('[name="email"]').value = s.email || '';
            document.querySelector('[name="grade_level"]').value = s.grade_level || '';
            document.querySelector('[name="contact_no"]').value = s.contact_no || '';
            document.getElementById('studentModal').style.display = 'flex';
        });
}

// View Profile (you can expand this later)
function viewProfile(id) {
    alert('View full profile for Student ID: ' + id);
    // Later: open detailed profile page
}

// Close modal when clicking outside
window.onclick = function(e) {
    const modal = document.getElementById('studentModal');
    if (e.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<script>
// Profile & Notification Dropdown Toggle
function toggleNotifDropdown(e) {
    e.stopPropagation();
    const d = document.getElementById('notifDropdown');
    d.style.display = d.style.display === 'block' ? 'none' : 'block';
}

function toggleProfileDropdown(e) {
    e.stopPropagation();
    const d = document.getElementById('profileDropdown');
    d.setAttribute('aria-hidden', d.getAttribute('aria-hidden') === 'true' ? 'false' : 'true');
}

document.addEventListener('click', () => {
    document.getElementById('notifDropdown').style.display = 'none';
    document.getElementById('profileDropdown').setAttribute('aria-hidden', 'true');
});
</script>
</body>
</html>