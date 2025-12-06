let selectedDateTime = null;
let selectedCounselor = null;
let dateCalendar = null;
let mainCalendar = null;

document.addEventListener('DOMContentLoaded', function () {
    // Main Dashboard Calendar
    mainCalendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
        slotMinTime: '08:00:00',
        slotMaxTime: '17:00:00',
        height: 'auto',
        selectable: true,
        select: function (info) {
            selectedDateTime = info.start.toISOString();
            openBookingModal();
            if (dateCalendar) dateCalendar.select(info.start);
        },
        events: [] // PHP already outputs events above
    });
    mainCalendar.render();

    // Modal Calendar
    dateCalendar = new FullCalendar.Calendar(document.getElementById('dateTimeCalendar'), {
        initialView: 'timeGridWeek',
        slotMinTime: '08:00:00',
        slotMaxTime: '17:00:00',
        selectable: true,
        select: function (info) {
            selectedDateTime = info.start.toISOString();
            document.getElementById('selectedTimeDisplay').textContent = info.start.toLocaleString();
            document.getElementById('nextBtn').disabled = false;
        }
    });
    dateCalendar.render();
});

function openBookingModal() {
    document.getElementById('bookingModal').style.display = 'flex';
    resetBookingFlow();
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
    resetBookingFlow();
}

function resetBookingFlow() {
    document.getElementById('step1Content').style.display = 'block';
    document.getElementById('step2Content').style.display = 'none';
    document.getElementById('step3Content').style.display = 'none';
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('step1').classList.add('active');
    document.getElementById('nextBtn').disabled = true;
    selectedDateTime = null;
    selectedCounselor = null;
}

document.getElementById('nextBtn').onclick = function () {
    document.getElementById('step1Content').style.display = 'none';
    document.getElementById('step2Content').style.display = 'block';
    document.getElementById('step1').classList.remove('active');
    document.getElementById('step2').classList.add('active');

    fetch('ajax_available_counselors.php?datetime=' + encodeURIComponent(selectedDateTime))
        .then(r => r.json())
        .then(counselors => {
            const list = document.getElementById('counselorList');
            list.innerHTML = counselors.length === 0
                ? '<p style="text-align:center;color:#e74c3c;padding:40px;">No counselors available at this time.</p>'
                : '';
            counselors.forEach(c => {
                const photo = c.photo || 'https://via.placeholder.com/70/8e44ad/white?text=' + c.fname[0];
                list.innerHTML += `
                    <div class="counselor-card available" onclick="selectCounselor(${c.counselor_id}, '${c.fname} ${c.lname}', '${photo}')">
                        <div style="display:flex;gap:16px;align-items:center;">
                            <img src="${photo}" class="counselor-photo">
                            <div>
                                <h4 style="margin:0;color:#4b2b63;">${c.fname} ${c.lname}</h4>
                                <p style="margin:4px 0;color:#8e44ad;font-weight:600;">${c.title || 'Guidance Counselor'}</p>
                                <small style="color:#27ae60;">Available</small>
                            </div>
                        </div>
                    </div>
                `;
            });
        });
};

function backToCalendar() {
    document.getElementById('step2Content').style.display = 'none';
    document.getElementById('step1Content').style.display = 'block';
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step1').classList.add('active');
}

function selectCounselor(id, name, photo) {
    selectedCounselor = { id, name, photo };
    document.getElementById('finalName').textContent = name;
    document.getElementById('finalPhoto').src = photo;

    document.getElementById('step2Content').style.display = 'none';
    document.getElementById('step3Content').style.display = 'block';
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step3').classList.add('active');
}

function backToCounselors() {
    document.getElementById('step3Content').style.display = 'none';
    document.getElementById('step2Content').style.display = 'block';
    document.getElementById('step3').classList.remove('active');
    document.getElementById('step2').classList.add('active');
}

function confirmBooking() {
    const reason = document.getElementById('reasonField').value.trim();
    fetch('ajax_book.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `counselor_id=${selectedCounselor.id}&student_id=<?= $student_id ?>&datetime=${selectedDateTime}&reason=${encodeURIComponent(reason)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlertBox("Appointment booked successfully!", function() {
                closeBookingModal();
                location.reload();
            });
        } else {
            showAlertBox("This time slot was just taken. Please choose another.");
        }
    });
}