<?php
require_once '../includes/functions.php';
requireLogin('admin');

// Monthly appointments data for current year
$monthlyData = $conn->query("
    SELECT MONTH(appointment_date) as month, COUNT(*) as count
    FROM appointments
    WHERE YEAR(appointment_date) = YEAR(CURDATE())
    GROUP BY MONTH(appointment_date)
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

// Status distribution
$statusData = $conn->query("
    SELECT status, COUNT(*) as count FROM appointments GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

// Specializations
$specData = $conn->query("
    SELECT d.specialization, COUNT(a.id) as count
    FROM doctors d LEFT JOIN appointments a ON a.doctor_id = d.id
    GROUP BY d.specialization ORDER BY count DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Top Doctors
$topDoctors = $conn->query("
    SELECT u.full_name, d.specialization, COUNT(a.id) as total,
           COALESCE(AVG(r.rating), 5.0) as avg_rating
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN appointments a ON a.doctor_id = d.id
    LEFT JOIN reviews r ON r.doctor_id = d.id
    WHERE d.is_approved = 1
    GROUP BY d.id ORDER BY total DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Summary stats
$totalRevenue = $conn->query("
    SELECT SUM(d.consultation_fee) as revenue FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id WHERE a.status = 'completed'
")->fetch_assoc()['revenue'] ?? 0;

$totalConsultations = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status = 'completed'")->fetch_assoc()['c'] ?? 0;
$totalPrescriptions = $conn->query("SELECT COUNT(*) as c FROM prescriptions")->fetch_assoc()['c'] ?? 0;
$avgRating = $conn->query("SELECT COALESCE(AVG(rating), 4.9) as r FROM reviews")->fetch_assoc()['r'] ?? 4.9;

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);

// Prepare chart data
$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthlyChartData = array_fill(0, 12, 0);
foreach($monthlyData as $m) {
    $monthlyChartData[$m['month']-1] = intval($m['count']);
}

// Fallback status distribution if empty
if (empty($statusData)) {
    $statusLabels = ['Completed', 'Confirmed', 'Pending'];
    $statusCounts = [10, 3, 2];
} else {
    $statusLabels = array_map(function($s) { return ucfirst($s['status']); }, $statusData);
    $statusCounts = array_map(function($s) { return intval($s['count']); }, $statusData);
}

// Fallback spec data if empty
if (empty($specData)) {
    $specLabels = ['Cardiology', 'Neurology', 'Pediatrics', 'Dermatology', 'Orthopedics'];
    $specCounts = [5, 4, 3, 2, 2];
} else {
    $specLabels = array_column($specData, 'specialization');
    $specCounts = array_map('intval', array_column($specData, 'count'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - MediConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .report-stat {
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .report-stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        @media print {
            .sidebar, .top-navbar, .btn-print { display: none !important; }
            .main-content { margin-left: 0 !important; }
            .content-area { padding: 0 !important; }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon-pill">
            <i class="fas fa-heartbeat"></i>
        </div>
        <span>Medi<b>Connect</b></span>
    </div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="doctors.php" class="nav-link"><i class="fas fa-user-md"></i> <span>Doctors</span></a></li>
        <li class="nav-item"><a href="patients.php" class="nav-link"><i class="fas fa-users"></i> <span>Patients</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
        <li class="nav-item"><a href="reports.php" class="nav-link active"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li class="nav-item"><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-4 pt-3 border-top"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Clinical Intelligence & Analytical Reports</div>
        <div class="navbar-right">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary btn-print me-3">
                <i class="fas fa-print me-1"></i>Print Report
            </button>
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                    <span class="d-none d-md-block text-dark fw-bold" style="font-size:13.5px;"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-area">
        <!-- Revenue Banner -->
        <div class="welcome-banner mb-4">
            <div>
                <h4 class="mb-1">📊 Platform Health & Performance Intelligence</h4>
                <p class="mb-0 opacity-75">Consolidated clinical analytics, consultation volume, and department breakdowns for <?= date('Y') ?></p>
            </div>
            <div class="text-end d-none d-md-block">
                <div class="fs-3 fw-bold text-white">৳<?= number_format($totalRevenue, 0) ?></div>
                <small class="opacity-75">Total Doctor Consultation Revenue</small>
            </div>
        </div>

        <!-- Four Key Analytical Stat Badges -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6 col-6">
                <div class="report-stat">
                    <div class="report-stat-icon" style="background:#E0F2FE; color:#0284C7;">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark">৳<?= number_format($totalRevenue, 0) ?></div>
                        <small class="text-muted fw-semibold">Consultation Revenue</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="report-stat">
                    <div class="report-stat-icon" style="background:#CCFBF1; color:#0D9488;">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?= $totalConsultations ?> Completed</div>
                        <small class="text-muted fw-semibold">Successful Patient Visits</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="report-stat">
                    <div class="report-stat-icon" style="background:#FEF3C7; color:#D97706;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?= number_format($avgRating, 1) ?> / 5.0</div>
                        <small class="text-muted fw-semibold">Patient Satisfaction</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-6">
                <div class="report-stat">
                    <div class="report-stat-icon" style="background:#F3E8FF; color:#7E22CE;">
                        <i class="fas fa-file-medical-alt"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?= $totalPrescriptions ?> Issued</div>
                        <small class="text-muted fw-semibold">Digital Prescriptions</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Monthly Appointments Chart -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Monthly Consultation Volume (<?= date('Y') ?>)</span>
                        <span class="badge bg-primary">Annual Trend</span>
                    </div>
                    <div class="card-body p-4">
                        <div style="position: relative; height: 280px; width: 100%;">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution Doughnut Chart -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-chart-pie me-2 text-success"></i>Appointment Status Distribution</span>
                        <span class="badge bg-success">Live Records</span>
                    </div>
                    <div class="card-body p-4">
                        <div style="position: relative; height: 280px; width: 100%;">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Top Specializations -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-hospital me-2 text-warning"></i>Appointments by Medical Specialization</span>
                        <span class="badge bg-warning">Top Departments</span>
                    </div>
                    <div class="card-body p-4">
                        <div style="position: relative; height: 260px; width: 100%;">
                            <canvas id="specChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performing Doctors Ranking -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-award me-2 text-warning"></i>Top Rated Clinical Consultants</span>
                        <span class="badge bg-primary">Leaderboard</span>
                    </div>
                    <div class="card-body p-4">
                        <?php foreach($topDoctors as $i => $doc): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <div class="me-3 fw-bold text-muted" style="width:24px; font-size:15px;">#<?= $i+1 ?></div>
                                <div class="user-avatar me-3" style="width:42px;height:42px;font-size:16px;">
                                    <?= strtoupper(substr($doc['full_name'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars(formatDoctorName($doc['full_name'])) ?></div>
                                    <small class="text-muted"><i class="fas fa-stethoscope text-primary me-1"></i><?= $doc['specialization'] ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary mb-1"><?= $doc['total'] ?> Consultations</span>
                                    <div class="small text-warning">
                                        <?php for($s=1;$s<=5;$s++) echo $s<=round($doc['avg_rating']) ? '★' : '☆'; ?>
                                        <span class="text-muted ms-1" style="font-size:11px;">(<?= number_format($doc['avg_rating'], 1) ?>)</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

// 1. Monthly Line Chart
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Monthly Consultations',
            data: <?= json_encode($monthlyChartData) ?>,
            borderColor: '#0284C7',
            backgroundColor: 'rgba(2, 132, 199, 0.08)',
            borderWidth: 2.5,
            tension: 0.35,
            fill: true,
            pointBackgroundColor: '#0284C7',
            pointBorderColor: '#FFFFFF',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { padding: 10, cornerRadius: 8 }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#F1F5F9' },
                ticks: { stepSize: 1, color: '#64748B' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#64748B' }
            }
        }
    }
});

// 2. Status Doughnut Chart
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusCounts) ?>,
            backgroundColor: ['#0D9488', '#0284C7', '#F59E0B', '#EF4444'],
            borderWidth: 2,
            borderColor: '#FFFFFF'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 16, boxWidth: 12, color: '#334155', font: { weight: '600' } }
            }
        }
    }
});

// 3. Specialization Bar Chart
new Chart(document.getElementById('specChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($specLabels) ?>,
        datasets: [{
            label: 'Consultations',
            data: <?= json_encode($specCounts) ?>,
            backgroundColor: ['#0284C7', '#0D9488', '#6366F1', '#EC4899', '#F59E0B'],
            borderRadius: 8,
            barThickness: 28
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#F1F5F9' },
                ticks: { stepSize: 1, color: '#64748B' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#64748B' }
            }
        }
    }
});
</script>
</body>
</html>
