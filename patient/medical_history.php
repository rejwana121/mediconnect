<?php
require_once '../includes/functions.php';
requireLogin('patient');

$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['id'];

// Pull medical history
$history = $conn->query("
    SELECT mh.*, u.full_name AS doctor_name, d.specialization
    FROM medical_history mh
    JOIN doctors d ON mh.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE mh.patient_id = $patient_id
    ORDER BY mh.visit_date DESC
")->fetch_all(MYSQLI_ASSOC);

// Also pull from completed appointments + prescriptions as history
$apt_history = $conn->query("
    SELECT a.appointment_date, a.reason, a.notes,
           u.full_name AS doctor_name, d.specialization,
           pr.diagnosis, pr.medicines, pr.instructions
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN prescriptions pr ON pr.appointment_id = a.id
    WHERE a.patient_id = $patient_id AND a.status = 'completed'
    ORDER BY a.appointment_date DESC
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .timeline { position: relative; padding-left: 40px; }
        .timeline::before { content: ''; position: absolute; left: 14px; top: 0; bottom: 0; width: 2px; background: #e0f2f1; }
        .timeline-item { position: relative; margin-bottom: 28px; }
        .timeline-dot {
            position: absolute; left: -33px; top: 8px;
            width: 18px; height: 18px; border-radius: 50%;
            background: linear-gradient(135deg, #00897B, #1E88E5);
            border: 3px solid white; box-shadow: 0 2px 8px rgba(0,137,123,0.4);
        }
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
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="medical_history.php" class="nav-link active"><i class="fas fa-history"></i> <span>Medical History</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>
<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Medical History</div>
        <div class="navbar-right">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>My Medical History</h5>
            <span class="badge bg-primary fs-6"><?= count($apt_history) ?> records</span>
        </div>

        <?php if(empty($apt_history)): ?>
            <div class="text-center py-5">
                <i class="fas fa-history fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No medical history yet</h5>
                <p class="text-muted">Your completed appointments and treatments will appear here.</p>
                <a href="book_appointment.php" class="btn btn-primary"><i class="fas fa-calendar-plus me-2"></i>Book Appointment</a>
            </div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach($apt_history as $h): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="card" style="border-left: 3px solid #00897B;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold mb-0"><?= htmlspecialchars(formatDoctorName($h['doctor_name'])) ?></h6>
                                        <small class="text-muted"><?= $h['specialization'] ?></small>
                                    </div>
                                    <span class="badge bg-success"><?= formatDate($h['appointment_date']) ?></span>
                                </div>
                                <?php if($h['reason']): ?>
                                    <p class="mb-2"><strong>Complaint:</strong> <?= htmlspecialchars($h['reason']) ?></p>
                                <?php endif; ?>
                                <?php if($h['diagnosis']): ?>
                                    <p class="mb-2"><strong>Diagnosis:</strong> <?= htmlspecialchars($h['diagnosis']) ?></p>
                                <?php endif; ?>
                                <?php if($h['medicines']): ?>
                                    <details>
                                        <summary class="text-primary fw-semibold" style="cursor:pointer">
                                            <i class="fas fa-pills me-1"></i>View Medicines
                                        </summary>
                                        <div class="mt-2 small">
                                            <?php foreach(explode("\n", $h['medicines']) as $m): if(trim($m)): ?>
                                                <div><i class="fas fa-circle small me-1 text-primary"></i><?= htmlspecialchars(trim($m)) ?></div>
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.getElementById('main-content').classList.toggle('expanded');}</script>
</body>
</html>
