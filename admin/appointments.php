<?php
require_once '../includes/functions.php';
requireLogin('admin');

$filter = sanitize($_GET['filter'] ?? 'all');
$where = "1=1";
if ($filter !== 'all') $where .= " AND a.status = '$filter'";

$appointments = $conn->query("
    SELECT a.*,
           u1.full_name AS patient_name,
           u2.full_name AS doctor_name,
           d.specialization, d.consultation_fee
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u1 ON p.user_id = u1.id
    JOIN users u2 ON d.user_id = u2.id
    WHERE $where
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - MediConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon-pill"><i class="fas fa-heartbeat"></i></div><span>Medi<b>Connect</b></span></div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="doctors.php" class="nav-link"><i class="fas fa-user-md"></i> <span>Doctors</span></a></li>
        <li class="nav-item"><a href="patients.php" class="nav-link"><i class="fas fa-users"></i> <span>Patients</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link active"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li class="nav-item"><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">All Appointments</div>
        <div class="navbar-right">
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2 text-primary"></i>All Appointments</h5>
            <span class="badge bg-primary fs-6"><?= count($appointments) ?> total</span>
        </div>

        <ul class="nav nav-pills mb-4">
            <?php $filters = ['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed','cancelled'=>'Cancelled'];
            foreach($filters as $key => $label): ?>
                <li class="nav-item"><a class="nav-link <?= $filter===$key?'active':'' ?>" href="?filter=<?= $key ?>"><?= $label ?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Date & Time</th><th>Reason</th><th>Fee</th><th>Status</th><th>Booked</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($appointments)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No appointments found</td></tr>
                            <?php else: foreach($appointments as $i => $apt): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($apt['patient_name']) ?></td>
                                    <td>
                                        <?= htmlspecialchars(formatDoctorName($apt['doctor_name'])) ?>
                                        <small class="d-block text-muted"><?= $apt['specialization'] ?></small>
                                    </td>
                                    <td>
                                        <?= formatDate($apt['appointment_date']) ?>
                                        <small class="d-block text-muted"><?= formatTime($apt['appointment_time']) ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($apt['reason'] ?? '—') ?></small></td>
                                    <td>
                                        <div class="fw-bold">৳<?= number_format($apt['consultation_fee'], 0) ?></div>
                                        <?php if(($apt['payment_status'] ?? '') === 'paid'): ?>
                                            <span class="badge bg-success" style="font-size:10px" title="TrxID: <?= htmlspecialchars($apt['transaction_id'] ?? '') ?>"><i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($apt['payment_method']) ?> (Paid)</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border" style="font-size:10px"><?= htmlspecialchars($apt['payment_method'] ?? 'Pay at Clinic') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getAppointmentStatus($apt['status']) ?></td>
                                    <td><small class="text-muted"><?= timeAgo($apt['created_at']) ?></small></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }</script>
</body>
</html>
