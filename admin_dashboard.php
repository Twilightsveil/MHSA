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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard • MHSA</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    <!-- SIDEBAR -->
 <aside class="sidebar">
    <div class="sidebar-menu">
        <ul>
            <li><a href="#" class="active" data-section="counselors"><i class="fa-solid fa-user-tie"></i> Counselors <span class="badge"><?=count($counselors)?></span></a></li>
            <li><a href="#" data-section="students"><i class="fa-solid fa-users"></i> Students <span class="badge"><?=count($students)?></span></a></li>
            <li><a href="#" data-section="appointments"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
            
            <!-- LOGOUT BUTTON AT THE BOTTOM -->
            <li style="margin-top: auto; padding-top: 20px; border-top: 1px solid #eee;">
                <a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
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
                    <h3><i class="fa-solid fa-user-tie"></i> Counselors</h3>
                    <button class="btn small" onclick="openAddModal('counselor')"><i class="fa-solid fa-plus"></i> Add New</button>
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
                                <i class="fa-solid fa-pen-to-square"></i> Edit
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
                    <h3><i class="fa-solid fa-users"></i> Students</h3>
                    <button class="btn small" onclick="openAddModal('student')"><i class="fa-solid fa-plus"></i> Add New</button>
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
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- EDIT / ADD MODAL -->
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
                <button type="submit" class="btn" style="margin-left:12px;">
                    <span class="text">Save Changes</span>
                    <span class="spinner" style="display:none;">Saving...</span>
                </button>
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

// Live Search
['Counselors', 'Students'].forEach(type => {
    const input = document.getElementById(`search${type}`);
    if (!input) return;
    input.addEventListener('input', () => {
        const term = input.value.toLowerCase();
        document.querySelectorAll(`#${type.toLowerCase()}Grid [data-search]`).forEach(card => {
            card.style.display = card.dataset.search.includes(term) ? '' : 'none';
        });
    });
});

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

// Close modal on outside click
window.addEventListener('click', e => {
    if (e.target === document.getElementById('editModal')) closeModal();
});
</script>
</body>
</html>