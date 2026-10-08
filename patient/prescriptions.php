<?php
require_once '../includes/functions.php';
requireLogin('patient');

$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['id'];

// Specific prescription view
$specific_id = intval($_GET['id'] ?? 0);

if ($specific_id) {
    $stmt = $conn->prepare("
        SELECT pr.*, u.full_name AS doctor_name, d.specialization, d.qualification,
               a.appointment_date, a.appointment_time, a.reason
        FROM prescriptions pr
        JOIN doctors d ON pr.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN appointments a ON pr.appointment_id = a.id
        WHERE pr.id = ? AND pr.patient_id = ?
    ");
    $stmt->bind_param("ii", $specific_id, $patient_id);
    $stmt->execute();
    $prescription = $stmt->get_result()->fetch_assoc();
} else {
    $prescription = null;
}

// All prescriptions
$prescriptions = $conn->query("
    SELECT pr.*, u.full_name AS doctor_name, d.specialization, a.appointment_date
    FROM prescriptions pr
    JOIN doctors d ON pr.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN appointments a ON pr.appointment_id = a.id
    WHERE pr.patient_id = $patient_id
    ORDER BY pr.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .rx-view { border: 2px solid #dee2e6; border-radius: 16px; }
        .rx-top { background: linear-gradient(135deg, #1a1a2e, #0f3460); color: white; border-radius: 14px 14px 0 0; padding: 24px; }
        .rx-symbol { font-size: 40px; font-weight: 900; color: #ffd700; font-style: italic; }
        @media print { .sidebar, .top-navbar, .no-print { display: none !important; } .main-content { margin: 0 !important; } }
    </style>
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon-pill"><i class="fas fa-heartbeat"></i></div><span>Medi<b>Connect</b></span></div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="find_doctors.php" class="nav-link"><i class="fas fa-search"></i> <span>Find Doctors</span></a></li>
        <li class="nav-item"><a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> <span>Book Appointment</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>My Appointments</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link active"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="medical_history.php" class="nav-link"><i class="fas fa-history"></i> <span>Medical History</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar no-print">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">My Prescriptions</div>
        <div class="navbar-right">
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                    <span class="d-none d-md-block"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-area">
        <?php if($prescription): ?>
            <!-- View Specific Prescription -->
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <a href="prescriptions.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
                <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Prescription</button>
            </div>

            <div class="rx-view">
                <div class="rx-top">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="rx-symbol">℞</div>
                            <h4 class="mt-2 mb-1"><?= htmlspecialchars(formatDoctorName($prescription['doctor_name'])) ?></h4>
                            <div><?= htmlspecialchars($prescription['specialization']) ?></div>
                            <?php if($prescription['qualification']): ?>
                                <div class="opacity-75 small"><?= htmlspecialchars($prescription['qualification']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">MediConnect</div>
                            <div class="opacity-75 small">Date: <?= formatDate($prescription['appointment_date']) ?></div>
                            <div class="opacity-75 small">Rx #<?= $prescription['id'] ?></div>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Patient Name:</strong> <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Visit Date:</strong> <?= formatDate($prescription['appointment_date']) ?>
                        </div>
                    </div>

                    <?php if($prescription['reason']): ?>
                        <div class="mb-4">
                            <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-comment-medical me-2"></i>Chief Complaint</h6>
                            <p><?= htmlspecialchars($prescription['reason']) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-stethoscope me-2"></i>Diagnosis</h6>
                        <p><?= nl2br(htmlspecialchars($prescription['diagnosis'])) ?></p>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-pills me-2"></i>Medicines</h6>
                        <?php foreach(explode("\n", $prescription['medicines']) as $med): ?>
                            <?php if(trim($med)): ?>
                                <div class="d-flex align-items-start mb-2">
                                    <i class="fas fa-circle small mt-1 me-2 text-primary"></i>
                                    <span><?= htmlspecialchars(trim($med)) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if($prescription['instructions']): ?>
                        <div class="mb-4">
                            <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-info-circle me-2"></i>Instructions</h6>
                            <p><?= nl2br(htmlspecialchars($prescription['instructions'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if($prescription['follow_up_date']): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <strong>Follow-up Date:</strong> <?= formatDate($prescription['follow_up_date']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-end mt-4 pt-4 border-top">
                        <div class="fw-bold"><?= htmlspecialchars(formatDoctorName($prescription['doctor_name'])) ?></div>
                        <div class="text-muted"><?= $prescription['specialization'] ?></div>
                        <div class="mt-2 text-muted small">Digital Prescription - MediConnect</div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Prescriptions List -->
            <h5 class="fw-bold mb-4"><i class="fas fa-file-medical me-2 text-primary"></i>My Prescriptions</h5>

            <?php if(empty($prescriptions)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-medical fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No prescriptions yet</h5>
                    <p class="text-muted">Your prescriptions will appear here after your consultations</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach($prescriptions as $rx): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card shadow-sm h-100" style="border-left: 4px solid #0d6efd; border-radius: 12px;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="fw-bold text-primary fs-4" style="font-style:italic">℞</div>
                                        <small class="text-muted"><?= formatDate($rx['appointment_date']) ?></small>
                                    </div>
                                    <h6 class="fw-bold"><?= htmlspecialchars(formatDoctorName($rx['doctor_name'])) ?></h6>
                                    <p class="text-muted small mb-2"><?= $rx['specialization'] ?></p>
                                    <p class="small mb-3"><strong>Diagnosis:</strong> <?= htmlspecialchars(substr($rx['diagnosis'], 0, 60)) ?>...</p>
                                    <?php if($rx['follow_up_date']): ?>
                                        <span class="badge bg-warning text-dark mb-2">
                                            <i class="fas fa-calendar me-1"></i>Follow-up: <?= formatDate($rx['follow_up_date']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <a href="?id=<?= $rx['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-2">
                                        <i class="fas fa-eye me-1"></i>View Prescription
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }</script>
</body>
</html>
