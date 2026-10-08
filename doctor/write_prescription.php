<?php
require_once '../includes/functions.php';
requireLogin('doctor');

$stmt = $conn->prepare("SELECT d.id AS doctor_id FROM doctors d WHERE d.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['doctor_id'];

$apt_id = intval($_GET['apt'] ?? 0);
if (!$apt_id) { header("Location: appointments.php"); exit(); }

// Verify appointment belongs to this doctor
$stmt = $conn->prepare("
    SELECT a.*, u.full_name AS patient_name, p.blood_group, p.allergies, p.id AS patient_rec_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.id = ? AND a.doctor_id = ?
");
$stmt->bind_param("ii", $apt_id, $doctor_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
if (!$appointment) { setAlert('danger', 'Appointment not found.'); header("Location: appointments.php"); exit(); }

// Check existing prescription
$existing = $conn->query("SELECT * FROM prescriptions WHERE appointment_id = $apt_id")->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis      = sanitize($_POST['diagnosis']);
    $medicines      = sanitize($_POST['medicines']);
    $instructions   = sanitize($_POST['instructions']);
    $follow_up_date = sanitize($_POST['follow_up_date']);
    $patient_id     = $appointment['patient_rec_id'];

    $follow_up = !empty($follow_up_date) ? $follow_up_date : null;

    if ($existing) {
        $stmt = $conn->prepare("UPDATE prescriptions SET diagnosis=?, medicines=?, instructions=?, follow_up_date=? WHERE appointment_id=?");
        $stmt->bind_param("ssssi", $diagnosis, $medicines, $instructions, $follow_up, $apt_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, diagnosis, medicines, instructions, follow_up_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissss", $apt_id, $doctor_id, $patient_id, $diagnosis, $medicines, $instructions, $follow_up);
    }

    if ($stmt->execute()) {
        // Mark appointment as completed
        $conn->query("UPDATE appointments SET status = 'completed' WHERE id = $apt_id");
        // Notify patient
        $pUserStmt = $conn->prepare("SELECT user_id FROM patients WHERE id = ?");
        $pUserStmt->bind_param("i", $patient_id);
        $pUserStmt->execute();
        $pUser = $pUserStmt->get_result()->fetch_assoc();
        createNotification($conn, $pUser['user_id'], 'Prescription Ready', 'Your doctor has written a prescription for your appointment on ' . formatDate($appointment['appointment_date']) . '.', 'prescription');
        setAlert('success', '✅ Prescription saved successfully!');
        header("Location: appointments.php"); exit();
    } else {
        setAlert('danger', 'Failed to save prescription.');
    }
}

// Medical history of patient
$history = $conn->query("
    SELECT mh.*, u.full_name AS doctor_name
    FROM medical_history mh
    JOIN doctors d ON mh.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE mh.patient_id = {$appointment['patient_rec_id']}
    ORDER BY mh.visit_date DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Prescription - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .rx-card { border: none; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .rx-header { background: linear-gradient(135deg, #198754, #0d6efd); color: white; border-radius: 20px 20px 0 0; padding: 24px; }
        .medicine-row { background: #f8f9fa; border-radius: 10px; padding: 12px; margin-bottom: 8px; }
        .patient-info-badge { background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 10px; padding: 12px; }
    </style>
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon-pill"><i class="fas fa-heartbeat"></i></div><span>Medi<b>Connect</b></span></div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link active"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
        <li class="nav-item"><a href="patients.php" class="nav-link"><i class="fas fa-users"></i> <span>My Patients</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="schedule.php" class="nav-link"><i class="fas fa-clock"></i> <span>My Schedule</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Write Prescription</div>
        <div class="navbar-right">
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2" style="background:#198754"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                    <span class="d-none d-md-block"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-area">
        <?= showAlert() ?>
        <div class="row g-4">
            <!-- Prescription Form -->
            <div class="col-lg-8">
                <div class="rx-card">
                    <div class="rx-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-1"><i class="fas fa-prescription me-2"></i>
                                    <?= $existing ? 'Update' : 'Write' ?> Prescription
                                </h4>
                                <p class="mb-0 opacity-75">For: <?= htmlspecialchars($appointment['patient_name']) ?></p>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold"><?= formatDate($appointment['appointment_date']) ?></div>
                                <small class="opacity-75"><?= formatTime($appointment['appointment_time']) ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <!-- Patient Info -->
                        <div class="patient-info-badge mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Patient:</strong> <?= htmlspecialchars($appointment['patient_name']) ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Blood Group:</strong>
                                    <?php if($appointment['blood_group']): ?>
                                        <span class="badge bg-danger"><?= $appointment['blood_group'] ?></span>
                                    <?php else: ?> N/A <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Allergies:</strong> <?= htmlspecialchars($appointment['allergies'] ?? 'None') ?>
                                </div>
                            </div>
                            <?php if($appointment['reason']): ?>
                                <div class="mt-2"><strong>Chief Complaint:</strong> <?= htmlspecialchars($appointment['reason']) ?></div>
                            <?php endif; ?>
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Diagnosis <span class="text-danger">*</span></label>
                                <textarea name="diagnosis" class="form-control" rows="2" placeholder="Primary diagnosis..." required><?= htmlspecialchars($existing['diagnosis'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Medicines & Dosage <span class="text-danger">*</span></label>
                                <div id="medicineList">
                                    <!-- Filled by JS -->
                                </div>
                                <button type="button" class="btn btn-outline-success btn-sm mt-2" onclick="addMedicineRow()">
                                    <i class="fas fa-plus me-1"></i>Add Medicine
                                </button>
                                <textarea name="medicines" id="medicinesHidden" class="form-control mt-2" rows="3"
                                          placeholder="Or type medicines manually: Medicine Name - Dosage - Duration"
                                          required><?= htmlspecialchars($existing['medicines'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Instructions for Patient</label>
                                <textarea name="instructions" class="form-control" rows="3"
                                          placeholder="Rest advice, diet, lifestyle changes..."><?= htmlspecialchars($existing['instructions'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Follow-up Date</label>
                                <input type="date" name="follow_up_date" class="form-control"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       value="<?= htmlspecialchars($existing['follow_up_date'] ?? '') ?>">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success flex-grow-1 py-2">
                                    <i class="fas fa-save me-2"></i><?= $existing ? 'Update' : 'Save' ?> Prescription
                                </button>
                                <a href="appointments.php" class="btn btn-outline-secondary py-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Patient History Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>Previous Visits</h6>
                    </div>
                    <div class="card-body">
                        <?php if(empty($history)): ?>
                            <p class="text-muted text-center">No previous history</p>
                        <?php else: foreach($history as $h): ?>
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold small"><?= htmlspecialchars($h['title']) ?></div>
                                <div class="text-muted small"><?= formatDate($h['visit_date']) ?></div>
                                <?php if($h['diagnosis']): ?>
                                    <div class="small mt-1"><strong>Dx:</strong> <?= htmlspecialchars(substr($h['diagnosis'], 0, 60)) ?>...</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Quick Templates -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-clipboard me-2 text-success"></i>Quick Templates</h6>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-outline-secondary btn-sm w-100 mb-2 text-start" onclick="fillTemplate('fever')">
                            <i class="fas fa-thermometer me-2"></i>Fever Template
                        </button>
                        <button class="btn btn-outline-secondary btn-sm w-100 mb-2 text-start" onclick="fillTemplate('cold')">
                            <i class="fas fa-wind me-2"></i>Cold & Cough Template
                        </button>
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start" onclick="fillTemplate('general')">
                            <i class="fas fa-pills me-2"></i>General Checkup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }

const templates = {
    fever: "Paracetamol 500mg - 1 tablet - 3x daily after meals (5 days)\nIbuprofen 400mg - 1 tablet - 2x daily after meals (3 days)\nORS Saline - As needed",
    cold: "Cetirizine 10mg - 1 tablet - 1x daily at night (7 days)\nAmoxicillin 500mg - 1 capsule - 3x daily (5 days)\nVitamin C 500mg - 1 tablet - 1x daily",
    general: "Multivitamin - 1 tablet - 1x daily after breakfast\nCalcium + Vitamin D - 1 tablet - 1x daily"
};

function fillTemplate(type) {
    document.getElementById('medicinesHidden').value = templates[type];
}
</script>
</body>
</html>
