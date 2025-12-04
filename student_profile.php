<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$message = '';
$error = ''; 

// Helper function to get value
function val($field) {
    global $student;
    // Check POST data first (in case of failed submit), then DB data
    return htmlspecialchars($_POST[$field] ?? $student[$field] ?? '');
}

// Fetch current profile from DB
$stmt = $conn->prepare("SELECT * FROM student WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

// Save updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $grade      = trim($_POST['grade'] ?? $student['grade']);
    $about      = trim($_POST['about'] ?? '');

    try {
        $update = $conn->prepare("
            UPDATE student 
            SET email = ?, 
                phone = ?, 
                grade = ?,
                about = ?
            WHERE student_id = ?
        ");

        $update->execute([$email, $phone, $grade, $about, $student_id]);
        $message = "Profile updated successfully!";

        // Refresh data
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error = "Database error: Could not update profile.";
        error_log("Student Profile Update Error: " . $e->getMessage());
    }
}

// Define current page for sidebar active state
$current_page = 'profile'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Profile • Student Portal</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="navbar">
    <div class="logo">Student Portal</div>
    <div class="nav-right">
        <button id="profileBtn" class="profile-btn" onclick="toggleProfileDropdown(event)" aria-controls="profileDropdown" aria-expanded="false" aria-label="Toggle profile menu">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
        </button>
        
        <div id="profileDropdown" class="profile-dropdown" aria-hidden="true">
            <div class="profile-row" style="display:flex;align-items:center;gap:15px;padding:12px 10px;border-radius:16px 16px 0 0;background: #8e44ad;">
                <div class="avatar" style=" width:20px;height:20px;"><i class="fas fa-user-graduate"></i></div>
                <div class="info">
                    <div class="info-name"><?= htmlspecialchars($fullname) ?></div>
                    <small>Student</small>
                </div>
            </div>
            <ul>
                <li><a href="student_dashboard.php" style="padding:10px 20px;display:block;text-decoration:none;color:var(--text-dark);font-size:15px;">
                    <i class="fas fa-user-circle" style="margin-right:10px;"></i> Home
                </a></li>
                <li><a href="logout.php" style="padding:10px 20px;display:block;text-decoration:none;color:var(--danger);font-size:15px;border-top:1px solid #f5f5f5;">
                    <i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Logout
                </a></li>
            </ul>
        </div>
    </div>
</div>
<div class="dashboard-container">
    
    <div class="main-content">
        <div class="profile-header">
            <div class="profile-header-content">
                <div class="profile-avatar">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div class="profile-header-info">
                    <h1><?= htmlspecialchars($fullname) ?></h1>
                    <div class="grade"><?= val('grade') ?: 'Student' ?></div>
                    <div class="student-id">
                        <i class="fa-solid fa-id-card"></i>
                        Student ID: <?= htmlspecialchars($student_id) ?>
                    </div>
                    <div class="status-badge" style="background: #27ae60; color: #f6f8f6ff;">
                         <i class="fa-solid fa-circle" style="font-size: 8px;"></i>
                        Active Student
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="success-message">
                <i class="fa-solid fa-check-circle"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="profile-form">
            <form method="POST">
                <div class="form-section">
                    <h3>
                        <i class="fa-solid fa-envelope"></i>
                        Contact Information
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= val('email') ?>" placeholder="idnumber@slc-sflu.edu.ph" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?= val('phone') ?>" placeholder="+63 912 345 6789">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>
                        <i class="fa-solid fa-graduation-cap"></i>
                        Academic Information
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="grade">Grade Level / Year</label>
                            <input type="text" id="grade" name="grade" value="<?= val('grade') ?>" placeholder="e.g. Grade 11 - STEM / 1st Year College">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>
                        <i class="fa-solid fa-pen-fancy"></i>
                        About You
                    </h3>
                    <div class="form-group">
                        <label for="about">About Yourself</label>
                        <textarea id="about" name="about" placeholder="Share a bit about yourself, your interests, hobbies, or anything you'd like your counselor to know..."><?= val('about') ?></textarea>
                    </div>
                    <p style="font-size: 13px; color: #8e44ad; margin-top: 8px;">
                        <i class="fa-solid fa-circle-info"></i>
                        This information helps your counselor understand you better.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-save" style="margin-right: 3px;"></i>
                        Save Changes
                    </button>
                    <a href="student_dashboard.php" class="btn-secondary">
                        <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                        Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
        </div>
    </div>
<script>
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
    // Placeholder function for "Book New Session" to prevent errors
    function openBookingModal() {
        alert("Booking Modal logic is handled by JavaScript, likely in student_dashboard.php or booking.js.");
    }
</script>
</body>
</html>