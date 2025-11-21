<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db/connection.php';

$error = '';

if (isset($_POST['login'])) {
    $id = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (empty($id) || empty($pass)) {
        $error = "Fill all fields";
    } else {
        $tables = ['admin', 'counselor', 'student'];
        $found = false;

        foreach ($tables as $table) {
            $id_field = $table . '_id';
            $stmt = $conn->prepare("SELECT * FROM `$table` WHERE `$id_field` = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if ($user) {
                // Remove this line if passwords are plain text temporarily
                if (password_verify($pass, $user['password']) || $pass === $user['password']) {
                    $name = trim($user['fname'] . ' ' . ($user['mi'] ? $user['mi'].'. ' : '') . ' ' . $user['lname']);
                    $_SESSION['user_id'] = $user[$id_field];
                    $_SESSION['fullname'] = $name;
                    $_SESSION['role'] = $table;

                    if ($table === 'counselor') header("Location: counselor_dashboard.php");
                    elseif ($table === 'student') header("Location: student_dashboard.php");
                    else header("Location: admin_dashboard.php");
                    exit();
                }
            }
        }
        $error = "Invalid ID or Password";
    }
}
?>
<!-- Keep your beautiful HTML login form exactly as is -->
<!DOCTYPE html>
<html>
<head>
    <title>Guidance Office Login</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-left">
        <div class="login-box">
            <div class="system-title">
                <h1>Guidance Office</h1>
                <p>Student Mental Health & Appointment System</p>
            </div>
            <form method="POST">
                <div class="input-field">
                    <label>ID Number</label>
                    <input type="text" name="username" required placeholder="Enter your ID">
                </div>
                <div class="input-field">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login">Sign In</button>
                <?php if(isset($error)) echo "<p style='color:#e74c3c;margin-top:10px;'>$error</p>"; ?>
            </form>
        </div>
    </div>
    <div class="login-right">
        <div class="hotline" role="note">
            <a class="hotline-card" href="tel:09175584673">
                <span class="hotline-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3 5.18 2 2 0 0 1 5 3h3a2 2 0 0 1 2 1.72c.07.78.24 1.53.5 2.24a2 2 0 0 1-.45 2.11L9.91 10.91a16 16 0 0 0 6.18 6.18l1.84-1.18a2 2 0 0 1 2.11-.45c.71.26 1.46.43 2.24.5A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </span>
                <span class="hotline-text">
                    <strong>In Crisis?</strong>
                    <small>Call Hopeline PH</small>
                </span>
                <span class="hotline-number">0917-558-4673</span>
            </a>
        </div>
    </div>
</div>
</body>
</html>