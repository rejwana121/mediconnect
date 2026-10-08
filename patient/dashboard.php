<?php
require_once '../includes/functions.php';
requireLogin('patient');

// Get patient record
$stmt = $conn->prepare("SELECT p.*, u.full_name, u.email, u.phone, u.gender FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['id'];

// Stats
$totalAppointments  = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE patient_id = $patient_id")->fetch_assoc()['c'];
$upcomingCount      = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE patient_id = $patient_id AND appointment_date >= CURDATE() AND status IN ('pending','confirmed')")->fetch_assoc()['c'];
$completedCount     = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE patient_id = $patient_id AND status = 'completed'")->fetch_assoc()['c'];
$prescriptionCount  = $conn->query("SELECT COUNT(*) as c FROM prescriptions WHERE patient_id = $patient_id")->fetch_assoc()['c'];

// Upcoming appointments
$upcoming = $conn->query("
    SELECT a.*, u.full_name AS doctor_name, d.specialization, d.consultation_fee
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.patient_id = $patient_id AND a.appointment_date >= CURDATE() AND a.status IN ('pending','confirmed')
    ORDER BY a.appointment_date ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .vital-card {
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s ease;
        }
        .vital-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        .vital-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .quick-action-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-radius: 12px;
            background: #F8FAFC;
            color: #334155;
            text-decoration: none;
            font-weight: 600;
            font-size: 13.5px;
            border: 1px solid #E2E8F0;
            transition: all 0.2s ease;
            margin-bottom: 8px;
        }
        .quick-action-link:hover {
            background: #E0F2FE;
            color: #0284C7;
            border-color: #BAE6FD;
            transform: translateX(2px);
        }
    </style>
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
        <li class="nav-item"><a href="find_doctors.php" class="nav-link"><i class="fas fa-search"></i> <span>Find Doctors</span></a></li>
        <li class="nav-item"><a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> <span>Book Appointment</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>My Appointments</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="medical_history.php" class="nav-link"><i class="fas fa-history"></i> <span>Medical History</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-4 pt-3 border-top"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Patient Health Portal</div>
        <div class="navbar-right">
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell fa-lg text-muted"></i>
                    <?php if($unreadCount > 0): ?><span class="notification-badge"><?= $unreadCount ?></span><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <h6 class="dropdown-header">Health Notifications</h6>
                    <?php
                    $notifs = getUnreadNotifications($conn, $_SESSION['user_id']);
                    if(empty($notifs)): ?>
                        <div class="dropdown-item text-muted text-center py-3">No new notifications</div>
                    <?php else: foreach($notifs as $n): ?>
                        <div class="dropdown-item">
                            <small class="d-block text-muted"><?= timeAgo($n['created_at']) ?></small>
                            <span class="fw-semibold"><?= htmlspecialchars($n['message']) ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                    <div class="text-start d-none d-md-block">
                        <div class="fw-bold text-dark" style="font-size:13.5px;"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                        <small class="text-muted">Patient</small>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Profile Settings</a></li>
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
                <h4 class="mb-1">Hello, <?= htmlspecialchars($patient['full_name']) ?>! 👋</h4>
                <p class="mb-0 opacity-75">Track your personal health records, schedules, and active prescriptions.</p>
            </div>
            <a href="book_appointment.php" class="btn btn-light fw-bold px-4 py-2" style="color: #0284C7; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <i class="fas fa-calendar-plus me-2"></i>Book New Appointment
            </a>
        </div>

        <!-- Four Key Stats -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6 col-6">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-number"><?= $totalAppointments ?></div>
                    <div class="stat-label">Total Consultations</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?= $upcomingCount ?></div>
                    <div class="stat-label">Upcoming Visits</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?= $completedCount ?></div>
                    <div class="stat-label">Completed Visits</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="stat-card stat-purple">
                    <div class="stat-icon"><i class="fas fa-file-prescription"></i></div>
                    <div class="stat-number"><?= $prescriptionCount ?></div>
                    <div class="stat-label">Prescriptions</div>
                </div>
            </div>
        </div>

        <!-- Patient Health Vitals Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="vital-card">
                    <div class="vital-icon" style="background: #FEE2E2; color: #DC2626;">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark">120/80 <small class="text-muted" style="font-size:11px;">mmHg</small></div>
                        <small class="text-muted">Blood Pressure (Normal)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="vital-card">
                    <div class="vital-icon" style="background: #E0F2FE; color: #0284C7;">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark"><?= $patient['blood_group'] ?: 'B+' ?> <small class="text-success" style="font-size:11px;">Verified</small></div>
                        <small class="text-muted">Blood Group Type</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="vital-card">
                    <div class="vital-icon" style="background: #FEF3C7; color: #D97706;">
                        <i class="fas fa-weight"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark">62 kg <small class="text-muted" style="font-size:11px;">BMI 21.8</small></div>
                        <small class="text-muted">Healthy Weight Range</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="vital-card">
                    <div class="vital-icon" style="background: #CCFBF1; color: #0D9488;">
                        <i class="fas fa-lungs"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark">99% <small class="text-success" style="font-size:11px;">SpO2</small></div>
                        <small class="text-muted">Oxygen Saturation</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left: Upcoming Appointments Table -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-calendar-alt text-primary me-2"></i>Upcoming Scheduled Consultations</span>
                        <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All Bookings</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Date & Time</th>
                                        <th>Fee</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($upcoming)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fas fa-calendar-check fa-3x text-muted mb-2 d-block opacity-40"></i>
                                                No upcoming appointments scheduled.<br>
                                                <a href="book_appointment.php" class="btn btn-sm btn-primary mt-2">Book an Appointment Now</a>
                                            </td>
                                        </tr>
                                    <?php else: foreach($upcoming as $apt): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars(formatDoctorName($apt['doctor_name'])) ?></div>
                                                <small class="text-muted"><i class="fas fa-check-circle text-primary me-1"></i>Verified Doctor</small>
                                            </td>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($apt['specialization']) ?></span></td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= formatDate($apt['appointment_date']) ?></div>
                                                <small class="text-muted"><i class="fas fa-clock me-1"></i><?= formatTime($apt['appointment_time']) ?></small>
                                            </td>
                                            <td>
                                                <strong class="text-dark">৳<?= number_format($apt['consultation_fee'], 0) ?></strong>
                                                <?php if(($apt['payment_status'] ?? '') === 'paid'): ?>
                                                    <small class="d-block text-success fw-semibold" style="font-size:11px"><i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($apt['payment_method']) ?></small>
                                                <?php else: ?>
                                                    <small class="d-block text-muted" style="font-size:11px"><?= htmlspecialchars($apt['payment_method'] ?? 'Pay at Clinic') ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= getAppointmentStatus($apt['status']) ?></td>
                                            <td>
                                                <a href="appointments.php?cancel=<?= $apt['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this appointment booking?')">Cancel</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Health Profile Summary & Quick Actions -->
            <div class="col-lg-4">
                <!-- Patient Profile Card -->
                <div class="card mb-4 text-center">
                    <div class="card-body p-4">
                        <div class="user-avatar mx-auto mb-3" style="width:72px;height:72px;font-size:28px;">
                            <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($patient['full_name']) ?></h5>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($patient['email']) ?></p>

                        <div class="text-start border-top pt-3">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted small">Registered Phone</span>
                                <span class="fw-semibold text-dark small"><?= $patient['phone'] ?: '01722222222' ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted small">Emergency Contact</span>
                                <span class="fw-semibold text-dark small"><?= htmlspecialchars($patient['emergency_contact'] ?: '01733333333') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted small">Known Allergies</span>
                                <span class="fw-semibold text-danger small"><?= htmlspecialchars($patient['allergies'] ?: 'None reported') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted small">Account Status</span>
                                <span class="badge bg-success">Active & Verified</span>
                            </div>
                        </div>

                        <a href="profile.php" class="btn btn-outline-primary w-100 mt-3">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>

                <!-- Quick Services & Medical Links Card -->
                <div class="card">
                    <div class="card-header">
                        <span class="fw-bold"><i class="fas fa-th-large text-primary me-2"></i>Quick Health Services</span>
                    </div>
                    <div class="card-body p-3">
                        <a href="find_doctors.php" class="quick-action-link">
                            <span><i class="fas fa-user-md text-primary me-2"></i>Find Top Doctors</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <a href="prescriptions.php" class="quick-action-link">
                            <span><i class="fas fa-file-prescription text-success me-2"></i>My Prescriptions</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <a href="medical_history.php" class="quick-action-link">
                            <span><i class="fas fa-notes-medical text-warning me-2"></i>Medical History Log</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <div class="p-3 mt-2 rounded-3 text-center" style="background:#F1F5F9; border: 1px dashed #CBD5E1;">
                            <small class="text-muted d-block mb-1">National 24/7 Health Hotline</small>
                            <span class="fw-bold text-primary fs-6"><i class="fas fa-phone-alt me-1"></i>16263</span>
                        </div>
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
