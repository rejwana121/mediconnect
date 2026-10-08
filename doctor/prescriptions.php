<?php
require_once '../includes/functions.php';
requireLogin('doctor');

$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['id'];

// All prescriptions written by this doctor
$prescriptions = $conn->query("
    SELECT pr.*, u.full_name AS patient_name, p.blood_group,
           a.appointment_date, a.reason
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN appointments a ON pr.appointment_id = a.id
    WHERE pr.doctor_id = $doctor_id
    ORDER BY pr.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - MediConnect</title>
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
        <li class="nav-item"><a href="patients.php" class="nav-link"><i class="fas fa-users"></i> <span>My Patients</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link active"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="schedule.php" class="nav-link"><i class="fas fa-clock"></i> <span>My Schedule</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>
<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">My Prescriptions</div>
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
            <h5 class="fw-bold mb-0"><i class="fas fa-file-medical me-2 text-primary"></i>Prescriptions Written</h5>
            <span class="badge bg-primary fs-6"><?= count($prescriptions) ?> total</span>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Patient</th><th>Visit Date</th><th>Diagnosis</th><th>Follow-up</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($prescriptions)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-file-medical fa-3x mb-3 d-block"></i>No prescriptions written yet
                                </td></tr>
                            <?php else: foreach($prescriptions as $i => $rx): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($rx['patient_name']) ?></div>
                                        <?php if($rx['blood_group']): ?><span class="badge bg-danger"><?= $rx['blood_group'] ?></span><?php endif; ?>
                                    </td>
                                    <td><?= formatDate($rx['appointment_date']) ?></td>
                                    <td><small><?= htmlspecialchars(substr($rx['diagnosis'], 0, 50)) ?>...</small></td>
                                    <td>
                                        <?php if($rx['follow_up_date']): ?>
                                            <span class="badge bg-warning text-dark"><?= formatDate($rx['follow_up_date']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rxModal<?= $rx['id'] ?>">
                                            <i class="fas fa-eye me-1"></i>View
                                        </button>
                                    </td>
                                </tr>
                                <!-- Prescription Modal -->
                                <div class="modal fade" id="rxModal<?= $rx['id'] ?>">
                                    <div class="modal-dialog modal-lg"><div class="modal-content border-0 rounded-4">
                                        <div class="modal-header" style="background:linear-gradient(135deg,#263238,#1B2A3B);color:white;border-radius:14px 14px 0 0">
                                            <div>
                                                <h5 class="modal-title mb-0" style="font-style:italic;color:#00BFA5;font-size:24px">℞ Prescription</h5>
                                                <small>Patient: <?= htmlspecialchars($rx['patient_name']) ?> · <?= formatDate($rx['appointment_date']) ?></small>
                                            </div>
                                            <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3"><strong class="text-primary">Diagnosis:</strong><p class="mt-1"><?= nl2br(htmlspecialchars($rx['diagnosis'])) ?></p></div>
                                            <div class="mb-3"><strong class="text-primary">Medicines:</strong>
                                                <?php foreach(explode("\n", $rx['medicines']) as $m): if(trim($m)): ?>
                                                    <div class="d-flex align-items-start mb-1 mt-1"><i class="fas fa-circle small mt-1 me-2 text-primary"></i><?= htmlspecialchars(trim($m)) ?></div>
                                                <?php endif; endforeach; ?>
                                            </div>
                                            <?php if($rx['instructions']): ?><div class="mb-3"><strong class="text-primary">Instructions:</strong><p class="mt-1"><?= nl2br(htmlspecialchars($rx['instructions'])) ?></p></div><?php endif; ?>
                                            <?php if($rx['follow_up_date']): ?><div class="alert alert-info"><i class="fas fa-calendar me-2"></i><strong>Follow-up:</strong> <?= formatDate($rx['follow_up_date']) ?></div><?php endif; ?>
                                        </div>
                                    </div></div>
                                </div>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.getElementById('main-content').classList.toggle('expanded');}</script>
</body>
</html>
