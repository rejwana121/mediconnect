<?php
require_once '../includes/functions.php';
requireLogin('admin');

// Actions
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE doctors SET is_approved = 1 WHERE id = $id");
    $res = $conn->query("SELECT user_id FROM doctors WHERE id = $id");
    $u = $res->fetch_assoc();
    createNotification($conn, $u['user_id'], 'Account Approved!', 'Congratulations! Your doctor account has been approved. You can now receive appointments.', 'system');
    setAlert('success', 'Doctor approved successfully!');
    header("Location: doctors.php"); exit();
}
if (isset($_GET['revoke'])) {
    $id = intval($_GET['revoke']);
    $conn->query("UPDATE doctors SET is_approved = 0 WHERE id = $id");
    setAlert('warning', 'Doctor approval revoked.');
    header("Location: doctors.php"); exit();
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $res = $conn->query("SELECT user_id FROM doctors WHERE id = $id")->fetch_assoc();
    $conn->query("DELETE FROM users WHERE id = {$res['user_id']}");
    setAlert('success', 'Doctor deleted successfully.');
    header("Location: doctors.php"); exit();
}

$search = sanitize($_GET['search'] ?? '');
$filter = sanitize($_GET['filter'] ?? 'all');

$where = "1=1";
if ($search) $where .= " AND (u.full_name LIKE '%$search%' OR d.specialization LIKE '%$search%')";
if ($filter === 'approved')   $where .= " AND d.is_approved = 1";
if ($filter === 'pending')    $where .= " AND d.is_approved = 0";

$doctors = $conn->query("
    SELECT d.*, u.full_name, u.email, u.phone, u.gender, u.created_at,
           COUNT(a.id) AS total_appointments
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN appointments a ON a.doctor_id = d.id
    WHERE $where
    GROUP BY d.id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - MediConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon-pill"><i class="fas fa-heartbeat"></i></div><span>Medi<b>Connect</b></span></div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="doctors.php" class="nav-link active"><i class="fas fa-user-md"></i> <span>Doctors</span></a></li>
        <li class="nav-item"><a href="patients.php" class="nav-link"><i class="fas fa-users"></i> <span>Patients</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li class="nav-item"><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Manage Doctors</div>
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
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-area">
        <?= showAlert() ?>

        <!-- Search & Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or specialization..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="filter" class="form-select">
                            <option value="all" <?= $filter==='all'?'selected':'' ?>>All Doctors</option>
                            <option value="approved" <?= $filter==='approved'?'selected':'' ?>>Approved</option>
                            <option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending Approval</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="doctors.php" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fas fa-user-md me-2 text-primary"></i><?= count($doctors) ?> Doctors</h5>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Doctor</th><th>Specialization</th><th>Experience</th><th>Fee</th><th>Appointments</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($doctors)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No doctors found</td></tr>
                            <?php else: foreach($doctors as $i => $doc): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars(formatDoctorName($doc['full_name'])) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($doc['email']) ?></small>
                                        <?php if($doc['qualification']): ?>
                                            <small class="d-block text-muted"><?= htmlspecialchars($doc['qualification']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $doc['specialization'] ?></td>
                                    <td><?= $doc['experience_years'] ?> yrs</td>
                                    <td>৳<?= number_format($doc['consultation_fee'], 0) ?></td>
                                    <td><span class="badge bg-primary"><?= $doc['total_appointments'] ?></span></td>
                                    <td>
                                        <?php if($doc['is_approved']): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if(!$doc['is_approved']): ?>
                                                <a href="?approve=<?= $doc['id'] ?>" class="btn btn-sm btn-success" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?revoke=<?= $doc['id'] ?>" class="btn btn-sm btn-warning" title="Revoke" onclick="return confirm('Revoke approval?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?delete=<?= $doc['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this doctor permanently?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
