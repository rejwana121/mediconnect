<?php
require_once '../includes/functions.php';
requireLogin('doctor');

// Get doctor record
$stmt = $conn->prepare("SELECT d.*, u.full_name, u.email, u.phone, u.gender FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['id'];

// Stats
$totalAppointments  = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE doctor_id = $doctor_id")->fetch_assoc()['c'];
$todayCount         = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE doctor_id = $doctor_id AND appointment_date = CURDATE()")->fetch_assoc()['c'];
$pendingCount       = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE doctor_id = $doctor_id AND status = 'pending'")->fetch_assoc()['c'];
$completedCount     = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE doctor_id = $doctor_id AND status = 'completed'")->fetch_assoc()['c'];

// Today's appointments
$todayAppointments = $conn->query("
    SELECT a.*, u.full_name AS patient_name, p.blood_group
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = $doctor_id AND a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
")->fetch_all(MYSQLI_ASSOC);

// Pending appointments
$pendingAppointments = $conn->query("
    SELECT a.*, u.full_name AS patient_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = $doctor_id AND a.status = 'pending'
    ORDER BY a.appointment_date ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon-pill">
            <i class="fas fa-heartbeat"></i>
        </div>
        <span>Medi<b>Connect</b></span>
    </div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item">
            <a href="appointments.php" class="nav-link">
                <i class="fas fa-calendar-check"></i> <span>Appointments</span>
                <?php if($pendingCount > 0): ?>
                    <span class="badge bg-danger ms-auto"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item"><a href="patients.php" class="nav-link"><i class="fas fa-users"></i> <span>My Patients</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="schedule.php" class="nav-link"><i class="fas fa-clock"></i> <span>My Schedule</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <div class="navbar-title">Doctor Dashboard</div>
        <div class="navbar-right">
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell fa-lg text-muted"></i>
                    <?php if($unreadCount > 0): ?><span class="notification-badge"><?= $unreadCount ?></span><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <?php $notifs = getUnreadNotifications($conn, $_SESSION['user_id']);
                    foreach($notifs as $n): ?>
                        <a class="dropdown-item" href="#"><small class="text-muted"><?= timeAgo($n['created_at']) ?></small><div><?= htmlspecialchars($n['message']) ?></div></a>
                    <?php endforeach;
                    if(empty($notifs)): ?><div class="text-center text-muted p-3">No notifications</div><?php endif; ?>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2" style="background:#198754"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
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

        <?php if(!$doctor['is_approved']): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4">
            <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
            <div>
                <strong>Account Pending Approval</strong><br>
                Your doctor account is awaiting admin approval. You'll be notified once approved.
            </div>
        </div>
        <?php endif; ?>

        <div class="welcome-banner mb-4">
            <div>
                <h4 class="mb-1">Good day, <?= htmlspecialchars(formatDoctorName($doctor['full_name'])) ?>! 👨‍⚕️</h4>
                <p class="mb-0 opacity-75"><?= $doctor['specialization'] ?> · <?= $todayCount ?> appointment(s) today</p>
            </div>
            <div class="text-end d-none d-md-block">
                <div class="fw-bold fs-5">৳<?= number_format($doctor['consultation_fee'], 0) ?></div>
                <small class="opacity-75">Consultation Fee</small>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-number"><?= $totalAppointments ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-number"><?= $todayCount ?></div>
                    <div class="stat-label">Today's Appointments</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card stat-yellow">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?= $pendingCount ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?= $completedCount ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Today's Appointments -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-day me-2 text-primary"></i>Today's Schedule</h6>
                        <span class="badge bg-primary"><?= date('d M Y') ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($todayAppointments)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No appointments scheduled for today</td></tr>
                                    <?php else: foreach($todayAppointments as $apt): ?>
                                        <tr>
                                            <td class="fw-bold"><?= formatTime($apt['appointment_time']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($apt['patient_name']) ?>
                                                <?php if($apt['blood_group']): ?>
                                                    <span class="badge bg-danger ms-1"><?= $apt['blood_group'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small class="text-muted"><?= htmlspecialchars($apt['reason'] ?? 'General') ?></small></td>
                                            <td>
                                                <?= getAppointmentStatus($apt['status']) ?>
                                                <div class="mt-1">
                                                    <?php if(($apt['payment_status'] ?? '') === 'paid'): ?>
                                                        <span class="badge bg-success" style="font-size:9.5px"><i class="fas fa-check me-1"></i><?= htmlspecialchars($apt['payment_method']) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border" style="font-size:9.5px"><?= htmlspecialchars($apt['payment_method'] ?? 'Pay at Clinic') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="write_prescription.php?apt=<?= $apt['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-prescription"></i>
                                                </a>
                                                <a href="appointments.php?complete=<?= $apt['id'] ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-clock me-2 text-warning"></i>Pending Requests</h6>
                        <a href="appointments.php" class="btn btn-sm btn-outline-warning">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if(empty($pendingAppointments)): ?>
                            <p class="text-muted text-center py-3">No pending requests</p>
                        <?php else: foreach($pendingAppointments as $apt): ?>
                            <div class="d-flex align-items-center justify-content-between mb-3 p-2 bg-light rounded">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($apt['patient_name']) ?></div>
                                    <small class="text-muted"><?= formatDate($apt['appointment_date']) ?> at <?= formatTime($apt['appointment_time']) ?></small>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="appointments.php?confirm=<?= $apt['id'] ?>" class="btn btn-sm btn-success" title="Confirm">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="appointments.php?cancel=<?= $apt['id'] ?>" class="btn btn-sm btn-danger" title="Cancel">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('main-content').classList.toggle('expanded');
}
</script>
</body>
</html>
