<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    header("Location: login.php");
    exit;
}

$counselor_id = $_SESSION['user_id'];
$message = '';

// Fetch current profile from DB
$stmt = $conn->prepare("SELECT * FROM counselor WHERE counselor_id = ?");
$stmt->execute([$counselor_id]);
$counselor = $stmt->fetch();

if (!$counselor) {
    die("Counselor not found.");
}

// Save updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');
    $department = trim($_POST['department'] ?? $counselor['department']); // optional

    // Optional: validate email/phone format if you want

    $update = $conn->prepare("
        UPDATE counselor 
        SET department = ?, 
            email = ?, 
            phone = ?, 
            bio = ?
        WHERE counselor_id = ?
    ");

    $update->execute([$department, $email, $phone, $bio, $counselor_id]);
    $message = "Profile updated successfully!";

    // Refresh data
    $stmt->execute([$counselor_id]);
    $counselor = $stmt->fetch();
}

// Helper to get value or default
function val($field) {
    global $counselor;
    return htmlspecialchars($counselor[$field] ?? '');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Profile • <?= val('fname') . ' ' . val('lname') ?></title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="navbar">
    <div class="left-group" style="display:flex;align-items:center;gap:12px">
        <button id="profileBtn" class="profile-btn" onclick="toggleProfileDropdown(event)">
            <span class="avatar"><i class="fa-solid fa-user"></i></span>
        </button>
        <div id="profileDropdown" class="profile-dropdown" aria-hidden="true">
            <div class="profile-row" style="padding:12px;">
                <div class="avatar" style="width:48px;height:48px;border-radius:8px;background:linear-gradient(135deg,#D8BEE5,#b88ed9);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="info">
                    <div style="font-weight:700"><?= val('fname') . ' ' . val('lname') ?></div>
                    <small style="color:#8e44ad"><?= val('department') ?: 'Counselor' ?></small>
                </div>
            </div>
            <a href="counselor_dashboard.php" class="profile-item">Dashboard</a>
            <a href="logout.php" class="profile-item">Logout</a>
        </div>
        <div class="logo">My Profile</div>
    </div>
</div>

<div class="dashboard-content">
    <div class="page-title">
        <h1>My Professional Profile</h1>
        <p>Update your details — visible to students when booking</p>
    </div>

    <?php if ($message): ?>
        <div style="background:#e6ffef;border:1px solid #27ae60;color:#27ae60;padding:16px;border-radius:12px;margin:20px 0;font-weight:600;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="widget" style="max-width:800px;">
        <form method="POST">
            
            <div style="display:flex;gap:24px;align-items:center;margin-bottom:32px;">
                <div style="width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,#D8BEE5,#b88ed9);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-user-tie" style="font-size:48px;color:white;"></i>
                </div>
                <div>
                    <h2 style="margin:0;color:var(--purple-dark);font-size:28px;">
                        <?= val('fname') . ' ' . (val('mi') ? val('mi').'. ' : '') . val('lname') ?>
                    </h2>
                    <p style="margin:8px 0 0;color:#8e44ad;font-weight:600;">
                        <?= val('department') ?: 'Guidance Counselor' ?>
                    </p>
                </div>
            </div>

            <div class="card-grid" style="grid-template-columns:1fr 1fr;gap:20px;">
                <div>
                    <label>Counselor Title</label>
                    <input type="text" name="title" value="<?= val('title') ?>" placeholder="e.g. Senior Guidance Counselor">
                </div>
                <div>
                    <label>Department / Office</label>
                    <input type="text" name="department" value="<?= val('department') ?>" placeholder="e.g. Guidance Office">
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= val('email') ?>" placeholder="counselor@school.edu.ph">
                </div>
                <div>
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= val('phone') ?>" placeholder="0917-123-4567">
                </div>
            </div>

            <div style="margin-top:24px;">
                <label>Professional Bio</label>
                <textarea name="bio" rows="6" placeholder="Share your approach, experience, or specialties..."><?= val('bio') ?></textarea>
            </div>
            

            <div style="margin-top:28px;display:flex;gap:12px;">
                <button type="submit" class="btn" style="padding:14px 32px;font-size:16px;">
                    Save Changes
                </button>
                <a href="counselor_dashboard.php" class="btn" style="background:#f8f5ff;color:var(--purple-dark);border:1px solid #e0d4f5;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>

function toggleProfileDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('profileDropdown');
    const hidden = dd.getAttribute('aria-hidden') === 'true';
    dd.setAttribute('aria-hidden', !hidden);
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