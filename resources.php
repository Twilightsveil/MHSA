<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['fullname'];
$firstName = explode(" ", $name)[0];


$current_page = 'resources';
?>

<!DOCTYPE html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mental Health Resources • <?= htmlspecialchars($name) ?></title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="CSS/style.css">
 <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />

<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Arial', sans-serif;
    background: #efe7f1ff;
}

.main-content {
    margin-left: 280px;
    padding: 30px 40px;
    width: calc(100% - 280px);
}
.resource-card {
    background: white;
    padding: 35px;
    border-radius: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    margin-bottom: 35px;
    transition: 0.3s;
    display: center;
}
.resource-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.25);
}
.card-title {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 26px;
    color: #4b0d7a;
    font-weight: bold;
    margin-bottom: 15px;
}
.card-title i {
    font-size: 30px;
    color: #8e44ad;
}
.resource-section {
    margin-top: 17px;
    line-height: 1.8;
    color: #5b4470;
    font-size: 17px;
}
.resource-section strong {
    color: #373638ff;
}
a.link {
    color: #8e44ad;
    text-decoration: none;
    font-weight: bold;
}
a.link:hover {
    text-decoration: underline;
}
.page-title h1 {
    font-size: 32px;
    color: var(--purple-dark);
}
.page-title p {
    color: var(--text-light);
    font-size: 18px;
}

.cards-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 30px;
}

.resource-card {
    max-width: 900px; 
    width: 100%; 
}
.back-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    margin-right: 10px;
    color: #4b0d7a;
    padding: 8px 10px;
    border-radius: 8px;
    transition: 0.3s;
}

.back-btn:hover {
    background: rgba(0,0,0,0.08);
}
.nav-right {
    display: flex;
    align-items: center;
    gap: 10px; 

}
.profile-btn .avatar {
    width: 100%;
    height: 100%;
    background: var(--primary);
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

</style>
</head>

<body>

<div class="navbar">
    <div class="logo">Mental Health Resources</div>
    <div class="nav-right">

    <!-- Back Button -->
    <button class="back-btn" onclick="history.back()">
        <i class="fas fa-arrow-left"></i>
    </button>

       
    </div>
</div>

<div class="dashboard-container">


   
    <div class="main-content">

    <div class="page-title">
        <h1>Hello, <?= htmlspecialchars($firstName) ?>!</h1>
        <p class="subtitle">Here are some tools and resources to support your well-being.</p>
    </div>

   
    <div class="cards-container">
        <div class="resource-card">
            <div class="card-title"><i class="fa-solid fa-phone"></i> Emergency Hotlines</div>
            <div class="resource-section">
                <strong>📞 National Mental Health Crisis Hotline (PH):</strong><br>
                • Landline: <strong>1553</strong> (toll-free) <br><br>

                <strong>📱 Hopeline PH:</strong><br>
                • Globe/TM: <strong>0917-558-4673</strong><br>
                • Smart/Sun: <strong>0918-873-4673</strong><br>
                • PLDT: <strong>(02) 804-4673</strong><br><br>

                <strong>🚨 When to call:</strong><br>
                • Feeling overwhelmed or unsafe<br>
                • Experiencing panic attacks<br>
                • Thinking about self-harm<br>
                • Emotional distress with no one to talk to
            </div>
        </div>

        <div class="resource-card">
            <div class="card-title"><i class="fa-solid fa-comments"></i> Mental Health Tips</div>
            <div class="resource-section">
                <li><strong>Practice Mindfulness:</strong> Even 3 minutes of slow breathing can calm your mind.</li>
                <li><strong>Talk to Someone:</strong> Sharing feelings helps release emotional pressure.</li>
                <li><strong>Get Enough Rest:</strong> 7–9 hours of sleep boosts mental clarity.</li>
                <li><strong>Take Study Breaks:</strong> Short breaks increase productivity and reduce burnout.</li>
                <li><strong>Stay Organized:</strong> Small tasks first → big tasks later.</li>
                <li><strong>Take Care of Your Body:</strong> Hydration and movement improve emotional well-being.</li>
            </div>
        </div>

        <div class="resource-card">
            <div class="card-title"><i class="fa-solid fa-book-open"></i> Self-Help Guides</div>
            <div class="resource-section">
                • <a class="link" href="https://www.helpguide.org/articles/stress/stress-management.htm" target="_blank">
                    Managing Stress & Anxiety – HelpGuide.org
                </a><br>

                • <a class="link" href="https://www.therapistaid.com/worksheets/grounding-techniques" target="_blank">
                    Grounding Techniques Workbook
                </a><br>

                • <a class="link" href="https://www.mindful.org/meditation/mindfulness-getting-started/" target="_blank">
                    Beginner Mindfulness Guide
                </a><br>

                • <a class="link" href="https://www.nccih.nih.gov/health/relaxation-techniques-for-health" target="_blank">
                    Breathing Exercises for Calmness
                </a><br><br>

                <strong>✨ Benefits:</strong><br>
                • Reduces emotional tension<br>
                • Helps organize thoughts<br>
                • Improves mental clarity<br>
                • Builds emotional resilience
            </div>
        </div>

        <div class="resource-card">
            <div class="card-title"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Support</div>
            <div class="resource-section">
                If you are in immediate danger or experiencing a crisis, please contact:<br><br>

                • Your nearest hospital emergency room<br>
                • Local barangay or municipal emergency hotline<br>
                • Trusted adults, guardians, or school officials<br><br>

                <strong>You are not alone. Help is available 24/7.</strong>
            </div>
        </div>
    </div> 

</div> 
</body>
</html>