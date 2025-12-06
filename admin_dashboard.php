<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['fullname'];
$firstName = explode(' ', trim($name))[0] ?? 'Admin';

// Fetch data
$counselors = $conn->query("SELECT * FROM counselor ORDER BY lname")->fetchAll(PDO::FETCH_ASSOC);
$students   = $conn->query("SELECT * FROM student ORDER BY lname")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all appointments with names
$appts = $conn->query("
    SELECT a.*, 
           CONCAT(s.fname, ' ', s.lname) as student_name,
           CONCAT(c.fname, ' ', c.lname) as counselor_name
    FROM appointments a
    JOIN student s ON a.student_Id = s.student_id
    JOIN counselor c ON a.counselor_ID = c.counselor_id
    ORDER BY a.appointment_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard • MHSA</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .appointment-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-left: 6px solid #8e44ad;
        }
        .appointment-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(142,68,173,0.15);
        }
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .appointment-date {
            font-weight: bold;
            color: #2c3e50;
            font-size: 16px;
        }
        .appointment-status {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: bold;
            color: white;
        }
        .pending   { background: #e67e22; }
        .approved  { background: #27ae60; }
        .done      { background: #3498db; }

        .appointment-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 16px 0;
        }
        .appointment-person {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .person-avatar {
            width: 48px; height: 48px;
            background: #8e44ad; color: white;
            border-radius: 50%; display: flex;
            align-items: center; justify-content: center;
            font-weight: bold; font-size: 18px;
        }
        .person-info strong { display: block; font-size: 16px; }
        .person-info small { color: #8e44ad; }

        .appointment-reason {
            background: #f5f0ff;
            padding: 14px;
            border-radius: 12px;
            font-size: 15px;
            margin-top: 12px;
        }

        .appointment-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-menu">
            <ul>
                <li><a href="#" class="active" data-section="counselors"><i class="fas fa-user-tie"></i> Counselors <span class="badge"><?=count($counselors)?></span></a></li>
                <li><a href="#" data-section="students"><i class="fas fa-user-graduate"></i> Students <span class="badge"><?=count($students)?></span></a></li>
                <li><a href="#" data-section="appointments"><i class="fas fa-calendar-check"></i>Appointments <span class="badge"><?=count($appts)?></span></a></li>
                
                <li style="margin-top: auto; padding-top: 20px; border-top: 1px solid #eee;">
                    <a href="logout.php" class="logout-link">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="page-title">
            <h1>Welcome back, <?=htmlspecialchars($firstName)?>!</h1>
            <p>Manage your counseling system with ease.</p>
        </div>

        <!-- COUNSELORS SECTION -->
        <section id="counselors" class="section active">
            <div class="widget">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3><i class="fas fa-user-tie"></i>Counselors</h3>
                    <button class="btn small" onclick="openAddModal('counselor')">Add New</button>
                </div>
                <div class="search-box">
                    <input type="text" id="searchCounselors" placeholder="Search counselors...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div class="card-grid" id="counselorsGrid">
                    <?php foreach($counselors as $c): 
                        $initials = strtoupper(substr($c['fname'],0,1).substr($c['lname'],0,1));
                        $search = strtolower($c['fname'].' '.$c['lname'].' '.$c['email'].' '.$c['department']);
                    ?>
                    <div class="profile-card" data-search="<?=htmlspecialchars($search)?>">
                        <div class="card-header">
                            <div class="avatar-initials"><?=$initials?></div>
                            <div>
                                <strong><?=htmlspecialchars($c['fname'].' '.$c['lname'].($c['mi']?' '.$c['mi'].'.':''))?></strong><br>
                                <small style="color:var(--primary);"><?=htmlspecialchars($c['title'] ?: 'Guidance Counselor')?></small>
                            </div>
                        </div>
                        <p><strong>Dept:</strong> <?=htmlspecialchars($c['department'] ?: '—')?></p>
                        <p><strong>Email:</strong> <?=htmlspecialchars($c['email'])?></p>
                        <div class="card-actions">
                            <button class="btn small" onclick="openEditModal(this)"
                                data-type="counselor"
                                data-id="<?=$c['counselor_id']?>"
                                data-fname="<?=htmlspecialchars($c['fname'])?>"
                                data-lname="<?=htmlspecialchars($c['lname'])?>"
                                data-mi="<?=htmlspecialchars($c['mi']??'')?>"
                                data-title="<?=htmlspecialchars($c['title']??'')?>"
                                data-department="<?=htmlspecialchars($c['department']??'')?>"
                                data-email="<?=htmlspecialchars($c['email'])?>"
                                data-phone="<?=htmlspecialchars($c['phone']??'')?>"
                                data-bio="<?=htmlspecialchars($c['bio']??'')?>">
                                Edit
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- STUDENTS SECTION -->
        <section id="students" class="section">
            <div class="widget">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3><i class="fas fa-user-graduate"></i> Students</h3>
                    <button class="btn small" onclick="openAddModal('student')">Add New</button>
                </div>
                <div class="search-box">
                    <input type="text" id="searchStudents" placeholder="Search students...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div class="card-grid" id="studentsGrid">
                    <?php foreach($students as $s): 
                        $initials = strtoupper(substr($s['fname'],0,1).substr($s['lname'],0,1));
                        $search = strtolower($s['fname'].' '.$s['lname'].' '.$s['course'].' '.$s['year'].$s['section']);
                    ?>
                    <div class="profile-card" data-search="<?=htmlspecialchars($search)?>">
                        <div class="card-header">
                            <div class="avatar-initials student"><?=$initials?></div>
                            <div>
                                <strong><?=htmlspecialchars($s['fname'].' '.$s['lname'].($s['mi']?' '.$s['mi'].'.':''))?></strong><br>
                                <small style="color:var(--info);"><?=htmlspecialchars($s['course'].' – '.$s['year'].$s['section'])?></small>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="btn small" onclick="openEditModal(this)"
                                data-type="student"
                                data-id="<?=$s['student_id']?>"
                                data-fname="<?=htmlspecialchars($s['fname'])?>"
                                data-lname="<?=htmlspecialchars($s['lname'])?>"
                                data-mi="<?=htmlspecialchars($s['mi']??'')?>"
                                data-course="<?=htmlspecialchars($s['course']??'')?>"
                                data-year="<?=htmlspecialchars($s['year']??'')?>"
                                data-section="<?=htmlspecialchars($s['section']??'')?>">
                                Edit
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- APPOINTMENTS SECTION -->
<section id="appointments" class="section">
    <div class="widget">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3><i class="fas fa-calendar-check"></i> Appointments</h3>
            <div style="color:#666; font-size:14px;">
                Total: <strong><?= count($appts) ?></strong>
            </div>
        </div>

        <div class="search-box">
            <input type="text" id="searchAppointments" placeholder="Search by student, counselor, or reason...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <!-- FLEX CONTAINER -->
        <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-top: 24px;" id="appointmentsGrid">
            <?php foreach($appts as $a): 
                $status = $a['status'] ?? 'pending';
                $studentInit = strtoupper(substr($a['student_name'],0,2));
                $counselorInit = strtoupper(substr($a['counselor_name'],0,2));
                $date = date('M j, Y \a\t g:i A', strtotime($a['appointment_date']));
                $search = strtolower($a['student_name'].' '.$a['counselor_name'].' '.$a['appointment_desc'].' '.$date);

                // Colors by status
                $cardBg = $status === 'approved' ? '#d4edda' : ($status === 'done' ? '#d1ecf1' : '#fff3cd');
                $borderLeft = $status === 'approved' ? '#27ae60' : ($status === 'done' ? '#3498db' : '#e67e22');
            ?>
            <div class="appointment-card" data-search="<?= htmlspecialchars($search) ?>"
                 style="flex: 1 1 360px; max-width: 420px; background:<?= $cardBg ?>; border-left:6px solid <?= $borderLeft ?>;">
                
                <div class="appointment-header">
                    <div class="appointment-date"><strong><?= $date ?></strong></div>
                    <div class="appointment-status <?= $status ?>">
                        <?= ucfirst($status) ?>
                    </div>
                </div>

                <div class="appointment-body">
                    <div class="appointment-person">
                        <div class="person-avatar"><?= $studentInit ?></div>
                        <div class="person-info">
                            <strong><?= htmlspecialchars($a['student_name']) ?></strong>
                            <small>Student</small>
                        </div>
                    </div>
                    <div class="appointment-person">
                        <div class="person-avatar" style="background:#27ae60;"><?= $counselorInit ?></div>
                        <div class="person-info">
                            <strong><?= htmlspecialchars($a['counselor_name']) ?></strong>
                            <small>Counselor</small>
                        </div>
                    </div>
                </div>

                <div class="appointment-reason">
                    <strong>Reason:</strong> <?= htmlspecialchars($a['appointment_desc'] ?: 'Not specified') ?>
                </div>

                <div class="appointment-actions">
                    <button class="btn small" style="background:#e74c3c;" onclick="deleteAppointment(<?= $a['appointment_id'] ?>)">
                        Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
    </main>
</div>

<!-- MODALS -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">×</span>
        <h2 id="modalTitle">Edit Record</h2>
        <form id="editForm">
            <input type="hidden" name="type" id="formType">
            <input type="hidden" name="id" id="formId">
            <div id="formFields"></div>
            <div style="margin-top:30px;text-align:right;">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn" style="margin-left:12px;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// Sidebar Navigation
document.querySelectorAll('.sidebar-menu a[data-section]').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.getElementById(link.dataset.section).classList.add('active');
        document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
        link.classList.add('active');
    });
});

// Search
['Counselors', 'Students', 'Appointments'].forEach(type => {
    const input = document.getElementById(`search${type}`);
    if (input) {
        input.addEventListener('input', () => {
            const term = input.value.toLowerCase();
            document.querySelectorAll(`#${type.toLowerCase()}Grid [data-search]`).forEach(card => {
                card.style.display = card.dataset.search.includes(term) ? '' : 'none';
            });
        });
    }
});

// Modal functions (unchanged from your original)
// Open Edit Modal
function openEditModal(btn) {
    const d = btn.dataset;
    document.getElementById('formType').value = d.type;
    document.getElementById('formId').value = d.id;
    document.getElementById('modalTitle').textContent = 'Edit ' + (d.type === 'counselor' ? 'Counselor' : 'Student');

    const fields = d.type === 'counselor'
        ? ['fname','lname','mi','title','department','email','phone','bio']
        : ['fname','lname','mi','course','year','section'];

    let html = '';
    fields.forEach(f => {
        const label = f.charAt(0).toUpperCase() + f.slice(1).replace('_', ' ');
        const value = (d[f] || '').replace(/"/g, '&quot;');
        html += `<div class="form-group">
            <label>${label}</label>
            <input type="text" name="${f}" value="${value}" required>
        </div>`;
    });
    document.getElementById('formFields').innerHTML = html;
    document.getElementById('editModal').style.display = 'flex';
}

// Open Add Modal
function openAddModal(type) {
    document.getElementById('formType').value = type;
    document.getElementById('formId').value = '';
    document.getElementById('modalTitle').textContent = 'Add New ' + (type === 'counselor' ? 'Counselor' : 'Student');
    document.getElementById('formFields').innerHTML = '';

    const fields = type === 'counselor'
        ? ['fname','lname','mi','title','department','email','phone','bio','password']
        : ['fname','lname','mi','course','year','section','password'];

    fields.forEach(f => {
        const label = f.charAt(0).toUpperCase() + f.slice(1).replace('_', ' ');
        html = `<div class="form-group">
            <label>${label}</label>
            <input type="${f==='password'?'password':'text'}" name="${f}" required>
        </div>`;
        document.getElementById('formFields').innerHTML += html;
    });
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Save (Edit or Add)
document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const text = btn.querySelector('.text');
    const spinner = btn.querySelector('.spinner');
    btn.disabled = true;
    text.style.display = 'none';
    spinner.style.display = 'inline-block';

    const formData = new FormData(this);
    formData.append('action', formData.get('id') ? 'update' : 'add');

    try {
        const res = await fetch('api/update_record.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert('Saved successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed'));
        }
    } catch(err) {
        alert('Network error');
    } finally {
        btn.disabled = false;
        text.style.display = '';
        spinner.style.display = 'none';
    }
});


// Search
document.getElementById('searchAppointments')?.addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('.appointment-card').forEach(card => {
        card.style.display = card.dataset.search.includes(term) ? '' : 'none';
    });
});

// Delete Appointment
function deleteAppointment(id) {
    showConfirmBox('Are you sure you want to delete this appointment?', function() {
        // continue after confirmation
        // ...existing code...
    });
    return;

    fetch('api/delete_appointment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.querySelector(`.appointment-card[data-id="${id}"]`)?.remove();
            alert('Appointment deleted successfully!');
        } else {
            alert('Failed to delete appointment.');
        }
    });
}

</script>
</body>
<script src="JS/confirmBox.js"></script>
<script src="JS/alertBox.js"></script>
</html>