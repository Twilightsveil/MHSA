<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db/connection.php';

$error = '';

if (isset($_POST['login'])) {
    $id   = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (empty($id) || empty($pass)) {
        $error = "Please fill in both ID and password.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $valid = password_verify($pass, $user['password']) 
                      || $pass === $user['password']; 

                if ($valid) {
                    $_SESSION['user_id']  = $user['user_id'];
                    $_SESSION['role']     = $user['role'];
                    $_SESSION['fullname'] = trim("{$user['fname']} " . ($user['mi'] ? $user['mi'].'.' : '') . " {$user['lname']}");

                    $redirect = match($user['role']) {
                        'admin'     => 'admin_dashboard.php',
                        'counselor' => 'counselor_dashboard.php',
                        'student'   => 'student_dashboard.php',
                        default     => 'student_dashboard.php'
                    };

                    header("Location: $redirect");
                    exit;
                }
            }
            $error = "Invalid ID or password.";
        } catch (Exception $e) {
            $error = "Login failed. Please try again.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login • MHSA</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="login-wrapper">
    <!-- Left: Login Form -->
    <div class="login-left">
        <div class="login-box">
            <div class="system-title">
                <h1>Student Mental Health &<br>Appointment System</h1>
                <p>Guidance Office</p>
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
                <button type="submit" name="login" class="btn">Sign In</button>
                <?php if(isset($error)) echo "<p style='color:#e74c3c;margin-top:10px;'>$error</p>"; ?>
            </form>
        </div>
    </div>

    <!-- Right: Background + Centered Logo + Hotline -->
    <div class="login-right">
        <!-- Centered Logo -->
        <div class="login-logo">
            <img src="assets/logo.png" alt="MHSA Logo" class="logo-img">
        </div>

        <!-- Hotline Card (Top-Right) -->
        <a href="tel:09175584673" class="hotline-card">
            <span class="hotline-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
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
</body>
</html>