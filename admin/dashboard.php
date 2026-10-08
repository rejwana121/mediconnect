<?php
require_once '../includes/functions.php';
requireLogin('admin');

// Dashboard stats
$stats = [];
$stats['total_doctors']      = $conn->query("SELECT COUNT(*) as c FROM doctors")->fetch_assoc()['c'];
$stats['total_patients']     = $conn->query("SELECT COUNT(*) as c FROM patients")->fetch_assoc()['c'];
$stats['total_appointments'] = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'];
$stats['pending_approvals']  = $conn->query("SELECT COUNT(*) as c FROM doctors WHERE is_approved = 0")->fetch_assoc()['c'];
$stats['today_appointments'] = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc()['c'];
$stats['pending_appointments']= $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='pending'")->fetch_assoc()['c'];

// Recent appointments
$recentAppointments = $conn->query("
    SELECT a.*, 
           u1.full_name AS patient_name, 
           u2.full_name AS doctor_name,
           d.specialization
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u1 ON p.user_id = u1.id
    JOIN users u2 ON d.user_id = u2.id
    ORDER BY a.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Recent users
$recentUsers = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MediConnect</title>
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
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="doctors.php" class="nav-link">
                <i class="fas fa-user-md"></i> Doctors
                <?php if($stats['pending_approvals'] > 0): ?>
                    <span class="badge bg-danger ms-auto"><?= $stats['pending_approvals'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a href="patients.php" class="nav-link">
                <i class="fas fa-users"></i> Patients
            </a>
        </li>
        <li class="nav-item">
            <a href="appointments.php" class="nav-link">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
        </li>
        <li class="nav-item">
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a href="messages.php" class="nav-link">
                <i class="fas fa-envelope"></i> Messages
            </a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user-cog"></i> Profile
            </a>
        </li>
        <li class="nav-item mt-3">
            <a href="../auth/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">

    <!-- Top Navbar -->
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <div class="navbar-title">Admin Dashboard</div>
        <div class="navbar-right">
            <!-- Notifications -->
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell fa-lg text-muted"></i>
                    <?php if($unreadCount > 0): ?>
                        <span class="notification-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <h6 class="dropdown-header">Notifications</h6>
                    <?php
                    $notifs = getUnreadNotifications($conn, $_SESSION['user_id']);
                    if (empty($notifs)): ?>
                        <div class="dropdown-item text-muted text-center">No new notifications</div>
                    <?php else: foreach($notifs as $n): ?>
                        <a class="dropdown-item" href="#">
                            <small class="d-block text-muted"><?= timeAgo($n['created_at']) ?></small>
                            <?= htmlspecialchars($n['message']) ?>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- User Menu -->
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-block"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <i class="fas fa-chevron-down ms-1 small text-muted"></i>
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

        <!-- Welcome Banner -->
        <div class="welcome-banner mb-4">
            <div>
                <h4 class="mb-1">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>! 👋</h4>
                <p class="mb-0 opacity-75">Here's what's happening with MediConnect today.</p>
            </div>
            <div class="text-end d-none d-md-block">
                <small class="opacity-75"><?= date('l, d F Y') ?></small>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-user-md"></i></div>
                    <div class="stat-number"><?= $stats['total_doctors'] ?></div>
                    <div class="stat-label">Total Doctors</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?= $stats['total_patients'] ?></div>
                    <div class="stat-label">Total Patients</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="stat-card stat-purple">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-number"><?= $stats['total_appointments'] ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-number"><?= $stats['today_appointments'] ?></div>
                    <div class="stat-label">Today's Appointments</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="stat-card stat-yellow">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?= $stats['pending_appointments'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="stat-card stat-red">
                    <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-number"><?= $stats['pending_approvals'] ?></div>
                    <div class="stat-label">Pending Approvals</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Appointments Table -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-check me-2 text-primary"></i>Recent Appointments</h6>
                        <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($recentAppointments)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No appointments yet</td></tr>
                                    <?php else: foreach($recentAppointments as $i => $apt): ?>
                                        <tr>
                                            <td><?= $i+1 ?></td>
                                            <td><?= htmlspecialchars($apt['patient_name']) ?></td>
                                            <td>
                                                <?= htmlspecialchars(formatDoctorName($apt['doctor_name'])) ?>
                                                <small class="d-block text-muted"><?= $apt['specialization'] ?></small>
                                            </td>
                                            <td>
                                                <?= formatDate($apt['appointment_date']) ?>
                                                <small class="d-block text-muted"><?= formatTime($apt['appointment_time']) ?></small>
                                            </td>
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
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-user-plus me-2 text-success"></i>New Members</h6>
                        <a href="patients.php" class="btn btn-sm btn-outline-success">View All</a>
                    </div>
                    <div class="card-body">
                        <?php foreach($recentUsers as $user): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="user-avatar me-3" style="background: <?= $user['role'] === 'doctor' ? '#198754' : '#0d6efd' ?>">
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?= htmlspecialchars($user['full_name']) ?></div>
                                <small class="text-muted"><?= ucfirst($user['role']) ?> · <?= timeAgo($user['created_at']) ?></small>
                            </div>
                            <span class="badge <?= $user['role'] === 'doctor' ? 'bg-success' : 'bg-primary' ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($recentUsers)): ?>
                            <p class="text-muted text-center">No users yet</p>
                        <?php endif; ?>
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
