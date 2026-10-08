<?php
require_once '../includes/functions.php';
requireLogin('doctor');

$stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

// Handle schedule update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $available_days  = implode(',', $_POST['available_days'] ?? []);
    $start_time      = sanitize($_POST['start_time']);
    $end_time        = sanitize($_POST['end_time']);
    $slot_duration   = intval($_POST['slot_duration']);
    $consultation_fee= floatval($_POST['consultation_fee']);

    $stmt = $conn->prepare("UPDATE doctors SET available_days=?, start_time=?, end_time=?, slot_duration=?, consultation_fee=? WHERE user_id=?");
    $stmt->bind_param("sssidi", $available_days, $start_time, $end_time, $slot_duration, $consultation_fee, $_SESSION['user_id']);
    if ($stmt->execute()) {
        setAlert('success', '✅ Schedule updated successfully!');
    } else {
        setAlert('danger', 'Update failed. Please try again.');
    }
    header("Location: schedule.php"); exit();
}

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$activeDays = explode(',', $doctor['available_days'] ?? 'Monday,Tuesday,Wednesday,Thursday,Friday');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - MediConnect</title>
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
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="schedule.php" class="nav-link active"><i class="fas fa-clock"></i> <span>My Schedule</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>
<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">My Schedule</div>
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
        <?= showAlert() ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm" style="border-radius:20px;">
                    <div class="card-header" style="background:linear-gradient(135deg,#00897B,#1E88E5);color:white;border-radius:20px 20px 0 0;padding:24px;">
                        <h5 class="mb-1 fw-bold"><i class="fas fa-clock me-2"></i>Manage My Schedule</h5>
                        <p class="mb-0 opacity-75">Set your available days, working hours and slot duration</p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <!-- Available Days -->
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-6">📅 Available Days</label>
                                <div class="row g-2">
                                    <?php foreach($days as $day): ?>
                                        <div class="col-md-3 col-6">
                                            <div class="form-check form-check-inline w-100">
                                                <input class="form-check-input" type="checkbox" name="available_days[]"
                                                       id="day_<?= $day ?>" value="<?= $day ?>"
                                                       <?= in_array($day, $activeDays) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-semibold" for="day_<?= $day ?>"><?= $day ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <hr>

                            <!-- Working Hours -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">⏰ Start Time</label>
                                    <input type="time" name="start_time" class="form-control form-control-lg"
                                           value="<?= $doctor['start_time'] ?? '09:00' ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">⏰ End Time</label>
                                    <input type="time" name="end_time" class="form-control form-control-lg"
                                           value="<?= $doctor['end_time'] ?? '17:00' ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">⏱️ Slot Duration</label>
                                    <select name="slot_duration" class="form-select form-select-lg">
                                        <?php foreach([15,20,30,45,60] as $min): ?>
                                            <option value="<?= $min ?>" <?= ($doctor['slot_duration'] ?? 30) == $min ? 'selected' : '' ?>>
                                                <?= $min ?> minutes
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <hr>

                            <!-- Consultation Fee -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">💰 Consultation Fee (৳)</label>
                                <input type="number" name="consultation_fee" class="form-control form-control-lg"
                                       value="<?= $doctor['consultation_fee'] ?? 500 ?>" min="0" step="50">
                            </div>

                            <!-- Preview -->
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-3 fs-4"></i>
                                <div>
                                    <strong>Current Schedule:</strong><br>
                                    <?= $doctor['available_days'] ?? 'Mon-Fri' ?> &nbsp;|&nbsp;
                                    <?= formatTime($doctor['start_time'] ?? '09:00:00') ?> – <?= formatTime($doctor['end_time'] ?? '17:00:00') ?> &nbsp;|&nbsp;
                                    <?= $doctor['slot_duration'] ?? 30 ?> min slots &nbsp;|&nbsp;
                                    ৳<?= number_format($doctor['consultation_fee'] ?? 500, 0) ?> fee
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-save me-2"></i>Save Schedule
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.getElementById('main-content').classList.toggle('expanded');}</script>
</body>
</html>
