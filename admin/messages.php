<?php
require_once '../includes/functions.php';
requireLogin('admin');

$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$unreadMsg = $conn->query("SELECT COUNT(*) as c FROM contact_messages WHERE is_read = 0")->fetch_assoc()['c'];

// Mark as read
if (isset($_GET['read'])) {
    $id = intval($_GET['read']);
    $conn->query("UPDATE contact_messages SET is_read = 1 WHERE id = $id");
    header("Location: messages.php"); exit();
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM contact_messages WHERE id = $id");
    setAlert('success', 'Message deleted.');
    header("Location: messages.php"); exit();
}

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - MediConnect Admin</title>
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
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li class="nav-item"><a href="messages.php" class="nav-link active"><i class="fas fa-envelope"></i> <span>Messages</span>
            <?php if($unreadMsg > 0): ?><span class="badge bg-danger ms-auto"><?= $unreadMsg ?></span><?php endif; ?>
        </a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Contact Messages</div>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Contact Messages <span class="badge bg-danger ms-2"><?= $unreadMsg ?> unread</span></h5>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Date</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($messages)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No messages yet</td></tr>
                            <?php else: foreach($messages as $i => $msg): ?>
                                <tr class="<?= !$msg['is_read'] ? 'table-warning' : '' ?>">
                                    <td><?= $i+1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($msg['name']) ?></td>
                                    <td><a href="mailto:<?= $msg['email'] ?>"><?= htmlspecialchars($msg['email']) ?></a></td>
                                    <td><?= htmlspecialchars($msg['subject'] ?? '—') ?></td>
                                    <td><small><?= htmlspecialchars(substr($msg['message'], 0, 60)) ?>...</small></td>
                                    <td><small><?= timeAgo($msg['created_at']) ?></small></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if(!$msg['is_read']): ?>
                                                <a href="?read=<?= $msg['id'] ?>" class="btn btn-sm btn-success" title="Mark Read"><i class="fas fa-check"></i></a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#msgModal<?= $msg['id'] ?>" title="View"><i class="fas fa-eye"></i></button>
                                            <a href="?delete=<?= $msg['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')" title="Delete"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Modal -->
                                <div class="modal fade" id="msgModal<?= $msg['id'] ?>">
                                    <div class="modal-dialog"><div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Message from <?= htmlspecialchars($msg['name']) ?></h5>
                                            <button class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>From:</strong> <?= htmlspecialchars($msg['name']) ?> &lt;<?= htmlspecialchars($msg['email']) ?>&gt;</p>
                                            <p><strong>Subject:</strong> <?= htmlspecialchars($msg['subject'] ?? 'No subject') ?></p>
                                            <hr>
                                            <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="mailto:<?= $msg['email'] ?>" class="btn btn-primary"><i class="fas fa-reply me-2"></i>Reply by Email</a>
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
<script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }</script>
</body>
</html>
