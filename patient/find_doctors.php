<?php
require_once '../includes/functions.php';
requireLogin('patient');

// Get patient record
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$patient_id = $patient['id'];

// Filters
$search = sanitize($_GET['search'] ?? '');
$spec   = sanitize($_GET['specialization'] ?? '');

// Build query
$where = "d.is_approved = 1";
if ($search) $where .= " AND (u.full_name LIKE '%$search%' OR d.specialization LIKE '%$search%')";
if ($spec)   $where .= " AND d.specialization = '$spec'";

$doctors = $conn->query("
    SELECT d.*, u.full_name, u.email, u.phone, u.gender,
           COALESCE(AVG(r.rating), 0) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN reviews r ON r.doctor_id = d.id
    WHERE $where
    GROUP BY d.id
    ORDER BY avg_rating DESC
")->fetch_all(MYSQLI_ASSOC);

// All specializations for filter
$specializations = $conn->query("SELECT DISTINCT specialization FROM doctors WHERE is_approved = 1 ORDER BY specialization")->fetch_all(MYSQLI_ASSOC);

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .doctor-card { border: none; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); transition: all 0.3s; height: 100%; }
        .doctor-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
        .doctor-avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 30px; color: white; font-weight: 700; flex-shrink: 0; }
        .star-rating .fas { color: #ffc107; }
        .star-rating .far { color: #ddd; }
        .spec-badge { background: #e8f0fe; color: #1a73e8; border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; }
        .search-box { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><div class="brand-icon-pill"><i class="fas fa-heartbeat"></i></div><span>Medi<b>Connect</b></span></div>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="find_doctors.php" class="nav-link active"><i class="fas fa-search"></i> <span>Find Doctors</span></a></li>
        <li class="nav-item"><a href="book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> <span>Book Appointment</span></a></li>
        <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>My Appointments</span></a></li>
        <li class="nav-item"><a href="prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
        <li class="nav-item"><a href="medical_history.php" class="nav-link"><i class="fas fa-history"></i> <span>Medical History</span></a></li>
        <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-3"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Find Doctors</div>
        <div class="navbar-right">
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell fa-lg text-muted"></i>
                    <?php if($unreadCount > 0): ?><span class="notification-badge"><?= $unreadCount ?></span><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <?php $notifs = getUnreadNotifications($conn, $_SESSION['user_id']);
                    foreach($notifs as $n): ?><a class="dropdown-item" href="#"><small class="text-muted"><?= timeAgo($n['created_at']) ?></small><div><?= htmlspecialchars($n['message']) ?></div></a><?php endforeach;
                    if(empty($notifs)): ?><div class="text-center text-muted p-3">No notifications</div><?php endif; ?>
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
        <!-- Search Bar -->
        <div class="search-box mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Search Doctors</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search text-primary"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Doctor name or specialization..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Specialization</label>
                    <select name="specialization" class="form-select">
                        <option value="">All Specializations</option>
                        <?php foreach($specializations as $s): ?>
                            <option value="<?= $s['specialization'] ?>" <?= $spec === $s['specialization'] ? 'selected' : '' ?>>
                                <?= $s['specialization'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-search me-2"></i>Search</button>
                    <a href="find_doctors.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-user-md me-2 text-primary"></i>
                <?= count($doctors) ?> Doctor<?= count($doctors) != 1 ? 's' : '' ?> Found
            </h5>
        </div>

        <!-- Doctor Cards -->
        <div class="row g-4">
            <?php if(empty($doctors)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-user-md fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No doctors found</h5>
                    <p class="text-muted">Try a different search term or specialization</p>
                    <a href="find_doctors.php" class="btn btn-primary">View All Doctors</a>
                </div>
            <?php else: foreach($doctors as $doc): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card doctor-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="doctor-avatar">
                                    <?= strtoupper(substr($doc['full_name'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars(formatDoctorName($doc['full_name'])) ?></h6>
                                    <span class="spec-badge"><?= $doc['specialization'] ?></span>
                                    <div class="star-rating mt-1">
                                        <?php
                                        $rating = round($doc['avg_rating']);
                                        for($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '<i class="fas fa-star small"></i>' : '<i class="far fa-star small"></i>';
                                        }
                                        ?>
                                        <small class="text-muted ms-1">(<?= $doc['review_count'] ?>)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="border-top pt-3">
                                <div class="row g-2 text-center mb-3">
                                    <div class="col-4">
                                        <div class="fw-bold text-primary"><?= $doc['experience_years'] ?>+</div>
                                        <small class="text-muted">Years Exp.</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-success">৳<?= number_format($doc['consultation_fee'], 0) ?></div>
                                        <small class="text-muted">Fee</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-warning"><?= number_format($doc['avg_rating'], 1) ?></div>
                                        <small class="text-muted">Rating</small>
                                    </div>
                                </div>

                                <?php if($doc['qualification']): ?>
                                    <p class="text-muted small mb-3"><i class="fas fa-graduation-cap me-1"></i><?= htmlspecialchars($doc['qualification']) ?></p>
                                <?php endif; ?>

                                <?php if($doc['bio']): ?>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars(substr($doc['bio'], 0, 80)) ?>...</p>
                                <?php endif; ?>

                                <a href="book_appointment.php?doctor_id=<?= $doc['id'] ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); document.getElementById('main-content').classList.toggle('expanded'); }</script>
</body>
</html>
