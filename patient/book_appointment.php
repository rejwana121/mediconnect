<?php
require_once '../includes/functions.php';
requireLogin('patient');

$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['id'];

// Pre-selected doctor from URL
$selected_doctor_id = intval($_GET['doctor_id'] ?? 0);

// All approved doctors
$doctors = $conn->query("
    SELECT d.*, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.is_approved = 1 ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);

// If doctor selected, get their details
$selectedDoctor = null;
$timeSlots = [];
if ($selected_doctor_id) {
    $stmt = $conn->prepare("SELECT d.*, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
    $stmt->bind_param("i", $selected_doctor_id);
    $stmt->execute();
    $selectedDoctor = $stmt->get_result()->fetch_assoc();
}

// AJAX: Get time slots for a date
if (isset($_GET['get_slots'])) {
    $doc_id = intval($_GET['doctor_id']);
    $date   = sanitize($_GET['date']);
    $slots  = getAvailableTimeSlots($conn, $doc_id, $date);
    echo json_encode($slots);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id   = intval($_POST['doctor_id']);
    $apt_date    = sanitize($_POST['appointment_date']);
    $apt_time_12 = sanitize($_POST['appointment_time']);
    $reason      = sanitize($_POST['reason']);

    // Convert 12h to 24h
    $apt_time = date('H:i:s', strtotime($apt_time_12));

    // Check if slot still available
    $check = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $check->bind_param("iss", $doctor_id, $apt_date, $apt_time);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        setAlert('danger', 'This time slot is already booked. Please choose another.');
    } else {
        $payment_method = sanitize($_POST['payment_method'] ?? 'Pay at Clinic');
        $payment_status = ($payment_method === 'Pay at Clinic') ? 'unpaid' : 'paid';
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        if (empty($transaction_id) && $payment_status === 'paid') {
            $transaction_id = ($payment_method === 'bKash / Nagad') ? 'BK' . rand(10000000, 99999999) : 'CARD_' . rand(100000, 999999);
        }

        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, payment_method, payment_status, transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss", $patient_id, $doctor_id, $apt_date, $apt_time, $reason, $payment_method, $payment_status, $transaction_id);

        if ($stmt->execute()) {
            // Notify doctor
            $drStmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
            $drStmt->bind_param("i", $doctor_id);
            $drStmt->execute();
            $drUser = $drStmt->get_result()->fetch_assoc();
            createNotification($conn, $drUser['user_id'], 'New Appointment Request', "You have a new appointment request for $apt_date at " . date('h:i A', strtotime($apt_time)) . " (Payment: $payment_method).", 'appointment');
            createNotification($conn, $_SESSION['user_id'], 'Appointment Booked', "Your appointment has been booked for $apt_date at " . date('h:i A', strtotime($apt_time)) . ". Payment preference: $payment_method.", 'appointment');
            setAlert('success', '✅ Appointment booked successfully! (' . $payment_method . ')');
            header("Location: appointments.php"); exit();
        } else {
            setAlert('danger', 'Booking failed. Please try again.');
        }
    }
}
$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .booking-card { border: none; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .step-badge { width: 36px; height: 36px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
        .slot-btn { border: 2px solid #dee2e6; border-radius: 10px; padding: 8px 16px; cursor: pointer; transition: all 0.2s; background: white; }
        .slot-btn:hover, .slot-btn.selected { background: #667eea; color: white; border-color: #667eea; }
        .doctor-info-card { background: linear-gradient(135deg, #667eea15, #764ba215); border: 1px solid #667eea30; border-radius: 16px; }
        .payment-card-option {
            border: 2px solid #E2E8F0;
            border-radius: 14px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #FFFFFF;
            height: 100%;
        }
        .payment-card-option:hover, .payment-card-option.active {
            border-color: #0284C7;
            background: #F0F9FF;
        }
    </style>
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon-pill"><i class="fas fa-heartbeat"></i></div><span>Medi<b>Connect</b></span></div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="find_doctors.php" class="nav-link"><i class="fas fa-search"></i> <span>Find Doctors</span></a></li>
        <li class="nav-item"><a href="book_appointment.php" class="nav-link active"><i class="fas fa-calendar-plus"></i> <span>Book Appointment</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>My Appointments</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="medical_history.php" class="nav-link"><i class="fas fa-history"></i> <span>Medical History</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Book Appointment</div>
        <div class="navbar-right">
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell fa-lg text-muted"></i>
                    <?php if($unreadCount > 0): ?><span class="notification-badge"><?= $unreadCount ?></span><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <?php $notifs = getUnreadNotifications($conn, $_SESSION['user_id']); foreach($notifs as $n): ?><a class="dropdown-item" href="#"><small class="text-muted"><?= timeAgo($n['created_at']) ?></small><div><?= htmlspecialchars($n['message']) ?></div></a><?php endforeach; if(empty($notifs)): ?><div class="text-center text-muted p-3">No notifications</div><?php endif; ?>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                    <span class="d-none d-md-block"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-area">
        <?= showAlert() ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card booking-card">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0"><i class="fas fa-calendar-plus me-2 text-primary"></i>Book an Appointment</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="bookingForm">

                            <!-- Step 1: Select Doctor -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="step-badge">1</div>
                                <h6 class="fw-bold mb-0">Select a Doctor</h6>
                            </div>
                            <div class="mb-4">
                                <select name="doctor_id" id="doctorSelect" class="form-select form-select-lg" required onchange="onDoctorChange()">
                                    <option value="">-- Choose a Doctor --</option>
                                    <?php foreach($doctors as $doc): ?>
                                        <option value="<?= $doc['id'] ?>"
                                            data-fee="<?= $doc['consultation_fee'] ?>"
                                            data-spec="<?= htmlspecialchars($doc['specialization']) ?>"
                                            data-days="<?= htmlspecialchars($doc['available_days']) ?>"
                                            <?= $selected_doctor_id == $doc['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(formatDoctorName($doc['full_name'])) ?> — <?= $doc['specialization'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Doctor Info -->
                            <div id="doctorInfo" class="doctor-info-card p-3 mb-4" style="display:none">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <i class="fas fa-stethoscope text-primary"></i>
                                        <div class="fw-bold mt-1" id="infoSpec"></div>
                                        <small class="text-muted">Specialization</small>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-money-bill text-success"></i>
                                        <div class="fw-bold mt-1" id="infoFee"></div>
                                        <small class="text-muted">Consultation Fee</small>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-calendar-week text-warning"></i>
                                        <div class="fw-bold mt-1 small" id="infoDays"></div>
                                        <small class="text-muted">Available Days</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Select Date -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="step-badge">2</div>
                                <h6 class="fw-bold mb-0">Select Appointment Date</h6>
                            </div>
                            <div class="mb-4">
                                <input type="date" name="appointment_date" id="dateInput" class="form-control form-control-lg"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       max="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                                       required onchange="loadTimeSlots()">
                            </div>

                            <!-- Step 3: Select Time Slot -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="step-badge">3</div>
                                <h6 class="fw-bold mb-0">Select Time Slot</h6>
                            </div>
                            <div id="slotsContainer" class="mb-4">
                                <p class="text-muted"><i class="fas fa-info-circle me-1"></i>Select a doctor and date first</p>
                            </div>
                            <input type="hidden" name="appointment_time" id="selectedTime" required>

                            <!-- Step 4: Reason -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="step-badge">4</div>
                                <h6 class="fw-bold mb-0">Reason for Visit</h6>
                            </div>
                            <div class="mb-4">
                                <textarea name="reason" class="form-control" rows="3" placeholder="Describe your symptoms or reason for the appointment..." required></textarea>
                            </div>

                            <!-- Step 5: Payment Preference -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="step-badge">5</div>
                                <h6 class="fw-bold mb-0">Select Payment Method</h6>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="payment-card-option active w-100" id="label_clinic">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_clinic" value="Pay at Clinic" checked onchange="togglePayment(this)">
                                            <label class="form-check-label fw-bold text-dark d-block" for="pay_clinic">
                                                <i class="fas fa-hospital-user text-primary me-1"></i> Pay at Clinic
                                            </label>
                                            <small class="text-muted d-block mt-1" style="font-size:12px">Pay cash or card at chamber upon visit</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="payment-card-option w-100" id="label_bkash">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_bkash" value="bKash / Nagad" onchange="togglePayment(this)">
                                            <label class="form-check-label fw-bold text-dark d-block" for="pay_bkash">
                                                <i class="fas fa-mobile-alt text-danger me-1"></i> bKash / Nagad
                                            </label>
                                            <small class="text-muted d-block mt-1" style="font-size:12px">Instant mobile banking payment</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="payment-card-option w-100" id="label_card">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_card" value="Debit / Credit Card" onchange="togglePayment(this)">
                                            <label class="form-check-label fw-bold text-dark d-block" for="pay_card">
                                                <i class="fas fa-credit-card text-success me-1"></i> Visa / Card
                                            </label>
                                            <small class="text-muted d-block mt-1" style="font-size:12px">Online card transaction</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div id="mfsBox" class="p-3 mb-4 rounded-3 border bg-light" style="display:none;">
                                <div class="row align-items-center g-2">
                                    <div class="col-md-7">
                                        <div class="small text-muted"><i class="fas fa-info-circle text-primary me-1"></i>MediConnect Merchant / Personal Number:</div>
                                        <span class="fw-bold text-dark">01700-000000 (bKash / Nagad)</span>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold mb-1">Transaction ID (TrxID):</label>
                                        <input type="text" name="transaction_id" class="form-control form-control-sm" placeholder="e.g. BK7829104X">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn" disabled>
                                <i class="fas fa-calendar-check me-2"></i>Confirm Appointment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }

function onDoctorChange() {
    const sel = document.getElementById('doctorSelect');
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('doctorInfo');
    if (sel.value) {
        document.getElementById('infoSpec').textContent = opt.dataset.spec;
        document.getElementById('infoFee').textContent = '৳' + parseFloat(opt.dataset.fee).toLocaleString();
        document.getElementById('infoDays').textContent = opt.dataset.days;
        info.style.display = 'block';
    } else {
        info.style.display = 'none';
    }
    loadTimeSlots();
}

function loadTimeSlots() {
    const doctorId = document.getElementById('doctorSelect').value;
    const date     = document.getElementById('dateInput').value;
    const container = document.getElementById('slotsContainer');

    if (!doctorId || !date) return;

    container.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin text-primary fa-2x"></i><p class="mt-2 text-muted">Loading available slots...</p></div>';

    fetch(`book_appointment.php?get_slots=1&doctor_id=${doctorId}&date=${date}`)
        .then(r => r.json())
        .then(slots => {
            if (slots.length === 0) {
                container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-circle me-2"></i>No available slots for this date. Please choose another date.</div>';
                document.getElementById('submitBtn').disabled = true;
                return;
            }
            let html = '<div class="d-flex flex-wrap gap-2">';
            slots.forEach(slot => {
                html += `<button type="button" class="slot-btn" onclick="selectSlot(this, '${slot}')">${slot}</button>`;
            });
            html += '</div>';
            container.innerHTML = html;
            document.getElementById('selectedTime').value = '';
            document.getElementById('submitBtn').disabled = true;
        });
}

function selectSlot(btn, time) {
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('selectedTime').value = time;
    document.getElementById('submitBtn').disabled = false;
}

function togglePayment(radio) {
    document.querySelectorAll('.payment-card-option').forEach(el => el.classList.remove('active'));
    radio.closest('.payment-card-option').classList.add('active');
    const mfsBox = document.getElementById('mfsBox');
    if (radio.value === 'bKash / Nagad') {
        mfsBox.style.display = 'block';
    } else {
        mfsBox.style.display = 'none';
    }
}

// Init if doctor pre-selected
window.onload = function() {
    if (document.getElementById('doctorSelect').value) onDoctorChange();
}
</script>
</body>
</html>
