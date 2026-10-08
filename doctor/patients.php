<?php
require_once '../includes/functions.php';
requireLogin('doctor');

$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['id'];

// Get all unique patients who booked with this doctor
$patients = $conn->query("
    SELECT DISTINCT u.full_name, u.email, u.phone, u.gender,
           p.blood_group, p.allergies, p.emergency_contact, p.id AS patient_id,
           COUNT(a.id) AS total_visits,
           MAX(a.appointment_date) AS last_visit
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = $doctor_id
    GROUP BY p.id
    ORDER BY last_visit DESC
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon-pill"><i class="fas fa-heartbeat"></i></div><span>Medi<b>Connect</b></span></div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
        <li class="nav-item"><a href="patients.php" class="nav-link active"><i class="fas fa-users"></i> <span>My Patients</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="schedule.php" class="nav-link"><i class="fas fa-clock"></i> <span>My Schedule</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">My Patients</div>
        <div class="navbar-right">
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2" style="background: linear-gradient(135deg,#00897B,#1E88E5)"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
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
            <h5 class="fw-bold mb-0"><i class="fas fa-users me-2 text-primary"></i>My Patients</h5>
            <span class="badge bg-primary fs-6"><?= count($patients) ?> patients</span>
        </div>

        <?php if(empty($patients)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No patients yet</h5>
                <p class="text-muted">Patients will appear here after they book appointments with you.</p>
            </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach($patients as $p): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100" style="border-radius:16px; border-left: 4px solid #00897B;">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="user-avatar me-3" style="width:50px;height:50px;font-size:20px">
                                    <?= strtoupper(substr($p['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($p['full_name']) ?></h6>
                                    <small class="text-muted"><?= $p['gender'] ?? 'N/A' ?></small>
                                </div>
                                <?php if($p['blood_group']): ?>
                                    <span class="badge bg-danger ms-auto"><?= $p['blood_group'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="border-top pt-3">
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <i class="fas fa-phone text-primary me-1"></i>
                                        <?= htmlspecialchars($p['phone'] ?? 'N/A') ?>
                                    </div>
                                    <div class="col-6">
                                        <i class="fas fa-calendar-check text-success me-1"></i>
                                        <?= $p['total_visits'] ?> visit(s)
                                    </div>
                                    <div class="col-12">
                                        <i class="fas fa-clock text-warning me-1"></i>
                                        Last: <?= formatDate($p['last_visit']) ?>
                                    </div>
                                    <?php if($p['allergies']): ?>
                                    <div class="col-12">
                                        <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                        <strong>Allergies:</strong> <?= htmlspecialchars($p['allergies']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }</script>
</body>
</html>
