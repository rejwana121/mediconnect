<?php
require_once '../includes/functions.php';
requireLogin('admin');

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $res = $conn->query("SELECT user_id FROM patients WHERE id = $id")->fetch_assoc();
    $conn->query("DELETE FROM users WHERE id = {$res['user_id']}");
    setAlert('success', 'Patient deleted.');
    header("Location: patients.php"); exit();
}

$search = sanitize($_GET['search'] ?? '');
$where = "1=1";
if ($search) $where .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%')";

$patients = $conn->query("
    SELECT p.*, u.full_name, u.email, u.phone, u.gender, u.date_of_birth, u.created_at,
           COUNT(a.id) AS total_appointments
    FROM patients p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN appointments a ON a.patient_id = p.id
    WHERE $where
    GROUP BY p.id ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - MediConnect Admin</title>
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
        <li class="nav-item"><a href="patients.php" class="nav-link active"><i class="fas fa-users"></i> <span>Patients</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li class="nav-item"><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Manage Patients</div>
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
        <?= showAlert() ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button></div>
                    <div class="col-md-2"><a href="patients.php" class="btn btn-outline-secondary w-100">Reset</a></div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fas fa-users me-2 text-primary"></i><?= count($patients) ?> Patients</h5>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Patient</th><th>Contact</th><th>Blood Group</th><th>Gender</th><th>Appointments</th><th>Joined</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($patients)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No patients found</td></tr>
                            <?php else: foreach($patients as $i => $p): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($p['full_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                                    <td>
                                        <?php if($p['blood_group']): ?>
                                            <span class="badge bg-danger"><?= $p['blood_group'] ?></span>
                                        <?php else: ?> — <?php endif; ?>
                                    </td>
                                    <td><?= $p['gender'] ?? '—' ?></td>
                                    <td><span class="badge bg-primary"><?= $p['total_appointments'] ?></span></td>
                                    <td><small><?= formatDate($p['created_at']) ?></small></td>
                                    <td>
                                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete patient?')">
                                            <i class="fas fa-trash"></i>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }</script>
</body>
</html>
