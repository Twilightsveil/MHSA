<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    header("Location: login.php");
    exit;
}

$counselor_id = $_SESSION['user_id'];
$message = '';
$error = ''; 

// Helper function to get value
function val($field) {
    global $counselor;
    // Check POST data first (in case of failed submit), then DB data
    // Also handling name concatenation
    if ($field === 'fullname') {
        $fname = htmlspecialchars($_POST['fname'] ?? $counselor['fname'] ?? '');
        $lname = htmlspecialchars($_POST['lname'] ?? $counselor['lname'] ?? '');
        $mi = htmlspecialchars($_POST['mi'] ?? $counselor['mi'] ?? '');
        return trim($fname . ' ' . ($mi ? $mi . '. ' : '') . $lname);
    }
    return htmlspecialchars($_POST[$field] ?? $counselor[$field] ?? '');
}

// Fetch current profile from DB
$stmt = $conn->prepare("SELECT * FROM counselor WHERE counselor_id = ?");
$stmt->execute([$counselor_id]);
$counselor = $stmt->fetch();

if (!$counselor) {
    die("Counselor not found.");
}

$fullname = val('fullname');

// Save updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title'] ?? $counselor['title']);
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $department = trim($_POST['department'] ?? $counselor['department']); 

    try {
        $update = $conn->prepare("
            UPDATE counselor 
            SET title = ?, 
                department = ?, 
                email = ?, 
                phone = ?, 
                bio = ?
            WHERE counselor_id = ?
        ");

        $update->execute([$title, $department, $email, $phone, $bio, $counselor_id]);
        $message = "Profile updated successfully!";

        // Refresh data
        $stmt->execute([$counselor_id]);
        $counselor = $stmt->fetch();
        $fullname = val('fullname'); // Re-fetch full name
        
    } catch (PDOException $e) {
        $error = "Database error: Could not update profile.";
        error_log("Counselor Profile Update Error: " . $e->getMessage());
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
    <title>My Profile • Counselor Portal</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="navbar">
    <div class="logo">Counselor Portal</div>
    <div class="nav-right">
        <button id="profileBtn" class="profile-btn" onclick="toggleProfileDropdown(event)" aria-controls="profileDropdown" aria-expanded="false" aria-label="Toggle profile menu">
            <div class="avatar"><i class="fas fa-user-tie"></i></div>
        </button>
        
        <div id="profileDropdown" class="profile-dropdown" aria-hidden="true">
            <div class="profile-row" style="display:flex;align-items:center;gap:15px;padding:15px 20px;border-bottom:1px solid var(--purple-lightest);background:#8e44ad">
                <div class="avatar" style="width:40px;height:40px;flex-shrink:0;"><i class="fas fa-user-tie"></i></div>
                <div class="info">
                    <div><?= htmlspecialchars($fullname) ?></div>
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

    <div class="main-content">
        <div class="profile-header">
            <div class="profile-header-content">
                <div class="profile-avatar" style="background: rgba(255, 255, 255, 0.3);">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="profile-header-info">
                    <h1><?= htmlspecialchars($fullname) ?></h1>
                    <div class="grade"><?= val('title') ?: 'Guidance Counselor' ?></div>
                    <div class="student-id">
                        <i class="fa-solid fa-id-card"></i>
                        Counselor ID: <?= htmlspecialchars($counselor_id) ?>
                    </div>
                    <div class="status-badge" style="background: rgba(115, 117, 116, 0.7); color:white;">
                         <i class="fa-solid fa-briefcase" style="font-size: 10px;"></i>
                        <?= val('department') ?: 'Guidance Department' ?>
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
                            <input type="email" id="email" name="email" value="<?= val('email') ?>" placeholder="counselor@school.edu.ph" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?= val('phone') ?>" placeholder="0917-123-4567">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>
                        <i class="fa-solid fa-briefcase"></i>
                        Professional Details
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Title / Role</label>
                            <input type="text" id="title" name="title" value="<?= val('title') ?>" placeholder="e.g. Senior Guidance Counselor">
                        </div>
                        <div class="form-group">
                            <label for="department">Department / Specialty</label>
                            <input type="text" id="department" name="department" value="<?= val('department') ?>" placeholder="e.g. Senior High / College Counselor">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>
                        <i class="fa-solid fa-pen-fancy"></i>
                        Professional Bio
                    </h3>
                    <div class="form-group">
                        <label for="bio">About Yourself</label>
                        <textarea id="bio" name="bio" placeholder="Share a bit about your professional background, counseling approach, and areas of expertise..."><?= val('bio') ?></textarea>
                    </div>
                    <p style="font-size: 13px; color: #8e44ad; margin-top: 8px;">
                        <i class="fa-solid fa-circle-info"></i>
                        This bio is visible to students when they book appointments.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-save" style="margin-right: 3px;"></i>
                        Save Changes
                    </button>
                    <a href="counselor_dashboard.php" class="btn-secondary">
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
</script>
</body>
</html>