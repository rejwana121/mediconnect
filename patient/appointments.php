<?php
require_once '../includes/functions.php';
requireLogin('patient');

$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['id'];

// Cancel appointment
if (isset($_GET['cancel'])) {
    $apt_id = intval($_GET['cancel']);
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND patient_id = ? AND appointment_date >= CURDATE()");
    $stmt->bind_param("ii", $apt_id, $patient_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        setAlert('success', 'Appointment cancelled successfully.');
    } else {
        setAlert('danger', 'Cannot cancel this appointment.');
    }
    header("Location: appointments.php"); exit();
}

// Filter
$filter = sanitize($_GET['filter'] ?? 'all');
$where = "a.patient_id = $patient_id";
if ($filter !== 'all') $where .= " AND a.status = '$filter'";

$appointments = $conn->query("
    SELECT a.*, u.full_name AS doctor_name, d.specialization, d.consultation_fee,
           p.id AS prescription_id
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN prescriptions p ON p.appointment_id = a.id
    WHERE $where
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon-pill"><i class="fas fa-heartbeat"></i></div><span>Medi<b>Connect</b></span></div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="find_doctors.php" class="nav-link"><i class="fas fa-search"></i> <span>Find Doctors</span></a></li>
        <li class="nav-item"><a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> <span>Book Appointment</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link active"><i class="fas fa-calendar-check"></i> <span>My Appointments</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="medical_history.php" class="nav-link"><i class="fas fa-history"></i> <span>Medical History</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">My Appointments</div>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2 text-primary"></i>My Appointments</h5>
            <a href="book_appointment.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Appointment</a>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-pills mb-4">
            <?php $filters = ['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled']; ?>
            <?php foreach($filters as $key => $label): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $filter === $key ? 'active' : '' ?>" href="?filter=<?= $key ?>"><?= $label ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Reason</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($appointments)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                                    No appointments found. <a href="book_appointment.php">Book one now!</a>
                                </td></tr>
                            <?php else: foreach($appointments as $i => $apt): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars(formatDoctorName($apt['doctor_name'])) ?></div>
                                        <small class="text-muted"><?= $apt['specialization'] ?></small>
                                    </td>
                                    <td>
                                        <div><?= formatDate($apt['appointment_date']) ?></div>
                                        <small class="text-muted"><?= formatTime($apt['appointment_time']) ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($apt['reason'] ?? '—') ?></small></td>
                                    <td>
                                        <div class="fw-bold">৳<?= number_format($apt['consultation_fee'], 0) ?></div>
                                        <?php if(($apt['payment_status'] ?? '') === 'paid'): ?>
                                            <span class="badge bg-success" style="font-size:10px" title="TrxID: <?= htmlspecialchars($apt['transaction_id'] ?? '') ?>"><i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($apt['payment_method']) ?> (Paid)</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border" style="font-size:10px"><i class="fas fa-clock me-1"></i><?= htmlspecialchars($apt['payment_method'] ?? 'Pay at Clinic') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getAppointmentStatus($apt['status']) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if($apt['prescription_id']): ?>
                                                <a href="prescriptions.php?id=<?= $apt['prescription_id'] ?>" class="btn btn-sm btn-outline-primary" title="View Prescription">
                                                    <i class="fas fa-file-medical"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if(in_array($apt['status'], ['pending','confirmed']) && $apt['appointment_date'] >= date('Y-m-d')): ?>
                                                <a href="?cancel=<?= $apt['id'] ?>" class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Cancel this appointment?')" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </a>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }</script>
</body>
</html>
